<?php
include '../conexion.php';

$sql = "SELECT p.idPedido, p.fechaPedido, p.estadoPedido, p.totalPedido,
               CONCAT(cl.primerNombre, ' ', cl.primerApellido, ' ', cl.razonSocial) AS cliente,
               p.idCotizacion
        FROM Pedido p
        INNER JOIN Cliente cl ON p.idCliente = cl.idCliente
        ORDER BY p.idPedido DESC";

$pedidos = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos</title>
</head>
<body>
<h2>📦 Listado de Pedidos</h2>
<a href="agregar.php">➕ Nuevo Pedido Manual</a>
<br><br>

<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Cliente</th>
        <th>Cotización Origen</th>
        <th>Fecha</th>
        <th>Estado</th>
        <th>Total</th>
        <th>Acciones</th>
    </tr>

    <?php while ($row = $pedidos->fetch_assoc()) { ?>
    <tr>
        <td><?= $row['idPedido'] ?></td>
        <td><?= $row['cliente'] ?></td>
        <td><?= $row['idCotizacion'] ? $row['idCotizacion'] : '—' ?></td>
        <td><?= $row['fechaPedido'] ?></td>
        <td><?= $row['estadoPedido'] ?></td>
        <td>$<?= number_format($row['totalPedido'], 2) ?></td>
        <td>
            <a href="detalle.php?id=<?= $row['idPedido'] ?>">📋 Detalle</a> |
            <a href="editar.php?id=<?= $row['idPedido'] ?>">✏ Editar</a> |
            <a href="eliminar.php?id=<?= $row['idPedido'] ?>" onclick="return confirm('¿Eliminar pedido?')">🗑 Eliminar</a>

            <?php if ($row['estadoPedido'] === 'CONFIRMADO') { ?>
                | <a href="cancelar.php?id=<?= $row['idPedido'] ?>" onclick="return confirm('¿Cancelar pedido y restaurar stock?')">↩ Cancelar</a>
            <?php } elseif ($row['estadoPedido'] !== 'CANCELADO') { ?>
                | <a href="confirmar.php?id=<?= $row['idPedido'] ?>">✅ Confirmar</a>
            <?php } ?>
        </td>
    </tr>
    <?php } ?>
</table>

<br>
<a href="../cotizaciones/listar.php">⬅ Volver a Cotizaciones</a>
</body>
</html>
