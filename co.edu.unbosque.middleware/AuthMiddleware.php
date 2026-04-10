<?php

namespace co\edu\unbosque\middleware;

use co\edu\unbosque\utils\Utils;
use Exception;

class AuthMiddleware
{
    /**
     * Cache interno del payload del JWT ya validado
     * para no estar decodificando/validando en cada llamada.
     */
    private static ?array $payloadCache = null;

    /**
     * Valida el JWT del header Authorization: Bearer xxx
     * Si falla -> responde 401 y sale.
     * Si OK   -> devuelve el payload decodificado como array.
     */
    public static function validateJwtOrDie(): array
    {
        // Si ya lo validamos antes en esta petición, reutilizamos
        if (self::$payloadCache !== null) {
            return self::$payloadCache;
        }

        header('Content-Type: application/json; charset=utf-8');

        // getallheaders en Apache; en otros entornos habría que adaptarlo
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Token no proporcionado'
            ]);
            exit;
        }

        $token = substr($authHeader, 7);

        try {
            $payload = Utils::validateJwt($token);

            // Aseguramos que sea array (por si viene como stdClass)
            if (is_object($payload)) {
                $payload = (array)$payload;
            }

            self::$payloadCache = $payload;

            return $payload;
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Token inválido o expirado'
            ]);
            exit;
        }
    }

    /**
     * Devuelve todo el payload del token ya validado.
     * Útil si quieres leer otros campos (roleId, username, etc.).
     */
    public static function getPayloadFromToken(): array
    {
        return self::validateJwtOrDie();
    }

    /**
     * Devuelve el id de usuario que viene en el JWT.
     * Ajusta la clave ('userId', 'id', 'sub', etc.) según tu Utils::createJwt(...)
     */
    public static function getUserIdFromToken(): int
    {
        $payload = self::validateJwtOrDie();

        // 🔁 Ajusta aquí según cómo estés firmando el token
        if (isset($payload['userId'])) {
            return (int)$payload['userId'];
        }

        if (isset($payload['id'])) {
            return (int)$payload['id'];
        }

        if (isset($payload['sub'])) {
            return (int)$payload['sub'];
        }

        throw new Exception('El token JWT no contiene el id de usuario.');
    }

    /**
     * (Opcional) Si quieres leer el rol desde el token
     */
    public static function getRoleIdFromToken(): ?int
    {
        $payload = self::validateJwtOrDie();

        if (isset($payload['roleId'])) {
            return (int)$payload['roleId'];
        }

        return null;
    }
}
