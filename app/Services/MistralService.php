<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Mistral AI Service for dynamic question generation.
 * Questions are never identical — a random seed phrase is injected into each
 * prompt so the model generates fresh, unique content every time.
 */
class MistralService
{
    private ?string $apiKey;
    private string $model;

    /** Seed words to randomise prompts across calls */
    private const SEED_PHRASES = [
        'Surprends-moi', 'Sois original', 'Évite le banal',
        'Choisis un angle inattendu', 'Propose quelque chose de rare',
        'Interroge sur un détail méconnu', 'Mets l\'accent sur une anecdote',
        'Explore une perspective peu connue', 'Questionne sur un fait surprenant',
        'Axe-toi sur l\'histoire récente', 'Axe-toi sur l\'histoire ancienne',
        'Cible des événements précis', 'Cible des personnalités méconnues',
        'Parle de conséquences et d\'effets', 'Parle de causes et origines',
    ];

    public function __construct()
    {
        $this->apiKey = $_ENV['MISTRAL_API_KEY'] ?? null;
        $this->model  = $_ENV['MISTRAL_MODEL'] ?? 'mistral-small-latest';
    }

    /**
     * Check if Mistral service is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Generate unique dynamic questions for a specific category / topic.
     *
     * @param string      $categoryName  Main category name
     * @param string|null $subCategory   Optional sub-category
     * @param int         $count         Number of questions (default 10)
     * @return array Formatted questions array, or [] on failure
     */
    public function generateQuestions(string $categoryName, ?string $subCategory = null, int $count = 10): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $subject = $categoryName;
        if (!empty($subCategory)) {
            $subject .= ' (sous-thématique : ' . $subCategory . ')';
        }

        // Random seed to force variety on every call
        $seed = self::SEED_PHRASES[array_rand(self::SEED_PHRASES)];
        $unique = substr(md5(uniqid((string)mt_rand(), true)), 0, 8);

        $prompt  = "Directive de variété [{$unique}]: {$seed}.\n\n";
        $prompt .= "Génère exactement {$count} questions de quiz UNIQUES et ORIGINALES en français sur le sujet : \"{$subject}\".\n";
        $prompt .= "Règles absolues :\n";
        $prompt .= "- Chaque question est un QCM avec exactement 4 réponses (1 correcte, 3 incorrectes).\n";
        $prompt .= "- Les questions ne doivent pas être triviales ni répétitives entre elles.\n";
        $prompt .= "- Toutes les réponses doivent être factuellement correctes ou incorrectes, jamais ambiguës.\n";
        $prompt .= "- Inclure une explication courte et factuelle pour chaque bonne réponse.\n\n";
        $prompt .= "Réponds UNIQUEMENT avec un objet JSON valide, sans balise Markdown, de la forme exacte :\n";
        $prompt .= '{"questions":[{"type":"mcq","question_text":"...?","points":10,"explanation":"...","answers":[{"answer_text":"réponse correcte","is_correct":1},{"answer_text":"fausse 1","is_correct":0},{"answer_text":"fausse 2","is_correct":0},{"answer_text":"fausse 3","is_correct":0}]}]}';

        $payload = [
            'model'           => $this->model,
            'messages'        => [['role' => 'user', 'content' => $prompt]],
            'temperature'     => 0.9,
            'response_format' => ['type' => 'json_object'],
        ];

        $ch = curl_init('https://api.mistral.ai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            error_log("Mistral API Error: HTTP {$httpCode} | cURL: {$curlErr} | Body: {$response}");
            return [];
        }

        try {
            $data    = json_decode($response, true);
            $content = trim($data['choices'][0]['message']['content'] ?? '');

            // Strip markdown code fences if the model wraps them anyway
            if (str_starts_with($content, '```')) {
                $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
                $content = preg_replace('/\s*```$/i', '', $content);
                $content = trim($content);
            }

            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                error_log('Mistral: invalid JSON — ' . $content);
                return [];
            }

            // Support both {"questions":[...]} and a direct array
            $rawQuestions = $decoded['questions']
                ?? (isset($decoded[0]) ? $decoded : ($decoded[array_key_first($decoded)] ?? null));

            if (!is_array($rawQuestions)) {
                error_log('Mistral: could not locate questions array in response.');
                return [];
            }

            $validated = [];
            foreach ($rawQuestions as $q) {
                if (empty($q['question_text']) || empty($q['answers']) || !is_array($q['answers'])) {
                    continue;
                }

                $fq = [
                    'id'            => rand(100000, 999999),
                    'type'          => $q['type'] ?? 'mcq',
                    'question_text' => (string)$q['question_text'],
                    'media_url'     => null,
                    'points'        => (int)($q['points'] ?? 10),
                    'explanation'   => (string)($q['explanation'] ?? ''),
                    'answers'       => [],
                ];

                foreach ($q['answers'] as $ans) {
                    if (empty($ans['answer_text'])) {
                        continue;
                    }
                    $fq['answers'][] = [
                        'id'          => rand(100000, 999999),
                        'answer_text' => (string)$ans['answer_text'],
                        'is_correct'  => (int)($ans['is_correct'] ?? 0),
                    ];
                }

                if (!empty($fq['answers'])) {
                    $validated[] = $fq;
                }
            }

            return $validated;

        } catch (\Throwable $e) {
            error_log('Mistral parse error: ' . $e->getMessage());
            return [];
        }
    }
}
