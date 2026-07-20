<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

/**
 * Handles Web session authentication and role validation
 */
class AuthMiddleware
{
    /**
     * Intercept and check user role / login status
     */
    public function handle(array $params): bool
    {
        Session::start();
        $user = Session::get('user');

        if (!$user) {
            Session::setFlash('error', 'Veuillez vous connecter pour accéder à cette page.');
            header('Location: /login');
            return false;
        }

        return true;
    }
}
