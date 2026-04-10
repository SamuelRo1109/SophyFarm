<?php
// co.edu.unbosque.report/rpt_cot_vs_ped.php

require_once __DIR__ . '/../co.edu.unbosque.config/DbConfig.php'; // $pdo
require_once __DIR__ . '/fpdf/fpdf.php';

header('Content-Type: application/pdf');

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('Reporte: Cotizaciones vs Pedidos'), 0, 1, 'C');
$pdf->Ln(4);

// Fecha de generación
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Fecha de generacion: ' . date('Y-m-d H:i'), 0, 1, 'R');
$pdf->Ln(4);

// ================== CONSULTA PRINCIPAL ==================
$sql = "
    SELECT 
        (SELECT COUNT(*) FROM cotizacion) AS total_cotizaciones,
        (SELECT COUNT(*) FROM pedido)     AS total_pedidos
";
$stmt = $pdo->query($sql);
$row  = $stmt->fetch(PDO::FETCH_ASSOC);

$totalCot = (int)($row['total_cotizaciones'] ?? 0);
$totalPed = (int)($row['total_pedidos']      ?? 0);

// Tasa de conversión Cotización -> Pedido
$conversion = $totalCot > 0 ? round($totalPed * 100 / $totalCot, 2) : 0;

// ================== TABLA RESUMEN ==================
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(230, 230, 230);

$pdf->Cell(80, 8, utf8_decode('Indicador'), 1, 0, 'C', true);
$pdf->Cell(80, 8, 'Valor', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 12);

$pdf->Cell(80, 8, utf8_decode('Total de cotizaciones'), 1);
$pdf->Cell(80, 8, $totalCot, 1, 1, 'C');

$pdf->Cell(80, 8, utf8_decode('Total de pedidos'), 1);
$pdf->Cell(80, 8, $totalPed, 1, 1, 'C');

$pdf->Cell(80, 8, utf8_decode('Tasa de conversión (Cotización -> Pedido)'), 1);
$pdf->Cell(80, 8, $conversion . ' %', 1, 1, 'C');

$pdf->Ln(10);

// ================== ANALISIS DE VALOR ==================
$pdf->SetFont('Arial', '', 11);

$analisis  = "Analisis de valor para la administracion:\n\n";
$analisis .= "- Se han generado un total de " . $totalCot . " cotizaciones y " . $totalPed . " pedidos.\n";
$analisis .= "- La tasa de conversion Cotizacion -> Pedido es de " . $conversion . " %.\n\n";

if ($conversion < 30) {
    $analisis .= "- Una tasa de conversion menor al 30 % suele indicar problemas en algun punto del proceso comercial: "
               . "precios poco competitivos, falta de seguimiento a las cotizaciones o tiempos de respuesta altos.\n\n";
    $analisis .= "-  Un valor superior al 100 % indica que hay más pedidos que cotizaciones\n";
    $analisis .= "-Ejemplo, un 175 % indicaría que por cada cotización se están generando 1.75 pedidos en promedio \n\n";         
    $analisis .= "Recomendaciones:\n";
    $analisis .= "- Revisar la politica de precios y compararse frente a la competencia.\n";
    $analisis .= "- Implementar un seguimiento sistematico de las cotizaciones (llamadas, correos, recordatorios).\n";
    $analisis .= "- Analizar por vendedor y por tipo de cliente para identificar donde se pierden mas oportunidades.\n";
} elseif ($conversion < 60) {
    $analisis .= "- La tasa de conversion es moderada. Hay un aprovechamiento aceptable de las oportunidades, "
               . "pero aun existe espacio para mejorar.\n\n";
    $analisis .= "Recomendaciones:\n";
    $analisis .= "- Identificar las cotizaciones que no se convierten en pedido y documentar las causas (precio, plazo, proveedor alterno, etc.).\n";
    $analisis .= "- Fortalecer habilidades comerciales y tecnicas del equipo de ventas.\n";
} else {
    $analisis .= "- La tasa de conversion es alta. La empresa esta convirtiendo una gran parte de las cotizaciones en pedidos, "
               . "lo cual refleja una buena alineacion entre precios, servicio y necesidades del cliente.\n\n";
    $analisis .= "Recomendaciones:\n";
    $analisis .= "- Mantener el nivel de servicio actual y considerar estrategias para incrementar el volumen de cotizaciones.\n";
    $analisis .= "- Priorizar los segmentos de clientes con mayor conversion para consolidar la relacion comercial.\n";
}

$pdf->MultiCell(0, 6, utf8_decode($analisis));

$pdf->Output('I', 'Reporte_Cotizaciones_vs_Pedidos.pdf');
exit;
