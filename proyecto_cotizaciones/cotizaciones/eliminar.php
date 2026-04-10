<?php
include '../conexion.php';

$idCotizacion = $_GET['id'];

// Primero eliminar los detalles
$conexion->query("DELETE FROM DetalleCotizacion WHERE idCotizacion = $idCotizacion");

// Luego eliminar la cotización
if ($conexion->query("DELETE FROM Cotizacion WHERE idCotizacion = $idCotizacion")) {
    header("Location: listar.php");
} else {
    echo "Error eliminando: " . $conexion->error;
}
?>
