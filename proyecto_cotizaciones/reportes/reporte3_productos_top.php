<?php
include '../conexion.php';

$cotizados = $conexion->query("
    SELECT p.nombre, SUM(dc.cantidad) AS total_cotizado
    FROM DetalleCotizacion dc
    INNER JOIN Producto p ON dc.idProducto = p.idProducto
    GROUP BY p.idProducto
    ORDER BY total_cotizado DESC
    LIMIT 5
");

$pedidos = $conexion->query("
    SELECT p.nombre, SUM(dp.cantidad) AS total_pedido
    FROM DetallePedido dp
    INNER JOIN Producto p ON dp.idProducto = p.idProducto
    GROUP BY p.idProducto
    ORDER BY total_pedido DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>💊 Reporte: Productos más cotizados y pedidos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h2 { color: #2b5797; }
        table { border-collapse: collapse; width: 90%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        a { text-decoration: none; color: #0056b3; }
        a:hover { text-decoration: underline; }
        .back { margin-top: 30px; display: inline-block; }
    </style>
</head>
<body>
<h2>💊 Reporte: Productos más cotizados y pedidos</h2>

<table>
    <tr>
        <th colspan="2">Más Cotizados</th>
        <th></th>
        <th colspan="2">Más Pedidos</th>
    </tr>
    <tr>
        <th>Producto</th><th>Cantidad</th><th></th>
        <th>Producto</th><th>Cantidad</th>
    </tr>

    <?php while ($cot = $cotizados->fetch_assoc()) { 
        $ped = $pedidos->fetch_assoc();
    ?>
    <tr>
        <td><?= $cot['nombre'] ?></td>
        <td><?= $cot['total_cotizado'] ?></td>
        <td></td>
        <td><?= $ped['nombre'] ?? '-' ?></td>
        <td><?= $ped['total_pedido'] ?? '-' ?></td>
    </tr>
    <?php } ?>
</table>

<p style="margin-top:20px;">
    📊 <strong>Análisis:</strong><br>
    Este reporte compara los productos más cotizados con los más pedidos, ofreciendo una visión clara sobre la demanda real.
    Cuando un producto tiene muchas cotizaciones pero pocos pedidos, puede haber problemas de precio, falta de stock o competencia directa.
    En cambio, los artículos que lideran ambos listados reflejan alto interés del cliente y deben considerarse estratégicos en inventario y ventas.
    <br><br>
    <strong>Recomendaciones:</strong><br>
    • Reforzar el inventario de los productos con alta rotación.<br>
    • Revisar precios o márgenes en artículos cotizados pero poco vendidos.<br>
    • Promocionar los productos con buena conversión entre cotización y pedido.<br>
    • Identificar tendencias para anticipar futuras necesidades del mercado.
</p>


<br><br>
<a href="reporte3_productos_top_pdf.php" target="_blank">🧾 Descargar este reporte en PDF</a>
<br><br>
<a href="index.php" class="back">⬅ Volver al menú de reportes</a>
</body>
</html>
