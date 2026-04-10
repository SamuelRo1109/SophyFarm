<?php
include '../conexion.php';

$sql = "SELECT c.idCotizacion,
               CONCAT(cl.primerNombre, ' ', cl.primerApellido, ' ', cl.razonSocial) AS cliente,
               CONCAT(v.primerNombre, ' ', v.primerApellido) AS vendedor,
               c.fechaCotizacion,
               c.estadoCotizacion,
               c.valorNeto
        FROM Cotizacion c
        INNER JOIN Cliente cl ON c.idCliente = cl.idCliente
        INNER JOIN Vendedor v ON c.idVendedor = v.idVendedor
        ORDER BY c.idCotizacion DESC";

$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotizaciones</title>
</head>
<body>
<h2>📋 Listado de Cotizaciones</h2>
<a href="agregar.php">➕ Nueva Cotización</a>
<br><br>

<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Cliente</th>
        <th>Vendedor</th>
        <th>Fecha</th>
        <th>Estado</th>
        <th>Total</th>
        <th>Acciones</th>
    </tr>
    <?php while ($row = $resultado->fetch_assoc()) { ?>
    <tr>
        <td><?= $row['idCotizacion'] ?></td>
        <td><?= $row['cliente'] ?></td>
        <td><?= $row['vendedor'] ?></td>
        <td><?= $row['fechaCotizacion'] ?></td>
        <td><?= $row['estadoCotizacion'] ?></td>
        <td>$<?= number_format($row['valorNeto'], 2) ?></td>
<td>
    <a href="detalle.php?id=<?= $row['idCotizacion'] ?>">📦 Detalles</a> |
    <a href="editar.php?id=<?= $row['idCotizacion'] ?>">✏ Editar</a> |
    <a href="eliminar.php?id=<?= $row['idCotizacion'] ?>" onclick="return confirm('¿Eliminar cotización?')">🗑 Eliminar</a>

    <?php if ($row['estadoCotizacion'] == 'A') { ?>
        | <a href="../pedidos/generar.php?idCotizacion=<?= $row['idCotizacion'] ?>">🛒 Generar Pedido</a>
    <?php } ?>
</td>
    </tr>
    <?php } ?>
</table>

<br>
<a href="../index.php">⬅ Volver al inicio</a>
</body>
</html>
