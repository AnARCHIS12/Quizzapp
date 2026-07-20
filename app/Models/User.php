<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * User Model wrapping database interactions for players and admins
 */
class User
{
    /**
     * Find user by ID
     */
    public static function findById(int $id): ?array
    {
        return Database::fetch("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$id]);
    }

    /**
     * Find user by Email
     */
    public static function findByEmail(string $email): ?array
    {
        return Database::fetch("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?", [$email]);
    }

    /**
     * Find user by Username
     */
    public static function findByUsername(string $username): ?array
    {
        return Database::fetch("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?", [$username]);
    }

    /**
     * Find user by Verification Token
     */
    public static function findByVerificationToken(string $token): ?array
    {
        return Database::fetch("SELECT * FROM users WHERE verification_token = ?", [$token]);
    }

    /**
     * Find user by Reset Password Token
     */
    public static function findByResetToken(string $token): ?array
    {
        return Database::fetch(
            "SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()",
            [$token]
        );
    }

    /**
     * Create a new user
     */
    public static function create(array $data): int
    {
        Database::query(
            "INSERT INTO users (username, email, password_hash, role_id, verification_token, email_verified) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['username'],
                $data['email'],
                $data['password_hash'],
                $data['role_id'] ?? 2, // default: user
                $data['verification_token'] ?? null,
                $data['email_verified'] ?? 0
            ]
        );

        $userId = (int) Database::lastInsertId();

        // Initialize user statistics
        Database::query(
            "INSERT INTO user_statistics (user_id, level, xp, total_played, correct_count, time_spent) VALUES (?, 1, 0, 0, 0, 0)",
            [$userId]
        );

        return $userId;
    }

    /**
     * Update user details
     */
    public static function update(int $id, array $data): bool
    {
        // Whitelist allowed columns for safety
        $allowedColumns = [
            'username',
            'email',
            'password_hash',
            'avatar_url',
            'role_id',
            'email_verified',
            'verification_token',
            'two_factor_secret',
            'two_factor_enabled',
            'reset_token',
            'reset_token_expires'
        ];

        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedColumns, true)) {
                $fields[] = "`{$key}` = ?";
                $params[] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        Database::query($sql, $params);
        return true;
    }

    /**
     * Delete user account and associated statistics
     */
    public static function delete(int $id): bool
    {
        Database::query("DELETE FROM users WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Get user statistics
     */
    public static function getStatistics(int $id): ?array
    {
        return Database::fetch("SELECT * FROM user_statistics WHERE user_id = ?", [$id]);
    }

    /**
     * Get user unlocked achievements
     */
    public static function getAchievements(int $id): array
    {
        return Database::fetchAll(
            "SELECT a.*, ua.unlocked_at FROM user_achievements ua 
             JOIN achievements a ON ua.achievement_id = a.id 
             WHERE ua.user_id = ? ORDER BY ua.unlocked_at DESC",
            [$id]
        );
    }

    /**
     * Get user match history
     */
    public static function getMatchHistory(int $id): array
    {
        return Database::fetchAll(
            "SELECT m.room_code, m.created_at, q.title as quiz_title, mp.score, 
             (SELECT COUNT(*) FROM match_players WHERE match_id = m.id) as total_players,
             (SELECT username FROM users WHERE id = (
                 SELECT user_id FROM match_players WHERE match_id = m.id ORDER BY score DESC LIMIT 1
             )) as winner_name
             FROM match_players mp
             JOIN matches m ON mp.match_id = m.id
             JOIN quizzes q ON m.quiz_id = q.id
             WHERE mp.user_id = ? AND m.status = 'finished'
             ORDER BY m.created_at DESC LIMIT 10",
            [$id]
        );
    }

    /**
     * Fetch all users with roles (for administration)
     */
    public static function getAll(): array
    {
        return Database::fetchAll(
            "SELECT u.*, r.name as role_name FROM users u 
             JOIN roles r ON u.role_id = r.id 
             ORDER BY u.created_at DESC"
        );
    }
}
