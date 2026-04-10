<?php
include '../conexion.php';

$idCotizacion   = $_POST['idCotizacion'];
$idCliente      = $_POST['idCliente'];
$idVendedor     = $_POST['idVendedor'];
$fecha          = $_POST['fechaCotizacion'];
$estado         = $_POST['estadoCotizacion'];

$sql = "UPDATE Cotizacion 
        SET idCliente = '$idCliente',
            idVendedor = '$idVendedor',
            fechaCotizacion = '$fecha',
            estadoCotizacion = '$estado'
        WHERE idCotizacion = $idCotizacion";

if ($conexion->query($sql)) {
    header("Location: listar.php");
} else {
    echo "Error al actualizar: " . $conexion->error;
}
?>
