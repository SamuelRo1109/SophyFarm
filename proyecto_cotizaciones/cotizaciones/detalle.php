<?php
include '../conexion.php';

// Obtener ID de cotización
$idCotizacion = $_GET['id'];

// 1. Datos generales de la cotización
$sql = "SELECT c.idCotizacion, c.fechaCotizacion, c.estadoCotizacion,
               cl.numeroDocumento, 
               CONCAT(cl.primerNombre, ' ', cl.primerApellido, ' ', cl.razonSocial) AS cliente
        FROM Cotizacion c
        INNER JOIN Cliente cl ON c.idCliente = cl.idCliente
        WHERE c.idCotizacion = $idCotizacion";
$cotizacion = $conexion->query($sql)->fetch_assoc();

// 2. Productos disponibles para agregar
$productos = $conexion->query("SELECT idProducto, nombre, precioBase FROM Producto");

// 3. Detalles ya agregados
$detalles = $conexion->query("SELECT d.idDetalleCotizacion, p.nombre, d.cantidad, d.precioUnitario, d.valorSubtotal
                              FROM DetalleCotizacion d
                              INNER JOIN Producto p ON d.idProducto = p.idProducto
                              WHERE d.idCotizacion = $idCotizacion");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Cotización</title>
</head>
<body>
    <h2>🧾 Detalle de Cotización #<?= $cotizacion['idCotizacion']; ?></h2>
    <p><strong>Cliente:</strong> <?= $cotizacion['cliente']; ?></p>
    <p><strong>Fecha:</strong> <?= $cotizacion['fechaCotizacion']; ?></p>
    <p><strong>Estado:</strong> <?= $cotizacion['estadoCotizacion']; ?></p>

    <hr>

    <!-- Formulario para agregar producto -->
    <h3>➕ Agregar Producto</h3>
    <form action="guardar_detalle.php" method="POST">
        <input type="hidden" name="idCotizacion" value="<?= $idCotizacion; ?>">

        <label>Producto:</label>
        <select name="idProducto" required>
            <?php while ($p = $productos->fetch_assoc()) { ?>
                <option value="<?= $p['idProducto']; ?>">
                    <?= $p['nombre']; ?> - $<?= $p['precioBase']; ?>
                </option>
            <?php } ?>
        </select><br><br>

        <label>Cantidad:</label>
        <input type="number" name="cantidad" min="1" required><br><br>

        <button type="submit">Agregar</button>
    </form>

    <hr>

<h3>📦 Productos en esta cotización</h3>
<table border="1">
    <tr>
        <th>Producto</th>
        <th>Cantidad</th>
        <th>Precio Unitario</th>
        <th>Subtotal</th>
        <th>Acciones</th>
    </tr>

    <?php 
    $total = 0;
    while ($d = $detalles->fetch_assoc()) { 
        $total += $d['valorSubtotal']; 
    ?>
    <tr>
        <td><?= $d['nombre']; ?></td>
        <td><?= $d['cantidad']; ?></td>
        <td>$<?= number_format($d['precioUnitario'], 2); ?></td>
        <td>$<?= number_format($d['valorSubtotal'], 2); ?></td>
        <td>
            <a href="editar_detalle.php?idDetalle=<?= $d['idDetalleCotizacion']; ?>&idCotizacion=<?= $idCotizacion; ?>">✏ Editar</a>
            |
            <a href="eliminar_detalle.php?id=<?= $d['idDetalleCotizacion']; ?>&idCotizacion=<?= $idCotizacion; ?>" onclick="return confirm('¿Eliminar este producto de la cotización?')">❌ Eliminar</a>
        </td>
    </tr>
    <?php } ?>

    <tr>
        <th colspan="3">TOTAL:</th>
        <th>$<?= number_format($total, 2); ?></th>
        <th></th>
    </tr>
</table>

    <br>
    <a href="listar.php">⬅ Volver a Cotizaciones</a>
</body>
</html>
