<?php

namespace co\edu\unbosque\service;

use PDO;
use Exception;
use PDOException;

class DetallePedidoService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crear un detalle de pedido
     * - Valida stock
     * - Descuenta existencia del producto
     */
    public function addDetailToPedido(
        int $idPedido,
        int $idProducto,
        int $cantidad,
        float $precioUnitario,
        float $valorSubtotal
    ): array {
        try {
            $this->pdo->beginTransaction();

            // 1. Verificar stock
            $sqlProd = "SELECT existencia, nombre 
                        FROM elemento 
                        WHERE idElemento = :idProducto";
            $stmtProd = $this->pdo->prepare($sqlProd);
            $stmtProd->execute([':idProducto' => $idProducto]);
            $producto = $stmtProd->fetch(PDO::FETCH_ASSOC);

            if (!$producto) {
                throw new Exception("El producto no existe");
            }

            if ((int)$producto['existencia'] < $cantidad) {
                throw new Exception(
                    "Stock insuficiente para {$producto['nombre']}. " .
                    "Stock actual: {$producto['existencia']}, solicitado: {$cantidad}"
                );
            }

            // 2. Insertar detalle
            $sql = "INSERT INTO detalle_pedido (
                        idPedido,
                        idProducto,
                        cantidad,
                        precioUnitario,
                        valorSubtotal
                    ) VALUES (
                        :idPedido,
                        :idProducto,
                        :cantidad,
                        :precioUnitario,
                        :valorSubtotal
                    )";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':idPedido'      => $idPedido,
                ':idProducto'    => $idProducto,
                ':cantidad'      => $cantidad,
                ':precioUnitario'=> $precioUnitario,
                ':valorSubtotal' => $valorSubtotal
            ]);

            $idDetalle = (int)$this->pdo->lastInsertId();

            // 3. Descontar stock
            $sqlUpd = "UPDATE elemento
                       SET existencia = existencia - :cantidad
                       WHERE idElemento = :idProducto";
            $stmtUpd = $this->pdo->prepare($sqlUpd);
            $stmtUpd->execute([
                ':cantidad'   => $cantidad,
                ':idProducto' => $idProducto
            ]);

            $this->pdo->commit();

            return [
                'idDetallePedido' => $idDetalle,
                'idPedido'        => $idPedido,
                'idProducto'      => $idProducto,
                'cantidad'        => $cantidad,
                'precioUnitario'  => $precioUnitario,
                'valorSubtotal'   => $valorSubtotal
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception('Error al agregar el detalle de pedido: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Obtener todos los detalles de un pedido (con info del producto)
     */
    public function getDetailsByPedido(int $idPedido): array
    {
        try {
            $sql = "
                SELECT 
                    d.idDetallePedido,
                    d.idPedido,
                    d.idProducto,
                    d.cantidad,
                    d.precioUnitario,
                    d.valorSubtotal,
                    e.nombre AS nombreProducto
                FROM detalle_pedido d
                JOIN elemento e ON d.idProducto = e.idElemento
                WHERE d.idPedido = :idPedido
                ORDER BY d.idDetallePedido ASC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':idPedido' => $idPedido]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new Exception('Error al obtener los detalles de pedido: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar un detalle de pedido
     * - Ajusta stock según la diferencia de cantidad
     */
     public function updateDetail(int $idDetallePedido, array $data): bool
{
    try {
        // 1. Traer el detalle actual (para saber idProducto y cantidad anterior)
        $sqlDet = "
            SELECT idProducto, cantidad
            FROM detalle_pedido
            WHERE idDetallePedido = :idDetallePedido
        ";
        $stmtDet = $this->pdo->prepare($sqlDet);
        $stmtDet->execute([':idDetallePedido' => $idDetallePedido]);
        $detalleActual = $stmtDet->fetch(PDO::FETCH_ASSOC);

        if (!$detalleActual) {
            throw new Exception('Detalle de pedido no encontrado.');
        }

        // 👇 Si no viene idProducto en $data, usamos el de BD
        if (isset($data['idProducto']) && $data['idProducto'] !== null && $data['idProducto'] !== '') {
            $idProducto = (int)$data['idProducto'];
        } else {
            $idProducto = (int)$detalleActual['idProducto'];
        }

        $cantidadAnterior = (int)$detalleActual['cantidad'];
        $cantidadNueva    = (int)$data['cantidad'];
        $diferencia       = $cantidadNueva - $cantidadAnterior; // puede ser negativa

        // 2. Consultar el producto
        $sqlProd = "
            SELECT existencia, nombre
            FROM elemento
            WHERE idElemento = :idProducto
        ";
        $stmtProd = $this->pdo->prepare($sqlProd);
        $stmtProd->execute([':idProducto' => $idProducto]);
        $producto = $stmtProd->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            throw new Exception('Producto no encontrado al actualizar detalle de pedido.');
        }

        $existencia = (int)$producto['existencia'];
        $nombreProd = $producto['nombre'];

        // 3. Validar stock sólo si se aumenta la cantidad
        if ($diferencia > 0 && $existencia < $diferencia) {
            throw new Exception(
                "Stock insuficiente para {$nombreProd} al actualizar. " .
                "Stock actual: {$existencia}, adicional solicitado: {$diferencia}"
            );
        }

        // 4. Transacción: actualizar detalle + stock
        $this->pdo->beginTransaction();

        // Actualizar detalle
        $sqlUpdateDet = "
            UPDATE detalle_pedido
            SET cantidad       = :cantidad,
                precioUnitario = :precioUnitario,
                valorSubtotal  = :valorSubtotal
            WHERE idDetallePedido = :idDetallePedido
        ";
        $stmtUpdateDet = $this->pdo->prepare($sqlUpdateDet);
        $stmtUpdateDet->execute([
            ':cantidad'        => $cantidadNueva,
            ':precioUnitario'  => $data['precioUnitario'],
            ':valorSubtotal'   => $data['valorSubtotal'],
            ':idDetallePedido' => $idDetallePedido
        ]);

        // Actualizar stock: restar diferencia (si es negativa, suma stock)
        $sqlUpdateStock = "
            UPDATE elemento
            SET existencia = existencia - :diferencia
            WHERE idElemento = :idProducto
        ";
        $stmtUpdateStock = $this->pdo->prepare($sqlUpdateStock);
        $stmtUpdateStock->execute([
            ':diferencia' => $diferencia,
            ':idProducto' => $idProducto
        ]);

        $this->pdo->commit();
        return true;

    } catch (PDOException $e) {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        throw new Exception('Error al actualizar el detalle de pedido: ' . $e->getMessage());
    } catch (Exception $e) {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        throw $e;
    }
}

    /**
     * Eliminar un detalle de pedido
     * - Devuelve stock al inventario
     */
    public function deleteDetail(int $idDetallePedido): bool
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Traer info del detalle
            $sqlSel = "SELECT idProducto, cantidad
                       FROM detalle_pedido
                       WHERE idDetallePedido = :idDetalle";
            $stmtSel = $this->pdo->prepare($sqlSel);
            $stmtSel->execute([':idDetalle' => $idDetallePedido]);
            $detalle = $stmtSel->fetch(PDO::FETCH_ASSOC);

            if (!$detalle) {
                throw new Exception("El detalle de pedido no existe");
            }

            // 2. Eliminar detalle
            $sqlDel = "DELETE FROM detalle_pedido
                       WHERE idDetallePedido = :idDetalle";
            $stmtDel = $this->pdo->prepare($sqlDel);
            $stmtDel->execute([':idDetalle' => $idDetallePedido]);

            // 3. Devolver stock
            $sqlUpd = "UPDATE elemento
                       SET existencia = existencia + :cantidad
                       WHERE idElemento = :idProducto";
            $stmtUpd = $this->pdo->prepare($sqlUpd);
            $stmtUpd->execute([
                ':cantidad'   => $detalle['cantidad'],
                ':idProducto' => $detalle['idProducto']
            ]);

            $this->pdo->commit();
            return true;

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception('Error al eliminar el detalle de pedido: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar todos los detalles de un pedido (para cuando se elimina el pedido completo)
     * - Devuelve stock de todos los productos
     */
    public function deleteDetails(int $idPedido): bool
{
    try {
        $this->pdo->beginTransaction();

        // 1. Traer todos los detalles del pedido
        $sqlSel = "SELECT idDetallePedido, idProducto, cantidad
                   FROM detalle_pedido
                   WHERE idPedido = :idPedido";
        $stmtSel = $this->pdo->prepare($sqlSel);
        $stmtSel->execute([':idPedido' => $idPedido]);
        $detalles = $stmtSel->fetchAll(PDO::FETCH_ASSOC);

        // 2. Por cada detalle: devolver stock y borrar el detalle
        foreach ($detalles as $det) {
            $idDetalle   = (int)$det['iddetallepedido'];
            $idProducto  = (int)$det['idproducto'];
            $cantidad    = (int)$det['cantidad'];

            // Devolver stock
            $sqlUpd = "UPDATE elemento
                       SET existencia = existencia + :cantidad
                       WHERE idElemento = :idProducto";
            $stmtUpd = $this->pdo->prepare($sqlUpd);
            $stmtUpd->execute([
                ':cantidad'   => $cantidad,
                ':idProducto' => $idProducto
            ]);

            // Borrar detalle
            $sqlDel = "DELETE FROM detalle_pedido
                       WHERE idDetallePedido = :idDetalle";
            $stmtDel = $this->pdo->prepare($sqlDel);
            $stmtDel->execute([':idDetalle' => $idDetalle]);
        }

        $this->pdo->commit();
        return true;

    } catch (PDOException $e) {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        throw new Exception('Error al eliminar los detalles de pedido: ' . $e->getMessage());
    } catch (Exception $e) {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        throw $e;
    }
}

}
