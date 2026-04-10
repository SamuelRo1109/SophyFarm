<?php

namespace co\edu\unbosque\controller;

use co\edu\unbosque\service\ElementoService;
use PDOException;

class ElementoController
{
   public function getAll()
{
    global $pdo;

    try {
        $service = new ElementoService($pdo);
        $productos = $service->getAllElementos();  // Método para obtener todos los elementos

        // Depuración: Verificar los productos antes de enviarlos como respuesta
        echo json_encode([
            'status' => 'success',
            'data' => $productos
        ]);
    } catch (PDOException $e) {
        // Enviar un mensaje de error detallado
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al obtener los productos',
            'error_details' => $e->getMessage()
        ]);
    }
}

}
