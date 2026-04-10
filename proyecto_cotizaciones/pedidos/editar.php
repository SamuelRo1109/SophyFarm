<?php
include '../conexion.php';

$idPedido = $_GET['id'];

// Datos del pedido
$pedido = $conexion->query("SELECT * FROM Pedido WHERE idPedido = $idPedido")->fetch_assoc();

// Clientes con cotizaciones aprobadas
$clientes = $conexion->query("
    SELECT DISTINCT cl.idCliente, 
           CONCAT(cl.primerNombre, ' ', cl.primerApellido, ' ', cl.razonSocial) AS nombre
    FROM Cliente cl
    INNER JOIN Cotizacion c ON c.idCliente = cl.idCliente
    WHERE c.estadoCotizacion = 'A'
");

// Cotizaciones aprobadas
$cotizaciones = $conexion->query("
    SELECT idCotizacion FROM Cotizacion WHERE estadoCotizacion = 'A'
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Pedido</title>
</head>
<body>
<h2>✏ Editar Pedido #<?= $idPedido ?></h2>

<form action="actualizar.php" method="POST">
    <input type="hidden" name="idPedido" value="<?= $idPedido ?>">

    <label>Cliente:</label><br>
    <select name="idCliente" required>
        <?php while ($c = $clientes->fetch_assoc()) { ?>
            <option value="<?= $c['idCliente'] ?>" 
                <?= ($c['idCliente'] == $pedido['idCliente']) ? 'selected' : '' ?>>
                <?= $c['nombre'] ?>
            </option>
        <?php } ?>
    </select><br><br>

    <label>Cotización (opcional):</label><br>
    <select name="idCotizacion">
        <option value="">(No aplica)</option>
        <?php while ($co = $cotizaciones->fetch_assoc()) { ?>
            <option value="<?= $co['idCotizacion'] ?>" 
                <?= ($co['idCotizacion'] == $pedido['idCotizacion']) ? 'selected' : '' ?>>
                <?= $co['idCotizacion'] ?>
            </option>
        <?php } ?>
    </select><br><br>

    <label>Fecha del Pedido:</label><br>
    <input type="date" name="fechaPedido" value="<?= $pedido['fechaPedido'] ?>" required><br><br>

    <label>Estado del Pedido:</label><br>
    <select name="estadoPedido">
        <option value="PENDIENTE" <?= ($pedido['estadoPedido'] == 'PENDIENTE') ? 'selected' : '' ?>>Pendiente</option>
        <option value="PROCESANDO" <?= ($pedido['estadoPedido'] == 'PROCESANDO') ? 'selected' : '' ?>>Procesando</option>
        <option value="CONFIRMADO" <?= ($pedido['estadoPedido'] == 'CONFIRMADO') ? 'selected' : '' ?>>Confirmado</option>
    </select><br><br>

    <button type="submit">💾 Actualizar Pedido</button>
    <a href="listar.php">Cancelar</a>
</form>
</body>
</html>
