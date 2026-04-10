<?php
include '../conexion.php';

$sql = "
    SELECT tc.nombreTipoCliente AS tipo, COUNT(c.idCotizacion) AS total
    FROM Cotizacion c
    INNER JOIN Cliente cl ON c.idCliente = cl.idCliente
    INNER JOIN TipoCliente tc ON cl.idTipoCliente = tc.idTipoCliente
    GROUP BY tc.nombreTipoCliente
";
$result = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>👥 Reporte: Tipos de Cliente en Cotizaciones</title>
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
<h2>👥 Reporte: Tipos de Cliente en Cotizaciones</h2>

<table>
    <tr><th>Tipo de Cliente</th><th>Total de Cotizaciones</th></tr>
    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['tipo'] ?></td>
            <td><?= $row['total'] ?></td>
        </tr>
    <?php } ?>
</table>

<p style="margin-top:20px;">
    📊 <strong>Análisis:</strong><br>
    Este reporte identifica qué tipo de cliente realiza más cotizaciones, lo que permite orientar la estrategia comercial.
    Si predominan las personas naturales, se recomienda fortalecer campañas B2C enfocadas en promociones, descuentos o canales digitales.
    Si las empresas son las principales, conviene reforzar estrategias B2B, ofreciendo precios por volumen o atención ejecutiva personalizada.
    <br><br>
    <strong>Recomendaciones:</strong><br>
    • Analizar qué tipo de cliente aporta más margen de ganancia.<br>
    • Crear campañas diferenciadas para cada segmento.<br>
    • Ajustar los productos y servicios ofrecidos según el tipo de comprador dominante.
</p>



<br><br>
<a href="reporte2_tipos_cliente_pdf.php" target="_blank">🧾 Descargar este reporte en PDF</a>
<br><br>
<a href="index.php" class="back">⬅ Volver al menú de reportes</a>
</body>
</html>
