<?php
include '../conexion.php';

$clientes = $conexion->query("SELECT idCliente, CONCAT(primerNombre, ' ', primerApellido, ' ', razonSocial) AS nombre FROM Cliente");
$vendedores = $conexion->query("SELECT idVendedor, CONCAT(primerNombre, ' ', primerApellido) AS nombre FROM Vendedor");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Cotización</title>
</head>
<body>
<h2>➕ Crear Cotización</h2>

<form action="guardar.php" method="POST">
    <label>Cliente:</label>
    <select name="idCliente" required>
        <?php while ($c = $clientes->fetch_assoc()) { ?>
            <option value="<?= $c['idCliente'] ?>"><?= $c['nombre'] ?></option>
        <?php } ?>
    </select>
    <br><br>

    <label>Vendedor:</label>
    <select name="idVendedor" required>
        <?php while ($v = $vendedores->fetch_assoc()) { ?>
            <option value="<?= $v['idVendedor'] ?>"><?= $v['nombre'] ?></option>
        <?php } ?>
    </select>
    <br><br>

    <label>Fecha:</label>
    <input type="date" name="fechaCotizacion" value="<?= date('Y-m-d') ?>" required>
    <br><br>

    <button type="submit">Guardar y agregar productos ➜</button>
</form>
</body>
</html>
