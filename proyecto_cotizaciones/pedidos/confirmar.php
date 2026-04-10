<?php
include '../conexion.php';

$idPedido = $_GET['id'];

// Verificar si el pedido ya está confirmado
$pedido = $conexion->query("SELECT estadoPedido FROM Pedido WHERE idPedido = $idPedido")->fetch_assoc();
if ($pedido['estadoPedido'] == 'CONFIRMADO') {
    die("<h3>⚠ El pedido ya está confirmado.</h3><br><a href='listar.php'>⬅ Volver</a>");
}

// Obtener detalles del pedido
$detalles = $conexion->query("
    SELECT idProducto, cantidad 
    FROM DetallePedido 
    WHERE idPedido = $idPedido
");

// Descontar stock de cada producto
while ($d = $detalles->fetch_assoc()) {
    $idProd = $d['idProducto'];
    $cant   = $d['cantidad'];

    // Consultar stock actual
    $stock = $conexion->query("SELECT stockActual FROM Producto WHERE idProducto = $idProd")->fetch_assoc()['stockActual'];

    if ($stock < $cant) {
        die("<h3>❌ Error: No hay suficiente stock para el producto ID $idProd. Stock actual: $stock, solicitado: $cant.</h3>
             <br><a href='detalle.php?id=$idPedido'>⬅ Volver</a>");
    }

    // Actualizar stock
    $conexion->query("UPDATE Producto SET stockActual = stockActual - $cant WHERE idProducto = $idProd");
}

// Cambiar estado del pedido a confirmado
$conexion->query("UPDATE Pedido SET estadoPedido = 'CONFIRMADO' WHERE idPedido = $idPedido");

header("Location: listar.php");
?>
