<?php
// co.edu.unbosque.report/rpt_rotacion_inventario.php

require_once __DIR__ . '/../co.edu.unbosque.config/DbConfig.php'; // $pdo
require_once __DIR__ . '/fpdf/fpdf.php';

header('Content-Type: application/pdf');

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 15, 10);

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('Reporte: Rotacion de Inventario por Producto'), 0, 1, 'C');
$pdf->Ln(4);

// Fecha
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Fecha de generacion: ' . date('Y-m-d H:i'), 0, 1, 'R');
$pdf->Ln(4);

// =================== CONSULTA ===================
// Tomamos todos los productos, con cantidad total vendida en pedidos Confirmados
$sql = "
    SELECT 
        e.idelemento,
        e.nombre,
        e.existencia,
        COALESCE(SUM(dp.cantidad), 0) AS cantidad_vendida
    FROM elemento e
    LEFT JOIN detalle_pedido dp ON e.idelemento = dp.idproducto
    LEFT JOIN pedido p ON p.idpedido = dp.idpedido AND p.estadopedido = 'Confirmado'
    GROUP BY e.idelemento, e.nombre, e.existencia
    ORDER BY cantidad_vendida DESC, e.nombre
";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =================== TABLA ===================
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(230, 230, 230);

$pdf->Cell(15, 7, 'ID', 1, 0, 'C', true);
$pdf->Cell(100, 7, 'Producto', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Vendidos', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Stock Actual', 1, 0, 'C', true);
$pdf->Cell(40, 7, 'Observacion', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);

if (empty($rows)) {
    $pdf->Cell(215, 7, utf8_decode('No hay productos para mostrar.'), 1, 1, 'C');
} else {
    foreach ($rows as $r) {
        $id       = $r['idelemento'];
        $nombre   = $r['nombre'];
        $vendidos = (int)$r['cantidad_vendida'];
        $stock    = (int)$r['existencia'];

        // Observacion simple segun cantidad vendida
        if ($vendidos >= 50) {
            $obs = 'Alta rotacion';
        } elseif ($vendidos >= 10) {
            $obs = 'Rotacion media';
        } elseif ($vendidos > 0) {
            $obs = 'Rotacion baja';
        } else {
            $obs = 'Sin rotacion';
        }

        $pdf->Cell(15, 7, $id, 1);
        $pdf->Cell(100, 7, utf8_decode($nombre), 1);
        $pdf->Cell(30, 7, $vendidos, 1, 0, 'C');
        $pdf->Cell(30, 7, $stock, 1, 0, 'C');
        $pdf->Cell(40, 7, utf8_decode($obs), 1, 1, 'C');
    }
}

$pdf->Ln(8);

// =================== ANÁLISIS ===================
$pdf->SetFont('Arial', '', 11);

$analisis  = "Analisis de valor para la administracion:\n\n";
$analisis .= "- Este reporte muestra, para cada producto, la cantidad total vendida (en pedidos confirmados) y el stock actual.\n";
$analisis .= "- La clasificacion en 'alta', 'media' o 'baja' rotacion permite identificar:\n";
$analisis .= "  * Productos que se mueven rapido y requieren reposicion frecuente.\n";
$analisis .= "  * Productos con muy poca salida que pueden estar sobreinventariados.\n\n";
$analisis .= "Recomendaciones:\n";
$analisis .= "- Revisar los productos de alta rotacion para garantizar disponibilidad y evitar quiebres de stock.\n";
$analisis .= "- Analizar los productos sin rotacion para definir estrategias: promociones, sustitucion o descontinuacion.\n";
$analisis .= "- Combinar este reporte con el TOP de productos mas cotizados para detectar oportunidades comerciales.\n";

$pdf->MultiCell(0, 6, utf8_decode($analisis));

$pdf->Output('I', 'Reporte_Rotacion_Inventario.pdf');
exit;
