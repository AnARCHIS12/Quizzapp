<?php
/**
 * Async AI Question Generator — Multi-Provider with Automatic Fallback
 *
 * Provider chain (automatic failover):
 *   1. Mistral AI     (MISTRAL_API_KEY)      — primary
 *   2. Groq           (GROQ_API_KEY)          — free, 14 400 req/day, ultra-fast
 *   3. OpenRouter     (OPENROUTER_API_KEY)    — aggregator, many free models
 *
 * If a provider returns rate-limit (429) or quota error (402/429), the next one is tried.
 * Usage: php bin/generate_questions_async.php [roomCode] <cat_id1> <cat_id2> ...
 */

declare(strict_types=1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/vendor/autoload.php';

// ─── Load .env ────────────────────────────────────────────────────────────────
$envFile = $baseDir . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v);
            putenv(trim($k) . '=' . trim($v));
        }
    }
}

// ─── Provider configuration ───────────────────────────────────────────────────
$providers = [];

$mistralKey = $_ENV['MISTRAL_API_KEY'] ?? getenv('MISTRAL_API_KEY') ?? '';
if (!empty($mistralKey)) {
    $providers[] = [
        'name'    => 'Mistral',
        'url'     => 'https://api.mistral.ai/v1/chat/completions',
        'key'     => $mistralKey,
        'model'   => $_ENV['MISTRAL_MODEL'] ?? getenv('MISTRAL_MODEL') ?? 'mistral-small-latest',
        'format'  => 'mistral', // uses response_format: json_object
    ];
}

$groqKey = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?? '';
if (!empty($groqKey)) {
    $providers[] = [
        'name'    => 'Groq',
        'url'     => 'https://api.groq.com/openai/v1/chat/completions',
        'key'     => $groqKey,
        'model'   => $_ENV['GROQ_MODEL'] ?? getenv('GROQ_MODEL') ?? 'llama-3.1-70b-versatile',
        'format'  => 'openai', // OpenAI-compatible, no json_object mode on all models
    ];
}

$openrouterKey = $_ENV['OPENROUTER_API_KEY'] ?? getenv('OPENROUTER_API_KEY') ?? '';
if (!empty($openrouterKey)) {
    $providers[] = [
        'name'    => 'OpenRouter',
        'url'     => 'https://openrouter.ai/api/v1/chat/completions',
        'key'     => $openrouterKey,
        'model'   => $_ENV['OPENROUTER_MODEL'] ?? getenv('OPENROUTER_MODEL') ?? 'meta-llama/llama-3.1-8b-instruct:free',
        'format'  => 'openai',
    ];
}

if (empty($providers)) {
    // No AI provider configured — exit silently
    exit(0);
}

// ─── Arguments ────────────────────────────────────────────────────────────────
$roomCode = null;
if (isset($argv[1]) && !is_numeric($argv[1])) {
    $roomCode    = strtoupper(trim($argv[1]));
    $categoryIds = array_map('intval', array_slice($argv, 2));
} else {
    $categoryIds = array_map('intval', array_slice($argv, 1));
}

if (empty($categoryIds)) exit(0);

// ─── Database connection ──────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'db',
            $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? '3306',
            $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'quizzapp'
        ),
        $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'quizzapp_user',
        $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    exit(0);
}

// ─── Helper: call one provider ────────────────────────────────────────────────
/**
 * @return array{questions: list<array>, provider: string}|null
 */
