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

    /**
     * Automatically ensure database tables and clean UTF-8 seed data exist
     */
    private static function ensureDatabaseSeeded(PDO $pdo): void
    {
        try {
            $stmt = $pdo->query("SELECT name FROM categories WHERE id = 1");
            $row = $stmt ? $stmt->fetch() : false;
            $needsSeeding = !$row || (isset($row['name']) && str_contains((string)$row['name'], 'Ã'));

            if ($needsSeeding) {
                $baseDir = dirname(__DIR__, 2);
                $migrationFile = $baseDir . '/database/migration.sql';
                $seedFile = $baseDir . '/database/seed.sql';

                if (file_exists($migrationFile) && file_exists($seedFile)) {
                    $pdo->exec("SET NAMES utf8mb4");
                    $migrationSql = file_get_contents($migrationFile);
                    $seedSql = file_get_contents($seedFile);

                    // Execute migration & seed
                    $pdo->exec($migrationSql);
                    $pdo->exec($seedSql);
                }
            }
        } catch (Exception $e) {
            error_log("Auto database seeding attempt: " . $e->getMessage());
        }
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
