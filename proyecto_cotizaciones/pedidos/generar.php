<?php
include '../conexion.php';

// 1. Recibir ID de la cotización
$idCotizacion = $_GET['idCotizacion'];

// 2. Consultar datos de la cotización (cliente + total)
$sql = "SELECT idCliente, valorNeto 
        FROM Cotizacion 
        WHERE idCotizacion = $idCotizacion AND estadoCotizacion = 'A'";

$result = $conexion->query($sql);

if ($result->num_rows == 0) {
    die("❌ Error: La cotización no existe o no está aprobada.");
}

$cotizacion = $result->fetch_assoc();
$idCliente = $cotizacion['idCliente'];
$totalInicial = $cotizacion['valorNeto'];

// 3. Insertar el pedido con estado Pendiente
$sqlInsert = "INSERT INTO Pedido (idCliente, idCotizacion, fechaPedido, estadoPedido, totalPedido)
              VALUES ('$idCliente', '$idCotizacion', NOW(), 'PENDIENTE', '$totalInicial')";

if ($conexion->query($sqlInsert)) {
    $idPedido = $conexion->insert_id;
    // 4. Redireccionar a detalle del pedido
    header("Location: detalle.php?id=$idPedido");
} else {
    echo "❌ Error al generar pedido: " . $conexion->error;
}
?>
