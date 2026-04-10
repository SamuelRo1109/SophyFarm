<?php
// co.edu.unbosque.report/rpt_pedidos_cancelados.php

require_once __DIR__ . '/../co.edu.unbosque.config/DbConfig.php'; // $pdo
require_once __DIR__ . '/fpdf/fpdf.php';

header('Content-Type: application/pdf');

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('Reporte: Pedidos Cancelados por Cliente'), 0, 1, 'C');
$pdf->Ln(4);

// Fecha
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Fecha de generacion: ' . date('Y-m-d H:i'), 0, 1, 'R');
$pdf->Ln(4);

// =================== CONSULTA POR CLIENTE ===================
$sqlCliente = "
    SELECT 
        c.idcliente,
        COALESCE(c.razonsocial, c.primernombre || ' ' || c.primerapellido) AS cliente,
        COUNT(*) AS pedidos_cancelados,
        COALESCE(SUM(p.totalpedido), 0) AS valor_cancelado
    FROM pedido p
    JOIN cliente c ON c.idcliente = p.idcliente
    WHERE p.estadopedido = 'Cancelado'
    GROUP BY c.idcliente, cliente
    ORDER BY pedidos_cancelados DESC
";
$stmtCli = $pdo->query($sqlCliente);
$rowsCli = $stmtCli->fetchAll(PDO::FETCH_ASSOC);

// =================== TABLA POR CLIENTE ===================
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(230, 230, 230);

$pdf->Cell(20, 8, 'ID', 1, 0, 'C', true);
$pdf->Cell(80, 8, 'Cliente', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Cancelados', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Valor Cancelado', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);

if (empty($rowsCli)) {
    $pdf->Cell(170, 8, utf8_decode('No hay pedidos cancelados.'), 1, 1, 'C');
} else {
    foreach ($rowsCli as $r) {
        $pdf->Cell(20, 8, $r['idcliente'], 1);
        $pdf->Cell(80, 8, utf8_decode($r['cliente']), 1);
        $pdf->Cell(30, 8, $r['pedidos_cancelados'], 1, 0, 'C');
        $pdf->Cell(40, 8, number_format($r['valor_cancelado'], 0, ',', '.'), 1, 1, 'R');
    }
}

$pdf->Ln(8);

// =================== (OPCIONAL) POR VENDEDOR ===================
// Esto depende de que tu tabla cotizacion tenga idvendedor
// y que dicho idvendedor apunte a la tabla usuario.id
$rowsVend = [];
$vendOk   = true;

try {
    $sqlVend = "
        SELECT 
            u.id,
            u.username,
            COUNT(*) AS pedidos_cancelados,
            COALESCE(SUM(p.totalpedido), 0) AS valor_cancelado
        FROM pedido p
        JOIN cotizacion c ON c.idcotizacion = p.idcotizacion
        JOIN usuario u ON u.id = c.idvendedor
        WHERE p.estadopedido = 'Cancelado'
        GROUP BY u.id, u.username
        ORDER BY pedidos_cancelados DESC
    ";
    $stmtVend = $pdo->query($sqlVend);
    $rowsVend = $stmtVend->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $vendOk = false; // Si hay error de esquema, simplemente no mostramos esta tabla
}

if ($vendOk) {
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 8, utf8_decode('Resumen por Vendedor (opcional)'), 0, 1, 'L');
    $pdf->Ln(2);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(230, 230, 230);

    $pdf->Cell(20, 8, 'ID', 1, 0, 'C', true);
    $pdf->Cell(80, 8, 'Vendedor', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Cancelados', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Valor Cancelado', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 10);

    if (empty($rowsVend)) {
        $pdf->Cell(170, 8, utf8_decode('No hay pedidos cancelados asociados a vendedores.'), 1, 1, 'C');
    } else {
        foreach ($rowsVend as $v) {
            $pdf->Cell(20, 8, $v['id'], 1);
            $pdf->Cell(80, 8, utf8_decode($v['username']), 1);
            $pdf->Cell(30, 8, $v['pedidos_cancelados'], 1, 0, 'C');
            $pdf->Cell(40, 8, number_format($v['valor_cancelado'], 0, ',', '.'), 1, 1, 'R');
        }
    }

    $pdf->Ln(8);
}

// =================== ANÁLISIS ===================
$pdf->SetFont('Arial', '', 11);

$analisis  = "Analisis de valor para la administracion:\n\n";
$analisis .= "- Este reporte identifica los clientes que mas pedidos cancelan, asi como el monto total asociado a dichas cancelaciones.\n";
$analisis .= "- Una concentracion alta de cancelaciones en pocos clientes puede indicar:\n";
$analisis .= "  * Problemas de comunicacion en las condiciones del pedido.\n";
$analisis .= "  * Clientes con mala planeacion o poca seriedad.\n";
$analisis .= "  * Errores internos (fechas, cantidades, referencias incorrectas).\n\n";
$analisis .= "Recomendaciones:\n";
$analisis .= "- Contactar a los clientes con mas cancelaciones y entender las causas.\n";
$analisis .= "- Definir politicas claras de confirmacion y tiempos maximos para cancelar.\n";
$analisis .= "- Si el subreporte por vendedor esta disponible, revisar si hay patrones de cancelacion asociados a algun usuario especifico.\n";

$pdf->MultiCell(0, 6, utf8_decode($analisis));

$pdf->Output('I', 'Reporte_Pedidos_Cancelados.pdf');
exit;
