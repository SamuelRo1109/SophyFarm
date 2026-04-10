<?php

namespace co\edu\unbosque\controller;

use co\edu\unbosque\model\User;
use co\edu\unbosque\utils\Utils;
use co\edu\unbosque\service\AuditoriaService;

class AuthController
{
    public function login(array $vars)
    {
        global $pdo;

        // Siempre responderemos JSON
        header('Content-Type: application/json; charset=utf-8');

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Faltan datos de login'
            ]);
            return;
        }

        $user = User::findByUsername($pdo, $username);

        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Usuario no encontrado o inactivo'
            ]);
            return;
        }

        // Contraseña en BD está hasheada -> usamos password_verify
        if (!password_verify($password, $user->getPassword())) {
            // (Opcional) podrías auditar intentos fallidos aquí también
            http_response_code(401);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Contraseña incorrecta'
            ]);
            return;
        }

        // Generar JWT
        $token = Utils::generateJwt(
            $user->getId(),
            $user->getUsername(),
            $user->getRoleId()
        );

        // Registrar auditoría de inicio de sesión exitoso
        try {
            $auditoria = new AuditoriaService($pdo);
            $auditoria->registrar(
                $user->getId(),
                'L', // L de "Login" (puedes dejarlo así, la columna no está limitada a I/U/D)
                'Inicio de sesión exitoso para el usuario ' . $user->getUsername()
            );
        } catch (\Throwable $e) {
            // No rompemos el login si falla la auditoría
            error_log('Error registrando auditoría de login: ' . $e->getMessage());
        }

        http_response_code(200);
        echo json_encode([
            'status'   => 'success',
            'message'  => 'Login exitoso',
            'token'    => $token,
            'username' => $user->getUsername(),
            'role_id'  => $user->getRoleId()
        ]);
    }
}
