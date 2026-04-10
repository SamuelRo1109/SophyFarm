<?php
include '../conexion.php';

$idDetalle = $_GET['idDetalle'];
$idCotizacion = $_GET['idCotizacion'];

// Obtener información del detalle
$sql = "SELECT d.idDetalleCotizacion, d.idProducto, d.cantidad, d.precioUnitario,
               p.nombre
        FROM DetalleCotizacion d
        INNER JOIN Producto p ON d.idProducto = p.idProducto
        WHERE idDetalleCotizacion = $idDetalle";

$detalle = $conexion->query($sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Detalle</title>
</head>
<body>
    <h2>✏ Editar Producto de la Cotización</h2>
    <p><strong>Producto:</strong> <?= $detalle['nombre']; ?></p>

    <form action="actualizar_detalle.php" method="POST">
        <input type="hidden" name="idDetalle" value="<?= $idDetalle; ?>">
        <input type="hidden" name="idCotizacion" value="<?= $idCotizacion; ?>">

        <label>Cantidad:</label>
        <input type="number" name="cantidad" min="1" value="<?= $detalle['cantidad']; ?>" required><br><br>

        <label>Precio Unitario:</label>
        <input type="number" step="0.01" name="precioUnitario" value="<?= $detalle['precioUnitario']; ?>" required><br><br>

        <button type="submit">💾 Guardar Cambios</button>
        <a href="detalle.php?id=<?= $idCotizacion; ?>">Cancelar</a>
    </form>
</body>
</html>
    