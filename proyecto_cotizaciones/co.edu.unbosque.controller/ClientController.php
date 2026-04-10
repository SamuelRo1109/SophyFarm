<?php

namespace co\edu\unbosque\controller;

use co\edu\unbosque\model\User;
use co\edu\unbosque\model\TipoDocumento;
use co\edu\unbosque\service\ClientService;
use co\edu\unbosque\middleware\AuthMiddleware;
use InvalidArgumentException;
use PDOException;

class ClientController
{
    public function getAll(array $vars)
    {
        global $pdo;

        AuthMiddleware::validateJwtOrDie();

        header('Content-Type: application/json; charset=utf-8');

        $search = isset($vars['search']) ? $vars['search'] : '';

        error_log("Recibiendo parámetros: Búsqueda: {$search}");

        try {
            $service = new ClientService($pdo);
            $clients = $service->getAllClients(0, 0, $search);

            echo json_encode([
                'status' => 'success',
                'data'   => $clients,
            ]);
        } catch (PDOException $e) {
            error_log("Error en la consulta: " . $e->getMessage());

            http_response_code(500);
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Error al obtener clientes',
                'error_details' => $e->getMessage()
            ]);
        }
    }

    // Tipos de documento
    public function getTipoDocumentos()
    {
        global $pdo;

        AuthMiddleware::validateJwtOrDie();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $tipoDocumentos = TipoDocumento::getActivos();

            echo json_encode([
                'status' => 'success',
                'data'   => $tipoDocumentos
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al obtener los tipos de documento'
            ]);
        }
    }

    public function getOne(array $vars)
    {
        global $pdo;

        AuthMiddleware::validateJwtOrDie();

        header('Content-Type: application/json; charset=utf-8');

        $id = isset($vars['id']) ? (int)$vars['id'] : 0;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
            return;
        }

        try {
            $service = new ClientService($pdo);
            $client  = $service->getClient($id);

            if (!$client) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado']);
                return;
            }

            echo json_encode(['status' => 'success', 'data' => $client]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al obtener el cliente']);
        }
    }

    public function create(array $vars)
    {
        global $pdo;

        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true);

        // Puede venir un arreglo de clientes o un solo cliente
        if (is_array($input) && isset($input[0])) {
            // Array de clientes
            try {
                $service        = new ClientService($pdo);
                $createdClients = [];

                foreach ($input as $clientData) {
                    $id = $service->createClient($clientData, (int)$usuarioId);
                    $createdClients[] = $id;
                }

                http_response_code(201);
                echo json_encode([
                    'status'     => 'success',
                    'message'    => 'Clientes creados correctamente',
                    'idClientes' => $createdClients
                ]);
            } catch (InvalidArgumentException $e) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            } catch (PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Error al crear los clientes']);
            }
        } else {
            // Un solo cliente
            try {
                $service = new ClientService($pdo);
                $id      = $service->createClient($input ?? [], (int)$usuarioId);

                http_response_code(201);
                echo json_encode([
                    'status'    => 'success',
                    'message'   => 'Cliente creado correctamente',
                    'idCliente' => $id
                ]);
            } catch (InvalidArgumentException $e) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            } catch (PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Error al crear el cliente']);
            }
        }
    }

    public function edit(array $vars)
    {
        global $pdo;

        AuthMiddleware::validateJwtOrDie();

        header('Content-Type: application/json; charset=utf-8');

        $id = isset($vars['id']) ? (int)$vars['id'] : 0;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
            return;
        }

        try {
            $service = new ClientService($pdo);
            $client  = $service->getClient($id);

            if (!$client) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado']);
                return;
            }

            echo json_encode(['status' => 'success', 'data' => $client]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al obtener el cliente']);
        }
    }

    public function update(array $vars)
    {
        global $pdo;

        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        $id = isset($vars['id']) ? (int)$vars['id'] : 0;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido']);
            return;
        }

        try {
            $service = new ClientService($pdo);
            $ok      = $service->updateClient($id, $input, (int)$usuarioId);

            if (!$ok) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado']);
                return;
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'Cliente actualizado correctamente'
            ]);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el cliente']);
        }
    }

    public function delete(array $vars)
    {
        global $pdo;

        AuthMiddleware::validateJwtOrDie();
        $usuarioId = AuthMiddleware::getUserIdFromToken();

        header('Content-Type: application/json; charset=utf-8');

        $id = isset($vars['id']) ? (int)$vars['id'] : 0;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
            return;
        }

        try {
            $service = new ClientService($pdo);
            $ok      = $service->deleteClient($id, (int)$usuarioId);

            if (!$ok) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado']);
                return;
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'Cliente eliminado correctamente'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el cliente']);
        }
    }
}
