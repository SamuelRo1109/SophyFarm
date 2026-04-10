<?php
// Incluir el archivo de configuración para la base de datos
require_once __DIR__ . '/DbConfig.php';

// Probar la conexión a la base de datos
try {
    // Intentamos ejecutar una simple consulta SQL para verificar la conexión
    $query = "SELECT 1";  // Solo para verificar la conexión
    $stmt = $pdo->query($query);
    
    if ($stmt) {
        echo "¡Conexión exitosa a la base de datos!";
    }
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>
