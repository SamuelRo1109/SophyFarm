<?php
// co.edu.unbosque.report/rpt_top_productos.php

require_once __DIR__ . '/../co.edu.unbosque.config/DbConfig.php'; // $pdo
require_once __DIR__ . '/fpdf/fpdf.php';

header('Content-Type: application/pdf');

$pdf = new FPDF('L', 'mm', 'A4'); // Horizontal
$pdf->AddPage();
$pdf->SetMargins(10, 15, 10);

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('Reporte: Productos más Cotizados vs Productos en Pedidos Confirmados'), 0, 1, 'C');
$pdf->Ln(4);

// Fecha
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Fecha de generacion: ' . date('Y-m-d H:i'), 0, 1, 'R');
$pdf->Ln(4);

// ==================== CONSULTAS ====================
// TOP productos más cotizados
$sqlCot = "
    SELECT 
        e.nombre,
        SUM(dc.cantidad) AS total_cotizado
    FROM detalle_cotizacion dc
    JOIN elemento e ON e.idelemento = dc.idproducto
    GROUP BY e.nombre
    ORDER BY total_cotizado DESC
    LIMIT 5
";
$cotizadosStmt = $pdo->query($sqlCot);
$cotizados = $cotizadosStmt->fetchAll(PDO::FETCH_ASSOC);

// TOP productos más pedidos (solo pedidos Confirmados)
$sqlPed = "
    SELECT 
        e.nombre,
        SUM(dp.cantidad) AS total_pedido
    FROM detalle_pedido dp
    JOIN pedido p ON p.idpedido = dp.idpedido
    JOIN elemento e ON e.idelemento = dp.idproducto
    WHERE p.estadopedido = 'Confirmado'
    GROUP BY e.nombre
    ORDER BY total_pedido DESC
    LIMIT 5
";
$pedidosStmt = $pdo->query($sqlPed);
$pedidos = $pedidosStmt->fetchAll(PDO::FETCH_ASSOC);

// Asegurarnos de tener 5 filas para iterar “a la par”
$maxFilas = max(count($cotizados), count($pedidos));

// ==================== TABLA ====================
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(230, 230, 230);

// Encabezados
$pdf->Cell(90, 8, utf8_decode('Producto más Cotizado'), 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Cantidad', 1, 0, 'C', true);
$pdf->Cell(10, 8, '', 0); // espacio
$pdf->Cell(90, 8, utf8_decode('Producto en Pedidos Confirmados'), 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Cantidad', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 11);

for ($i = 0; $i < $maxFilas; $i++) {
    $cot = $cotizados[$i] ?? null;
    $ped = $pedidos[$i]  ?? null;

    // Columna cotizados
    $pdf->Cell(90, 8, utf8_decode($cot['nombre'] ?? '-'), 1);
    $pdf->Cell(25, 8, $cot['total_cotizado'] ?? '-', 1, 0, 'C');

    // espacio
    $pdf->Cell(10, 8, '', 0);

    // Columna pedidos
    $pdf->Cell(90, 8, utf8_decode($ped['nombre'] ?? '-'), 1);
    $pdf->Cell(25, 8, $ped['total_pedido'] ?? '-', 1, 1, 'C');
}

$pdf->Ln(10);

// ==================== ANÁLISIS DE VALOR ====================
$pdf->SetFont('Arial', '', 11);

$analisis  = "Analisis de valor para la administracion:\n\n";
$analisis .= "- Este cuadro compara los productos con mayor demanda en cotizaciones frente a los productos que mas se consolidan en pedidos confirmados.\n";
$analisis .= "- Si un producto aparece en el TOP de cotizaciones pero NO en el TOP de pedidos confirmados, puede indicar:\n";
$analisis .= "  * Problemas de precio (el cliente cotiza pero no compra).\n";
$analisis .= "  * Falta de stock o tiempos de entrega largos.\n";
$analisis .= "  * Competidores con mejores condiciones.\n\n";
$analisis .= "- Cuando un producto aparece en ambos rankings, es un candidato a considerarse producto estrategico: alta demanda y alta conversion.\n\n";
$analisis .= "Recomendaciones:\n";
$analisis .= "- Revisar margenes y disponibilidad de los productos muy cotizados pero poco pedidos.\n";
$analisis .= "- Priorizar reposicion de inventario y negociacion con proveedores de los productos que se ubican alto en pedidos confirmados.\n";
$analisis .= "- Diseñar promociones especificas para convertir mas cotizaciones en pedidos en los productos con brecha.\n";

$pdf->MultiCell(0, 6, utf8_decode($analisis));

$pdf->Output('I', 'Reporte_Top_Productos_Cot_vs_Ped.pdf');
exit;
