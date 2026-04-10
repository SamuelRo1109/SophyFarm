<?php
// ClientController.php

require_once __DIR__ . '/../co.edu.unbosque.service/ClientService.php';


class ClientController {

    // Crear un nuevo cliente
    public function createClient($data) {
        $clientService = new ClientService();
        $result = $clientService->createClient($data);
        echo json_encode($result);  // Retornar la respuesta en formato JSON
    }

    // Obtener todos los clientes
    public function getAllClients($request, $response) {
        $clientService = new ClientService();
        $result = $clientService->getAllClients();
        return $response->withJson($result);
    }

    // Obtener un cliente por su ID
    public function getClientById($request, $response, $args) {
        $clientService = new ClientService();
        $result = $clientService->getClientById($args['id']);
        return $response->withJson($result);
    }

    // Actualizar un cliente
    public function updateClient($request, $response, $args) {
        $data = $request->getParsedBody();  // Obtener los datos actualizados
        $clientService = new ClientService();
        $result = $clientService->updateClient($args['id'], $data);
        return $response->withJson($result);
    }

    // Eliminar un cliente
    public function deleteClient($request, $response, $args) {
        $clientService = new ClientService();
        $result = $clientService->deleteClient($args['id']);
        return $response->withJson($result);
    }
}
}
}
?>
