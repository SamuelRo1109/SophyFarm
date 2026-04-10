<?php
// co.edu.unbosque.report/rpt_pedidos_confirmados_mes.php

require_once __DIR__ . '/../co.edu.unbosque.config/DbConfig.php'; // $pdo
require_once __DIR__ . '/fpdf/fpdf.php';

header('Content-Type: application/pdf');

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('Reporte: Pedidos Confirmados por Mes'), 0, 1, 'C');
$pdf->Ln(4);

// Fecha
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Fecha de generacion: ' . date('Y-m-d H:i'), 0, 1, 'R');
$pdf->Ln(4);

// =================== CONSULTA ===================
$sql = "
    SELECT
        DATE_TRUNC('month', fechapedido) AS mes,
        COUNT(*)                        AS cantidad_pedidos,
        COALESCE(SUM(totalpedido), 0)   AS valor_total
    FROM pedido
    WHERE estadopedido = 'Confirmado'
    GROUP BY DATE_TRUNC('month', fechapedido)
    ORDER BY mes
";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para analisis simple de tendencia
$primerMes = null;
$ultimoMes = null;
$primCant  = null;
$ultCant   = null;

// =================== TABLA ===================
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(230, 230, 230);

$pdf->Cell(40, 8, 'Mes', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Pedidos Conf.', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Valor Total', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 11);

if (empty($rows)) {
    $pdf->Cell(130, 8, utf8_decode('No hay pedidos confirmados para mostrar.'), 1, 1, 'C');
} else {
    $i = 0;
    foreach ($rows as $r) {
        $mesRaw  = $r['mes']; // tipo timestamp
        $mesText = date('Y-m', strtotime($mesRaw));

        if ($i === 0) {
            $primerMes = $mesText;
            $primCant  = (int)$r['cantidad_pedidos'];
        }
        $ultimoMes = $mesText;
        $ultCant   = (int)$r['cantidad_pedidos'];

        $pdf->Cell(40, 8, $mesText, 1);
        $pdf->Cell(40, 8, $r['cantidad_pedidos'], 1, 0, 'C');
        $pdf->Cell(50, 8, number_format($r['valor_total'], 0, ',', '.'), 1, 1, 'R');

        $i++;
    }
}

$pdf->Ln(10);

// =================== ANÁLISIS ===================
$pdf->SetFont('Arial', '', 11);

$analisis  = "Analisis de valor para la administracion:\n\n";
$analisis .= "- Este reporte muestra la cantidad y el valor total de pedidos confirmados por mes.\n";
$analisis .= "- Permite visualizar la tendencia del modulo de pedidos en el tiempo (picos, temporadas bajas, etc.).\n\n";

if ($primerMes !== null && $ultimoMes !== null && $primerMes !== $ultimoMes) {
    if ($ultCant > $primCant) {
        $analisis .= "- Comparando " . $primerMes . " con " . $ultimoMes . ", la cantidad de pedidos confirmados aumento de "
                   . $primCant . " a " . $ultCant . ", lo que sugiere una tendencia positiva.\n\n";
    } elseif ($ultCant < $primCant) {
        $analisis .= "- Comparando " . $primerMes . " con " . $ultimoMes . ", la cantidad de pedidos confirmados disminuyo de "
                   . $primCant . " a " . $ultCant . ", lo que puede indicar una desaceleracion de la demanda.\n\n";
    } else {
        $analisis .= "- Comparando " . $primerMes . " con " . $ultimoMes . ", la cantidad de pedidos confirmados se mantuvo estable ("
                   . $primCant . " pedidos en ambos meses).\n\n";
    }
}

$analisis .= "Recomendaciones:\n";
$analisis .= "- Identificar meses con picos altos y cruzarlos con campañas comerciales o eventos externos.\n";
$analisis .= "- Revisar meses con caidas fuertes para detectar problemas de stock, tiempos de entrega o cambios de competencia.\n";
$analisis .= "- Utilizar esta informacion para planear inventarios y metas comerciales mensuales.\n";

$pdf->MultiCell(0, 6, utf8_decode($analisis));

$pdf->Output('I', 'Reporte_Pedidos_Confirmados_Por_Mes.pdf');
exit;
