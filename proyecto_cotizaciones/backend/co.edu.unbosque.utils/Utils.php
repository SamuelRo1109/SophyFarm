<?php
use \Firebase\JWT\JWT;

class Utils {

    private static $secret_key = "MI_CLAVE_SECRETA";

    public static function verifyPassword($inputPassword, $storedPassword) {
        return password_verify($inputPassword, $storedPassword);
    }

    public static function generateJWT($userId) {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600;  // 1 hora de validez
        $payload = array(
            "iat" => $issuedAt,
            "exp" => $expirationTime,
            "userId" => $userId
        );

        return JWT::encode($payload, self::$secret_key);
    }

    public static function decodeJWT($token) {
        try {
            return JWT::decode($token, self::$secret_key, array('HS256'));
        } catch (Exception $e) {
            return null;
        }
    }
}
?>
