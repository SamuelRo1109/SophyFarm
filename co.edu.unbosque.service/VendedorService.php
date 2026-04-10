<?php

namespace co\edu\unbosque\service;

use PDO;

class VendedorService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Método para obtener todos los vendedores
    public function getAllVendedores()
    {
        $sql = "
            SELECT v.idVendedor, v.primerNombre, v.primerApellido
            FROM vendedor v
            JOIN usuario u ON v.idUsuario = u.id
            WHERE u.estado_usrio = 'A'  -- Solo vendedores activos
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
