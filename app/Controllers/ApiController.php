<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\User;
use App\Models\Category;
use App\Models\Quiz;
use App\Models\Question;
use App\Services\JWTService;
use App\Services\MistralService;
use App\Services\QuestionSelectionService;

/**
 * JSON REST API controller for the QuizzApp mobile application.
 * All responses are JSON. Auth via Bearer JWT token.
 */
class ApiController
{
    private JWTService $jwt;

    public function __construct()
    {
        $this->jwt = new JWTService();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function error(string $message, int $status = 400): void
    {
        $this->json(['success' => false, 'error' => $message], $status);
    }

    private function authUser(): ?array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }
        $token = substr($authHeader, 7);
        return $this->jwt->decodeToken($token);
    }

    private function bodyJson(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw ?: '{}', true) ?? [];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/ping  — Health check & server info
    // ─────────────────────────────────────────────────────────────────────────

    public function ping(): void
    {
        $this->json([
            'success'   => true,
            'app'       => 'QuizzApp',
            'version'   => '2.0',
            'timestamp' => date('c'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/login
    // ─────────────────────────────────────────────────────────────────────────

    public function login(): void
    {
        $body = $this->bodyJson();
        $loginInput = trim($body['login'] ?? '');
        $password   = $body['password'] ?? '';

        if (empty($loginInput) || empty($password)) {
            $this->error('Identifiant et mot de passe requis.');
        }

        $user = str_contains($loginInput, '@')
            ? User::findByEmail($loginInput)
            : User::findByUsername($loginInput);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->error('Identifiants incorrects.', 401);
        }

        if ((int)$user['email_verified'] !== 1) {
            $this->error('Veuillez vérifier votre adresse e-mail avant de vous connecter.', 403);
        }

        // Generate JWT (30 days for mobile)
        $token = $this->jwt->generateToken($user, 3600 * 24 * 30);
        $stats = User::getStatistics((int)$user['id']);

        $this->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'         => (int)$user['id'],
                'username'   => $user['username'],
                'email'      => $user['email'],
                'role_id'    => (int)$user['role_id'],
                'avatar_url' => $user['avatar_url'] ?? null,
                'level'      => (int)($stats['level'] ?? 1),
                'xp'         => (int)($stats['xp'] ?? 0),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/register
    // ─────────────────────────────────────────────────────────────────────────

    public function register(): void
    {
        $body     = $this->bodyJson();
        $username = trim($body['username'] ?? '');
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            $this->error('Tous les champs sont requis.');
        }
        if (strlen($username) < 3 || strlen($username) > 50) {
            $this->error('Le nom d\'utilisateur doit faire entre 3 et 50 caractères.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Adresse e-mail invalide.');
        }
        if (strlen($password) < 8) {
            $this->error('Le mot de passe doit contenir au moins 8 caractères.');
        }
        if (User::findByUsername($username)) {
            $this->error('Ce nom d\'utilisateur est déjà pris.');
        }
        if (User::findByEmail($email)) {
            $this->error('Cette adresse e-mail est déjà enregistrée.');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        User::create([
            'username'           => $username,
            'email'              => $email,
            'password_hash'      => $hash,
            'verification_token' => null,
            'email_verified'     => 1,
        ]);

        $user  = User::findByUsername($username);
        $token = $this->jwt->generateToken($user, 3600 * 24 * 30);

        $this->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'         => (int)$user['id'],
                'username'   => $user['username'],
                'email'      => $user['email'],
                'role_id'    => (int)$user['role_id'],
                'avatar_url' => null,
                'level'      => 1,
                'xp'         => 0,
            ],
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/categories  — All categories tree (public)
    // ─────────────────────────────────────────────────────────────────────────

    public function categories(): void
    {
        $rows = Database::fetchAll(
            "SELECT c.id, c.name, c.slug, c.description, c.parent_id,
                    p.name AS parent_name
             FROM categories c
             LEFT JOIN categories p ON c.parent_id = p.id
             ORDER BY c.parent_id ASC, c.name ASC"
        );

        $this->json(['success' => true, 'categories' => $rows]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/category/{id}/quizzes  — Quizzes in a category
    // ─────────────────────────────────────────────────────────────────────────

    public function categoryQuizzes(array $params): void
    {
        $catId = (int)($params['id'] ?? 0);
        $quizzes = Quiz::getByCategory($catId);
        $this->json(['success' => true, 'quizzes' => $quizzes]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/quiz/generate  — Generate a dynamic AI solo quiz on the fly
    // Body: { "category_id": 12, "category_name": "...", "sub_category": "..." }
    // ─────────────────────────────────────────────────────────────────────────

    public function generateSoloQuiz(): void
    {
        $body = $this->bodyJson();
        $catName = trim($body['category_name'] ?? 'Culture Générale');
        $subCat  = trim($body['sub_category'] ?? '');
        $count   = (int)($body['count'] ?? 10);

        $mistral = new MistralService();
        $questions = [];

        if ($mistral->isConfigured()) {
            $questions = $mistral->generateQuestions($catName, $subCat ?: null, $count);
        }

        if (empty($questions) && !empty($body['category_id'])) {
            // Fallback to database questions if AI unavailable
            $catId = (int)$body['category_id'];
            $dbQuizzes = Quiz::getByCategory($catId);
            if (!empty($dbQuizzes)) {
                $dbQuestions = Question::getByQuiz((int)$dbQuizzes[0]['id']);
                $dbQuestions = QuestionSelectionService::deduplicateByText($dbQuestions);
                shuffle($dbQuestions);
                $questions = array_slice($dbQuestions, 0, $count);
            }
        }

        if (empty($questions)) {
            // Ultimate fallback sample questions
            $questions = [
                [
                    'id' => 1,
                    'type' => 'mcq',
                    'question_text' => "Quelle est la capitale de la France ?",
                    'explanation' => "Paris est la capitale et la plus grande ville de France.",
                    'points' => 10,
                    'answers' => [
                        ['id' => 1, 'answer_text' => 'Paris', 'is_correct' => 1],
                        ['id' => 2, 'answer_text' => 'Lyon', 'is_correct' => 0],
                        ['id' => 3, 'answer_text' => 'Marseille', 'is_correct' => 0],
                        ['id' => 4, 'answer_text' => 'Bordeaux', 'is_correct' => 0],
                    ]
                ]
            ];
        }

        $this->json([
            'success' => true,
            'title' => $subCat ? "$catName — $subCat" : $catName,
            'questions' => $questions
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/quiz/submit  — Submit solo score and update XP
    // ─────────────────────────────────────────────────────────────────────────

    public function submitSoloScore(): void
    {
        $auth = $this->authUser();
        if (!$auth) {
            $this->error('Non authentifié.', 401);
        }

        $userId = (int)$auth['id'];
        $body = $this->bodyJson();

        $quizId       = (int)($body['quiz_id'] ?? 0);
        $score        = (int)($body['score'] ?? 0);
        $correctCount = (int)($body['correct_count'] ?? 0);
        $total        = (int)($body['total_questions'] ?? 10);
        $timeSpent    = (float)($body['time_spent'] ?? 0.0);

        $xpReward = 15;
        $xpEarned = (int)($score / 10) + $xpReward;

        $stats = User::getStatistics($userId);
        $levelUp = false;
        $newLevel = 1;

        if ($stats) {
            $newTotalXp = $stats['xp'] + $xpEarned;
            $newLevel = User::calculateLevel($newTotalXp);
            $levelUp = ($newLevel > $stats['level']);

            Database::query(
                "UPDATE user_statistics 
                 SET xp = ?, level = ?, total_played = total_played + 1, correct_count = correct_count + ?
                 WHERE user_id = ?",
                [$newTotalXp, $newLevel, $correctCount, $userId]
            );
        }

        $this->json([
            'success'      => true,
            'xp_earned'    => $xpEarned,
            'level_up'     => $levelUp,
            'new_level'    => $newLevel,
            'new_total_xp' => ($stats['xp'] ?? 0) + $xpEarned
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/leaderboard  — Top players rankings
    // ─────────────────────────────────────────────────────────────────────────

    public function leaderboard(): void
    {
        $rows = Database::fetchAll(
            "SELECT u.id, u.username, u.avatar_url, s.level, s.xp, s.total_played
             FROM users u
             JOIN user_statistics s ON u.id = s.user_id
             ORDER BY s.xp DESC, s.level DESC
             LIMIT 50"
        );

        $this->json(['success' => true, 'leaderboard' => $rows]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/profile  — Authenticated user profile
    // ─────────────────────────────────────────────────────────────────────────

    public function profile(): void
    {
        $auth = $this->authUser();
        if (!$auth) {
            $this->error('Non authentifié.', 401);
        }

        $user    = User::findById((int)$auth['id']);
        $stats   = User::getStatistics((int)$auth['id']);
        $history = User::getMatchHistory((int)$auth['id']);

        if (!$user) {
            $this->error('Utilisateur introuvable.', 404);
        }

        // Achievements computation
        $allAchievements = Database::fetchAll("SELECT * FROM achievements ORDER BY id ASC");
        if (empty($allAchievements)) {
            $allAchievements = [
                ['id' => 1, 'name' => 'Premier pas', 'description' => 'Complétez votre premier quiz.', 'badge_image' => 'badge_first_quiz.png', 'criteria_type' => 'quizzes_played', 'criteria_value' => 1],
                ['id' => 2, 'name' => 'Passionné', 'description' => 'Complétez 10 quiz.', 'badge_image' => 'badge_10_quizzes.png', 'criteria_type' => 'quizzes_played', 'criteria_value' => 10],
                ['id' => 3, 'name' => 'Expert', 'description' => 'Complétez 50 quiz.', 'badge_image' => 'badge_50_quizzes.png', 'criteria_type' => 'quizzes_played', 'criteria_value' => 50],
                ['id' => 4, 'name' => 'Nouveau Niveau', 'description' => 'Atteignez le niveau 5.', 'badge_image' => 'badge_level_5.png', 'criteria_type' => 'level_reached', 'criteria_value' => 5],
                ['id' => 5, 'name' => 'Maître du Quiz', 'description' => 'Atteignez le niveau 10.', 'badge_image' => 'badge_level_10.png', 'criteria_type' => 'level_reached', 'criteria_value' => 10],
                ['id' => 6, 'name' => 'Sans Faute', 'description' => 'Obtenez un score parfait de 100% sur un quiz.', 'badge_image' => 'badge_perfect_score.png', 'criteria_type' => 'perfect_score', 'criteria_value' => 1],
            ];
        }

        $userAchievements = User::getAchievements((int)$auth['id']);
        $unlockedMap = [];
        foreach ($userAchievements as $ua) {
            $unlockedMap[(int)$ua['id']] = $ua['unlocked_at'];
        }

        // Auto unlock based on stats if not yet registered in DB
        $totalPlayed = (int)($stats['total_played'] ?? 0);
        $level = (int)($stats['level'] ?? 1);
        $correctCount = (int)($stats['correct_count'] ?? 0);

        $formattedAchievements = [];
        foreach ($allAchievements as $ach) {
            $achId = (int)$ach['id'];
            $isUnlocked = isset($unlockedMap[$achId]);

            if (!$isUnlocked) {
                if ($ach['criteria_type'] === 'quizzes_played' && $totalPlayed >= (int)$ach['criteria_value']) {
                    $isUnlocked = true;
                    Database::query("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)", [(int)$auth['id'], $achId]);
                } elseif ($ach['criteria_type'] === 'level_reached' && $level >= (int)$ach['criteria_value']) {
                    $isUnlocked = true;
                    Database::query("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)", [(int)$auth['id'], $achId]);
                }
            }

            $formattedAchievements[] = [
                'id'            => $achId,
                'name'          => $ach['name'],
                'description'   => $ach['description'],
                'badge_image'   => $ach['badge_image'] ?? 'badge_default.png',
                'criteria_type' => $ach['criteria_type'],
                'criteria_value'=> (int)$ach['criteria_value'],
                'is_unlocked'   => $isUnlocked,
                'unlocked_at'   => $unlockedMap[$achId] ?? ($isUnlocked ? date('Y-m-d H:i:s') : null),
            ];
        }

        $totalQuestionsPlayed = $totalPlayed * 10;
        $successRate = $totalPlayed > 0 ? round(($correctCount / max(1, $totalQuestionsPlayed)) * 100, 1) : 0.0;
        $avgTime = round((float)($stats['average_time_per_question'] ?? 0.0), 1);

        $this->json([
            'success' => true,
            'user' => [
                'id'            => (int)$user['id'],
                'username'      => $user['username'],
                'email'         => $user['email'],
                'avatar_url'    => $user['avatar_url'] ?? null,
                'role_id'       => (int)$user['role_id'],
                'created_at'    => $user['created_at'],
                'level'         => $level,
                'xp'            => (int)($stats['xp'] ?? 0),
                'total_played'  => $totalPlayed,
                'correct_count' => $correctCount,
                'success_rate'  => $successRate,
                'average_time'  => $avgTime,
            ],
            'stats' => [
                'level'         => $level,
                'xp'            => (int)($stats['xp'] ?? 0),
                'total_played'  => $totalPlayed,
                'correct_count' => $correctCount,
                'success_rate'  => $successRate,
                'average_time'  => $avgTime,
            ],
            'achievements' => $formattedAchievements,
            'history' => $history,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/profile/avatar  — Update user avatar
    // Body: { "avatar_url": "..." }
    // ─────────────────────────────────────────────────────────────────────────

    public function updateAvatar(): void
    {
        $auth = $this->authUser();
        if (!$auth) {
            $this->error('Non authentifié.', 401);
        }

        $userId = (int)$auth['id'];
        $body = $this->bodyJson();
        $avatarUrl = trim($body['avatar_url'] ?? '');

        if (empty($avatarUrl)) {
            $this->error('URL ou identifiant de l\'avatar requis.');
        }

        User::update($userId, ['avatar_url' => $avatarUrl]);

        $this->json([
            'success'    => true,
            'avatar_url' => $avatarUrl,
            'message'    => 'Avatar mis à jour avec succès.'
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/ws-token  — Get a fresh JWT token to connect to WebSocket
    // ─────────────────────────────────────────────────────────────────────────

    public function wsToken(): void
    {
        $auth = $this->authUser();
        if (!$auth) {
            $this->error('Non authentifié.', 401);
        }

        $user  = User::findById((int)$auth['id']);
        if (!$user) {
            $this->error('Utilisateur introuvable.', 404);
        }

        $token = $this->jwt->generateToken($user, 3600);

        $this->json(['success' => true, 'token' => $token]);
    }
}
