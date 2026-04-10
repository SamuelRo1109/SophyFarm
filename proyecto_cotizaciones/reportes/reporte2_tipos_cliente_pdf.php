<?php
require('fpdf.php');
include '../conexion.php';

$sql = "
    SELECT tc.nombreTipoCliente AS tipo, COUNT(c.idCotizacion) AS total
    FROM Cotizacion c
    INNER JOIN Cliente cl ON c.idCliente = cl.idCliente
    INNER JOIN TipoCliente tc ON cl.idTipoCliente = tc.idTipoCliente
    GROUP BY tc.nombreTipoCliente
";
$result = $conexion->query($sql);

// Crear PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Reporte: Tipos de Cliente en Cotizaciones',0,1,'C');
$pdf->Ln(10);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(100,10,'Tipo de Cliente',1);
$pdf->Cell(40,10,'Total Cotizaciones',1);
$pdf->Ln();

$pdf->SetFont('Arial','',12);
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(100,10,utf8_decode($row['tipo']),1);
    $pdf->Cell(40,10,$row['total'],1);
    $pdf->Ln();
}

$pdf->Ln(10);
$pdf->MultiCell(0,8,utf8_decode(
"Análisis:\n" .
"Este reporte permite identificar el tipo de público que más interactúa con la empresa a través de cotizaciones. " .
"Si predominan los clientes naturales, significa que las estrategias deben centrarse en la venta directa al consumidor (modelo B2C). " .
"En cambio, si las empresas son las que más cotizan, se debe fortalecer el enfoque corporativo (modelo B2B), " .
"ofreciendo beneficios por volumen, atención personalizada o contratos recurrentes.\n\n" .
"Recomendaciones:\n" .
"- Analizar qué tipo de cliente genera más rentabilidad, no solo volumen de cotizaciones.\n" .
"- Diseñar campañas diferenciadas: promociones para naturales y planes de fidelización para empresas.\n" .
"- Ajustar el portafolio de productos según la demanda predominante (productos de uso masivo o institucional)."
));

$pdf->Output('I','Reporte_Tipos_Cliente.pdf');
?>
