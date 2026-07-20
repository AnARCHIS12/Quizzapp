<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Models\Category;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\User;
use App\Core\Database;

/**
 * Controller managing landing page, quiz selectors, single-player session flow, and grading
 */
class QuizController
{
    /**
     * Show categories landing page
     */
    public function index(): void
    {
        $categories = Category::getAll();
        
        // Load recent quizzes
        $quizzes = Quiz::getAll();

        View::render('quiz/categories', [
            'categories' => $categories,
            'quizzes' => $quizzes
        ]);
    }

    /**
     * Show quizzes in a category
     */
    public function showCategory(array $params): void
    {
        $slug = $params['slug'];
        $category = Category::findBySlug($slug);

        if (!$category) {
            header('Location: /');
            return;
        }

        $quizzes = Quiz::getByCategory((int)$category['id']);
        $subcategories = Category::getSubcategories((int)$category['id']);

        View::render('quiz/category_quizzes', [
            'category' => $category,
            'quizzes' => $quizzes,
            'subcategories' => $subcategories
        ]);
    }

    /**
     * Play a quiz (Interactive game panel)
     */
    public function play(array $params): void
    {
        $id = (int)$params['id'];
        $quiz = Quiz::findById($id);

        if (!$quiz) {
            Session::setFlash('error', 'Quiz introuvable.');
            header('Location: /');
            return;
        }

        $questions = [];
        $mistral = new \App\Services\MistralService();
        if ($mistral->isConfigured()) {
            $aiQuestions = $mistral->generateQuestions($quiz['title'], $quiz['description'], 5);
            if (!empty($aiQuestions)) {
                $questions = $aiQuestions;
            }
        }

        if (empty($questions)) {
            $questions = Question::getByQuiz($id);
        }

        if (empty($questions)) {
            Session::setFlash('error', 'Ce quiz ne contient aucune question.');
            header("Location: /category/{$quiz['category_slug']}");
            return;
        }

        Session::start();
        $userId = Session::get('user')['id'] ?? null;
        $isFavorited = $userId ? Quiz::isFavorited($userId, $id) : false;

        // Shuffle questions or answers for fresh experience, keeping ranking and association intact
        // Format for JSON/JavaScript playing
        $formattedQuestions = [];
        foreach ($questions as $q) {
            $formattedQ = [
                'id' => (int)$q['id'],
                'type' => $q['type'],
                'question_text' => $q['question_text'],
                'media_url' => $q['media_url'] ? '/assets/uploads/' . $q['media_url'] : null,
                'points' => (int)$q['points'],
                'explanation' => $q['explanation'],
                'answers' => []
            ];

            foreach ($q['answers'] as $a) {
                $formattedA = [
                    'id' => (int)$a['id'],
                    'answer_text' => $a['answer_text'],
                    'is_correct' => (int)$a['is_correct']
                ];
                if ($q['type'] === 'ranking') {
                    $formattedA['match_order'] = (int)($a['match_order'] ?? 0);
                }
                if ($q['type'] === 'association') {
                    $formattedA['association_pair'] = $a['association_pair'] ?? '';
                }
                $formattedQ['answers'][] = $formattedA;
            }

            // Shuffle MCQ answers for play
            if (in_array($q['type'], ['mcq', 'multi_choice'], true)) {
                shuffle($formattedQ['answers']);
            }

            $formattedQuestions[] = $formattedQ;
        }

        View::render('quiz/play', [
            'quiz' => $quiz,
            'questionsJson' => json_encode($formattedQuestions),
            'isFavorited' => $isFavorited,
            'csrf_token' => Session::csrfToken()
        ]);
    }

    /**
     * Play a dynamic AI-generated quiz for a category
     */
    public function playDynamic(array $params): void
    {
        $id = (int)$params['id'];
        $category = Category::findById($id);

        if (!$category) {
            Session::setFlash('error', 'Catégorie introuvable.');
            header('Location: /');
            return;
        }

        $questions = [];
        $mistral = new \App\Services\MistralService();
        $isAI = false;

        if ($mistral->isConfigured()) {
            $questions = $mistral->generateQuestions($category['name'], null, 10);
            if (!empty($questions)) {
                $isAI = true;
            }
        }

        // Fallback to random DB questions from this category if any exist
        if (empty($questions)) {
            $questions = Database::fetchAll(
                "SELECT q.* FROM questions q 
                 JOIN quizzes quiz ON q.quiz_id = quiz.id 
                 WHERE quiz.category_id = ? 
                 ORDER BY RAND() LIMIT 10", 
                [$id]
            );
            foreach ($questions as &$q) {
                $q['answers'] = Database::fetchAll("SELECT id, answer_text, is_correct, match_order, association_pair FROM answers WHERE question_id = ?", [$q['id']]);
            }
        }

        if (empty($questions)) {
            Session::setFlash('error', "Aucune question disponible. Veuillez renseigner MISTRAL_API_KEY dans votre fichier .env pour activer la génération automatique de quiz sur \"" . $category['name'] . "\".");
            header('Location: /');
            return;
        }

        $quiz = [
            'id' => 0, // dynamic ID indicates not saved in DB
            'category_id' => $category['id'],
            'category_slug' => $category['slug'],
            'title' => ($isAI ? "🤖 Quiz IA : " : "🎲 Quiz Aléatoire : ") . $category['name'],
            'description' => "Quiz généré dynamiquement sur la thématique " . $category['name'] . ".",
            'time_limit' => 20,
            'xp_reward' => 15
        ];

        Session::start();
        $formattedQuestions = [];
        foreach ($questions as $q) {
            $formattedQ = [
                'id' => (int)$q['id'],
                'type' => $q['type'],
                'question_text' => $q['question_text'],
                'media_url' => null,
                'points' => (int)($q['points'] ?? 10),
                'explanation' => $q['explanation'] ?? '',
                'answers' => []
            ];

            foreach ($q['answers'] as $a) {
                $formattedQ['answers'][] = [
                    'id' => (int)$a['id'],
                    'answer_text' => $a['answer_text'],
                    'is_correct' => (int)$a['is_correct']
                ];
            }

            shuffle($formattedQ['answers']);
            $formattedQuestions[] = $formattedQ;
        }

        View::render('quiz/play', [
            'quiz' => $quiz,
            'questionsJson' => json_encode($formattedQuestions),
            'isFavorited' => false,
            'csrf_token' => Session::csrfToken()
        ]);
    }