function callProvider(array $provider, string $prompt): ?array
{
    $isMistral = $provider['format'] === 'mistral';

    $body = [
        'model'       => $provider['model'],
        'messages'    => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.85,
        'max_tokens'  => 2000,
    ];

    // Mistral supports json_object mode; Groq/OpenRouter: add JSON instruction in prompt instead
    if ($isMistral) {
        $body['response_format'] = ['type' => 'json_object'];
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $provider['key'],
    ];

    // OpenRouter requires site info headers
    if ($provider['name'] === 'OpenRouter') {
        $headers[] = 'HTTP-Referer: https://quizzapp.revlibertaire.com';
        $headers[] = 'X-Title: QuizzApp';
    }

    $ch = curl_init($provider['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 429 = rate limit, 402 = quota exceeded → signal to try next provider
    if (in_array($httpCode, [429, 402, 503], true)) {
        error_log("[QuizzApp AI] {$provider['name']} quota/rate-limit ($httpCode) — trying next provider");
        return null;
    }

    if ($httpCode !== 200 || !$response) {
        error_log("[QuizzApp AI] {$provider['name']} error $httpCode");
        return null;
    }

    $decoded = json_decode($response, true);
    $content = $decoded['choices'][0]['message']['content'] ?? '';
    if (empty($content)) return null;

    // Parse JSON — handle array or wrapped {"questions":[...]}
    $parsed = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Try to extract JSON from text (Groq sometimes adds prose)
        if (preg_match('/\[.*\]/s', $content, $m)) {
            $parsed = json_decode($m[0], true);
        }
        if (json_last_error() !== JSON_ERROR_NONE) return null;
    }

    if (isset($parsed['questions'])) $parsed = $parsed['questions'];
    if (!isset($parsed[0])) $parsed = [$parsed];
    if (!is_array($parsed)) return null;

    return ['questions' => $parsed, 'provider' => $provider['name']];
}

// ─── Helper: call chain with fallback ─────────────────────────────────────────
function callWithFallback(array $providers, string $prompt): ?array
{
    foreach ($providers as $provider) {
        $result = callProvider($provider, $prompt);
        if ($result !== null) return $result;
    }
    error_log('[QuizzApp AI] All providers failed — no questions generated for this category');
    return null;
}

// ─── Main generation loop ─────────────────────────────────────────────────────
$generatedThisRun = [];

foreach ($categoryIds as $catId) {
    // Fetch category
    $stmt = $pdo->prepare(
        "SELECT c.id, c.name, c.description, p.name AS parent_name
         FROM categories c LEFT JOIN categories p ON c.parent_id = p.id
         WHERE c.id = ?"
    );
    $stmt->execute([$catId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$category) continue;

    // Fetch quiz
    $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE category_id = ? LIMIT 1");
    $stmt->execute([$catId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quiz) continue;
    $quizId = (int)$quiz['id'];

    $categoryName = $category['name'];
    $parentName   = $category['parent_name'] ?? null;
    $description  = $category['description'] ?? '';

    if ($parentName) {
        $themeContext = "la sous-catégorie \"{$categoryName}\" (appartenant à \"{$parentName}\")";
        $scopeWarning = "IMPORTANT : questions EXCLUSIVEMENT sur \"{$categoryName}\", pas sur d'autres thèmes de \"{$parentName}\".";
    } else {
        $themeContext = "la catégorie \"{$categoryName}\"";
        $scopeWarning = "IMPORTANT : questions EXCLUSIVEMENT sur \"{$categoryName}\".";
    }
    if (!empty($description)) $themeContext .= " (description : {$description})";

    $prompt = "Tu es un expert en quiz éducatif francophone. Génère exactement 3 questions de quiz uniques et inédites sur {$themeContext}.

{$scopeWarning}

Pour chaque question, utilise ce format JSON exact :
{
  \"question\": \"Texte précis de la question ?\",
  \"type\": \"qcm\",
  \"points\": 10,
  \"explanation\": \"Courte explication factuelle de la bonne réponse.\",
  \"answers\": [
    {\"text\": \"Réponse correcte\", \"correct\": true},
    {\"text\": \"Mauvaise réponse 1\", \"correct\": false},
    {\"text\": \"Mauvaise réponse 2\", \"correct\": false},
    {\"text\": \"Mauvaise réponse 3\", \"correct\": false}
  ]
}

Retourne UNIQUEMENT un tableau JSON valide de 3 objets, sans texte avant ou après.
Les questions doivent être variées, précises, éducatives et difficiles. Évite les questions trop génériques.";

    // ─── Call AI with automatic fallback ─────────────────────────────────────
    $result = callWithFallback($providers, $prompt);
    if ($result === null) continue;

    $questions = $result['questions'];

    // ─── Insert questions in DB ───────────────────────────────────────────────
    foreach ($questions as $q) {
        $questionText = trim($q['question'] ?? '');
        $explanation  = trim($q['explanation'] ?? '');
        $points       = (int)($q['points'] ?? 10);
        $answers      = $q['answers'] ?? [];

        if (empty($questionText) || count($answers) < 2) continue;
        if (in_array($questionText, $generatedThisRun, true)) continue;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id = ? AND question_text = ?");
        $stmt->execute([$quizId, $questionText]);
        if ((int)$stmt->fetchColumn() > 0) continue;

        $generatedThisRun[] = $questionText;

        $stmt = $pdo->prepare(
            "INSERT INTO questions (quiz_id, question_text, question_type, points, explanation, sorting_order, match_room_code)
             VALUES (?, ?, 'qcm', ?, ?, 0, ?)"
        );
        $stmt->execute([$quizId, $questionText, $points, $explanation, $roomCode]);
        $questionId = (int)$pdo->lastInsertId();

        $stmtAns = $pdo->prepare("INSERT IGNORE INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
        foreach ($answers as $ans) {
            $ansText   = trim($ans['text'] ?? '');
            $isCorrect = ($ans['correct'] ?? false) ? 1 : 0;
            if (!empty($ansText)) {
                $stmtAns->execute([$questionId, $ansText, $isCorrect]);
            }
        }
    }
}

exit(0);
