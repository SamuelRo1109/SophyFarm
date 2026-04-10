<?php
include '../conexion.php';

$idCliente = $_POST['idCliente'];
$idVendedor = $_POST['idVendedor'];
$fecha = $_POST['fechaCotizacion'];

$sql = "INSERT INTO Cotizacion (idCliente, idVendedor, fechaCotizacion, valorBruto, valorDescuento, valorIVA, valorNeto, saldoCotizacion, estadoCotizacion)
        VALUES ('$idCliente', '$idVendedor', '$fecha', 0, 0, 0, 0, 0, 'P')";

if ($conexion->query($sql)) {
    $idGenerado = $conexion->insert_id;
    header("Location: detalle.php?id=$idGenerado");
    exit();
} else {
    echo "Error: " . $conexion->error;
}
?>
