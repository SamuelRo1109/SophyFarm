<?php
include '../conexion.php';

$idPedido     = $_POST['idPedido'];
$idCliente    = $_POST['idCliente'];
$idCotizacion = !empty($_POST['idCotizacion']) ? $_POST['idCotizacion'] : 'NULL';
$fechaPedido  = $_POST['fechaPedido'];
$estado       = $_POST['estadoPedido'];

$sql = "UPDATE Pedido 
        SET idCliente = '$idCliente',
            idCotizacion = $idCotizacion,
            fechaPedido = '$fechaPedido',
            estadoPedido = '$estado'
        WHERE idPedido = $idPedido";

if ($conexion->query($sql)) {
    header("Location: listar.php");
} else {
    echo "❌ Error al actualizar: " . $conexion->error;
}
?>
