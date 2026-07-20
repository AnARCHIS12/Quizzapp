<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

/**
 * Validates CSRF token for non-GET state-modifying requests
 */
class CSRFMiddleware
{
    public function handle(array $params): bool
    {
        Session::start();
        
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (!Session::verifyCsrf($token)) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'CSRF verification failed']);
                return false;
            }
        }

        return true;
    }
}
