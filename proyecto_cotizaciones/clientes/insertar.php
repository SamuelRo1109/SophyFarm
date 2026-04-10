<?php
include '../conexion.php';

$nombre = $_POST['nombre'];
$tipo = $_POST['tipo_cliente'];
$email = $_POST['email'];
$telefono = $_POST['telefono'];

$sql = "INSERT INTO clientes (nombre, tipo_cliente, email, telefono) 
        VALUES ('$nombre', '$tipo', '$email', '$telefono')";

if ($conexion->query($sql) === TRUE) {
    header("Location: listar.php");
    exit();
} else {
    echo "Error: " . $conexion->error;
}
?>
