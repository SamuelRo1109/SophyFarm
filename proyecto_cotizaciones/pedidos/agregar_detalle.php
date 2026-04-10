<?php
include '../conexion.php';

$idPedido = $_GET['id'];

// Obtener todos los productos
$productos = $conexion->query("SELECT idProducto, nombre, stockActual, precioBase FROM Producto");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Producto al Pedido</title>
    <script>
    // Cargar mapa de productos con su stock y precio
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
<h2>➕ Agregar Producto al Pedido</h2>

<form action="guardar_detalle.php" method="POST">
    <input type="hidden" name="idPedido" value="<?= $idPedido; ?>">

    <label>Producto:</label><br>
    <select name="idProducto" id="idProducto" onchange="actualizarInfo()" required>
        <option value="">-- Seleccione un producto --</option>
        <?php
        // Rehacer la consulta porque la anterior se consumió en el bloque JS
        $productos2 = $conexion->query("SELECT idProducto, nombre FROM Producto");
        while ($p2 = $productos2->fetch_assoc()) { ?>
            <option value="<?= $p2['idProducto']; ?>"><?= $p2['nombre']; ?></option>
        <?php } ?>
    </select>
    <br><br>

    <p><strong>Stock disponible:</strong> <span id="stockLabel">—</span></p>
    <input type="hidden" id="stock" value="0">

    <label>Cantidad:</label><br>
    <input type="number" id="cantidad" name="cantidad" min="1" required oninput="validarCantidad()"><br><br>

    <label>Precio Unitario:</label><br>
    <input type="number" step="0.01" id="precioUnitario" name="precioUnitario" required><br><br>

    <button type="submit">💾 Agregar</button>
    <a href="detalle.php?id=<?= $idPedido; ?>">Cancelar</a>
</form>

<script>
    // Inicializar con la opción seleccionada (si existe)
    actualizarInfo();
</script>
</body>
</html>
