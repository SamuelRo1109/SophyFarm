<?php
include '../conexion.php';

// Obtener todos los clientes
$clientes = $conexion->query("SELECT idCliente, CONCAT(primerNombre, ' ', primerApellido, ' ', razonSocial) AS nombre FROM Cliente");

// Obtener cotizaciones aprobadas (opcional)
$cotizaciones = $conexion->query("
    SELECT c.idCotizacion, 
           CONCAT('COT-', c.idCotizacion, ' | ', cl.primerNombre, ' ', cl.primerApellido, ' ', cl.razonSocial) AS info,
           c.idCliente
    FROM Cotizacion c
    INNER JOIN Cliente cl ON c.idCliente = cl.idCliente
    WHERE c.estadoCotizacion = 'A'
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Pedido</title>
    <script>
    // Relacionar cotización con cliente
    const cotizacionCliente = {
        <?php while ($co = $cotizaciones->fetch_assoc()) { ?>
            "<?= $co['idCotizacion']; ?>": "<?= $co['idCliente']; ?>",
        <?php } ?>
    };

    function actualizarCliente() {
        const idCot = document.getElementById("idCotizacion").value;
        if (idCot !== "") {
            const idCli = cotizacionCliente[idCot];
            document.getElementById("idCliente").value = idCli;
            document.getElementById("idCliente").disabled = true;
        } else {
            document.getElementById("idCliente").disabled = false;
        }
    }
    </script>
</head>
<body>
<h2>🛒 Crear Pedido</h2>

<form action="guardar.php" method="POST">
    <!-- Cliente -->
    <label>Cliente:</label><br>
    <select name="idCliente" id="idCliente" required>
        <option value="">Seleccione un cliente</option>
        <?php
        $clientes2 = $conexion->query("SELECT idCliente, CONCAT(primerNombre, ' ', primerApellido, ' ', razonSocial) AS nombre FROM Cliente");
        while ($c = $clientes2->fetch_assoc()) { ?>
            <option value="<?= $c['idCliente'] ?>"><?= $c['nombre'] ?></option>
        <?php } ?>
    </select>
    <br><br>

    <!-- Cotización (opcional) -->
    <label>¿Proviene de una cotización?</label><br>
    <select name="idCotizacion" id="idCotizacion" onchange="actualizarCliente()">
        <option value="">(No aplica)</option>
        <?php
        $cotizaciones2 = $conexion->query("
            SELECT c.idCotizacion, 
                   CONCAT('COT-', c.idCotizacion, ' | ', cl.primerNombre, ' ', cl.primerApellido, ' ', cl.razonSocial) AS info
            FROM Cotizacion c
            INNER JOIN Cliente cl ON c.idCliente = cl.idCliente
            WHERE c.estadoCotizacion = 'A'
        ");
        while ($co2 = $cotizaciones2->fetch_assoc()) { ?>
            <option value="<?= $co2['idCotizacion'] ?>"><?= $co2['info'] ?></option>
        <?php } ?>
    </select>
    <br><br>

    <label>Fecha del Pedido:</label><br>
    <input type="date" name="fechaPedido" value="<?= date('Y-m-d') ?>" required><br><br>

    <label>Estado del Pedido:</label><br>
    <select name="estadoPedido">
        <option value="PENDIENTE">Pendiente</option>
        <option value="PROCESANDO">Procesando</option>
    </select><br><br>

    <button type="submit">💾 Guardar Pedido</button>
    <a href="listar.php">Cancelar</a>
</form>

<script>
    // Reinicia el listado de clientes si se recarga con cotizaciones
    actualizarCliente();
</script>
</body>
</html>
