<?php
define('DB_HOST', '192.168.10.24');
define('DB_NAME', 'sophyfarm');
define('DB_USER', 'postgres');
define('DB_PASS', '1234');

try {
    $pdo = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit;
}
?>
