<?php
class Role {
    private $id;
    private $roleName;

    // Constructor
    public function __construct($id, $roleName) {
        $this->id = $id;
        $this->roleName = $roleName;
    }

    // Métodos Getter
    public function getId() {
        return $this->id;
    }

    public function getRoleName() {
        return $this->roleName;
    }

    // Método para encontrar el rol por ID
    public static function findById($pdo, $roleId) {
        $stmt = $pdo->prepare("SELECT * FROM rol WHERE id = :roleId");
        $stmt->bindParam(":roleId", $roleId);
        $stmt->execute();
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($role) {
            return new Role($role['id'], $role['nombre_rol']);
        }
        return null;
    }
}
?>
