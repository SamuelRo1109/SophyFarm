<?php
require_once __DIR__ . '/../co.edu.unbosque.service/AuthService.php';

class AuthController {
    public function login(array $data) {
        header('Content-Type: application/json');

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (!$username || !$password) {
            echo json_encode(['message' => 'Faltan credenciales']);
            return;
        }

        $authService = new AuthService();
        $user = $authService->login($username, $password);

        if ($user) {
            echo json_encode([
                'message' => 'Login exitoso',
                'user'    => $user
            ]);
        } else {
            echo json_encode(['message' => 'Credenciales incorrectas']);
        }
    }
}
?>
