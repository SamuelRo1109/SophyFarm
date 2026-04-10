<?php
require('fpdf.php');
include '../conexion.php';

// Obtener datos
$totalCotizaciones = $conexion->query("SELECT COUNT(*) AS total FROM Cotizacion")->fetch_assoc()['total'];
$totalPedidos = $conexion->query("SELECT COUNT(*) AS total FROM Pedido")->fetch_assoc()['total'];
$conPedido = $conexion->query("
    SELECT COUNT(DISTINCT c.idCotizacion) AS total
    FROM Cotizacion c
    INNER JOIN Pedido p ON c.idCotizacion = p.idCotizacion
")->fetch_assoc()['total'];
$sinPedido = $totalCotizaciones - $conPedido;

// Crear PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Reporte: Cotizaciones vs Pedidos',0,1,'C');
$pdf->Ln(10);

$pdf->SetFont('Arial','',12);
$pdf->Cell(80,10,'Total Cotizaciones:',1,0);
$pdf->Cell(40,10,$totalCotizaciones,1,1);
$pdf->Cell(80,10,'Cotizaciones con Pedido:',1,0);
$pdf->Cell(40,10,$conPedido,1,1);
$pdf->Cell(80,10,'Cotizaciones sin Pedido:',1,0);
$pdf->Cell(40,10,$sinPedido,1,1);
$pdf->Cell(80,10,'Total Pedidos:',1,0);
$pdf->Cell(40,10,$totalPedidos,1,1);

$pdf->Ln(10);
$pdf->MultiCell(0,8,utf8_decode(
"Análisis:\n" .
"Este reporte permite evaluar la eficiencia del proceso comercial midiendo cuántas cotizaciones se convierten finalmente en pedidos. " .
"Un alto número de cotizaciones sin pedido puede indicar fallas en el seguimiento al cliente, precios poco competitivos o falta de disponibilidad de productos. " .
"Por otro lado, un equilibrio entre cotizaciones y pedidos sugiere una buena gestión comercial y una comunicación efectiva con los clientes.\n\n" .
"Recomendaciones:\n" .
"- Revisar las causas de las cotizaciones no convertidas en pedidos (precio, tiempos de entrega, disponibilidad, etc.).\n" .
"- Implementar recordatorios o seguimientos automáticos para mejorar la tasa de conversión.\n" .
"- Analizar el desempeño de los vendedores según su tasa de conversión para detectar oportunidades de capacitación."
));


$pdf->Output('I','Reporte_Cotizaciones_vs_Pedidos.pdf');
?>
