<?php

namespace co\edu\unbosque\service;

use PDO;
use Exception;

class ElementoService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Método para obtener todos los elementos
    public function getAllElementos()
    {
        try {
            $sql = "SELECT * FROM elemento WHERE estado = 'A'"; // Solo los elementos activos
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Error al obtener los elementos de la base de datos: ' . $e->getMessage());
        }
    }
}
