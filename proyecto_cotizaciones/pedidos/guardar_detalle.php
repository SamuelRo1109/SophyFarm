<?php
include '../conexion.php';

$idPedido   = $_POST['idPedido'];
$idProducto = $_POST['idProducto'];
$cantidad   = $_POST['cantidad'];

// Validar stock
$producto = $conexion->query("SELECT precioBase, stockActual FROM Producto WHERE idProducto = $idProducto")->fetch_assoc();

if ($cantidad > $producto['stockActual']) {
    die("<h3>❌ Error: No hay stock suficiente para este producto. Stock disponible: {$producto['stockActual']}, solicitado: $cantidad.</h3>
         <br><a href='detalle.php?id=$idPedido'>⬅ Volver</a>");
}

$precioUnitario = $producto['precioBase'];
$subtotal = $cantidad * $precioUnitario;

// Insertar detalle
$sql = "INSERT INTO DetallePedido (idPedido, idProducto, cantidad, precioUnitario, valorSubtotal)
        VALUES ('$idPedido', '$idProducto', '$cantidad', '$precioUnitario', '$subtotal')";

if ($conexion->query($sql)) {
    $conexion->query("UPDATE Pedido SET totalPedido = totalPedido + $subtotal WHERE idPedido = $idPedido");
    header("Location: detalle.php?id=$idPedido");
} else {
    echo "❌ Error al agregar producto: " . $conexion->error;
}
?>
