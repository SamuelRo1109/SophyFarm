<?php

namespace co\edu\unbosque\controller;

use co\edu\unbosque\service\CotizacionService;
use co\edu\unbosque\service\DetalleCotizacionService;
use co\edu\unbosque\middleware\AuthMiddleware;
use Exception;
use PDOException;

class CotizacionController
{
    /**
     * Crear cotización (cabecera)
     */
    public function create(array $vars)
    {
        global $pdo;
        // Validar token y obtener id de usuario
        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken(); // ajusta al nombre real de tu método

        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido']);
            return;
        }

        if (
            !isset($input['idCliente'])  || !is_numeric($input['idCliente'])  ||
            !isset($input['idVendedor']) || !is_numeric($input['idVendedor']) ||
            empty($input['fechaCotizacion'])
        ) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Cliente, Vendedor y Fecha de cotización son requeridos y deben ser válidos.'
            ]);
            return;
        }

        try {
            $service      = new CotizacionService($pdo);
            $cotizacionId = $service->createCotizacion($input, (int)$usuarioId);

            http_response_code(201);
            echo json_encode([
                'status'       => 'success',
                'idCotizacion' => $cotizacionId
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al crear la cotización',
                'error'   => $e->getMessage()
            ]);
        }
    }

    /**
     * Agregar detalle a una cotización
     */
    public function addDetail($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (!is_array($data)) {
                throw new Exception('JSON inválido');
            }

            $idCotizacion   = $data['idCotizacion']   ?? null;
            $idProducto     = $data['idProducto']     ?? null;
            $cantidad       = $data['cantidad']       ?? null;
            $precioUnitario = $data['precioUnitario'] ?? null;
            $valorSubtotal  = $data['valorSubtotal']  ?? null;

            if (
                !$idCotizacion || !$idProducto ||
                $cantidad === null || $precioUnitario === null || $valorSubtotal === null
            ) {
                throw new Exception('Faltan datos en la solicitud');
            }

            // Guardar detalle
            $detalleService = new DetalleCotizacionService($pdo);
            $detalle        = $detalleService->addDetailToCotizacion(
                $idCotizacion,
                $idProducto,
                $cantidad,
                $precioUnitario,
                $valorSubtotal
            );

            // Recalcular totales de la cabecera (con auditoría)
            $cotizacionService = new CotizacionService($pdo);
            $cotizacionService->recalculateTotals((int)$idCotizacion, (int)$usuarioId);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Detalle agregado correctamente',
                'data'    => $detalle
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al agregar el detalle',
                'error_details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener todas las cotizaciones
     */
    public function getAll()
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie(); // también protegido

        header('Content-Type: application/json; charset=utf-8');

        try {
            $service      = new CotizacionService($pdo);
            $cotizaciones = $service->getAllCotizaciones();

            echo json_encode([
                'status' => 'success',
                'data'   => $cotizaciones
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al obtener las cotizaciones',
                'error_details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cambiar estado de una cotización (P, A, C)
     * Acepta body JSON o x-www-form-urlencoded
     */
    public function changeStatus($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $idCotizacion = (int)$vars['id'];

            // Leer raw body
            $raw   = file_get_contents('php://input');
            $input = json_decode($raw, true);

            if (!is_array($input)) {
                // Si no es JSON, intentar parsear como query-string
                $parsed = [];
                parse_str($raw, $parsed);
                $input = $parsed;
            }

            $estadoCotizacion = $input['estadoCotizacion'] ?? ($_POST['estadoCotizacion'] ?? 'A');

            $service = new CotizacionService($pdo);
            $service->changeStatus($idCotizacion, $estadoCotizacion, (int)$usuarioId);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Estado de cotización actualizado'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al actualizar el estado de la cotización',
                'error_details' => $e->getMessage()
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
     * Eliminar cotización y sus detalles
     */
    public function delete($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $idCotizacion = (int)$vars['id'];

            // Primero eliminar detalles
            $detalleService = new DetalleCotizacionService($pdo);
            $detalleService->deleteDetails($idCotizacion);

            // Luego eliminar cabecera (con auditoría)
            $service = new CotizacionService($pdo);
            $service->deleteCotizacion($idCotizacion, (int)$usuarioId);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Cotización eliminada correctamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al eliminar la cotización',
                'error_details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Forzar recálculo de totales de una cotización
     */
    public function editCotizacion($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $idCotizacion = (int)$vars['id'];

            $service = new CotizacionService($pdo);
            $service->recalculateTotals($idCotizacion, (int)$usuarioId);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Cotización actualizada correctamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al actualizar la cotización',
                'error_details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener TODOS los detalles de una cotización
     */
    public function getDetailsByCotizacion($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $idCotizacion = (int)$vars['id'];

            $service  = new DetalleCotizacionService($pdo);
            $detalles = $service->getDetailsByCotizacion($idCotizacion);

            echo json_encode([
                'status' => 'success',
                'data'   => $detalles
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al obtener los detalles de la cotización',
                'error_details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar un detalle concreto
     */
    public function updateDetail($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $idDetalle = (int)$vars['id'];
            $data      = json_decode(file_get_contents('php://input'), true);

            if (!is_array($data)) {
                throw new Exception('JSON inválido');
            }

            $service = new DetalleCotizacionService($pdo);
            $service->updateDetail($idDetalle, [
                'cantidad'       => $data['cantidad'],
                'precioUnitario' => $data['precioUnitario'],
                'valorSubtotal'  => $data['valorSubtotal']
            ]);

            // Si viene idCotizacion en el body, recalculamos (con auditoría)
            if (!empty($data['idCotizacion'])) {
                $cotService = new CotizacionService($pdo);
                $cotService->recalculateTotals((int)$data['idCotizacion'], (int)$usuarioId);
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'Detalle actualizado correctamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al actualizar el detalle',
                'error_details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar un detalle concreto
     */
    public function deleteDetail($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $idDetalle = (int)$vars['id'];

            $service = new DetalleCotizacionService($pdo);
            $service->deleteDetail($idDetalle);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Detalle eliminado correctamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al eliminar el detalle',
                'error_details' => $e->getMessage()
            ]);
        }
    }
}
