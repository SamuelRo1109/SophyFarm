<?php
include '../conexion.php';

$id_cotizacion = $_POST['id_cotizacion'];
$fecha = $_POST['fecha_pedido'];
$valor = $_POST['valor_total'];
$estado = $_POST['estado'];

$sql = "INSERT INTO pedidos (id_cotizacion, fecha_pedido, valor_total, estado)
        VALUES ('$id_cotizacion', '$fecha', '$valor', '$estado')";

if ($conexion->query($sql)) {
    header("Location: listar.php");
    exit();
} else {
    echo "Error al guardar: " . $conexion->error;
}
?>
