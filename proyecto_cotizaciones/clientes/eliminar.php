<?php
include '../conexion.php';

$id = $_GET['id'];

$sql = "DELETE FROM Cliente WHERE idCliente = $id";

if ($conexion->query($sql) === TRUE) {
    header("Location: listar.php");
} else {
    echo "Error: " . $conexion->error;
}
?>
