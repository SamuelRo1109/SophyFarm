<?php
include '../conexion.php';

$idCliente = $_POST['id_cliente'];
$descripcion = $_POST['descripcion'];
$valor = $_POST['valor_total'];
$fecha = $_POST['fecha_cotizacion'];
$estado = $_POST['estado'];

$sql = "INSERT INTO cotizaciones (id_cliente, descripcion, valor_total, fecha_cotizacion, estado)
        VALUES ('$idCliente', '$descripcion', '$valor', '$fecha', '$estado')";

if ($conexion->query($sql) === TRUE) {
    header("Location: listar.php");
    exit();
} else {
    echo "Error al guardar: " . $conexion->error;
}
?>
