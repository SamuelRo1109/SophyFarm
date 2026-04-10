<?php
class User {
    private $id;
    private $username;
    private $password;
    private $role_id;

    // Constructor
    public function __construct($id, $username, $password, $role_id) {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->role_id = $role_id;
    }

    // Getters
    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getRoleId() {
        return $this->role_id;
    }

    // Buscar usuario por username (uniendo usuario con usuario_rol)
    public static function findByUsername($pdo, $username) {
        $sql = "
            SELECT u.id,
                   u.username_usrio,
                   u.clave_usrio,
                   ur.rol_id
            FROM usuario u
            LEFT JOIN usuario_rol ur ON u.id = ur.usuario_id
            WHERE u.username_usrio = :username
              AND u.estado_usrio = 'A'
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return new User(
                $user['id'],
                $user['username_usrio'],
                $user['clave_usrio'],
                $user['rol_id'] ?? null
            );
        }

        return null;
    }
}
?>
