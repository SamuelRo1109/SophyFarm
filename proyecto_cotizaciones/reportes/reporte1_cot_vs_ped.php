<?php
include '../conexion.php';

$totalCotizaciones = $conexion->query("SELECT COUNT(*) AS total FROM Cotizacion")->fetch_assoc()['total'];
$totalPedidos = $conexion->query("SELECT COUNT(*) AS total FROM Pedido")->fetch_assoc()['total'];
$conPedido = $conexion->query("
    SELECT COUNT(DISTINCT c.idCotizacion) AS total
    FROM Cotizacion c
    INNER JOIN Pedido p ON c.idCotizacion = p.idCotizacion
")->fetch_assoc()['total'];
$sinPedido = $totalCotizaciones - $conPedido;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>📈 Reporte: Cotizaciones vs Pedidos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h2 { color: #2b5797; }
        table { border-collapse: collapse; width: 60%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        a { text-decoration: none; color: #0056b3; }
        a:hover { text-decoration: underline; }
        .back { margin-top: 30px; display: inline-block; }
    </style>
</head>
<body>
<h2>📈 Reporte: Cotizaciones vs Pedidos</h2>

<table>
    <tr><th>Concepto</th><th>Total</th></tr>
    <tr><td>Total Cotizaciones</td><td><?= $totalCotizaciones ?></td></tr>
    <tr><td>Cotizaciones con Pedido</td><td><?= $conPedido ?></td></tr>
    <tr><td>Cotizaciones sin Pedido</td><td><?= $sinPedido ?></td></tr>
    <tr><td>Total Pedidos</td><td><?= $totalPedidos ?></td></tr>
</table>

<p style="margin-top:20px;">
    📊 <strong>Análisis:</strong><br>
    Este reporte mide la eficiencia del proceso comercial al mostrar cuántas cotizaciones terminan convirtiéndose en pedidos.
    Un número alto de cotizaciones sin pedido puede reflejar problemas como falta de seguimiento al cliente,
    precios poco competitivos o demoras en la entrega. En cambio, una alta conversión indica una gestión efectiva y buena relación con el cliente.
    <br><br>
    <strong>Recomendaciones:</strong><br>
    • Revisar las causas de las cotizaciones no convertidas (precio, tiempos, disponibilidad).<br>
    • Implementar seguimientos automáticos o contacto post-cotización.<br>
    • Evaluar el desempeño de los vendedores en función de la tasa de conversión.
</p>


<br><br>
<a href="reporte1_cot_vs_ped_pdf.php" target="_blank">🧾 Descargar este reporte en PDF</a>
<br><br>
<a href="index.php" class="back">⬅ Volver al menú de reportes</a>
</body>
</html>
