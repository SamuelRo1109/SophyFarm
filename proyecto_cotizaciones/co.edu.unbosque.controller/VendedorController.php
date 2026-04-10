<?php

namespace co\edu\unbosque\controller;

use co\edu\unbosque\service\VendedorService;
use PDOException;

class VendedorController
{
    public function getAll()
    {
        global $pdo;

        try {
            $service = new VendedorService($pdo);
            $vendedores = $service->getAllVendedores();  // Método para obtener todos los vendedores

            echo json_encode([
                'status' => 'success',
                'data' => $vendedores
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener los vendedores',
                'error_details' => $e->getMessage()
            ]);
        }
    }
}
