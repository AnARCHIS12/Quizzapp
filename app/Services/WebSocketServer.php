<?php

declare(strict_types=1);

namespace App\Services;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Core\Database;
use App\Services\JWTService;
use Exception;

/**
 * WebSocket Game server coordinating private duels in real-time
 */
class WebSocketServer implements MessageComponentInterface
{
    /**
     * Map connection ID to connection resource
     */
    protected \SplObjectStorage $clients;

    /**
     * Track authenticated user profiles by connection resource ID
     */
    protected array $verifiedUsers = [];

    /**
     * Simple in-memory socket message rate limiter tracker
     */
    protected array $messageCounts = [];

    /**
     * Track active game rooms in memory:
     * [
     *   'ROOM_CODE' => [
     *      'quiz_id' => int,
     *      'status' => 'waiting'|'playing'|'finished',
     *      'questions' => [...],
     *      'current_index' => int,
     *      'players' => [
     *         connection_resource_id => [
     *             'user_id' => int,
     *             'username' => string,
     *             'score' => int,
     *             'is_ready' => bool,
     *             'answers' => [ question_index => ['correct' => bool, 'time' => int] ],
     *             'connection' => ConnectionInterface
     *         ]
     *      ]
     *   ]
     * ]
     */
    protected array $rooms = [];

