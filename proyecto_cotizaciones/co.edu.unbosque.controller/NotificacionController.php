<?php

namespace co\edu\unbosque\controller;

use Exception;
use co\edu\unbosque\middleware\AuthMiddleware;
use co\edu\unbosque\service\NotificacionService;

class NotificacionController
{
    /**
     * Enviar cotización por correo
     * POST /co.edu.unbosque.notificacion/cotizacion/{id}/email
     */
    public function sendCotizacionEmail(array $vars)
    {
        global $pdo;

        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $idCotizacion = (int)($vars['id'] ?? 0);
            if ($idCotizacion <= 0) {
                throw new Exception('ID de cotización inválido.');
            }

            $service = new NotificacionService($pdo);
            $result  = $service->enviarCotizacionPorCorreo($idCotizacion, (int)$usuarioId);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Notificación de cotización enviada por correo.',
                'data'    => $result
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generar enlace de WhatsApp para cotización aprobada
     * POST /co.edu.unbosque.notificacion/cotizacion/{id}/whatsapp
     */
    public function sendCotizacionWhatsApp(array $vars)
    {
        global $pdo;

        AuthMiddleware::validateJwtOrDie();
        header('Content-Type: application/json; charset=utf-8');

        $idCot = (int)($vars['id'] ?? 0);
        if ($idCot <= 0) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'ID de cotización inválido'
            ]);
            return;
        }

        try {
            $service = new NotificacionService($pdo);
            $data    = $service->enviarCotizacionPorWhatsApp($idCot);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Notificación de cotización generada para WhatsApp.',
                'data'    => $data
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al generar la notificación de WhatsApp.',
                'error_details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar pedido confirmado por correo
     * POST /co.edu.unbosque.notificacion/pedido/{id}/email
     */
    public function sendPedidoEmail(array $vars)
    {
        global $pdo;

        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $idPedido = (int)($vars['id'] ?? 0);
            if ($idPedido <= 0) {
                throw new Exception('ID de pedido inválido.');
            }

            $service = new NotificacionService($pdo);
            $result  = $service->enviarPedidoPorCorreo($idPedido, (int)$usuarioId);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Notificación de pedido enviada por correo.',
                'data'    => $result
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generar enlace de WhatsApp para pedido confirmado
     * POST /co.edu.unbosque.notificacion/pedido/{id}/whatsapp
     */
    public function sendPedidoWhatsApp(array $vars)
    {
        global $pdo;

        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $idPedido = (int)($vars['id'] ?? 0);
            if ($idPedido <= 0) {
                throw new Exception('ID de pedido inválido.');
            }

            $service = new NotificacionService($pdo);
            $result  = $service->enviarPedidoPorWhatsApp($idPedido, (int)$usuarioId);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Notificación de pedido generada para WhatsApp.',
                'data'    => $result
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
