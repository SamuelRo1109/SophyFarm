<?php
include '../conexion.php';

// Datos enviados desde agregar.php
$idCliente = $_POST['idCliente'];
$idCotizacion = !empty($_POST['idCotizacion']) ? $_POST['idCotizacion'] : 'NULL';
$fechaPedido = $_POST['fechaPedido'];
$estadoPedido = $_POST['estadoPedido'];

// 🚫 Eliminamos la validación estricta que exigía cotización aprobada
// ✅ Si viene de cotización, tomamos total desde la cotización
if ($idCotizacion != 'NULL') {
    $consultaCot = $conexion->query("SELECT valorNeto, estadoCotizacion FROM Cotizacion WHERE idCotizacion = $idCotizacion");
    
    if ($consultaCot->num_rows == 0) {
        die("<h3>❌ Error: La cotización seleccionada no existe.<br><a href='listar.php'>⬅ Volver</a></h3>");
    }

    $cot = $consultaCot->fetch_assoc();

    // Validamos que esté aprobada
    if ($cot['estadoCotizacion'] != 'A') {
        die("<h3>⚠ Error: Solo se pueden generar pedidos a partir de cotizaciones aprobadas.<br><a href='listar.php'>⬅ Volver</a></h3>");
    }

    $total = $cot['valorNeto'];
} else {
    // ✅ Caso pedido manual (sin cotización)
    $total = 0; // se calculará luego con los productos en detalle
}

// Insertar el pedido
$sql = "INSERT INTO Pedido (idCliente, idCotizacion, fechaPedido, estadoPedido, totalPedido)
        VALUES ('$idCliente', $idCotizacion, '$fechaPedido', '$estadoPedido', '$total')";

if ($conexion->query($sql)) {
    $idPedido = $conexion->insert_id;
    header("Location: detalle.php?id=$idPedido");
} else {
    echo "❌ Error al guardar pedido: " . $conexion->error;
}
?>