    private JWTService $jwt;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        $this->jwt = new JWTService();
    }

    /**
     * Upgrade WebSocket handshake connection and authenticate via query token parameter
     */
    public function onOpen(ConnectionInterface $conn): void
    {
        // 1. Extract and validate JWT Token from query parameters
        $psrRequest = $conn->httpRequest;
        $query = $psrRequest->getUri()->getQuery();
        $queryParams = [];
        parse_str($query, $queryParams);
        
        $token = $queryParams['token'] ?? '';
        $user = $this->jwt->decodeToken($token);

        if (!$user) {
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'Connexion rejetée. Jeton d\'authentification manquant ou expiré.'
            ]));
            $conn->close();
            return;
        }

        // 2. Attach connection and register verified user details
        $this->clients->attach($conn);
        $this->verifiedUsers[$conn->resourceId] = $user;
        $this->messageCounts[$conn->resourceId] = ['count' => 0, 'start' => time()];
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        $this->handleDisconnect($conn);
        
        // Clean memory bindings
        unset($this->verifiedUsers[$conn->resourceId]);
        unset($this->messageCounts[$conn->resourceId]);
    }

    public function onError(ConnectionInterface $conn, Exception $e): void
    {
        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        // 1. Enforce authentication checking
        if (!isset($this->verifiedUsers[$from->resourceId])) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Non authentifié.']));
            $from->close();
            return;
        }

        // 2. In-Memory rate limiter (Limit to 15 messages per 10 seconds)
        $rateLimitTracker =& $this->messageCounts[$from->resourceId];
        $timeElapsed = time() - $rateLimitTracker['start'];
        
        if ($timeElapsed > 10) {
            $rateLimitTracker = ['count' => 1, 'start' => time()];
        } else {
            $rateLimitTracker['count']++;
            if ($rateLimitTracker['count'] > 15) {
                $from->send(json_encode(['type' => 'error', 'message' => 'Limite de requêtes dépassée.']));
                $from->close();
                return;
            }
        }

        // 3. Decode payload
        $data = json_decode($msg, true);
        if (!$data || !isset($data['action'])) {
            return;
        }

        try {
            switch ($data['action']) {
                case 'create':
                    $this->createRoom($from, $data);
                    break;
                case 'join':
                    $this->joinRoom($from, $data);
                    break;
                case 'ready':
                    $this->playerReady($from, $data);
                    break;
                case 'pick_category':
                    $this->pickCategory($from, $data);
                    break;
                case 'submit_answer':
                    $this->submitAnswer($from, $data);
                    break;
                case 'play_again':
                    $this->playAgain($from, $data);
                    break;
            }
        } catch (Exception $e) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => $e->getMessage()
            ]));
        }
    }

    /**
     * Create a room
     */
    private function createRoom(ConnectionInterface $conn, array $data): void
    {
        $user = $this->verifiedUsers[$conn->resourceId];
        $userId = (int) $user['id'];
        $username = $user['username'];

        // Generate cryptographically secure 6-character hex code
        $code = strtoupper(bin2hex(random_bytes(3)));

        $this->rooms[$code] = [
            'quiz_id'       => null,
            'status'        => 'waiting',
            'questions'     => [],
            'current_index' => 0,
            // Category selection tracking
            'selecting'     => false,
            'picks_per_player' => 3,   // each player picks 3 categories
            'picked_categories' => [], // [userId => [cat_id, ...], ...]
            'pick_order'    => [],     // ordered list of userIds (alternating)
            'pick_index'    => 0,      // current position in pick_order
            'players'       => [
                $conn->resourceId => [
                    'user_id'    => $userId,
                    'username'   => $username,
                    'score'      => 0,
                    'is_ready'   => false,
                    'answers'    => [],
                    'connection' => $conn
                ]
            ]
        ];

        // Save match to DB (no quiz_id yet)
        Database::query("INSERT INTO matches (room_code, quiz_id, status) VALUES (?, NULL, 'waiting')", [$code]);
        $matchId = (int) Database::lastInsertId();
        Database::query("INSERT INTO match_players (match_id, user_id, is_ready) VALUES (?, ?, 0)", [$matchId, $userId]);

        $conn->send(json_encode([
            'type'      => 'room_created',
            'room_code' => $code,
            'players'   => $this->getPlayersList($code)
        ]));
    }

    /**
     * Join a room
     */
    private function joinRoom(ConnectionInterface $conn, array $data): void
    {
        $user = $this->verifiedUsers[$conn->resourceId];
        $userId = (int) $user['id'];
        $username = $user['username'];
        $code = strtoupper($data['room_code'] ?? '');

        if (!isset($this->rooms[$code])) {
            // Try to load room details if matches database has it as waiting or active
            $match = Database::fetch("SELECT * FROM matches WHERE room_code = ? AND status IN ('waiting', 'selecting', 'playing')", [$code]);
            if (!$match) {
                throw new Exception("Salle introuvable ou partie terminée.");
            }

            // Restore from database
            $quizId = $match['quiz_id'] ? (int)$match['quiz_id'] : null;
            if ($quizId) {
                $questions = Database::fetchAll("SELECT * FROM questions WHERE quiz_id = ? ORDER BY sorting_order ASC", [$quizId]);
            } else {
                $questions = Database::fetchAll("SELECT * FROM questions ORDER BY RAND() LIMIT 10");
            }
            foreach ($questions as &$q) {
                $rawAnswers = Database::fetchAll("SELECT id, answer_text, is_correct, match_order, association_pair FROM answers WHERE question_id = ?", [$q['id']]);
                $unique = [];
                foreach ($rawAnswers as $ans) {
                    $k = trim((string)$ans['answer_text']);
                    if (!isset($unique[$k])) {
                        $unique[$k] = $ans;
                    }
                }
                $q['answers'] = array_values($unique);
            }

            $this->rooms[$code] = [
                'quiz_id' => $quizId,
                'status' => $match['status'] ?? 'waiting',
                'questions' => $questions,
                'current_index' => (int)($match['current_question_index'] ?? 0),
                'selecting' => ($match['status'] === 'selecting'),
                'picks_per_player' => 3,
                'picked_categories' => [],
                'pick_order' => [],
                'pick_index' => 0,
                'players' => []
            ];
        }

        // Check if user is an existing player reconnecting
        $existingResourceId = null;
        foreach ($this->rooms[$code]['players'] as $resId => $pData) {
            if ((int)$pData['user_id'] === $userId) {
                $existingResourceId = $resId;
                break;
            }
        }

        if ($existingResourceId !== null) {
            // Reconnect existing player with new connection resource ID
            $playerData = $this->rooms[$code]['players'][$existingResourceId];
            unset($this->rooms[$code]['players'][$existingResourceId]);
            $playerData['connection'] = $conn;
            $this->rooms[$code]['players'][$conn->resourceId] = $playerData;
        } else {
            // New player joining
            if ($this->rooms[$code]['status'] !== 'waiting') {
                throw new Exception("La partie a déjà débuté.");
            }

            $this->rooms[$code]['players'][$conn->resourceId] = [
                'user_id' => $userId,
                'username' => $username,
                'score' => 0,
                'is_ready' => false,
                'answers' => [],
                'connection' => $conn
            ];

            // Register in DB if not already present
            $match = Database::fetch("SELECT id FROM matches WHERE room_code = ?", [$code]);
            if ($match) {
                Database::query("INSERT IGNORE INTO match_players (match_id, user_id, is_ready) VALUES (?, ?, 0)", [$match['id'], $userId]);
            }
        }

        // Send room_joined event
        $conn->send(json_encode([
            'type' => 'room_joined',
            'room_code' => $code,
            'players' => $this->getPlayersList($code)
        ]));

        // Notify room members
        $this->broadcast($code, [
            'type' => 'player_joined',
            'players' => $this->getPlayersList($code)
        ]);

        // When 2nd player joins, trigger AI question generation in background for all categories
        if (count($this->rooms[$code]['players']) >= 2 && $this->rooms[$code]['status'] === 'waiting') {
            $this->triggerAsyncAIGeneration();
        }

        // Catch-up for rejoining players if game is in progress
        if ($this->rooms[$code]['status'] === 'selecting') {
            $categories = Database::fetchAll(
                "SELECT id, name, description FROM categories ORDER BY name ASC"
            );
            $conn->send(json_encode([
                'type' => 'category_selection_start',
                'pick_order' => $this->rooms[$code]['pick_order'],
                'current_picker' => $this->rooms[$code]['pick_order'][$this->rooms[$code]['pick_index']] ?? null,
                'picks_per_player' => $this->rooms[$code]['picks_per_player'],
                'categories' => $categories,
                'picked' => []
            ]));
        } elseif ($this->rooms[$code]['status'] === 'playing') {
            $currIdx = $this->rooms[$code]['current_index'];
            if (isset($this->rooms[$code]['questions'][$currIdx])) {
                $this->sendQuestion($code, $currIdx);
            }
        }
    }

    /**
     * Toggle player ready status — once all ready, start category selection
     */
    private function playerReady(ConnectionInterface $conn, array $data): void
    {
        $code = strtoupper($data['room_code'] ?? '');
        if (!isset($this->rooms[$code])) {
            return;
        }

        $this->rooms[$code]['players'][$conn->resourceId]['is_ready'] = true;

        // Save ready in DB
        $match = Database::fetch("SELECT id FROM matches WHERE room_code = ?", [$code]);
        if ($match) {
            Database::query("UPDATE match_players SET is_ready = 1 WHERE match_id = ? AND user_id = ?", [
                $match['id'],
                $this->rooms[$code]['players'][$conn->resourceId]['user_id']
            ]);
        }

        $this->broadcast($code, [
            'type'    => 'player_ready',
            'players' => $this->getPlayersList($code)
        ]);

        // Check if all players are ready (min 2)
        $allReady = true;
        foreach ($this->rooms[$code]['players'] as $player) {
            if (!$player['is_ready']) { $allReady = false; break; }
        }

        if ($allReady && count($this->rooms[$code]['players']) >= 2) {
            $this->startCategorySelection($code);
        }
    }

    /**
     * Start the alternating category selection phase
     */
    private function startCategorySelection(string $code): void
    {
        $room = &$this->rooms[$code];
        $room['status']    = 'selecting';
        $room['selecting'] = true;

        // Build alternating pick order: A B A B A B (3 picks per player = 6 total)
        $playerIds = array_map(fn($p) => $p['user_id'], array_values($room['players']));
        $pickOrder = [];
        $picksEach = $room['picks_per_player'];
        for ($i = 0; $i < $picksEach; $i++) {
            foreach ($playerIds as $uid) {
                $pickOrder[] = $uid;
            }
        }
        $room['pick_order'] = $pickOrder;
        $room['pick_index'] = 0;
        foreach ($playerIds as $uid) {
            $room['picked_categories'][$uid] = [];
        }

        // Load all available categories
        $categories = Database::fetchAll(
            "SELECT id, name, description FROM categories ORDER BY name ASC"
        );

        Database::query("UPDATE matches SET status = 'selecting' WHERE room_code = ?", [$code]);

        $this->broadcast($code, [
            'type'           => 'category_selection_start',
            'pick_order'     => $pickOrder,
            'current_picker' => $pickOrder[0],
            'picks_per_player' => $picksEach,
            'categories'     => $categories,
            'picked'         => []
        ]);
    }

    /**
     * Handle a player picking a category
     */
    private function pickCategory(ConnectionInterface $conn, array $data): void
    {
        $code = strtoupper($data['room_code'] ?? '');
        if (!isset($this->rooms[$code])) return;

        $room = &$this->rooms[$code];
        if ($room['status'] !== 'selecting') return;

        $user      = $this->verifiedUsers[$conn->resourceId];
        $userId    = (int)$user['id'];
        $catId     = (int)($data['category_id'] ?? 0);
        $pickIndex = $room['pick_index'];

        // Validate it's this player's turn
        if (($room['pick_order'][$pickIndex] ?? null) !== $userId) {
            $conn->send(json_encode(['type' => 'error', 'message' => "Ce n'est pas votre tour de choisir."]));
            return;
        }

        // Validate category exists
        $cat = Database::fetch("SELECT id, name FROM categories WHERE id = ?", [$catId]);
        if (!$cat) {
            $conn->send(json_encode(['type' => 'error', 'message' => 'Catégorie introuvable.']));
            return;
        }

        // Register pick
        $room['picked_categories'][$userId][] = $catId;
        $room['pick_index']++;

        // Build flat list of all picked categories with owner info
        $allPicked = [];
        foreach ($room['picked_categories'] as $uid => $catIds) {
            foreach ($catIds as $cid) {
                $allPicked[] = ['user_id' => $uid, 'category_id' => $cid];
            }
        }

        $nextPickIndex  = $room['pick_index'];
        $totalPicks     = count($room['pick_order']);
        $selectionDone  = ($nextPickIndex >= $totalPicks);

        if ($selectionDone) {
            $playerUserIds = array_map(fn($p) => (int)$p['user_id'], array_values($room['players']));
            $apiKey = getenv('MISTRAL_API_KEY') ?: '';
            $hasAI  = !empty($apiKey) && function_exists('proc_open');

            if ($hasAI) {
                // Collect all chosen category IDs
                $chosenCatIds = [];
                foreach ($room['picked_categories'] as $uid => $catIds) {
                    foreach ($catIds as $cId) {
                        $chosenCatIds[] = (int)$cId;
                    }
                }
                $chosenCatIds = array_unique($chosenCatIds);

                // Launch targeted AI generation ONLY for chosen categories, tagged with this match code
                $this->triggerAsyncAIGenerationForCategories($code, $chosenCatIds);

                // Show loading screen to players during AI generation (12 seconds)
                $this->broadcast($code, [
                    'type'    => 'generating_questions',
                    'message' => '🤖 L\'IA génère 18 nouvelles questions personnalisées...',
                    'seconds' => 12,
                    'picked'  => $allPicked
                ]);

                Database::query("UPDATE matches SET status = 'playing' WHERE room_code = ?", [$code]);
                $room['status']    = 'playing';
                $room['selecting'] = false;
                $room['picked_categories_snapshot'] = $room['picked_categories'];
                $room['player_user_ids_snapshot']   = $playerUserIds;

                // After 12s, load the 18 freshly AI-generated questions for this room code
                $loop = \React\EventLoop\Loop::get();
                $loop->addTimer(12.0, function() use ($code, $allPicked) {
                    if (!isset($this->rooms[$code])) return;

                    // Priority 1: Load 100% brand new AI-generated questions for this exact room
                    $questions = $this->loadFreshAIQuestionsForRoom($code);

                    // Priority 2: Fallback to DB pool if AI generation was unavailable
                    if (empty($questions)) {
                        $pickedCats = $this->rooms[$code]['picked_categories_snapshot'] ?? $this->rooms[$code]['picked_categories'];
                        $playerUids = $this->rooms[$code]['player_user_ids_snapshot']   ?? [];
                        $questions  = $this->loadQuestionsFromCategoryPicks($pickedCats, $playerUids);
                    }

                    if (empty($questions)) return;
                    shuffle($questions);
                    $this->rooms[$code]['questions'] = $questions;

                    $this->broadcast($code, [
                        'type'            => 'selection_complete',
                        'picked'          => $allPicked,
                        'total_questions' => count($questions)
                    ]);

                    $loop2 = \React\EventLoop\Loop::get();
                    $loop2->addTimer(2.0, function() use ($code) {
                        if (isset($this->rooms[$code])) {
                            $this->sendQuestion($code, 0);
                        }
                    });
                });

            } else {
                // No AI key: load questions immediately from DB
                $questions = $this->loadQuestionsFromCategoryPicks($room['picked_categories'], $playerUserIds);
                if (empty($questions)) {
                    throw new \Exception('Pas assez de questions pour les catégories choisies.');
                }
                shuffle($questions);
                $room['questions'] = $questions;
                $room['status']    = 'playing';
                $room['selecting'] = false;

                Database::query("UPDATE matches SET status = 'playing' WHERE room_code = ?", [$code]);

                $this->broadcast($code, [
                    'type'            => 'selection_complete',
                    'picked'          => $allPicked,
                    'total_questions' => count($questions)
                ]);

                $loop = \React\EventLoop\Loop::get();
                $loop->addTimer(2.0, function() use ($code) {
                    if (isset($this->rooms[$code])) {
                        $this->sendQuestion($code, 0);
                    }
                });
            }
        } else {
            $this->broadcast($code, [
                'type'           => 'category_picked',
                'picked_by'      => $userId,
                'category'       => $cat,
                'current_picker' => $room['pick_order'][$nextPickIndex],
                'picks_done'     => $nextPickIndex,
                'total_picks'    => $totalPicks,
                'all_picked'     => $allPicked
            ]);
        }
    }

    /**
     * Launch async background PHP process to generate AI questions for SPECIFIC chosen categories only.
     * Uses popen+nohup+& : the shell forks immediately and pclose returns in <1ms, never blocking the event loop.
     */
    private function triggerAsyncAIGenerationForCategories(string $roomCode, array $catIds): void
    {
        if (!function_exists('popen')) return;
        if (empty($catIds)) return;

        $scriptPath = dirname(__DIR__, 2) . '/bin/generate_questions_async.php';
        if (!file_exists($scriptPath)) return;

        $args     = implode(' ', array_map('intval', $catIds));
        $safeRoom = escapeshellarg($roomCode);
        $safeScript = escapeshellarg($scriptPath);

        // nohup + & : shell forks child and exits immediately => pclose returns in <1ms
        $handle = \popen("nohup php {$safeScript} {$safeRoom} {$args} > /dev/null 2>&1 &", 'r');
        if (is_resource($handle)) \pclose($handle);
    }

    /**
     * Load freshly generated AI questions specifically created for this match room.
     * Returns empty array gracefully if column doesn't exist yet (existing DB before migration).
     */
    private function loadFreshAIQuestionsForRoom(string $roomCode): array
    {
        try {
            $rows = Database::fetchAll(
                "SELECT * FROM questions WHERE match_room_code = ? ORDER BY id ASC",
                [$roomCode]
            );
        } catch (\Exception $e) {
            // Column may not exist yet on older databases — fall back to DB pool silently
            return [];
        }

        if (empty($rows)) return [];

        $allQuestions = [];
        foreach ($rows as $q) {
            $rawAnswers = Database::fetchAll(
                "SELECT id, answer_text, is_correct, match_order, association_pair FROM answers WHERE question_id = ?",
                [$q['id']]
            );
            $unique = [];
            foreach ($rawAnswers as $ans) {
                $k = trim((string)$ans['answer_text']);
                if (!isset($unique[$k])) {
                    $unique[$k] = $ans;
                }
            }
            $q['answers'] = array_values($unique);
            $allQuestions[] = $q;
        }

        return $allQuestions;
    }

    /**
     * Launch async background PHP process to generate fresh AI questions for ALL categories.
     * Non-blocking: uses proc_open with STDIN/STDOUT/STDERR redirected to /dev/null.
     * Falls back silently if MISTRAL_API_KEY is not set.
     */
    private function triggerAsyncAIGeneration(): void
    {
        if (!function_exists('proc_open')) {
            return; // Safety guard: if proc_open is disabled, skip silently
        }

        $apiKey = getenv('MISTRAL_API_KEY') ?: '';
        if (empty($apiKey)) {
            return; // No key configured — skip silently
        }

        // Get ALL category IDs so fresh questions are ready for ANY theme the players pick
        $categories = Database::fetchAll("SELECT id FROM categories");
        $catIds = array_column($categories, 'id');
        if (empty($catIds)) return;

        $scriptPath = dirname(__DIR__, 2) . '/bin/generate_questions_async.php';
        if (!file_exists($scriptPath)) return;

        $args       = implode(' ', array_map('intval', $catIds));
        $safeScript = escapeshellarg($scriptPath);

        // nohup + & : shell forks child and exits immediately => pclose returns in <1ms
        $handle = \popen("nohup php {$safeScript} {$args} > /dev/null 2>&1 &", 'r');
        if (is_resource($handle)) \pclose($handle);
    }

    private function loadQuestionsFromCategoryPicks(array $pickedByPlayer, array $playerUserIds = []): array
    {
        $questionsPerCategory = 3;
        $allQuestions = [];
        $seen = [];

        // Exclude all questions previously played by any player in this room across past matches
        if (!empty($playerUserIds)) {
            $cleanUserIds = array_map('intval', array_filter($playerUserIds));
            if (!empty($cleanUserIds)) {
                try {
                    $pastPlayed = Database::fetchAll(
                        "SELECT DISTINCT question_id FROM user_question_history WHERE user_id IN (" . implode(',', $cleanUserIds) . ")"
                    );
                    foreach ($pastPlayed as $pp) {
                        $seen[] = (int)$pp['question_id'];
                    }
                } catch (Exception $e) {}
            }
        }

        foreach ($pickedByPlayer as $uid => $catIds) {
            foreach ($catIds as $catId) {
                $key = (int)$catId;
                
                $notInClause = !empty($seen) ? "AND q.id NOT IN (" . implode(',', array_map('intval', $seen)) . ")" : "";

                // 1. Fetch 3 unseen questions matching this category or its subcategories
                $rows = Database::fetchAll(
                    "SELECT DISTINCT q.* FROM questions q
                     JOIN quizzes quiz ON q.quiz_id = quiz.id
                     JOIN categories c ON quiz.category_id = c.id
                     WHERE (c.id = ? OR c.parent_id = ?) {$notInClause}
                     ORDER BY RAND()
                     LIMIT 3",
                    [$key, $key]
                );

                // 2. If not enough unseen questions, fallback to parent category or sibling subcategories
                if (count($rows) < 3) {
                    $extraRows = Database::fetchAll(
                        "SELECT DISTINCT q.* FROM questions q
                         JOIN quizzes quiz ON q.quiz_id = quiz.id
                         JOIN categories c ON quiz.category_id = c.id
                         WHERE (
                            c.id IN (SELECT parent_id FROM categories WHERE id = ? AND parent_id IS NOT NULL)
                            OR c.parent_id IN (SELECT parent_id FROM categories WHERE id = ? AND parent_id IS NOT NULL)
                            OR c.parent_id = ?
                         ) {$notInClause}
                         ORDER BY RAND()
                         LIMIT 3",
                        [$key, $key, $key]
                    );
                    foreach ($extraRows as $er) {
                        if (count($rows) >= 3) break;
                        if (!in_array($er['id'], array_column($rows, 'id'), true)) {
                            $rows[] = $er;
                        }
                    }
                }

                // 3. If still not enough, reuse questions from this SAME category family (ignore seen/history)
                if (count($rows) < 3) {
                    $repeatRows = Database::fetchAll(
                        "SELECT DISTINCT q.* FROM questions q
                         JOIN quizzes quiz ON q.quiz_id = quiz.id
                         JOIN categories c ON quiz.category_id = c.id
                         WHERE (
                            c.id = ? OR c.parent_id = ?
                            OR c.id IN (SELECT parent_id FROM categories WHERE id = ? AND parent_id IS NOT NULL)
                            OR c.parent_id IN (SELECT parent_id FROM categories WHERE id = ? AND parent_id IS NOT NULL)
                         )
                         ORDER BY RAND()
                         LIMIT 3",
                        [$key, $key, $key, $key]
                    );
                    foreach ($repeatRows as $rr) {
                        if (count($rows) >= 3) break;
                        if (!in_array($rr['id'], array_column($rows, 'id'), true)) {
                            $rows[] = $rr;
                        }
                    }
                }

                foreach ($rows as $q) {
                    $rawAnswers = Database::fetchAll(
                        "SELECT id, answer_text, is_correct, match_order, association_pair FROM answers WHERE question_id = ?",
                        [$q['id']]
                    );
                    $unique = [];
                    foreach ($rawAnswers as $ans) {
                        $k = trim((string)$ans['answer_text']);
                        if (!isset($unique[$k])) {
                            $unique[$k] = $ans;
                        }
                    }
                    $q['answers'] = array_values($unique);
                    $allQuestions[] = $q;
                    $seen[] = $q['id'];
                }
            }
        }

        // Guarantee exactly 18 questions, strictly padded using only the chosen category questions
        if (!empty($allQuestions) && count($allQuestions) < 18) {
            $pool = $allQuestions;
            while (count($allQuestions) < 18) {
                foreach ($pool as $pq) {
                    $allQuestions[] = $pq;
                    if (count($allQuestions) >= 18) break;
                }
            }
        }

        return $allQuestions;
    }

    /**
     * Send question index to players
     */
    private function sendQuestion(string $code, int $index): void
    {
        $this->rooms[$code]['current_index'] = $index;
        $question = $this->rooms[$code]['questions'][$index];

        // Format question for security (strip correct answer flags)
        $clientQuestion = [
            'id' => $question['id'],
            'type' => $question['type'],
            'question_text' => $question['question_text'],
            'media_url' => $question['media_url'] ? '/assets/uploads/' . $question['media_url'] : null,
            'points' => $question['points'],
            'answers' => array_map(function($ans) use ($question) {
                $formatted = [
                    'id' => $ans['id'],
                    'answer_text' => $ans['answer_text']
                ];
                if ($question['type'] === 'association') {
                    $formatted['association_pair'] = $ans['association_pair'];
                }
                return $formatted;
            }, $question['answers'])
        ];

        // Update DB
        Database::query("UPDATE matches SET current_question_index = ? WHERE room_code = ?", [$index, $code]);

        $this->broadcast($code, [
            'type' => 'new_question',
            'index' => $index,
            'total' => count($this->rooms[$code]['questions']),
            'question' => $clientQuestion
        ]);
    }

    /**
     * Submit an answer
     */
    private function submitAnswer(ConnectionInterface $conn, array $data): void
    {
        $code = strtoupper($data['room_code'] ?? '');
        if (!isset($this->rooms[$code]) || $this->rooms[$code]['status'] !== 'playing') {
            return;
        }

        $index = $this->rooms[$code]['current_index'];
        $question = $this->rooms[$code]['questions'][$index];
        $timeSpent = (float)($data['time_spent'] ?? 0.0);
        
        $isCorrect = $this->validateAnswer($question, $data['answer'] ?? null);
        $pointsEarned = 0;

        if ($isCorrect) {
            // Compute score with speed multiplier: base_points + speed_bonus
            $basePoints = (int)$question['points'];
            $maxTime = 20; // default max seconds
            $speedFactor = max(0.0, ($maxTime - $timeSpent) / $maxTime);
            $pointsEarned = (int)($basePoints + round($basePoints * 0.5 * $speedFactor));
        }

        // Save player answer state
        $player =& $this->rooms[$code]['players'][$conn->resourceId];
        $player['score'] += $pointsEarned;
        $player['answers'][$index] = [
            'correct' => $isCorrect,
            'time' => $timeSpent
        ];

        // Save player score in Database
        $match = Database::fetch("SELECT id FROM matches WHERE room_code = ?", [$code]);
        if ($match) {
            Database::query("UPDATE match_players SET score = ?, current_answered_index = ? WHERE match_id = ? AND user_id = ?", [
                $player['score'],
                $index,
                $match['id'],
                $player['user_id']
            ]);
        }

        // Check if all players answered current question
        $allAnswered = true;
        foreach ($this->rooms[$code]['players'] as $p) {
            if (!isset($p['answers'][$index])) {
                $allAnswered = false;
                break;
            }
        }

        if ($allAnswered) {
            // Compile explanation and correct answers
            $correctAnswers = [];
            foreach ($question['answers'] as $ans) {
                if ($ans['is_correct']) {
                    $correctAnswers[] = [
                        'id' => $ans['id'],
                        'answer_text' => $ans['answer_text'],
                        'match_order' => $ans['match_order'],
                        'association_pair' => $ans['association_pair']
                    ];
                }
            }

            // Send feedback
            $this->broadcast($code, [
                'type' => 'question_feedback',
                'explanation' => $question['explanation'],
                'correct_answers' => $correctAnswers,
                'scores' => $this->getPlayersList($code)
            ]);

            // Next question or finish game
            $nextIndex = $index + 1;
            if ($nextIndex < count($this->rooms[$code]['questions'])) {
                // Wait 5 seconds to let players review explanation, then send next
                $this->scheduleNextQuestion($code, $nextIndex);
            } else {
                // Wait 5 seconds to let players review explanation of the last question, then show podium
                $loop = \React\EventLoop\Loop::get();
                $loop->addTimer(5.0, function() use ($code) {
                    $this->finishGame($code);
                });
            }
        }
    }

    /**
     * Evaluate correctness for various question types
     */
    private function validateAnswer(array $question, $clientAnswer): bool
    {
        if ($clientAnswer === null) return false;

        switch ($question['type']) {
            case 'mcq':
            case 'true_false':
            case 'image':
            case 'audio':
            case 'video':
                $ansId = (int)$clientAnswer;
                foreach ($question['answers'] as $ans) {
                    if ($ans['id'] === $ansId && $ans['is_correct']) {
                        return true;
                    }
                }
                return false;

            case 'multi_choice':
                if (!is_array($clientAnswer)) return false;
                $correctIds = [];
                foreach ($question['answers'] as $ans) {
                    if ($ans['is_correct']) {
                        $correctIds[] = $ans['id'];
                    }
                }
                sort($correctIds);
                sort($clientAnswer);
                return $correctIds === array_map('intval', $clientAnswer);

            case 'ranking':
                if (!is_array($clientAnswer)) return false;
                foreach ($question['answers'] as $ans) {
                    $submittedOrder = $clientAnswer[$ans['id']] ?? null;
                    if ((int)$submittedOrder !== (int)$ans['match_order']) {
                        return false;
                    }
                }
                return true;

            case 'association':
                if (!is_array($clientAnswer)) return false;
                foreach ($question['answers'] as $ans) {
                    $submittedPair = $clientAnswer[$ans['id']] ?? null;
                    if ($submittedPair !== $ans['association_pair']) {
                        return false;
                    }
                }
                return true;
        }

        return false;
    }

    /**
     * Delayed sending of next question
     */
    private function scheduleNextQuestion(string $code, int $nextIndex): void
    {
        $loop = \React\EventLoop\Loop::get();
        $loop->addTimer(5.0, function() use ($code, $nextIndex) {
            if (isset($this->rooms[$code]) && $this->rooms[$code]['status'] === 'playing') {
                $this->sendQuestion($code, $nextIndex);
            }
        });
    }

    /**
     * Mark duel finished
     */
    private function finishGame(string $code): void
    {
        if (!isset($this->rooms[$code])) return;

        $this->rooms[$code]['status'] = 'finished';
        Database::query("UPDATE matches SET status = 'finished' WHERE room_code = ?", [$code]);

        $match = Database::fetch("SELECT id, quiz_id FROM matches WHERE room_code = ?", [$code]);
        // Default XP reward for general/category mode (no specific quiz)
        $xpReward = 10;
        if (!empty($match['quiz_id'])) {
            $xpReward = (int)Database::fetchColumn("SELECT xp_reward FROM quizzes WHERE id = ?", [$match['quiz_id']]);
        }

        // Process XP rewards and statistics
        foreach ($this->rooms[$code]['players'] as $player) {
            $userId = $player['user_id'];
            $score = $player['score'];
            $totalQuestions = count($this->rooms[$code]['questions']);

            // Save history of questions played by this user so they are not repeated in future matches
            foreach ($this->rooms[$code]['questions'] as $q) {
                try {
                    Database::query("INSERT IGNORE INTO user_question_history (user_id, question_id) VALUES (?, ?)", [
                        $userId, $q['id']
                    ]);
                } catch (Exception $e) {}
            }

            // Calculate metrics
            $correctCount = 0;
            $totalTime = 0;
            foreach ($player['answers'] as $ans) {
                if ($ans['correct']) $correctCount++;
                $totalTime += $ans['time'];
            }

            // Save metrics to match_players
            Database::query("UPDATE match_players SET score = ?, finished_at = NOW() WHERE match_id = ? AND user_id = ?", [
                $score, $match['id'], $userId
            ]);

            // Update user global statistics
            $stats = Database::fetch("SELECT * FROM user_statistics WHERE user_id = ?", [$userId]);
            if ($stats) {
                $newPlayed = $stats['total_played'] + 1;
                $newCorrect = $stats['correct_count'] + $correctCount;
                $newTimeSpent = $stats['time_spent'] + $totalTime;
                $avgSpeed = $newPlayed > 0 ? ($newTimeSpent / ($newPlayed * $totalQuestions)) : 0;
                
                // Add XP (score / 10 + completion reward)
                $xpEarned = (int)($score / 10) + $xpReward;
                $newXp = $stats['xp'] + $xpEarned;
                $newLevel = (int) floor($newXp / 100) + 1; // Level up every 100 XP

                Database::query(
                    "UPDATE user_statistics SET level = ?, xp = ?, total_played = ?, correct_count = ?, time_spent = ?, average_time_per_question = ? WHERE user_id = ?",
                    [$newLevel, $newXp, $newPlayed, $newCorrect, $newTimeSpent, $avgSpeed, $userId]
                );

                // Trigger achievement checking (standard milestones)
                $this->checkAchievements($userId, $newLevel, $newPlayed, $correctCount, $totalQuestions);
            }
        }

        $this->broadcast($code, [
            'type' => 'game_over',
            'podium' => $this->getPlayersList($code)
        ]);
    }

    /**
     * Check and unlock achievements
     */
    private function checkAchievements(int $userId, int $level, int $quizzesPlayed, int $correctAnswers, int $totalQuestions): void
    {
        $achievements = Database::fetchAll("SELECT * FROM achievements");
        foreach ($achievements as $ach) {
            $unlocked = false;
            switch ($ach['criteria_type']) {
                case 'quizzes_played':
                    if ($quizzesPlayed >= $ach['criteria_value']) $unlocked = true;
                    break;
                case 'level_reached':
                    if ($level >= $ach['criteria_value']) $unlocked = true;
                    break;
                case 'perfect_score':
                    if ($correctAnswers === $totalQuestions) $unlocked = true;
                    break;
            }

            if ($unlocked) {
                Database::query("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)", [$userId, $ach['id']]);
            }
        }
    }

    /**
     * Play again resets player scores & answers, keeps connections
     */
    private function playAgain(ConnectionInterface $conn, array $data): void
    {
        $code = strtoupper($data['room_code'] ?? '');
        if (!isset($this->rooms[$code])) return;

        // Reset room
        $this->rooms[$code]['status'] = 'waiting';
        $this->rooms[$code]['current_index'] = 0;
        $this->rooms[$code]['selecting'] = false;
        $this->rooms[$code]['picked_categories'] = [];
        $this->rooms[$code]['pick_order'] = [];
        $this->rooms[$code]['pick_index'] = 0;
        $this->rooms[$code]['questions'] = [];
        
        foreach ($this->rooms[$code]['players'] as &$player) {
            $player['score'] = 0;
            $player['is_ready'] = false;
            $player['answers'] = [];
        }

        // Reset match record in DB or create new one
        Database::query("UPDATE matches SET status = 'waiting', current_question_index = 0 WHERE room_code = ?", [$code]);
        $match = Database::fetch("SELECT id FROM matches WHERE room_code = ?", [$code]);
        if ($match) {
            Database::query("UPDATE match_players SET score = 0, is_ready = 0, current_answered_index = -1, finished_at = NULL WHERE match_id = ?", [$match['id']]);
        }

        $this->broadcast($code, [
            'type' => 'play_again_reset',
            'players' => $this->getPlayersList($code)
        ]);
    }

    /**
     * Clean client connection on disconnect
     */
    private function handleDisconnect(ConnectionInterface $conn): void
    {
        foreach ($this->rooms as $code => $room) {
            if (isset($room['players'][$conn->resourceId])) {
                $user = $room['players'][$conn->resourceId];
                unset($this->rooms[$code]['players'][$conn->resourceId]);

                if (empty($this->rooms[$code]['players'])) {
                    // Destroy empty room
                    unset($this->rooms[$code]);
                    Database::query("UPDATE matches SET status = 'finished' WHERE room_code = ?", [$code]);
                } else {
                    $this->broadcast($code, [
                        'type' => 'player_left',
                        'username' => $user['username'],
                        'players' => $this->getPlayersList($code)
                    ]);

                    // If game playing and only one player left, automatically finish it
                    if ($room['status'] === 'playing' && count($this->rooms[$code]['players']) < 2) {
                        $this->finishGame($code);
                    }
                }
                break;
            }
        }
    }

    /**
     * Broadcast to all players in a room
     */
    private function broadcast(string $code, array $payload): void
    {
        if (!isset($this->rooms[$code])) return;

        $msg = json_encode($payload);
        foreach ($this->rooms[$code]['players'] as $player) {
            $player['connection']->send($msg);
        }
    }

    /**
     * Get serializable players list
     */
    private function getPlayersList(string $code): array
    {
        $list = [];
        if (!isset($this->rooms[$code])) return [];

        foreach ($this->rooms[$code]['players'] as $p) {
            $list[] = [
                'user_id' => $p['user_id'],
                'username' => $p['username'],
                'score' => $p['score'],
                'is_ready' => $p['is_ready']
            ];
        }

        // Sort descending by score
        usort($list, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $list;
    }
}
