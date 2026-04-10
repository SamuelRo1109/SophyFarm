<?php
require('fpdf.php');
include '../conexion.php';

// Consultas
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

// Crear PDF
$pdf = new FPDF('L', 'mm', 'A4'); // 'L' = horizontal (landscape)
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('Reporte: Productos más Cotizados y Pedidos'), 0, 1, 'C');
$pdf->Ln(6);

// Encabezados
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(90, 10, utf8_decode('Producto Cotizado'), 1, 0, 'C', true);
$pdf->Cell(25, 10, 'Cantidad', 1, 0, 'C', true);
$pdf->Cell(10, 10, '', 0);
$pdf->Cell(90, 10, utf8_decode('Producto Pedido'), 1, 0, 'C', true);
$pdf->Cell(25, 10, 'Cantidad', 1, 1, 'C', true);

// Cuerpo
$pdf->SetFont('Arial', '', 11);
while ($cot = $cotizados->fetch_assoc()) {
    $ped = $pedidos->fetch_assoc();
    $pdf->Cell(90, 9, utf8_decode($cot['nombre']), 1);
    $pdf->Cell(25, 9, $cot['total_cotizado'], 1, 0, 'C');
    $pdf->Cell(10, 9, '', 0);
    $pdf->Cell(90, 9, utf8_decode($ped['nombre'] ?? '-'), 1);
    $pdf->Cell(25, 9, $ped['total_pedido'] ?? '-', 1, 1, 'C');
}

$pdf->Ln(10);
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0,8,utf8_decode(
"Análisis:\n" .
"Este reporte muestra los productos con mayor demanda tanto en cotizaciones como en pedidos, lo que permite analizar las tendencias del mercado. " .
"Si un producto se cotiza mucho pero se pide poco, puede indicar precios altos, falta de disponibilidad o sustitutos más accesibles. " .
"Por el contrario, los productos más cotizados y también más pedidos reflejan buena aceptación del mercado y deben considerarse estratégicos.\n\n" .
"Recomendaciones:\n" .
"- Priorizar el reabastecimiento de productos con alta rotación en pedidos.\n" .
"- Revisar precios o estrategias de promoción para los productos muy cotizados pero poco pedidos.\n" .
"- Identificar oportunidades para ampliar líneas exitosas o mejorar márgenes en artículos de alta demanda."
));


$pdf->Output('I', 'Reporte_Productos_Top.pdf');
?>
