<?php
include '../conexion.php';

$idDetalle = $_POST['idDetalle'];
$idCotizacion = $_POST['idCotizacion'];
$cantidad = $_POST['cantidad'];
$precioUnitario = $_POST['precioUnitario'];

// Consultar subtotal anterior
$antiguo = $conexion->query("SELECT valorSubtotal FROM DetalleCotizacion WHERE idDetalleCotizacion = $idDetalle")->fetch_assoc();
$subtotalAnterior = $antiguo['valorSubtotal'];

// Nuevo subtotal
$nuevoSubtotal = $cantidad * $precioUnitario;

// Actualizar el detalle
$conexion->query("UPDATE DetalleCotizacion 
                  SET cantidad = '$cantidad', precioUnitario = '$precioUnitario', valorSubtotal = '$nuevoSubtotal'
                  WHERE idDetalleCotizacion = $idDetalle");

// Ajustar totales de la cotización
$conexion->query("UPDATE Cotizacion 
                  SET valorBruto = valorBruto - $subtotalAnterior + $nuevoSubtotal,
                      valorNeto = valorNeto - $subtotalAnterior + $nuevoSubtotal
                  WHERE idCotizacion = $idCotizacion");

header("Location: detalle.php?id=$idCotizacion");
?>
