<?php
// co.edu.unbosque.report/rpt_clientes_tipo.php

require_once __DIR__ . '/../co.edu.unbosque.config/DbConfig.php'; // $pdo (PDO)
require_once __DIR__ . '/fpdf/fpdf.php';

header('Content-Type: application/pdf');

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('Reporte: Clientes Naturales vs Empresas'), 0, 1, 'C');
$pdf->Ln(4);

// Fecha de generación
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Fecha de generacion: ' . date('Y-m-d H:i'), 0, 1, 'R');
$pdf->Ln(4);

// ================== CONSULTA ==================
$sql = "
    SELECT 
        SUM(CASE WHEN idtipocliente = 1 THEN 1 ELSE 0 END) AS naturales,
        SUM(CASE WHEN idtipocliente = 2 THEN 1 ELSE 0 END) AS empresas
    FROM cliente
    WHERE estado = 'A'
";

$stmt = $pdo->query($sql);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$naturales = (int)($row['naturales'] ?? 0);
$empresas  = (int)($row['empresas']  ?? 0);
$total     = $naturales + $empresas;

$porcNat = $total > 0 ? round($naturales * 100 / $total, 2) : 0;
$porcEmp = $total > 0 ? round($empresas  * 100 / $total, 2) : 0;

// ================== TABLA ==================
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(230, 230, 230);

$pdf->Cell(80, 8, utf8_decode('Tipo de cliente'), 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Cantidad', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Porcentaje', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 12);

// Fila Naturales
$pdf->Cell(80, 8, utf8_decode('Persona natural'), 1);
$pdf->Cell(40, 8, $naturales, 1, 0, 'C');
$pdf->Cell(40, 8, $porcNat . ' %', 1, 1, 'C');

// Fila Empresas
$pdf->Cell(80, 8, utf8_decode('Empresa (razón social)'), 1);
$pdf->Cell(40, 8, $empresas, 1, 0, 'C');
$pdf->Cell(40, 8, $porcEmp . ' %', 1, 1, 'C');

// Totales
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(80, 8, 'TOTAL', 1);
$pdf->Cell(40, 8, $total, 1, 0, 'C');
$pdf->Cell(40, 8, '100 %', 1, 1, 'C');

$pdf->Ln(10);

// ================== ANALISIS DE VALOR ==================
$pdf->SetFont('Arial', '', 11);

$analisis  = "Analisis de valor para la administracion:\n\n";
$analisis .= "- Actualmente la base de clientes activos se compone de "
           . $naturales . " clientes naturales (" . $porcNat . " %) y "
           . $empresas  . " clientes empresa (" . $porcEmp . " %).\n\n";

if ($naturales > $empresas) {
    $analisis .= "- La mayor parte de los clientes son personas naturales. "
               . "Es conveniente enfocar campañas de fidelizacion, promociones "
               . "y seguimiento comercial al segmento retail (consumidor final).\n\n";
} elseif ($empresas > $naturales) {
    $analisis .= "- La mayor parte de los clientes son empresas. "
               . "Puede ser rentable diseñar paquetes corporativos, descuentos por volumen "
               . "y acuerdos de largo plazo (contratos marco, convenios de suministro).\n\n";
} else {
    $analisis .= "- La distribucion entre clientes naturales y empresas es equilibrada. "
               . "Se recomienda diseñar estrategias diferenciadas para ambos segmentos.\n\n";
}

$analisis .= "Recomendaciones generales:\n";
$analisis .= "- Monitorear mensualmente la evolucion de este indicador para evaluar si la empresa "
           . "esta captando el tipo de cliente objetivo.\n";
$analisis .= "- Cruzar este reporte con la rentabilidad por tipo de cliente (cuando se tenga modulo de facturacion) "
           . "para priorizar esfuerzos comerciales.\n";

$pdf->MultiCell(0, 6, utf8_decode($analisis));

$pdf->Output('I', 'Reporte_Clientes_Naturales_vs_Empresas.pdf');
exit;
