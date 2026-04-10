<?php

namespace co\edu\unbosque\service;

use PDO;
use Exception;
use PDOException;

class DetalleCotizacionService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crear un detalle de cotización
     */
    public function addDetailToCotizacion($idCotizacion, $idProducto, $cantidad, $precioUnitario, $valorSubtotal)
{
    try {
        $sql = "INSERT INTO detalle_cotizacion 
                    (idCotizacion, idProducto, cantidad, precioUnitario, valorSubtotal) 
                VALUES 
                    (:idCotizacion, :idProducto, :cantidad, :precioUnitario, :valorSubtotal)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idCotizacion'   => $idCotizacion,
            ':idProducto'     => $idProducto,
            ':cantidad'       => $cantidad,
            ':precioUnitario' => $precioUnitario,
            ':valorSubtotal'  => $valorSubtotal
        ]);

        // 👇 AQUÍ está la clave: nada de fetch(), usamos lastInsertId()
        $idDetalle = (int) $this->pdo->lastInsertId();

        return [
            'idDetalleCotizacion' => $idDetalle,
            'idCotizacion'        => $idCotizacion,
            'idProducto'          => $idProducto,
            'cantidad'            => $cantidad,
            'precioUnitario'      => $precioUnitario,
            'valorSubtotal'       => $valorSubtotal
        ];
    } catch (PDOException $e) {
        throw new Exception('Error al agregar el detalle: ' . $e->getMessage());
    }
}





    /**
     * Obtener todos los detalles con información del producto
     */
    public function getDetailsByCotizacion(int $idCotizacion): array
    {
        try {
            $sql = "
                SELECT 
                    d.idDetalleCotizacion,
                    d.idCotizacion,
                    d.idProducto,
                    d.cantidad,
                    d.precioUnitario,
                    d.valorSubtotal,
                    e.nombre AS nombreProducto,
                    e.precio_venta_ac AS precioProducto
                FROM detalle_cotizacion d
                JOIN elemento e ON d.idProducto = e.idElemento
                WHERE d.idCotizacion = :idCotizacion
                ORDER BY d.idDetalleCotizacion ASC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':idCotizacion' => $idCotizacion]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new Exception('Error al obtener los detalles: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar un detalle
     */
    public function updateDetail(int $idDetalleCotizacion, array $data): bool
    {
        try {
            $sql = "
                UPDATE detalle_cotizacion
                SET cantidad = :cantidad,
                    precioUnitario = :precioUnitario,
                    valorSubtotal = :valorSubtotal
                WHERE idDetalleCotizacion = :idDetalleCotizacion
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':cantidad'            => $data['cantidad'],
                ':precioUnitario'      => $data['precioUnitario'],
                ':valorSubtotal'       => $data['valorSubtotal'],
                ':idDetalleCotizacion' => $idDetalleCotizacion
            ]);

            return true;

        } catch (PDOException $e) {
            throw new Exception('Error al actualizar el detalle: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar un detalle
     */
    public function deleteDetail(int $idDetalleCotizacion): bool
    {
        try {
            $sql = "
                DELETE FROM detalle_cotizacion 
                WHERE idDetalleCotizacion = :idDetalleCotizacion
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':idDetalleCotizacion' => $idDetalleCotizacion]);

            return true;

        } catch (PDOException $e) {
            throw new Exception('Error al eliminar el detalle: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar todos los detalles de una cotización
     */
    public function deleteDetails(int $idCotizacion): bool
    {
        try {
            $sql = "DELETE FROM detalle_cotizacion WHERE idCotizacion = :idCotizacion";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':idCotizacion' => $idCotizacion]);
            return true;

        } catch (PDOException $e) {
            throw new Exception('Error al eliminar los detalles: ' . $e->getMessage());
        }
    }

    

    
}
