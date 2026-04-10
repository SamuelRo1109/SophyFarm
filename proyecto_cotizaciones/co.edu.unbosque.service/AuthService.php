<?php

namespace co\edu\unbosque\service;

use PDO;
use co\edu\unbosque\model\User;
use co\edu\unbosque\utils\Utils;

class AuthService
{
    private PDO $pdo;
    private AuditoriaService $auditoria;

    public function __construct(PDO $pdo)
    {
        $this->pdo       = $pdo;
        $this->auditoria = new AuditoriaService($pdo);
    }

    public function authenticate(string $username, string $password): array
    {
        $user = User::findByUsername($this->pdo, $username);

        if (!$user || !password_verify($password, $user->getPassword())) {
            return [
                'status'  => 'error',
                'message' => 'Credenciales incorrectas'
            ];
        }

        $token = Utils::generateJwt(
            $user->getId(),
            $user->getUsername(),
            $user->getRoleId()
        );

        // Auditoría de login
        $this->auditoria->registrar(
            $user->getId(),
            'L',
            'Inicio de sesión exitoso para el usuario ' . $user->getUsername()
        );

        return [
            'status'   => 'success',
            'message'  => 'Login exitoso',
            'token'    => $token,
            'username' => $user->getUsername(),
            'role_id'  => $user->getRoleId()
        ];
    }
}
