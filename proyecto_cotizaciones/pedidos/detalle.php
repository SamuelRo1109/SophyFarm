<?php
include '../conexion.php';

$idPedido = $_GET['id'];

// Obtener datos del pedido
$sql = "SELECT p.*, 
               CONCAT(c.primerNombre, ' ', c.primerApellido, ' ', c.razonSocial) AS cliente,
               co.idCotizacion
        FROM Pedido p
        INNER JOIN Cliente c ON p.idCliente = c.idCliente
        LEFT JOIN Cotizacion co ON p.idCotizacion = co.idCotizacion
        WHERE p.idPedido = $idPedido";

$pedido = $conexion->query($sql)->fetch_assoc();

// Obtener los productos del pedido
$detalles = $conexion->query("
    SELECT dp.idDetallePedido, dp.idProducto, pr.nombre, dp.cantidad, dp.precioUnitario, dp.valorSubtotal
    FROM DetallePedido dp
    INNER JOIN Producto pr ON dp.idProducto = pr.idProducto
    WHERE dp.idPedido = $idPedido
");

// Calcular total (por si no está actualizado)
$total = 0;
while ($row = $detalles->fetch_assoc()) {
    $total += $row['valorSubtotal'];
    $detalleRows[] = $row;
}
// Actualizar total en BD por consistencia
$conexion->query("UPDATE Pedido SET totalPedido = $total WHERE idPedido = $idPedido");

// Obtener el estado final
$pedido = $conexion->query($sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Pedido</title>
</head>
<body>
<h2>📋 Detalle del Pedido #<?= $pedido['idPedido']; ?></h2>

<p><strong>Cliente:</strong> <?= $pedido['cliente']; ?></p>
<p><strong>Cotización Origen:</strong> <?= $pedido['idCotizacion'] ? $pedido['idCotizacion'] : '—'; ?></p>
<p><strong>Fecha:</strong> <?= $pedido['fechaPedido']; ?></p>
<p><strong>Estado:</strong> <?= $pedido['estadoPedido']; ?></p>

<hr>

<h3>🧾 Productos del pedido</h3>

<table border="1" cellpadding="5">
    <tr>
        <th>Producto</th>
        <th>Cantidad</th>
        <th>Precio Unitario</th>
        <th>Subtotal</th>
        <th>Acciones</th>
    </tr>

    <?php if (!empty($detalleRows)) { ?>
        <?php foreach ($detalleRows as $d) { ?>
        <tr>
            <td><?= $d['nombre']; ?></td>
            <td><?= $d['cantidad']; ?></td>
            <td>$<?= number_format($d['precioUnitario'], 2); ?></td>
            <td>$<?= number_format($d['valorSubtotal'], 2); ?></td>
            <td>
                <?php if ($pedido['estadoPedido'] !== 'CONFIRMADO') { ?>
                    <a href="editar_detalle.php?idDetalle=<?= $d['idDetallePedido']; ?>&idPedido=<?= $idPedido; ?>">✏ Editar</a> |
                    <a href="eliminar_detalle.php?idDetalle=<?= $d['idDetallePedido']; ?>&idPedido=<?= $idPedido; ?>" onclick="return confirm('¿Eliminar producto del pedido?')">🗑 Eliminar</a>
                <?php } else { ?>
                    —
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    <?php } else { ?>
        <tr><td colspan="5">No hay productos agregados a este pedido.</td></tr>
    <?php } ?>
</table>

<br>
<p><strong>Total del Pedido:</strong> $<?= number_format($total, 2); ?></p>

<hr>

<!-- BOTONES DE ACCIÓN -->
<?php if ($pedido['estadoPedido'] !== 'CONFIRMADO' && $pedido['estadoPedido'] !== 'CANCELADO') { ?>
    <a href="agregar_detalle.php?id=<?= $idPedido; ?>">➕ Agregar Producto</a> |
    <a href="confirmar.php?id=<?= $idPedido; ?>" onclick="return confirm('¿Confirmar pedido y descontar stock?')">✅ Confirmar Pedido</a>
<?php } elseif ($pedido['estadoPedido'] === 'CONFIRMADO') { ?>
    <a href="cancelar.php?id=<?= $idPedido; ?>" onclick="return confirm('¿Cancelar pedido y restaurar stock?')">↩ Cancelar Pedido</a>
<?php } ?>

<br><br>
<a href="listar.php">⬅ Volver al listado de pedidos</a>

</body>
</html>
