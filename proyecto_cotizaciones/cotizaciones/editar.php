<?php
include '../conexion.php';

$idCotizacion = $_GET['id'];

// Datos de la cotización
$cotizacion = $conexion->query("
    SELECT * FROM Cotizacion 
    WHERE idCotizacion = $idCotizacion
")->fetch_assoc();

// Clientes y vendedores para los combos
$clientes = $conexion->query("SELECT idCliente, CONCAT(primerNombre, ' ', primerApellido, ' ', razonSocial) AS nombre FROM Cliente");
$vendedores = $conexion->query("SELECT idVendedor, CONCAT(primerNombre, ' ', primerApellido) AS nombre FROM Vendedor");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cotización</title>
</head>
<body>
    <h2>✏ Editar Cotización #<?= $idCotizacion ?></h2>

    <form action="actualizar.php" method="POST">
        <input type="hidden" name="idCotizacion" value="<?= $idCotizacion ?>">

        <label>Cliente:</label><br>
        <select name="idCliente" required>
            <?php while($row = $clientes->fetch_assoc()) { ?>
                <option value="<?= $row['idCliente']; ?>"
                    <?= ($row['idCliente'] == $cotizacion['idCliente']) ? 'selected' : ''; ?>>
                    <?= $row['nombre']; ?>
                </option>
            <?php } ?>
        </select><br><br>

        <label>Vendedor:</label><br>
        <select name="idVendedor" required>
            <?php while($row = $vendedores->fetch_assoc()) { ?>
                <option value="<?= $row['idVendedor']; ?>"
                    <?= ($row['idVendedor'] == $cotizacion['idVendedor']) ? 'selected' : ''; ?>>
                    <?= $row['nombre']; ?>
                </option>
            <?php } ?>
        </select><br><br>

        <label>Fecha de Cotización:</label><br>
        <input type="date" name="fechaCotizacion" value="<?= $cotizacion['fechaCotizacion']; ?>" required><br><br>

        <label>Estado:</label><br>
        <select name="estadoCotizacion">
            <option value="P" <?= ($cotizacion['estadoCotizacion'] == 'P' ? 'selected' : '') ?>>Pendiente</option>
            <option value="A" <?= ($cotizacion['estadoCotizacion'] == 'A' ? 'selected' : '') ?>>Aprobada</option>
            <option value="C" <?= ($cotizacion['estadoCotizacion'] == 'C' ? 'selected' : '') ?>>Cancelada</option>
        </select><br><br>

        <button type="submit">💾 Guardar Cambios</button>
        <a href="listar.php">Cancelar</a>
    </form>
</body>
</html>
