<?php
include '../conexion.php';

$idPedido = $_GET['id'];

// Primero eliminar los detalles
$conexion->query("DELETE FROM DetallePedido WHERE idPedido = $idPedido");

// Luego eliminar el pedido
if ($conexion->query("DELETE FROM Pedido WHERE idPedido = $idPedido")) {
    header("Location: listar.php");
} else {
    echo "❌ Error eliminando pedido: " . $conexion->error;
}
?>
