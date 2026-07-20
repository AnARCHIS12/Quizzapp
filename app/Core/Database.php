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
            } catch (PDOException $e) {
                error_log("Database connection failure: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                throw new Exception("Database connection failure.");
            }
        }

        return self::$pdo;
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
