<?php
require_once __DIR__ . '/../co.edu.unbosque.config/DbConfig.php';
require_once __DIR__ . '/../co.edu.unbosque.model/User.php';
require_once __DIR__ . '/../co.edu.unbosque.model/Role.php';

class AuthService {
    public function login($username, $password) {
        // Conexión PDO
        $pdo = DbConfig::getConnection();

        // 1. Buscar usuario por username
        $user = User::findByUsername($pdo, $username);

        if (!$user) {
            // Usuario no encontrado
            return null;
        }

        // 2. Comparar contraseña en TEXTO PLANO (si la contraseña está en texto plano)
        if ($password !== $user->getPassword()) {
            return null;
        }

        // 3. Buscar rol del usuario
        $roleName = null;
        if ($user->getRoleId() !== null) {
            $role = Role::findById($pdo, $user->getRoleId());
            $roleName = $role ? $role->getRoleName() : null;
        }

        // Guardar el rol del usuario en la sesión
        session_start();
        $_SESSION['role'] = $roleName;

        // 4. Retornar la info que usará el frontend
        return [
            'id'       => $user->getId(),
            'username' => $user->getUsername(),
            'role'     => $roleName
        ];
    }
}
?>
