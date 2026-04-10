<?php
$host = "localhost";
$user = "root";  // Usuario por defecto de MySQL en XAMPP
$pass = "";      // Contraseña vacía si no la modificaste
$db = "sophyfarm";

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
} else {
    // echo "Conexión exitosa a la base de datos";
}
?>
