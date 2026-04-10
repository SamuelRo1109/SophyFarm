<?php
// Script temporal para hashear las contraseñas de admin y vendedor

require_once __DIR__ . '/co.edu.unbosque.config/DbConfig.php'; // aquí ya tienes $pdo

try {
    // Usuarios que quieres actualizar: username => contraseña_en_claro_actual
    $users = [
        'admin'    => 'admin123',
        'vendedor' => 'vendedor123',
    ];

    foreach ($users as $username => $plainPassword) {
        // Generar hash seguro
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

        // Actualizar en la BD
        $sql = "UPDATE usuario
                SET clave_usrio = :hash
                WHERE username_usrio = :username";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':hash'     => $hash,
            ':username' => $username,
        ]);

        echo "Password hasheado para usuario: $username<br>";
    }

    echo "<br>Listo. Ahora las contraseñas están hasheadas.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
