<?php
/**
 * Async AI Question Generator
 * Called in background (proc_open / non-blocking) when a new duel room is ready.
 * Generates 5 fresh questions per category using Mistral AI and stores them in DB.
 * Questions are strictly scoped to the exact category + its parent context.
 *
 * Usage: php bin/generate_questions_async.php <category_id1> <category_id2> ...
 */

declare(strict_types=1);

// Bootstrap the application
$baseDir = dirname(__DIR__);
require_once $baseDir . '/vendor/autoload.php';

// Load .env
$envFile = $baseDir . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

$mistralKey   = $_ENV['MISTRAL_API_KEY'] ?? getenv('MISTRAL_API_KEY') ?? '';
$mistralModel = $_ENV['MISTRAL_MODEL']   ?? getenv('MISTRAL_MODEL')   ?? 'mistral-small-latest';

if (empty($mistralKey)) {
    exit(0);
}

// Get room code and category IDs from arguments
$roomCode = null;
if (isset($argv[1]) && !is_numeric($argv[1])) {
    $roomCode = strtoupper(trim($argv[1]));
    $categoryIds = array_map('intval', array_slice($argv, 2));
} else {
    $categoryIds = array_map('intval', array_slice($argv, 1));
}

if (empty($categoryIds)) {
    exit(0);
}

// Database connection
$dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'db';
$dbPort = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'quizzapp';
$dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'quizzapp_user';
$dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    exit(0);
}

// Track all question texts generated in this run to avoid same-text duplicates across repeated categories
$generatedThisRun = [];

foreach ($categoryIds as $catId) {
    // Get category with its parent name for precise context
    $stmt = $pdo->prepare(
        "SELECT c.id, c.name, c.description, p.name AS parent_name
         FROM categories c
         LEFT JOIN categories p ON c.parent_id = p.id
         WHERE c.id = ?"
    );
    $stmt->execute([$catId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$category) continue;

    // Get quiz for this category
    $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE category_id = ? LIMIT 1");
    $stmt->execute([$catId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quiz) continue;
    $quizId = (int)$quiz['id'];

    $categoryName = $category['name'];
    $parentName   = $category['parent_name'] ?? null;
    $description  = $category['description'] ?? '';

    // Build a precise themed context for Mistral
    if ($parentName) {
        $themeContext = "la sous-catégorie \"{$categoryName}\" (qui appartient à la catégorie parente \"{$parentName}\")";
        $scopeWarning = "IMPORTANT : Toutes les questions doivent porter EXCLUSIVEMENT sur \"{$categoryName}\", pas sur d'autres thèmes de \"{$parentName}\".";
    } else {
        $themeContext = "la catégorie \"{$categoryName}\"";
        $scopeWarning = "IMPORTANT : Toutes les questions doivent porter EXCLUSIVEMENT sur \"{$categoryName}\".";
    }

    if (!empty($description)) {
        $themeContext .= " (description : {$description})";
    }

    // Prompt with strict category scoping
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

Retourne UNIQUEMENT un tableau JSON valide de 5 objets, sans texte avant ou après.
Les questions doivent être variées, précises, éducatives et difficiles. Évite les questions trop génériques ou déjà vues.";

    $payload = json_encode([
        'model'    => $mistralModel,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature'     => 0.85,
        'max_tokens'      => 2000,
        'response_format' => ['type' => 'json_object']
    ]);

    $ch = curl_init('https://api.mistral.ai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $mistralKey
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) continue;

    $decoded = json_decode($response, true);
    $content = $decoded['choices'][0]['message']['content'] ?? '';
    if (empty($content)) continue;

    // Parse JSON — handle array or wrapped object
    $questions = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) continue;

    if (isset($questions['questions'])) {
        $questions = $questions['questions'];
    } elseif (!isset($questions[0])) {
        $questions = [$questions];
    }

    if (!is_array($questions)) continue;

    foreach ($questions as $q) {
        $questionText = trim($q['question'] ?? '');
        $explanation  = trim($q['explanation'] ?? '');
        $points       = (int)($q['points'] ?? 10);
        $answers      = $q['answers'] ?? [];

        if (empty($questionText) || count($answers) < 2) continue;

        // Skip if already generated in this run (same category processed twice = 2nd pick)
        if (in_array($questionText, $generatedThisRun, true)) continue;

        // Skip exact duplicates already in DB
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id = ? AND question_text = ?");
        $stmt->execute([$quizId, $questionText]);
        if ((int)$stmt->fetchColumn() > 0) continue;

        $generatedThisRun[] = $questionText;

        // Insert question tagged with match_room_code
        $stmt = $pdo->prepare(
            "INSERT INTO questions (quiz_id, question_text, question_type, points, explanation, sorting_order, match_room_code)
             VALUES (?, ?, 'qcm', ?, ?, 0, ?)"
        );
        $stmt->execute([$quizId, $questionText, $points, $explanation, $roomCode]);
        $questionId = (int)$pdo->lastInsertId();

        // Insert answers
        $stmtAns = $pdo->prepare(
            "INSERT IGNORE INTO answers (question_id, answer_text, is_correct)
             VALUES (?, ?, ?)"
        );
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
