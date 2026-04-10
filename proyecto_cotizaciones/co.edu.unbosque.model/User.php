<?php

namespace co\edu\unbosque\model;

use PDO;

class User
{
    private $id;
    private $username;
    private $password;
    private $role_id;

    public function __construct($id, $username, $password, $role_id)
    {
        $this->id       = $id;
        $this->username = $username;
        $this->password = $password;
        $this->role_id  = $role_id;
    }

    public function getId()      { return $this->id; }
    public function getUsername(){ return $this->username; }
    public function getPassword(){ return $this->password; }
    public function getRoleId()  { return $this->role_id; }

    public static function findByUsername(PDO $pdo, string $username): ?User
    {
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
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new User(
            $row['id'],
            $row['username_usrio'],
            $row['clave_usrio'],
            $row['rol_id'] ?? null
        );
    }
}
