<?php

namespace co\edu\unbosque\model;

use PDO;

class DetalleCotizacion
{
    // Método para crear el detalle de una cotización
    public static function create(PDO $pdo, array $data): int
    {
        $sql = "
            INSERT INTO detalle_cotizacion (idCotizacion, idProducto, cantidad, precioUnitario, valorSubtotal)
            VALUES (:idCotizacion, :idProducto, :cantidad, :precioUnitario, :valorSubtotal)
            RETURNING idDetalleCotizacion
        ";

        // Calculamos el valorSubtotal
        $valorSubtotal = $data['cantidad'] * $data['precioUnitario'];

        // Preparar la consulta
        $stmt = $pdo->prepare($sql);    
        $stmt->execute([
            ':idCotizacion' => $data['idCotizacion'],
            ':idProducto' => $data['idProducto'],
            ':cantidad' => $data['cantidad'],
            ':precioUnitario' => $data['precioUnitario'],
            ':valorSubtotal' => $valorSubtotal
        ]);

        // Obtener el ID del detalle de cotización
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['idDetalleCotizacion'];
    }

    // Método para obtener todos los detalles de una cotización
    public static function getDetailsByCotizacion(PDO $pdo, int $idCotizacion): array
    {
        $sql = "
            SELECT dc.*, e.nombre, e.precio_venta_ac
            FROM detalle_cotizacion dc
            JOIN elemento e ON dc.idProducto = e.idElemento
            WHERE dc.idCotizacion = :idCotizacion
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idCotizacion', $idCotizacion, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
