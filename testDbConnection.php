<?php
// Ruta absoluta usando __DIR__
require_once __DIR__ . '/co.edu.unbosque.config/DbConfig.php';

try {
    // Verificar si la conexión se ha realizado correctamente
    $stmt = $pdo->query("SELECT version()");  // Consulta simple para verificar la conexión
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Conexión exitosa a la base de datos. Versión de PostgreSQL: " . $row['version'];
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>
