<?php

namespace co\edu\unbosque\utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Utils
{
    private static string $secretKey = 'mi_clave_super_secreta_123';

    public static function generateJwt(int $userId, string $username, ?int $roleId): string
    {
        $issuedAt  = time();
        $expiresAt = $issuedAt + 10800; // 1 hora

        $payload = [
            'iat'      => $issuedAt,
            'exp'      => $expiresAt,
            'sub'      => $userId,
            'username' => $username,
            'role_id'  => $roleId,
        ];

        return JWT::encode($payload, self::$secretKey, 'HS256');
    }

    public static function validateJwt(string $jwt)
    {
        return JWT::decode($jwt, new Key(self::$secretKey, 'HS256'));
    }
}
