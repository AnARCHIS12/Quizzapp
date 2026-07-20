<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Models\Quiz;
use App\Models\Category;
use App\Models\Match;
use App\Services\JWTService;

/**
 * Controller managing room creations, duel lobby redirections, and client configurations
 */
class DuelController
{
    private JWTService $jwt;

    public function __construct(JWTService $jwt)
    {
        $this->jwt = $jwt;
    }

    /**
     * Show lobby select panel (Create or Join a room)
     */
    public function index(): void
    {
        Session::start();
        $user = Session::get('user');

        if (!$user) {
            Session::setFlash('error', 'Veuillez vous connecter pour participer aux duels.');
            header('Location: /login');
            return;
        }

        // Get all quizzes and categories to let player select which style to challenge on
        $quizzes = Quiz::getAll();
        $categories = Category::getAll();

        View::render('duel/lobby_selection', [
            'quizzes' => $quizzes,
            'categories' => $categories,
            'csrf_token' => Session::csrfToken()
        ]);
    }

    /**
     * Render the live duel room interface
     */
    public function playRoom(array $params): void
    {
        $code = strtoupper($params['code']);

        Session::start();
        $user = Session::get('user');

        if (!$user) {
            Session::setFlash('error', 'Veuillez vous connecter pour participer aux duels.');
            header('Location: /login');
            return;
        }

        // Web socket URL configuration
        // Check if explicit URL is defined in ENV, else auto-resolve hostname and port
        $wsUrl = $_ENV['WS_URL'] ?? null;
        if (!$wsUrl) {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $domain = parse_url('http://' . $host, PHP_URL_HOST);
            $wsPort = $_ENV['WS_PORT'] ?? '8080';
            $wsUrl = "ws://{$domain}:{$wsPort}";
        }

        // Generate dynamic one-off JWT Token to authenticate the WebSocket upgrade connection
        $jwtToken = $this->jwt->generateToken([
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role_id' => $user['role_id']
        ]);

        View::render('duel/play_lobby', [
            'roomCode' => $code,
            'user' => $user,
            'wsUrl' => $wsUrl,
            'jwtToken' => $jwtToken,
            'csrf_token' => Session::csrfToken()
        ], 'main');
    }
}
