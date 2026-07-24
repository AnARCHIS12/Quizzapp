<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use Exception;

/**
 * Core Database Wrapper using PDO
 */
class Database
{
    private const BULK_QUIZ_ID_MIN     = 1000;
    private const BULK_QUESTION_ID_MIN = 10000;
    private const BULK_SEED_HASH_KEY   = 'bulk_seed_hash';

    private static ?PDO $pdo = null;

    /**
     * Get the PDO Connection instance (Singleton)
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $configPath = dirname(__DIR__, 2) . '/config/database.php';
            if (!file_exists($configPath)) {
                throw new Exception("Database configuration file not found at: {$configPath}");
            }
            
            $config = require $configPath;

            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            try {
                self::$pdo = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
                self::ensureDatabaseSeeded(self::$pdo);
            } catch (PDOException $e) {
                error_log("Database connection failure: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                throw new Exception("Database connection failure.");
            }
        }

        return self::$pdo;
    }

    private static bool $seedChecked = false;

    /**
     * Automatically ensure database tables and clean UTF-8 seed data exist
     */
    private static function ensureDatabaseSeeded(PDO $pdo): void
    {
        if (self::$seedChecked) {
            return;
        }
        self::$seedChecked = true;

        try {
            // Always run these schema migrations regardless of seeding state
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `user_question_history` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `question_id` INT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY `uk_user_question` (`user_id`, `question_id`),
                    INDEX `idx_uqh_user` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            // Add match_room_code column if it doesn't exist
            try {
                $exists = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'questions' AND COLUMN_NAME = 'match_room_code'");
                if ($exists && (int)$exists->fetchColumn() === 0) {
                    $pdo->exec("ALTER TABLE `questions` ADD COLUMN `match_room_code` VARCHAR(10) DEFAULT NULL, ADD INDEX `idx_questions_room` (`match_room_code`)");
                }
            } catch (Exception $e) {}

            $stmtCat = $pdo->query("SELECT COUNT(*) FROM categories");
            $catCount = $stmtCat ? (int)$stmtCat->fetchColumn() : 0;

            $stmtUser = $pdo->query("SELECT password_hash FROM users WHERE id = 1");
            $rowUser = $stmtUser ? $stmtUser->fetch() : false;
            $validAdmin = $rowUser && password_verify('admin123', (string)($rowUser['password_hash'] ?? ''));

            $stmtQ = $pdo->query("SELECT COUNT(*) FROM questions");
            $qCount = $stmtQ ? (int)$stmtQ->fetchColumn() : 0;

            self::ensureSettingsTable($pdo);

            $baseDir = dirname(__DIR__, 2);
            $migrationFile = $baseDir . '/database/migration.sql';
            $seedFile = $baseDir . '/database/seed.sql';
            $seedBulkFile = $baseDir . '/database/seed_bulk.sql';

            // Base vide : migration + seed initial + bulk.
            $needsFullSeed = ($catCount < 21) || (($qCount === 0) && !$validAdmin);

            if ($needsFullSeed && file_exists($migrationFile) && file_exists($seedFile)) {
                $pdo->exec("SET NAMES utf8mb4");
                $pdo->exec(file_get_contents($migrationFile));
                $pdo->exec(file_get_contents($seedFile));

                if (file_exists($seedBulkFile)) {
                    $pdo->exec(file_get_contents($seedBulkFile));
                    self::storeBulkSeedHash($pdo, $seedBulkFile);
                }

                // Ensure status ENUM includes 'selecting'
                try {
                    $pdo->exec("ALTER TABLE `matches` MODIFY COLUMN `status` ENUM('waiting', 'selecting', 'playing', 'finished') DEFAULT 'waiting'");
                } catch (Exception $e) {}

                // Deduplicate answers and enforce unique index
                try {
                    $pdo->exec("DELETE a1 FROM answers a1 JOIN answers a2 ON a1.question_id = a2.question_id AND a1.answer_text = a2.answer_text AND a1.id > a2.id");
                    $pdo->exec("ALTER TABLE answers ADD UNIQUE INDEX idx_answers_unique (question_id, answer_text(191))");
                } catch (Exception $e) {}
            } elseif (file_exists($seedBulkFile)) {
                self::syncBulkSeed($pdo, $seedBulkFile);
            }
        } catch (Exception $e) {
            error_log("Auto database seeding attempt: " . $e->getMessage());
        }
    }

    private static function ensureSettingsTable(PDO $pdo): void
    {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `setting_key` VARCHAR(100) NOT NULL UNIQUE,
                `setting_value` TEXT DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Exception $e) {}
    }

    private static function getSetting(PDO $pdo, string $key): ?string
    {
        try {
            $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();

            return $value === false ? null : (string)$value;
        } catch (Exception $e) {
            return null;
        }
    }

    private static function setSetting(PDO $pdo, string $key, string $value): void
    {
        $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        )->execute([$key, $value]);
    }

    private static function storeBulkSeedHash(PDO $pdo, string $seedBulkFile): void
    {
        $hash = hash_file('sha256', $seedBulkFile);
        if ($hash !== false) {
            self::setSetting($pdo, self::BULK_SEED_HASH_KEY, $hash);
        }
    }

    private static function purgeGeneratedBulkSeed(PDO $pdo): void
    {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('DELETE FROM answers WHERE question_id >= ' . self::BULK_QUESTION_ID_MIN);
        $pdo->exec('DELETE FROM user_question_history WHERE question_id >= ' . self::BULK_QUESTION_ID_MIN);
        $pdo->exec('DELETE FROM questions WHERE id >= ' . self::BULK_QUESTION_ID_MIN);
        $pdo->exec('DELETE FROM quizzes WHERE id >= ' . self::BULK_QUIZ_ID_MIN);
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Importe ou remplace automatiquement seed_bulk.sql quand le fichier change (mise à jour app).
     */
    private static function syncBulkSeed(PDO $pdo, string $seedBulkFile): void
    {
        $hash = hash_file('sha256', $seedBulkFile);
        if ($hash === false) {
            return;
        }

        $storedHash = self::getSetting($pdo, self::BULK_SEED_HASH_KEY);
        if ($storedHash === $hash) {
            return;
        }

        $pdo->exec('SET NAMES utf8mb4');

        if ($storedHash !== null) {
            self::purgeGeneratedBulkSeed($pdo);
        } else {
            $stmt = $pdo->query('SELECT COUNT(*) FROM quizzes WHERE id >= ' . self::BULK_QUIZ_ID_MIN);
            $existingBulk = $stmt ? (int)$stmt->fetchColumn() : 0;
            if ($existingBulk > 0) {
                self::purgeGeneratedBulkSeed($pdo);
            }
        }

        $pdo->exec(file_get_contents($seedBulkFile));
        self::setSetting($pdo, self::BULK_SEED_HASH_KEY, $hash);
    }

    /**
     * Execute a query with parameters and return the statement
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $pdo = self::getConnection();
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query execution error: " . $e->getMessage() . " | SQL: " . $sql . "\n" . $e->getTraceAsString());
            throw new Exception("Erreur d'exécution de la base de données.");
        }
    }

    /**
     * Execute query and fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Execute query and fetch a single row
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result ? $result : null;
    }

    /**
     * Execute query and fetch a single column value
     */
    public static function fetchColumn(string $sql, array $params = [], int $columnNumber = 0)
    {
        return self::query($sql, $params)->fetchColumn($columnNumber);
    }

    /**
     * Get the last inserted ID
     */
    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollBack(): bool
    {
        return self::getConnection()->rollBack();
    }
}
