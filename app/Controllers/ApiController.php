<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\User;
use App\Models\Category;
use App\Services\JWTService;

/**
 * JSON REST API controller for the QuizzApp mobile application.
 * All responses are JSON. Auth via Bearer JWT token (same secret as WebSocket).
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
            'success'  => true,
            'app'      => 'QuizzApp',
            'version'  => '2.0',
            'timestamp' => date('c'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/login
    // Body: { "login": "username_or_email", "password": "..." }
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
                'id'       => (int)$user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role_id'  => (int)$user['role_id'],
                'avatar_url' => $user['avatar_url'] ?? null,
                'level'    => (int)($stats['level'] ?? 1),
                'xp'       => (int)($stats['xp'] ?? 0),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/register
    // Body: { "username": "...", "email": "...", "password": "..." }
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

        // Mobile registration: auto-verify email (no email server required)
        User::create([
            'username'           => $username,
            'email'              => $email,
            'password_hash'      => $hash,
            'verification_token' => null,
            'email_verified'     => 1,
        ]);

        // Fetch newly created user to generate token
        $user  = User::findByUsername($username);
        $token = $this->jwt->generateToken($user, 3600 * 24 * 30);

        $this->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'       => (int)$user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role_id'  => (int)$user['role_id'],
                'avatar_url' => null,
                'level'    => 1,
                'xp'       => 0,
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
    // GET /api/profile  — Authenticated user profile
    // ─────────────────────────────────────────────────────────────────────────

    public function profile(): void
    {
        $auth = $this->authUser();
        if (!$auth) {
            $this->error('Non authentifié.', 401);
        }

        $user  = User::findById((int)$auth['id']);
        $stats = User::getStatistics((int)$auth['id']);
        $history = User::getMatchHistory((int)$auth['id']);

        if (!$user) {
            $this->error('Utilisateur introuvable.', 404);
        }

        $this->json([
            'success' => true,
            'user' => [
                'id'         => (int)$user['id'],
                'username'   => $user['username'],
                'email'      => $user['email'],
                'avatar_url' => $user['avatar_url'] ?? null,
                'role_id'    => (int)$user['role_id'],
                'created_at' => $user['created_at'],
                'level'      => (int)($stats['level'] ?? 1),
                'xp'         => (int)($stats['xp'] ?? 0),
                'total_played' => (int)($stats['total_played'] ?? 0),
                'correct_count' => (int)($stats['correct_count'] ?? 0),
            ],
            'history' => $history,
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

        // Short-lived token (1 hour) for WebSocket connection
        $token = $this->jwt->generateToken($user, 3600);

        $this->json(['success' => true, 'token' => $token]);
    }
}
