<?php
include '../conexion.php';

$idDetalle = $_GET['id'];
$idCotizacion = $_GET['idCotizacion'];

// Obtener el subtotal eliminado
$detalle = $conexion->query("SELECT valorSubtotal FROM DetalleCotizacion WHERE idDetalleCotizacion = $idDetalle")->fetch_assoc();
$subtotal = $detalle['valorSubtotal'];

// Eliminar el detalle
$conexion->query("DELETE FROM DetalleCotizacion WHERE idDetalleCotizacion = $idDetalle");

// Restar valores en la cotización
$conexion->query("UPDATE Cotizacion SET valorBruto = valorBruto - $subtotal,
                   valorNeto = valorNeto - $subtotal
                   WHERE idCotizacion = $idCotizacion");

header("Location: detalle.php?id=$idCotizacion");
?>
