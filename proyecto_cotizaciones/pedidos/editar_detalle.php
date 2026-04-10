<?php
include '../conexion.php';

$idDetalle = $_GET['idDetalle'];
$idPedido  = $_GET['idPedido'];

// Obtener detalle actual
$sql = "SELECT d.idDetallePedido, d.idProducto, d.cantidad, d.precioUnitario,
               p.nombre, p.stockActual
        FROM DetallePedido d
        INNER JOIN Producto p ON d.idProducto = p.idProducto
        WHERE idDetallePedido = $idDetalle";
$detalle = $conexion->query($sql)->fetch_assoc();

// Obtener todos los productos (por si se quiere cambiar)
$productos = $conexion->query("SELECT idProducto, nombre, stockActual, precioBase FROM Producto");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Detalle Pedido</title>
    <script>
    // Mapa de stocks y precios por producto
    const productos = {
        <?php while ($p = $productos->fetch_assoc()) { ?>
            "<?= $p['idProducto']; ?>": { 
                stock: <?= $p['stockActual']; ?>, 
                precio: <?= $p['precioBase']; ?> 
            },
        <?php } ?>
    };

    function actualizarInfo() {
        const idProd = document.getElementById("idProducto").value;
        const stock = productos[idProd].stock;
        const precio = productos[idProd].precio;
        document.getElementById("stock").value = stock;
        document.getElementById("stockLabel").innerText = stock;
        document.getElementById("precioUnitario").value = precio;
    }

    function validarCantidad() {
        const cantidad = parseInt(document.getElementById("cantidad").value);
        const stock = parseInt(document.getElementById("stock").value);
        if (cantidad > stock) {
            alert("⚠ La cantidad solicitada (" + cantidad + ") supera el stock disponible (" + stock + ").");
        }
    }
    </script>
</head>
<body>
<h2>✏ Editar Producto del Pedido</h2>

<form action="actualizar_detalle.php" method="POST">
    <input type="hidden" name="idDetalle" value="<?= $detalle['idDetallePedido']; ?>">
    <input type="hidden" name="idPedido" value="<?= $idPedido; ?>">

    <!-- PRODUCTO -->
    <label>Producto:</label><br>
    <select name="idProducto" id="idProducto" onchange="actualizarInfo()" required>
        <?php
        // Volvemos a ejecutar el query de productos (porque ya se consumió el anterior en el JS)
        $productos2 = $conexion->query("SELECT idProducto, nombre FROM Producto");
        while ($p2 = $productos2->fetch_assoc()) { ?>
            <option value="<?= $p2['idProducto']; ?>" 
                <?= ($p2['idProducto'] == $detalle['idProducto']) ? 'selected' : ''; ?>>
                <?= $p2['nombre']; ?>
            </option>
        <?php } ?>
    </select>
    <br><br>

    <!-- STOCK -->
    <p><strong>Stock disponible:</strong> 
        <span id="stockLabel"><?= $detalle['stockActual']; ?></span>
    </p>
    <input type="hidden" id="stock" value="<?= $detalle['stockActual']; ?>">

    <!-- CANTIDAD -->
    <label>Cantidad:</label><br>
    <input type="number" id="cantidad" name="cantidad" min="1" 
           value="<?= $detalle['cantidad']; ?>" required 
           oninput="validarCantidad()"><br><br>

    <!-- PRECIO -->
    <label>Precio Unitario:</label><br>
    <input type="number" step="0.01" id="precioUnitario" name="precioUnitario" 
           value="<?= $detalle['precioUnitario']; ?>" required><br><br>

    <button type="submit">💾 Guardar Cambios</button>
    <a href="detalle.php?id=<?= $idPedido; ?>">Cancelar</a>
</form>

<script>
    // Inicializa info del producto actual al cargar
    actualizarInfo();
</script>
</body>
</html>
