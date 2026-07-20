<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Core Session and Security Helper
 */
class Session
{
    /**
     * Start secure session
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Detect secure connection (accounting for proxy ssl offloading)
            $secure = isset($_SERVER['HTTPS']) || 
                      (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            session_start([
                'cookie_lifetime' => 0,
                'cookie_path' => '/',
                'cookie_secure' => $secure,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }

        // Enforce absolute inactivity timeout (30 minutes)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
            session_start();
        }
        $_SESSION['last_activity'] = time();

        // Regenerate session identifier every 24 hours to mitigate session hijacking
        if (!isset($_SESSION['created_at'])) {
            $_SESSION['created_at'] = time();
        } elseif (time() - $_SESSION['created_at'] > 86400) {
            session_regenerate_id(true);
            $_SESSION['created_at'] = time();
        }
    }

    /**
     * Set a session value
     */
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value
     */
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if key exists
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove key from session
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Destroy current session
     */
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Regenerate session ID (Prevent Session Fixation)
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    /**
     * Generate or fetch CSRF Token
     */
    public static function csrfToken(): string
    {
        self::start();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF Token
     */
    public static function verifyCsrf(string $token): bool
    {
        self::start();
        $stored = self::get('csrf_token');
        if (!$stored) {
            return false;
        }
        return hash_equals($stored, $token);
    }

    /**
     * Set flash message
     */
    public static function setFlash(string $type, string $message): void
    {
        self::set("flash_{$type}", $message);
    }

    /**
     * Get flash message
     */
    public static function getFlash(string $type): ?string
    {
        $message = self::get("flash_{$type}");
        self::remove("flash_{$type}");
        return $message;
    }

    /**
     * Log user activity to database
     */
    public static function logAction(?int $userId, string $action): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        Database::query(
            "INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)",
            [$userId, $action, $ip, $userAgent]
        );
    }

    /**
     * Rate limiter for actions (e.g. login)
     * Limit: $maxAttempts within last $timeframeSeconds
     */
    public static function checkRateLimit(string $action, int $maxAttempts = 5, int $timeframeSeconds = 900): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $sql = "SELECT COUNT(*) FROM user_logs 
                WHERE ip_address = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $attempts = (int) Database::fetchColumn($sql, [$ip, $action, $timeframeSeconds]);
        
        return $attempts < $maxAttempts;
    }
}
