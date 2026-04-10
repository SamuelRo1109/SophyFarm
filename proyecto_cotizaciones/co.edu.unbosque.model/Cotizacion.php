<?php

namespace co\edu\unbosque\model;

use PDO;

class Cotizacion
{
    public static function create(PDO $pdo, array $data): int
    {
        $sql = "
            INSERT INTO cotizacion (idCliente, idVendedor, fechaCotizacion, valorBruto, valorDescuento, valorIVA, valorNeto, estadoCotizacion)
            VALUES (:idCliente, :idVendedor, :fechaCotizacion, :valorBruto, :valorDescuento, :valorIVA, :valorNeto, :estadoCotizacion)
            RETURNING idCotizacion
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idCliente' => $data['idCliente'],
            ':idVendedor' => $data['idVendedor'],
            ':fechaCotizacion' => $data['fechaCotizacion'],
            ':valorBruto' => $data['valorBruto'],
            ':valorDescuento' => $data['valorDescuento'],
            ':valorIVA' => $data['valorIVA'],
            ':valorNeto' => $data['valorNeto'],
            ':estadoCotizacion' => $data['estadoCotizacion']
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['idCotizacion'];
    }

    public static function getAll(PDO $pdo): array
    {
        $sql = "
            SELECT * FROM cotizacion
            ORDER BY fechaCotizacion DESC
        ";

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
