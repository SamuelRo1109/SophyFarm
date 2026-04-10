<?php

namespace co\edu\unbosque\service;

use PDO;
use DateTime;
use Exception;
use co\edu\unbosque\model\Notificacion;
use co\edu\unbosque\model\Client;

// Cargar Composer (PHPMailer viene por aquí)
require_once dirname(__DIR__) . '/vendor/autoload.php';

// FPDF
require_once dirname(__DIR__) . '/co.edu.unbosque.report/fpdf/fpdf.php';

// PHPMailer vía Composer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class NotificacionService
{
    private PDO $pdo;

    /** Carpeta física donde se guardarán los PDFs */
    private string $pdfDir;

    /** Prefijo público (URL) para acceder a los PDFs desde el navegador */
    private string $pdfPublicBase;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        // Ruta física donde se guardan los PDF
        $this->pdfDir = dirname(__DIR__) . '/co.edu.unbosque.report/pdf';
        if (!is_dir($this->pdfDir)) {
            @mkdir($this->pdfDir, 0775, true);
        }

        /**
         * URL pública base para los PDFs
         * Tomamos el host real de la petición (localhost, 192.168.x.x, dominio, etc.)
         * para que el enlace funcione igual desde el navegador y desde WhatsApp.
         */
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';   // ej: localhost, 192.168.0.10, midominio.com
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        // Base del proyecto en web
        $baseUrl = $scheme . '://' . $host . '/proyectoFinalIS2';

        // Carpeta de reportes
        $this->pdfPublicBase = $baseUrl . '/co.edu.unbosque.report/pdf';
    }

    /* ===========================================================
       ===============   COTIZACIONES   ===========================
       =========================================================== */

    public function enviarCotizacionPorCorreo(int $idCotizacion, int $usuarioId): array
    {
        $cotService = new CotizacionService($this->pdo);
        $detService = new DetalleCotizacionService($this->pdo);

        $cotizacion = $cotService->getCotizacionWithCliente($idCotizacion);
        if (!$cotizacion) {
            throw new Exception("La cotización #{$idCotizacion} no existe.");
        }

        $estadoRaw = $cotizacion['estadocotizacion'] ?? $cotizacion['estadoCotizacion'] ?? 'P';
        if ($estadoRaw !== 'A') {
            throw new Exception("Solo se pueden notificar cotizaciones en estado APROBADA.");
        }

        $idCliente = $cotizacion['idcliente'] ?? $cotizacion['idCliente'] ?? null;
        $correo    = $cotizacion['correo'] ?? null;

        if (!$correo) {
            throw new Exception("El cliente asociado a la cotización no tiene correo registrado.");
        }

        $detalles = $detService->getDetailsByCotizacion($idCotizacion);

        [$pdfPath, $pdfUrl] = $this->generarPdfCotizacion($cotizacion, $detalles);

        $totalNeto = $cotizacion['valorneto'] ?? $cotizacion['valorNeto'] ?? 0;
        $subject   = "Detalle de su cotización #{$idCotizacion}";
        $body      = "Estimado(a),\n\nAdjuntamos el detalle de su cotización #{$idCotizacion}.\n" .
                     "Valor neto: $" . $totalNeto . "\n\nGracias por confiar en nosotros.";

        $this->enviarCorreoConAdjunto($correo, $subject, $body, $pdfPath);

        $contenido = "Envío de cotización #{$idCotizacion} al correo {$correo}.";
        $idNotif   = $this->registrarNotificacion((int)$idCliente, 'Cotización', $contenido);

        return [
            'idNotificacion' => $idNotif,
            'idCotizacion'   => $idCotizacion,
            'correo'         => $correo,
            'pdfPath'        => $pdfPath,
            'pdfUrl'         => $pdfUrl
        ];
    }

    public function enviarCotizacionPorWhatsApp(int $idCotizacion): array
    {
        $cotService = new CotizacionService($this->pdo);
        $detService = new DetalleCotizacionService($this->pdo);

        $cotizacion = $cotService->getCotizacionWithCliente($idCotizacion);
        if (!$cotizacion) {
            throw new Exception("La cotización #{$idCotizacion} no existe.");
        }

        $estadoRaw = $cotizacion['estadocotizacion'] ?? $cotizacion['estadoCotizacion'] ?? 'P';
        if ($estadoRaw !== 'A') {
            throw new Exception("Solo se pueden notificar cotizaciones en estado APROBADA.");
        }

        $idCliente = (int)($cotizacion['idcliente'] ?? $cotizacion['idCliente'] ?? 0);
        if ($idCliente <= 0) {
            throw new Exception("La cotización no tiene cliente asociado.");
        }

        // Teléfono del cliente (normalizado a formato WhatsApp)
        $telefono = $this->normalizarTelefono($cotizacion['telefono'] ?? '');
        if ($telefono === '') {
            throw new Exception("El cliente asociado a la cotización no tiene teléfono registrado o válido.");
        }

        $detalles = $detService->getDetailsByCotizacion($idCotizacion);
        [$pdfPath, $pdfUrl] = $this->generarPdfCotizacion($cotizacion, $detalles);

        $valorNeto     = $cotizacion['valorneto'] ?? $cotizacion['valorNeto'] ?? 0;
        $nombreCliente = $this->obtenerNombreCliente($cotizacion);

        $mensaje  = "Hola {$nombreCliente}, te compartimos el detalle de tu cotización #{$idCotizacion}.";
        $mensaje .= "\nValor neto: $" . number_format((float)$valorNeto, 2, ',', '.');
        $mensaje .= "\n\nPuedes ver el PDF aquí: {$pdfUrl}";

        $contenidoLog = "Generación de enlace WhatsApp para cotización #{$idCotizacion} al número {$telefono}.";
        $idNotif      = $this->registrarNotificacion($idCliente, 'Cotización', $contenidoLog);

        $waUrl = "https://wa.me/{$telefono}?text=" . rawurlencode($mensaje);

        return [
            'idNotificacion' => $idNotif,
            'idCotizacion'   => $idCotizacion,
            'telefono'       => $telefono,
            'waUrl'          => $waUrl,
            'pdfPath'        => $pdfPath,
            'pdfUrl'         => $pdfUrl,
            'mensaje'        => $mensaje,
        ];
    }

    /* ===========================================================
       ==================   PEDIDOS   ============================
       =========================================================== */

    public function enviarPedidoPorCorreo(int $idPedido, int $usuarioId): array
    {
        $pedService = new PedidoService($this->pdo);
        $detService = new DetallePedidoService($this->pdo);

        $pedido = $pedService->getPedidoWithCliente($idPedido);
        if (!$pedido) {
            throw new Exception("El pedido #{$idPedido} no existe.");
        }

        $estado = $pedido['estadopedido'] ?? $pedido['estadoPedido'] ?? '';
        if ($estado !== 'Confirmado') {
            throw new Exception("Solo se pueden notificar pedidos en estado CONFIRMADO.");
        }

        $idCliente = $pedido['idcliente'] ?? $pedido['idCliente'] ?? null;
        $correo    = $pedido['correo'] ?? null;

        if (!$correo) {
            throw new Exception("El cliente asociado al pedido no tiene correo registrado.");
        }

        $detalles = $detService->getDetailsByPedido($idPedido);
        [$pdfPath, $pdfUrl] = $this->generarPdfPedido($pedido, $detalles);

        $total   = $pedido['totalpedido'] ?? $pedido['totalPedido'] ?? 0;
        $subject = "Confirmación de su pedido #{$idPedido}";
        $body    = "Estimado(a),\n\nAdjuntamos el detalle de su pedido #{$idPedido} CONFIRMADO.\n" .
                   "Total del pedido: $" . $total . "\n\nGracias por su compra.";

        $this->enviarCorreoConAdjunto($correo, $subject, $body, $pdfPath);

        $contenido = "Envío de pedido confirmado #{$idPedido} al correo {$correo}.";
        $idNotif   = $this->registrarNotificacion((int)$idCliente, 'Pedido', $contenido);

        return [
            'idNotificacion' => $idNotif,
            'idPedido'       => $idPedido,
            'correo'         => $correo,
            'pdfPath'        => $pdfPath,
            'pdfUrl'         => $pdfUrl
        ];
    }

    public function enviarPedidoPorWhatsApp(int $idPedido, int $usuarioId): array
    {
        $pedService = new PedidoService($this->pdo);
        $detService = new DetallePedidoService($this->pdo);

        $pedido = $pedService->getPedidoWithCliente($idPedido);
        if (!$pedido) {
            throw new Exception("El pedido #{$idPedido} no existe.");
        }

        $estado = $pedido['estadopedido'] ?? $pedido['estadoPedido'] ?? '';
        if ($estado !== 'Confirmado') {
            throw new Exception("Solo se pueden notificar pedidos en estado CONFIRMADO.");
        }

        $idCliente = $pedido['idcliente'] ?? $pedido['idCliente'] ?? null;

        $telefono = $this->normalizarTelefono($pedido['telefono'] ?? '');
        if ($telefono === '') {
            throw new Exception("El cliente asociado al pedido no tiene teléfono registrado o válido.");
        }

        $detalles = $detService->getDetailsByPedido($idPedido);
        [$pdfPath, $pdfUrl] = $this->generarPdfPedido($pedido, $detalles);

        $total         = $pedido['totalpedido'] ?? $pedido['totalPedido'] ?? 0;
        $nombreCliente = $this->obtenerNombreCliente($pedido);

        $mensaje = "Hola {$nombreCliente}, te compartimos la confirmación de tu pedido #{$idPedido}.\n" .
                   "Total: $" . $total . "\n\n" .
                   "Puedes ver el PDF aquí: {$pdfUrl}";

        $waUrl = "https://wa.me/{$telefono}?text=" . urlencode($mensaje);

        $contenido = "Generación de enlace WhatsApp para pedido #{$idPedido} al número {$telefono}.";
        $idNotif   = $this->registrarNotificacion((int)$idCliente, 'Pedido', $contenido);

        return [
            'idNotificacion' => $idNotif,
            'idPedido'       => $idPedido,
            'telefono'       => $telefono,
            'waUrl'          => $waUrl,
            'pdfPath'        => $pdfPath,
            'pdfUrl'         => $pdfUrl,
            'mensaje'        => $mensaje,
        ];
    }

    /* ===========================================================
       ==================   HELPERS   ============================
       =========================================================== */

    private function registrarNotificacion(int $idCliente, string $tipo, string $contenido): int
    {
        $fecha = (new DateTime())->format('Y-m-d H:i:s');

        return Notificacion::crear($this->pdo, [
            'idCliente'        => $idCliente,
            'tipoNotificacion' => $tipo,
            'contenido'        => $contenido,
            'fechaEnvio'       => $fecha,
            'estado'           => 'E'  // Enviado / generado
        ]);
    }

    private function obtenerNombreCliente(array $row): string
    {
        if (!empty($row['razonsocial'] ?? $row['razonSocial'] ?? '')) {
            return $row['razonsocial'] ?? $row['razonSocial'];
        }

        $pn = $row['primernombre'] ?? $row['primerNombre'] ?? '';
        $pa = $row['primerapellido'] ?? $row['primerApellido'] ?? '';
        $nombre = trim($pn . ' ' . $pa);

        if ($nombre === '') {
            $idCli = $row['idcliente'] ?? $row['idCliente'] ?? '';
            return "Cliente #{$idCli}";
        }

        return $nombre;
    }

    /**
     * Normaliza el teléfono a formato válido para wa.me:
     * - Solo dígitos
     * - Incluye código de país
     * - Para Colombia: si tiene 10 dígitos, se antepone '57'
     */
    private function normalizarTelefono(?string $telefonoRaw): string
    {
        $tel = preg_replace('/\D/', '', (string)$telefonoRaw);
        if ($tel === '') {
            return '';
        }

        // Si ya empieza por 57 (Colombia) y tiene al menos 11 dígitos, lo dejamos
        if (substr($tel, 0, 2) === '57' && strlen($tel) >= 11) {
            return $tel;
        }

        // Si es un celular colombiano típico de 10 dígitos, le agregamos 57
        if (preg_match('/^\d{10}$/', $tel)) {
            return '57' . $tel;
        }

        // En cualquier otro caso devolvemos tal cual (pero limpio)
        return $tel;
    }

    private function generarPdfCotizacion(array $cotizacion, array $detalles): array
    {
        $idCot    = $cotizacion['idcotizacion'] ?? $cotizacion['idCotizacion'];
        $fileName = "cotizacion_{$idCot}.pdf";
        $filePath = $this->pdfDir . '/' . $fileName;
        $fileUrl  = $this->pdfPublicBase . '/' . $fileName;

        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, utf8_decode("Cotización #{$idCot}"), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 10);
        $nombreCliente = $this->obtenerNombreCliente($cotizacion);
        $fecha         = $cotizacion['fechacotizacion'] ?? $cotizacion['fechaCotizacion'] ?? '';

        $pdf->Cell(0, 6, utf8_decode("Cliente: {$nombreCliente}"), 0, 1);
        $pdf->Cell(0, 6, utf8_decode("Fecha: {$fecha}"), 0, 1);
        $pdf->Ln(4);

        $valorBruto = $cotizacion['valorbruto'] ?? $cotizacion['valorBruto'] ?? 0;
        $valorDesc  = $cotizacion['valordescuento'] ?? $cotizacion['valorDescuento'] ?? 0;
        $valorIva   = $cotizacion['valoriva'] ?? $cotizacion['valorIVA'] ?? 0;
        $valorNeto  = $cotizacion['valorneto'] ?? $cotizacion['valorNeto'] ?? 0;

        $pdf->Cell(0, 6, utf8_decode("Valor bruto: $" . $valorBruto), 0, 1);
        $pdf->Cell(0, 6, utf8_decode("Descuento: $" . $valorDesc), 0, 1);
        $pdf->Cell(0, 6, utf8_decode("IVA: $" . $valorIva), 0, 1);
        $pdf->Cell(0, 6, utf8_decode("Valor neto: $" . $valorNeto), 0, 1);
        $pdf->Ln(6);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 7, utf8_decode('Producto'), 1);
        $pdf->Cell(20, 7, utf8_decode('Cant.'), 1, 0, 'R');
        $pdf->Cell(40, 7, utf8_decode('Precio Unit.'), 1, 0, 'R');
        $pdf->Cell(40, 7, utf8_decode('Subtotal'), 1, 0, 'R');
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 9);

        foreach ($detalles as $det) {
            $nombreProd = $det['nombreproducto'] ?? $det['nombreProducto'] ?? ('Producto #' . ($det['idproducto'] ?? $det['idProducto']));
            $cant       = $det['cantidad'];
            $precio     = $det['preciounitario'] ?? $det['precioUnitario'];
            $sub        = $det['valorsubtotal'] ?? $det['valorSubtotal'];

            $pdf->Cell(60, 6, utf8_decode($nombreProd), 1);
            $pdf->Cell(20, 6, number_format((float)$cant, 2), 1, 0, 'R');
            $pdf->Cell(40, 6, number_format((float)$precio, 2), 1, 0, 'R');
            $pdf->Cell(40, 6, number_format((float)$sub, 2), 1, 0, 'R');
            $pdf->Ln();
        }

        $pdf->Output('F', $filePath);
        return [$filePath, $fileUrl];
    }

    private function generarPdfPedido(array $pedido, array $detalles): array
    {
        $idPed    = $pedido['idpedido'] ?? $pedido['idPedido'];
        $fileName = "pedido_{$idPed}.pdf";
        $filePath = $this->pdfDir . '/' . $fileName;
        $fileUrl  = $this->pdfPublicBase . '/' . $fileName;

        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, utf8_decode("Pedido #{$idPed} (Confirmado)"), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 10);
        $nombreCliente = $this->obtenerNombreCliente($pedido);
        $fecha         = $pedido['fechapedido'] ?? $pedido['fechaPedido'] ?? '';

        $pdf->Cell(0, 6, utf8_decode("Cliente: {$nombreCliente}"), 0, 1);
        $pdf->Cell(0, 6, utf8_decode("Fecha pedido: {$fecha}"), 0, 1);

        $total = $pedido['totalpedido'] ?? $pedido['totalPedido'] ?? 0;
        $pdf->Cell(0, 6, utf8_decode("Total pedido: $" . $total), 0, 1);
        $pdf->Ln(6);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 7, utf8_decode('Producto'), 1);
        $pdf->Cell(20, 7, utf8_decode('Cant.'), 1, 0, 'R');
        $pdf->Cell(40, 7, utf8_decode('Precio Unit.'), 1, 0, 'R');
        $pdf->Cell(40, 7, utf8_decode('Subtotal'), 1, 0, 'R');
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 9);

        foreach ($detalles as $det) {
            $nombreProd = $det['nombreproducto'] ?? $det['nombreProducto'] ?? ('Producto #' . ($det['idproducto'] ?? $det['idProducto']));
            $cant       = $det['cantidad'];
            $precio     = $det['preciounitario'] ?? $det['precioUnitario'];
            $sub        = $det['valorsubtotal'] ?? $det['valorSubtotal'];

            $pdf->Cell(60, 6, utf8_decode($nombreProd), 1);
            $pdf->Cell(20, 6, number_format((float)$cant, 2), 1, 0, 'R');
            $pdf->Cell(40, 6, number_format((float)$precio, 2), 1, 0, 'R');
            $pdf->Cell(40, 6, number_format((float)$sub, 2), 1, 0, 'R');
            $pdf->Ln();
        }

        $pdf->Output('F', $filePath);
        return [$filePath, $fileUrl];
    }

    private function enviarCorreoConAdjunto(
        string $para,
        string $asunto,
        string $cuerpo,
        string $pdfPath,
        ?string $pdfName = null
    ): void {
        $mail = new PHPMailer(true);

        if ($pdfName === null) {
            $pdfName = basename($pdfPath);
        }

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'nominaservicio6@gmail.com';
            $mail->Password   = 'fssyeaehormgoyql'; // app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('nominaservicio6@gmail.com', 'Nombre de la empresa');
            $mail->addAddress($para);

            $mail->isHTML(false);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpo;

            if (is_readable($pdfPath)) {
                $mail->addAttachment($pdfPath, $pdfName);
            } else {
                error_log("No se pudo leer el PDF a adjuntar: $pdfPath");
            }

            $mail->send();
        } catch (PHPMailerException $e) {
            error_log('Error al enviar correo: ' . $e->getMessage());
            throw new \RuntimeException('No se pudo enviar el correo.');
        }
    }
}
