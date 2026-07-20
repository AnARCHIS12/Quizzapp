<?php

declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

/**
 * Service to handle JWT creation and decoding for API routes
 */
class JWTService
{
    private string $secretKey;
    private string $algorithm;

    public function __construct()
    {
        $secret = $_ENV['JWT_SECRET'] ?? '';
        if (empty($secret) || $secret === 'quizzapp_super_secret_jwt_key_2026_change_me') {
            throw new \Exception("Erreur de Configuration : JWT_SECRET est manquant ou non sécurisé.");
        }
        $this->secretKey = $secret;
        $this->algorithm = 'HS256';
    }

    /**
     * Generate a new JWT token for a user
     */
    public function generateToken(array $userData, int $expirationSeconds = 3600 * 24): string
    {
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'aud' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + $expirationSeconds,
            'user' => [
                'id' => $userData['id'],
                'username' => $userData['username'],
                'email' => $userData['email'],
                'role_id' => $userData['role_id']
            ]
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Decode and validate a JWT token
     */
    public function decodeToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded->user;
        } catch (Exception $e) {
            return null;
        }
    }
}
