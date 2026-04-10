<?php
// co.edu.unbosque.report/rpt_margen_confirmados.php

require_once __DIR__ . '/../co.edu.unbosque.config/DbConfig.php'; // $pdo
require_once __DIR__ . '/fpdf/fpdf.php';

header('Content-Type: application/pdf');

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('Reporte: Resumen de Pedidos Confirmados'), 0, 1, 'C');
$pdf->Ln(4);

// Fecha
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Fecha de generacion: ' . date('Y-m-d H:i'), 0, 1, 'R');
$pdf->Ln(4);

// =================== CONSULTA ===================
$sql = "
    SELECT 
        COUNT(*)                      AS cantidad_pedidos,
        COALESCE(SUM(totalpedido),0)  AS total_confirmado,
        COALESCE(AVG(totalpedido),0)  AS promedio,
        COALESCE(MAX(totalpedido),0)  AS maximo,
        COALESCE(MIN(totalpedido),0)  AS minimo
    FROM pedido
    WHERE estadopedido = 'Confirmado'
";
$stmt = $pdo->query($sql);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$cant    = (int)($row['cantidad_pedidos']  ?? 0);
$total   = (float)($row['total_confirmado'] ?? 0);
$prom    = (float)($row['promedio'] ?? 0);
$maximo  = (float)($row['maximo']   ?? 0);
$minimo  = (float)($row['minimo']   ?? 0);

// =================== TABLA ===================
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(230, 230, 230);

$pdf->Cell(80, 8, utf8_decode('Indicador'), 1, 0, 'C', true);
$pdf->Cell(80, 8, 'Valor', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 11);

$pdf->Cell(80, 8, utf8_decode('Cantidad de pedidos confirmados'), 1);
$pdf->Cell(80, 8, $cant, 1, 1, 'C');

$pdf->Cell(80, 8, utf8_decode('Valor total confirmado'), 1);
$pdf->Cell(80, 8, number_format($total, 0, ',', '.'), 1, 1, 'R');

$pdf->Cell(80, 8, utf8_decode('Valor promedio por pedido'), 1);
$pdf->Cell(80, 8, number_format($prom, 0, ',', '.'), 1, 1, 'R');

$pdf->Cell(80, 8, utf8_decode('Pedido de mayor valor'), 1);
$pdf->Cell(80, 8, number_format($maximo, 0, ',', '.'), 1, 1, 'R');

$pdf->Cell(80, 8, utf8_decode('Pedido de menor valor'), 1);
$pdf->Cell(80, 8, number_format($minimo, 0, ',', '.'), 1, 1, 'R');

$pdf->Ln(10);

// =================== ANÁLISIS ===================
$pdf->SetFont('Arial', '', 11);

$analisis  = "Analisis de valor para la administracion:\n\n";
$analisis .= "- Este reporte resume el comportamiento de los pedidos que han alcanzado el estado 'Confirmado'.\n";
$analisis .= "- El monto total confirmado sirve como aproximacion al volumen de negocio gestionado por el modulo de pedidos.\n";
$analisis .= "- El valor promedio por pedido ayuda a entender el ticket promedio de las operaciones.\n\n";
$analisis .= "Recomendaciones:\n";
$analisis .= "- Monitorear mensualmente este indicador para detectar crecimiento o caidas en el volumen de pedidos confirmados.\n";
$analisis .= "- Combinar este reporte con el de 'Pedidos confirmados por mes' para ver la tendencia en el tiempo.\n";
$analisis .= "- Cuando se disponga de costos por producto, se puede evolucionar este reporte a margenes reales (ingreso - costo) por pedido.\n";

$pdf->MultiCell(0, 6, utf8_decode($analisis));

$pdf->Output('I', 'Reporte_Pedidos_Confirmados_Resumen.pdf');
exit;
