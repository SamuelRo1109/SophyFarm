<?php

namespace co\edu\unbosque\controller;

use co\edu\unbosque\middleware\AuthMiddleware;
use co\edu\unbosque\service\PedidoService;
use co\edu\unbosque\service\DetallePedidoService;
use Exception;
use PDOException;

class PedidoController
{
    /**
     * Crear pedido (solo cabecera)
     * POST /co.edu.unbosque.pedido
     * Body JSON: { "idCotizacion": 1, "fechaPedido": "2025-11-20" }
     */
    public function create($vars)
    {
        global $pdo;

        // Igual que en CotizacionController
        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!is_array($input)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'JSON inválido']);
                return;
            }

            if (empty($input['idCotizacion']) || empty($input['fechaPedido'])) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'idCotizacion y fechaPedido son requeridos'
                ]);
                return;
            }

            $service   = new PedidoService($pdo);
            // 👉 ahora pasamos el usuario que hace la operación
            $idPedido  = $service->createPedido($input, (int)$usuarioId);

            http_response_code(201);
            echo json_encode([
                'status'   => 'success',
                'idPedido' => $idPedido
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al crear el pedido',
                'error'   => $e->getMessage()
            ]);
        }
    }

    /**
     * Agregar detalle a un pedido
     * POST /co.edu.unbosque.detallepedido
     * Body JSON: { idPedido, idProducto, cantidad, precioUnitario, valorSubtotal }
     */
    public function addDetail($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie(); // si quieres, aquí podrías también auditar
        header('Content-Type: application/json; charset=utf-8');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!is_array($data)) {
                throw new Exception('JSON inválido');
            }

            $idPedido      = $data['idPedido']      ?? null;
            $idProducto    = $data['idProducto']    ?? null;
            $cantidad      = $data['cantidad']      ?? null;
            $precioUnitario= $data['precioUnitario']?? null;
            $valorSubtotal = $data['valorSubtotal'] ?? null;

            if (
                !$idPedido || !$idProducto ||
                $cantidad === null || $precioUnitario === null || $valorSubtotal === null
            ) {
                throw new Exception('Faltan datos en la solicitud de detalle de pedido');
            }

            $detalleService = new DetallePedidoService($pdo);
            $detalle        = $detalleService->addDetailToPedido(
                (int)$idPedido,
                (int)$idProducto,
                (int)$cantidad,
                (float)$precioUnitario,
                (float)$valorSubtotal
            );

            // Recalcular total del pedido
            $pedidoService = new PedidoService($pdo);
            $pedidoService->recalculateTotals((int)$idPedido);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Detalle de pedido agregado correctamente',
                'data'    => $detalle
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
     * Listar pedidos (para la tabla en pedido.html)
     */
    public function getAll($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $service = new PedidoService($pdo);
            $pedidos = $service->getAllPedidos();

            echo json_encode([
                'status' => 'success',
                'data'   => $pedidos
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al obtener los pedidos',
                'error_details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cambiar estado del pedido
     * PUT /co.edu.unbosque.pedido/{id}
     * Body JSON: { "estadoPedido": "Confirmado" }
     */
    public function changeStatus($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $idPedido = (int)$vars['id'];
            $input    = json_decode(file_get_contents('php://input'), true);

            if (!is_array($input) || empty($input['estadoPedido'])) {
                throw new Exception('estadoPedido es requerido');
            }

            $service = new PedidoService($pdo);
            // 👉 también le pasamos quién hizo el cambio
            $result  = $service->changeStatus($idPedido, $input['estadoPedido'], (int)$usuarioId);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Estado de pedido actualizado',
                'data'    => $result   // aquí vienen stocks y estados
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
     * Eliminar pedido (y sus detalles)
     * DELETE /co.edu.unbosque.pedido/{id}
     */
    public function delete($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $idPedido = (int)$vars['id'];

            // 1. Eliminar detalles (devolviendo stock si tu lógica lo hace en DetallePedidoService)
            $detalleService = new DetallePedidoService($pdo);
            $detalleService->deleteDetails($idPedido);

            // 2. Eliminar cabecera (y auditar dentro del service)
            $pedidoService = new PedidoService($pdo);
            $pedidoService->deletePedido($idPedido, (int)$usuarioId);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Pedido eliminado correctamente'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al eliminar el pedido',
                'error_details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener detalles de un pedido (para editPedido.html)
     * GET /co.edu.unbosque.detallepedido/{idPedido}
     */
    public function getDetailsByPedido($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $idPedido = (int)$vars['id'];

            $service  = new DetallePedidoService($pdo);
            $detalles = $service->getDetailsByPedido($idPedido);

            echo json_encode([
                'status' => 'success',
                'data'   => $detalles
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al obtener los detalles del pedido',
                'error_details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar un detalle de pedido
     * PUT /co.edu.unbosque.detallepedido/{idDetalle}
     */
    public function updateDetail($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $idDetalle = (int)$vars['id'];
            $data      = json_decode(file_get_contents('php://input'), true);

            if (!is_array($data)) {
                throw new Exception('JSON inválido');
            }

            $detalleService = new DetallePedidoService($pdo);
            $detalleService->updateDetail($idDetalle, $data);

            // Si viene idPedido en el body, recalculamos total
            if (!empty($data['idPedido'])) {
                $pedidoService = new PedidoService($pdo);
                $pedidoService->recalculateTotals((int)$data['idPedido']);
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'Detalle de pedido actualizado correctamente'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al actualizar el detalle de pedido',
                'error_details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar un detalle de pedido
     * DELETE /co.edu.unbosque.detallepedido/{idDetalle}
     */
    public function deleteDetail($vars)
    {
        global $pdo;
        AuthMiddleware::validateJwtOrDie();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $idDetalle = (int)$vars['id'];

            $detalleService = new DetallePedidoService($pdo);
            $detalleService->deleteDetail($idDetalle);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Detalle de pedido eliminado correctamente'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al eliminar el detalle de pedido',
                'error_details' => $e->getMessage()
            ]);
        }
    }
}
