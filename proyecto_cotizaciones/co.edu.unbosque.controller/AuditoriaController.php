<?php

namespace co\edu\unbosque\controller;

use co\edu\unbosque\middleware\AuthMiddleware;
use co\edu\unbosque\service\AuditoriaService;
use Exception;

class AuditoriaController
{
    public function getAll($vars)
    {
        global $pdo;

        header('Content-Type: application/json; charset=utf-8');

        try {
            AuthMiddleware::validateJwtOrDie();

            $usuario = $_GET['usuario'] ?? null;
            $accion  = $_GET['accion']  ?? null;
            $desde   = $_GET['desde']   ?? null;
            $hasta   = $_GET['hasta']   ?? null;

            $usuario = ($usuario !== '') ? (int)$usuario : null;
            $accion  = ($accion  !== '') ? $accion       : null;
            $desde   = ($desde   !== '') ? $desde        : null;
            $hasta   = ($hasta   !== '') ? $hasta        : null;

            $service = new AuditoriaService($pdo);
            $rows    = $service->getAuditoria($usuario, $accion, $desde, $hasta);

            echo json_encode([
                'status' => 'success',
                'data'   => $rows
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al obtener la auditoría',
                'error_details' => $e->getMessage()
            ]);
        }
    }
}
