<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Match Model for representing duel lobby sessions
 */
class Match
{
    public static function findByCode(string $code): ?array
    {
        return Database::fetch("SELECT m.*, q.title as quiz_title FROM matches m JOIN quizzes q ON m.quiz_id = q.id WHERE m.room_code = ?", [$code]);
    }

    public static function getPlayers(int $matchId): array
    {
        return Database::fetchAll(
            "SELECT mp.*, u.username, u.avatar_url FROM match_players mp 
             JOIN users u ON mp.user_id = u.id 
             WHERE mp.match_id = ? ORDER BY mp.score DESC",
            [$matchId]
        );
    }

    public static function getActiveMatchesCount(): int
    {
        return (int) Database::fetchColumn("SELECT COUNT(*) FROM matches WHERE status = 'playing'");
    }
}
