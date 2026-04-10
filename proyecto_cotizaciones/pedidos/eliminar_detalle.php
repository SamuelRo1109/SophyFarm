<?php
include '../conexion.php';

$idDetalle = $_GET['id'];
$idPedido  = $_GET['idPedido'];

// Obtener subtotal eliminado
$detalle = $conexion->query("SELECT valorSubtotal FROM DetallePedido WHERE idDetallePedido = $idDetalle")->fetch_assoc();
$subtotal = $detalle['valorSubtotal'];

// Eliminar detalle
$conexion->query("DELETE FROM DetallePedido WHERE idDetallePedido = $idDetalle");

// Restar del total del pedido
$conexion->query("UPDATE Pedido SET totalPedido = totalPedido - $subtotal WHERE idPedido = $idPedido");

header("Location: detalle.php?id=$idPedido");
?>
