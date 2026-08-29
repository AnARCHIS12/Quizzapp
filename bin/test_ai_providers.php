<?php

declare(strict_types=1);

/**
 * Diagnostic & Testing Script for QuizzApp AI Providers (Mistral, Groq, OpenRouter)
 * Usage: php bin/test_ai_providers.php
 */

$baseDir = dirname(__DIR__);

// Load .env
$envFile = $baseDir . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"'");
            $_ENV[$k] = $v;
            putenv("$k=$v");
        }
    }
}

echo "\n=======================================================\n";
echo "       QuizzApp - Test des Fournisseurs d'IA\n";
echo "=======================================================\n\n";

$testPrompt = "Génère exactement 1 question QCM de quiz en français sur le thème \"Astronomie\".\nRéponds UNIQUEMENT avec un JSON valide sous la forme :\n{\"questions\":[{\"question_text\":\"Quelle est la planète la plus proche du Soleil ?\",\"explanation\":\"Mercure est la première planète du système solaire.\",\"answers\":[{\"answer_text\":\"Mercure\",\"is_correct\":1},{\"answer_text\":\"Vénus\",\"is_correct\":0},{\"answer_text\":\"Mars\",\"is_correct\":0},{\"answer_text\":\"Jupiter\",\"is_correct\":0}]}]}";

$providers = [
    [
        'name' => 'Mistral AI',
        'key'  => $_ENV['MISTRAL_API_KEY'] ?? getenv('MISTRAL_API_KEY') ?? '',
        'model'=> $_ENV['MISTRAL_MODEL'] ?? getenv('MISTRAL_MODEL') ?? 'mistral-small-latest',
        'url'  => 'https://api.mistral.ai/v1/chat/completions',
        'format' => 'mistral'
    ],
    [
        'name' => 'Groq',
        'key'  => $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?? '',
        'model'=> $_ENV['GROQ_MODEL'] ?? getenv('GROQ_MODEL') ?? 'llama-3.3-70b-versatile',
        'url'  => 'https://api.groq.com/openai/v1/chat/completions',
        'format' => 'openai'
    ],
    [
        'name' => 'OpenRouter',
        'key'  => $_ENV['OPENROUTER_API_KEY'] ?? getenv('OPENROUTER_API_KEY') ?? '',
        'model'=> $_ENV['OPENROUTER_MODEL'] ?? getenv('OPENROUTER_MODEL') ?? 'meta-llama/llama-3.1-8b-instruct:free',
        'url'  => 'https://openrouter.ai/api/v1/chat/completions',
        'format' => 'openai'
    ]
];

$activeCount = 0;
$successCount = 0;

foreach ($providers as $p) {
    echo "▶ Test de " . $p['name'] . " (" . $p['model'] . ") ...\n";

    if (empty($p['key'])) {
        echo "  [NON CONFIGURE] Aucune clé API trouvée dans l'environnement.\n\n";
        continue;
    }

    $activeCount++;
    $start = microtime(true);

    $payload = [
        'model' => $p['model'],
        'messages' => [
            ['role' => 'user', 'content' => $testPrompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 800,
    ];

    if ($p['format'] === 'mistral') {
        $payload['response_format'] = ['type' => 'json_object'];
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $p['key'],
    ];

    if ($p['name'] === 'OpenRouter') {
        $headers[] = 'HTTP-Referer: https://quizzapp.revlibertaire.com';
        $headers[] = 'X-Title: QuizzApp';
    }

    $ch = curl_init($p['url']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    $elapsed = round((microtime(true) - $start) * 1000);

    if ($response === false || !empty($curlErr)) {
        echo "  [ERREUR RESEAU] $curlErr ($elapsed ms)\n\n";
        continue;
    }

    if ($httpCode !== 200) {
        echo "  [ERREUR HTTP $httpCode] en $elapsed ms\n";
        $body = json_decode((string)$response, true);
        $errMsg = $body['error']['message'] ?? $body['message'] ?? substr((string)$response, 0, 200);
        echo "  Détail : $errMsg\n\n";
        continue;
    }

    $data = json_decode((string)$response, true);
    $content = $data['choices'][0]['message']['content'] ?? '';

    // Strip markdown fences if present
    $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
    $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);

    $parsed = json_decode($cleanJson, true);
    $questions = $parsed['questions'] ?? null;

    if (is_array($questions) && count($questions) > 0) {
        $q = $questions[0];
        $text = $q['question_text'] ?? 'N/A';
        $answers = count($q['answers'] ?? []);
        echo "  [SUCCES] Réponse reçue en {$elapsed} ms !\n";
        echo "  Question générée : \"$text\" ($answers réponses)\n\n";
        $successCount++;
    } else {
        echo "  [AVERTISSEMENT] HTTP 200 mais format JSON inattendu en {$elapsed} ms.\n";
        echo "  Extrait : " . substr($content, 0, 150) . "...\n\n";
    }
}

echo "=======================================================\n";
echo " Bilan : $successCount / $activeCount fournisseur(s) actif(s) opérationnel(s).\n";
if ($successCount > 0) {
    echo " Le système de secours automatique est PRÊT et FONCTIONNEL !\n";
} else {
    echo " Attention : Aucun fournisseur d'IA n'est fonctionnel actuellement.\n";
}
echo "=======================================================\n\n";
