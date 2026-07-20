<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Models\User;
use App\Models\Category;
use App\Models\Quiz;
use App\Models\Question;
use Exception;

/**
 * Controller managing administrative panels, database backups, and JSON imports/exports
 */
class AdminController
{
    /**
     * Show admin dashboard statistics
     */
    public function dashboard(): void
    {
        $userCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM users");
        $categoryCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM categories");
        $quizCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM quizzes");
        $questionCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM questions");
        $matchCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM matches");

        // Fetch recent user registrations
        $recentUsers = Database::fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

        // Fetch recent logs
        $recentLogs = Database::fetchAll("SELECT l.*, u.username FROM user_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 5");

        View::render('admin/dashboard', [
            'stats' => [
                'users' => $userCount,
                'categories' => $categoryCount,
                'quizzes' => $quizCount,
                'questions' => $questionCount,
                'matches' => $matchCount
            ],
            'recentUsers' => $recentUsers,
            'recentLogs' => $recentLogs,
            'csrf_token' => Session::csrfToken()
        ]);
    }

    /**
     * Users management list
     */
    public function listUsers(): void
    {
        $users = User::getAll();
        View::render('admin/users', [
            'users' => $users,
            'csrf_token' => Session::csrfToken()
        ]);
    }

    /**
     * Update user role
     */
    public function updateUserRole(): void
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $roleId = (int) ($_POST['role_id'] ?? 2); // 1 = admin, 2 = user

        if ($userId === (int) Session::get('user')['id']) {
            Session::setFlash('error', 'Vous ne pouvez pas modifier votre propre rôle.');
            header('Location: /admin/users');
            return;
        }

        User::update($userId, ['role_id' => $roleId]);
        Session::logAction((int)Session::get('user')['id'], "Mise à jour rôle utilisateur ID: {$userId} -> Rôle: {$roleId}");
        Session::setFlash('success', 'Rôle de l\'utilisateur mis à jour avec succès.');
        header('Location: /admin/users');
    }

    /**
     * Export all quizzes and questions to JSON
     */
    public function exportJSON(): void
    {
        $export = [];
        $categories = Category::getAll();

        foreach ($categories as $cat) {
            $catNode = [
                'category_name' => $cat['name'],
                'category_slug' => $cat['slug'],
                'category_description' => $cat['description'],
                'quizzes' => []
            ];

            $quizzes = Quiz::getByCategory((int)$cat['id']);
            foreach ($quizzes as $q) {
                $qNode = [
                    'title' => $q['title'],
                    'description' => $q['description'],
                    'time_limit' => (int)$q['time_limit'],
                    'xp_reward' => (int)$q['xp_reward'],
                    'questions' => []
                ];

                $questions = Question::getByQuiz((int)$q['id']);
                foreach ($questions as $qs) {
                    $qsNode = [
                        'type' => $qs['type'],
                        'question_text' => $qs['question_text'],
                        'media_url' => $qs['media_url'],
                        'points' => (int)$qs['points'],
                        'explanation' => $qs['explanation'],
                        'answers' => []
                    ];

                    foreach ($qs['answers'] as $ans) {
                        $qsNode['answers'][] = [
                            'answer_text' => $ans['answer_text'],
                            'is_correct' => (int)$ans['is_correct'],
                            'match_order' => $ans['match_order'] ? (int)$ans['match_order'] : null,
                            'association_pair' => $ans['association_pair']
                        ];
                    }

                    $qNode['questions'][] = $qsNode;
                }

                $catNode['quizzes'][] = $qNode;
            }

            $export[] = $catNode;
        }

        // Set download headers
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="quizzapp_export_' . date('Y-m-d') . '.json"');
        echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Import quizzes from JSON file
     */
    public function importJSON(): void
    {
        if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('error', 'Veuillez sélectionner un fichier JSON valide.');
            header('Location: /admin');
            return;
        }

        $jsonContent = file_get_contents($_FILES['json_file']['tmp_name']);
        $data = json_decode($jsonContent, true);

        if (!$data || !is_array($data)) {
            Session::setFlash('error', 'Structure JSON invalide ou illisible.');
            header('Location: /admin');
            return;
        }

        Database::beginTransaction();
        try {
            foreach ($data as $cat) {
                // Find or create Category
                $slug = $cat['category_slug'] ?? strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $cat['category_name']));
                $category = Category::findBySlug($slug);

                if (!$category) {
                    $catId = Category::create([
                        'name' => $cat['category_name'],
                        'slug' => $slug,
                        'description' => $cat['category_description'] ?? ''
                    ]);
                } else {
                    $catId = (int)$category['id'];
                }

                foreach ($cat['quizzes'] ?? [] as $quiz) {
                    $quizId = Quiz::create([
                        'category_id' => $catId,
                        'title' => $quiz['title'],
                        'description' => $quiz['description'] ?? '',
                        'time_limit' => $quiz['time_limit'] ?? 30,
                        'xp_reward' => $quiz['xp_reward'] ?? 10
                    ]);

                    foreach ($quiz['questions'] ?? [] as $q) {
                        Question::create([
                            'quiz_id' => $quizId,
                            'type' => $q['type'],
                            'question_text' => $q['question_text'],
                            'media_url' => $q['media_url'] ?? null,
                            'points' => $q['points'] ?? 10,
                            'explanation' => $q['explanation'] ?? '',
                            'sorting_order' => 0
                        ], $q['answers'] ?? []);
                    }
                }
            }

            Database::commit();
            Session::logAction((int)Session::get('user')['id'], "Import complet de quiz via JSON");
            Session::setFlash('success', 'Importation JSON complétée avec succès !');
        } catch (Exception $e) {
            Database::rollBack();
            Session::setFlash('error', 'Erreur d\'importation SQL : ' . $e->getMessage());
        }

        header('Location: /admin');
    }

    /**
     * Generate complete database SQL backup file
     */
    public function backupDatabase(): void
    {
        try {
            $tables = ['settings', 'notifications', 'user_logs', 'user_favorites', 'user_achievements', 'achievements', 'user_statistics', 'match_players', 'matches', 'answers', 'questions', 'quizzes', 'categories', 'users', 'roles'];
            
            $backupSql = "-- Quizzapp Database Backup\n";
            $backupSql .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";
            $backupSql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                // Get create table structure
                $row = Database::fetch("SHOW CREATE TABLE `{$table}`");
                if ($row) {
                    $backupSql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                    $backupSql .= $row['Create Table'] . ";\n\n";
                }

                // Get table contents
                $rows = Database::fetchAll("SELECT * FROM `{$table}`");
                if (!empty($rows)) {
                    $backupSql .= "INSERT INTO `{$table}` VALUES ";
                    $insertValues = [];
                    foreach ($rows as $r) {
                        $escapedValues = array_map(function($val) {
                            if ($val === null) return 'NULL';
                            return Database::getConnection()->quote((string)$val);
                        }, array_values($r));
                        $insertValues[] = "\n(" . implode(', ', $escapedValues) . ")";
                    }
                    $backupSql .= implode(', ', $insertValues) . ";\n\n";
                }
            }

            $backupSql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            // Trigger file download
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="quizzapp_backup_' . date('Y-m-d_H-i-s') . '.sql"');
            echo $backupSql;
            exit();

        } catch (Exception $e) {
            Session::setFlash('error', 'Erreur lors de la sauvegarde : ' . $e->getMessage());
            header('Location: /admin');
        }
    }
}
