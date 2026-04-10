<?php
include '../conexion.php';

$idCotizacion = $_POST['idCotizacion'];
$idProducto   = $_POST['idProducto'];
$cantidad     = $_POST['cantidad'];

// Consultar precio del producto
$producto = $conexion->query("SELECT precioBase FROM Producto WHERE idProducto = $idProducto")->fetch_assoc();
$precioUnitario = $producto['precioBase'];
$subtotal = $precioUnitario * $cantidad;

// Insertar detalle
$sql = "INSERT INTO DetalleCotizacion (idCotizacion, idProducto, cantidad, precioUnitario, valorSubtotal)
        VALUES ('$idCotizacion', '$idProducto', '$cantidad', '$precioUnitario', '$subtotal')";

if ($conexion->query($sql)) {
    // Actualizar totales del encabezado
    $conexion->query("UPDATE Cotizacion SET valorBruto = valorBruto + $subtotal, 
                       valorNeto = valorNeto + $subtotal 
                       WHERE idCotizacion = $idCotizacion");

    header("Location: detalle.php?id=$idCotizacion");
} else {
    echo "Error: " . $conexion->error;
}
?>
