<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Quiz Model representing collections of questions
 */
class Quiz
{
    public static function getAll(): array
    {
        return Database::fetchAll(
            "SELECT q.*, c.name as category_name, 
             (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count 
             FROM quizzes q 
             JOIN categories c ON q.category_id = c.id 
             ORDER BY q.play_count DESC, q.created_at DESC"
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::fetch(
            "SELECT q.*, c.name as category_name, c.slug as category_slug 
             FROM quizzes q 
             JOIN categories c ON q.category_id = c.id 
             WHERE q.id = ?",
            [$id]
        );
    }

    public static function getByCategory(int $categoryId): array
    {
        return Database::fetchAll(
            "SELECT q.*, 
             (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count 
             FROM quizzes q 
             WHERE q.category_id = ? 
             ORDER BY q.title ASC",
            [$categoryId]
        );
    }

    public static function create(array $data): int
    {
        Database::query(
            "INSERT INTO quizzes (category_id, title, description, time_limit, xp_reward) VALUES (?, ?, ?, ?, ?)",
            [
                $data['category_id'],
                $data['title'],
                $data['description'] ?? null,
                $data['time_limit'] ?? 30,
                $data['xp_reward'] ?? 10
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        Database::query(
            "UPDATE quizzes SET category_id = ?, title = ?, description = ?, time_limit = ?, xp_reward = ? WHERE id = ?",
            [
                $data['category_id'],
                $data['title'],
                $data['description'] ?? null,
                $data['time_limit'] ?? 30,
                $data['xp_reward'] ?? 10,
                $id
            ]
        );
        return true;
    }

    public static function delete(int $id): bool
    {
        Database::query("DELETE FROM quizzes WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Check if a quiz is favorited by user
     */
    public static function isFavorited(int $userId, int $quizId): bool
    {
        $count = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM user_favorites WHERE user_id = ? AND quiz_id = ?",
            [$userId, $quizId]
        );
        return $count > 0;
    }

    /**
     * Add quiz to user favorites
     */
    public static function addFavorite(int $userId, int $quizId): bool
    {
        Database::query("INSERT IGNORE INTO user_favorites (user_id, quiz_id) VALUES (?, ?)", [$userId, $quizId]);
        return true;
    }

    /**
     * Remove quiz from user favorites
     */
    public static function removeFavorite(int $userId, int $quizId): bool
    {
        Database::query("DELETE FROM user_favorites WHERE user_id = ? AND quiz_id = ?", [$userId, $quizId]);
        return true;
    }

    /**
     * Get user favorite quizzes
     */
    public static function getFavorites(int $userId): array
    {
        return Database::fetchAll(
            "SELECT q.*, c.name as category_name,
             (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
             FROM user_favorites uf
             JOIN quizzes q ON uf.quiz_id = q.id
             JOIN categories c ON q.category_id = c.id
             WHERE uf.user_id = ?
             ORDER BY q.title ASC",
            [$userId]
        );
    }
}
