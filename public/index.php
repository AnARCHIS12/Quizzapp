<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Controllers\QuizController;
use App\Controllers\AuthController;
use App\Controllers\DuelController;
use App\Controllers\AdminController;

// 1. Boot up Autoloader
require dirname(__DIR__) . '/vendor/autoload.php';

// Turn off error display in browser (Production security hardening)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Send HTTP Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https://api.dicebear.com https://api.qrserver.com https://images.unsplash.com; media-src 'self' data:; connect-src 'self' ws: wss:; frame-ancestors 'none';");

// 2. Start Secure Sessions
Session::start();

// 3. Initialize DI Container and Services
$container = new Container();

// 4. Instantiate Router
$router = new Router($container);

// 5. DEFINE ROUTING MAPS

// A. Public / Quiz Navigation Routes
$router->get('/', [QuizController::class, 'index']);
$router->get('/category/{slug}', [QuizController::class, 'showCategory']);
$router->get('/quiz/{id}', [QuizController::class, 'play']);
$router->get('/quiz/dynamic/{id}', [QuizController::class, 'playDynamic']);
$router->post('/api/quiz/submit', [QuizController::class, 'submitScore'], [CSRFMiddleware::class]);
$router->post('/api/quiz/favorite', [QuizController::class, 'toggleFavorite'], [CSRFMiddleware::class]);

// B. Authentication Routes
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register'], [CSRFMiddleware::class]);
$router->get('/verify-email', [AuthController::class, 'verifyEmail']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'], [CSRFMiddleware::class]);
$router->get('/login/2fa', [AuthController::class, 'show2FA']);
$router->post('/login/2fa', [AuthController::class, 'verify2FA'], [CSRFMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class, CSRFMiddleware::class]);

$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword'], [CSRFMiddleware::class]);
$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], [CSRFMiddleware::class]);

// C. User Profile and Settings (Auth Protected)
$router->get('/dashboard', [AuthController::class, 'dashboard'], [AuthMiddleware::class]);
$router->get('/settings', [AuthController::class, 'showSettings'], [AuthMiddleware::class]);
$router->post('/settings/profile', [AuthController::class, 'updateProfile'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->post('/settings/2fa/generate', [AuthController::class, 'generate2FA'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->post('/settings/2fa/enable', [AuthController::class, 'enable2FA'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->post('/settings/2fa/disable', [AuthController::class, 'disable2FA'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->post('/settings/profile/delete', [AuthController::class, 'deleteAccount'], [AuthMiddleware::class, CSRFMiddleware::class]);

// D. Live Duel Routes (Auth Protected)
$router->get('/duel', [DuelController::class, 'index'], [AuthMiddleware::class]);
$router->get('/duel/{code}', [DuelController::class, 'playRoom'], [AuthMiddleware::class]);

// E. Admin Controls (Admin Protected)
$router->get('/admin', [AdminController::class, 'dashboard'], [AdminMiddleware::class]);
$router->get('/admin/users', [AdminController::class, 'listUsers'], [AdminMiddleware::class]);
$router->post('/admin/users/role', [AdminController::class, 'updateUserRole'], [AdminMiddleware::class, CSRFMiddleware::class]);
$router->post('/admin/backup', [AdminController::class, 'backupDatabase'], [AdminMiddleware::class, CSRFMiddleware::class]);
$router->post('/admin/export', [AdminController::class, 'exportJSON'], [AdminMiddleware::class, CSRFMiddleware::class]);
$router->post('/admin/import', [AdminController::class, 'importJSON'], [AdminMiddleware::class, CSRFMiddleware::class]);

// 6. Dispatch router Request
try {
    $router->dispatch();
} catch (Exception $e) {
    http_response_code(500);
    // Log exception details securely on the server
    error_log("Internal Server Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo "<h1>500 - Erreur Interne du Serveur</h1><p>Une erreur inattendue est survenue. Veuillez contacter l'administrateur.</p>";
}
