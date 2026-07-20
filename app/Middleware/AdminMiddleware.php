<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

/**
 * Enforces admin authorization checks
 */
class AdminMiddleware
{
    public function handle(array $params): bool
    {
        Session::start();
        $user = Session::get('user');

        if (!$user || (int)$user['role_id'] !== 1) {
            Session::setFlash('error', 'Accès réservé aux administrateurs.');
            header('Location: /');
            return false;
        }

        return true;
    }
}