    /**
     * Submit single-player quiz scores
     */
    public function submitScore(): void
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Input invalide']);
            return;
        }

        Session::start();
        $user = Session::get('user');
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            return;
        }

        $userId = $user['id'];
        $quizId = (int)($input['quiz_id'] ?? 0);
        $score = (int)($input['score'] ?? 0);
        $correctCount = (int)($input['correct_count'] ?? 0);
        $totalQuestions = (int)($input['total_questions'] ?? 0);
        $timeSpent = (float)($input['time_spent'] ?? 0.0);

        $xpReward = 15;
        $quizTitle = "Quiz Dynamique";
        if ($quizId !== 0) {
            $quiz = Quiz::findById($quizId);
            if (!$quiz) {
                http_response_code(404);
                echo json_encode(['error' => 'Quiz introuvable']);
                return;
            }
            $xpReward = (int)$quiz['xp_reward'];
            $quizTitle = $quiz['title'];
            // Increment actual play statistics count
            Database::query("UPDATE quizzes SET play_count = play_count + 1 WHERE id = ?", [$quizId]);
        }

        // Calculate XP reward
        $xpEarned = (int)($score / 10) + $xpReward;

        // Load stats
        $stats = User::getStatistics($userId);
        $levelUp = false;
        $newLevel = 1;

        if ($stats) {
            $newPlayed = $stats['total_played'] + 1;
            $newCorrect = $stats['correct_count'] + $correctCount;
            $newTimeSpent = $stats['time_spent'] + (int)$timeSpent;
            $avgSpeed = $newPlayed > 0 ? ($newTimeSpent / ($newPlayed * max(1, $totalQuestions))) : 0.0;
            
            $newXp = $stats['xp'] + $xpEarned;
            $newLevel = (int) floor($newXp / 100) + 1;

            if ($newLevel > (int)$stats['level']) {
                $levelUp = true;
            }

            Database::query(
                "UPDATE user_statistics SET level = ?, xp = ?, total_played = ?, correct_count = ?, time_spent = ?, average_time_per_question = ? WHERE user_id = ?",
                [$newLevel, $newXp, $newPlayed, $newCorrect, $newTimeSpent, $avgSpeed, $userId]
            );

            // Log completion
            Session::logAction($userId, "Quiz complété: {$quizTitle} (Score: {$score}, XP gagnés: {$xpEarned})");

            // Evaluate achievements
            $this->checkAchievements($userId, $newLevel, $newPlayed, $correctCount, $totalQuestions);
        }

        echo json_encode([
            'success' => true,
            'xp_earned' => $xpEarned,
            'new_xp' => ($stats ? $stats['xp'] + $xpEarned : $xpEarned),
            'new_level' => $newLevel,
            'level_up' => $levelUp
        ]);
    }

    /**
     * Check achievement milestones
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
                    if ($correctAnswers === $totalQuestions && $totalQuestions > 0) $unlocked = true;
                    break;
            }

            if ($unlocked) {
                Database::query("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)", [$userId, $ach['id']]);
            }
        }
    }

    /**
     * Toggle Favorite via AJAX
     */
    public function toggleFavorite(): void
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $quizId = (int)($input['quiz_id'] ?? 0);

        Session::start();
        $user = Session::get('user');
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            return;
        }

        $userId = $user['id'];
        $isFavorited = Quiz::isFavorited($userId, $quizId);

        if ($isFavorited) {
            Quiz::removeFavorite($userId, $quizId);
            $status = 'removed';
        } else {
            Quiz::addFavorite($userId, $quizId);
            $status = 'added';
        }

        echo json_encode(['success' => true, 'status' => $status]);
    }
}
