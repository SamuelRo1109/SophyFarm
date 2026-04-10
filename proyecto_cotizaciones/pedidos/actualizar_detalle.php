<?php
include '../conexion.php';

$idDetalle = $_POST['idDetalle'];
$idPedido  = $_POST['idPedido'];
$cantidad  = $_POST['cantidad'];
$precio    = $_POST['precioUnitario'];

// Obtener producto asociado
$info = $conexion->query("SELECT idProducto, valorSubtotal FROM DetallePedido WHERE idDetallePedido = $idDetalle")->fetch_assoc();
$idProducto = $info['idProducto'];
$subtotalAnterior = $info['valorSubtotal'];

// Verificar stock
$stock = $conexion->query("SELECT stockActual FROM Producto WHERE idProducto = $idProducto")->fetch_assoc()['stockActual'];
if ($cantidad > $stock) {
    die("<h3>❌ Error: Stock insuficiente para actualizar. Stock disponible: $stock, solicitado: $cantidad.</h3>
         <br><a href='detalle.php?id=$idPedido'>⬅ Volver</a>");
}

// Calcular nuevo subtotal
$nuevoSubtotal = $cantidad * $precio;

// Actualizar detalle
$conexion->query("UPDATE DetallePedido 
                  SET cantidad = '$cantidad', precioUnitario = '$precio', valorSubtotal = '$nuevoSubtotal'
                  WHERE idDetallePedido = $idDetalle");

// Actualizar total del pedido
$conexion->query("UPDATE Pedido 
                  SET totalPedido = totalPedido - $subtotalAnterior + $nuevoSubtotal 
                  WHERE idPedido = $idPedido");

header("Location: detalle.php?id=$idPedido");
?>
