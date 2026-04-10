<?php

namespace co\edu\unbosque\service;

use PDO;
use Exception;
use PDOException;
use DateTime;

class PedidoService
{
    private PDO $pdo;
    private AuditoriaService $auditoria;

    public function __construct(PDO $pdo)
    {
        $this->pdo        = $pdo;
        $this->auditoria  = new AuditoriaService($pdo);
    }

    /**
     * Crear pedido cabecera
     * Regla: siempre ligado a una cotización existente.
     * totalPedido arranca en 0 y se recalcula cuando se agregan detalles.
     */
    public function createPedido(array $data, int $usuarioId): int
    {
        if (empty($data['idCotizacion']) || empty($data['fechaPedido'])) {
            throw new Exception("idCotizacion y fechaPedido son requeridos");
        }

        $idCotizacion = (int)$data['idCotizacion'];

        // Obtener cotización para validar y traer idCliente
        $sqlCot = "SELECT * FROM cotizacion WHERE idCotizacion = :idCotizacion";
        $stmtCot = $this->pdo->prepare($sqlCot);
        $stmtCot->execute([':idCotizacion' => $idCotizacion]);
        $cotizacion = $stmtCot->fetch(PDO::FETCH_ASSOC);

        if (!$cotizacion) {
            throw new Exception("La cotización asociada no existe");
        }

        $sql = "INSERT INTO pedido (
                    idCliente,
                    idCotizacion,
                    fechaPedido,
                    estadoPedido,
                    totalPedido
                ) VALUES (
                    :idCliente,
                    :idCotizacion,
                    :fechaPedido,
                    :estadoPedido,
                    :totalPedido
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idCliente'    => $cotizacion['idcliente'] ?? $cotizacion['idCliente'],
            ':idCotizacion' => $idCotizacion,
            ':fechaPedido'  => $data['fechaPedido'],
            ':estadoPedido' => 'Pendiente',
            ':totalPedido'  => 0
        ]);

        $idPedido = (int)$this->pdo->lastInsertId();

        // 🔍 Auditoría
        $idCliente = $cotizacion['idcliente'] ?? $cotizacion['idCliente'] ?? null;
        $descripcion = sprintf(
            'Creación de pedido #%d asociado a la cotización #%d para el cliente #%s',
            $idPedido,
            $idCotizacion,
            $idCliente ?? 'N/D'
        );
        $this->auditoria->registrar($usuarioId, 'I', $descripcion);

        return $idPedido;
    }

    /**
     * Obtener todos los pedidos (para la tabla pedido.html)
     */
    public function getAllPedidos(): array
    {
        $sql = "SELECT 
                    p.*,
                    c.numerodocumento,
                    c.razonsocial,
                    c.primernombre,
                    c.primerapellido
                FROM pedido p
                JOIN cliente c ON p.idCliente = c.idCliente
                ORDER BY p.idPedido DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPedidoById(int $idPedido): ?array
    {
        $sql = "SELECT * FROM pedido WHERE idPedido = :idPedido";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idPedido' => $idPedido]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cambiar estado del pedido
     * - Maneja Confirmado / Cancelado y stock
     * - Registra en auditoría
     */
    public function changeStatus(int $idPedido, string $estadoPedido, int $usuarioId): array
    {
        // Estados permitidos
        $valid = ['Pendiente', 'Confirmado', 'Cancelado'];
        if (!in_array($estadoPedido, $valid)) {
            throw new Exception("Estado de pedido no válido");
        }

        // Obtener estado actual del pedido
        $sqlSel = "SELECT estadoPedido FROM pedido WHERE idPedido = :idPedido";
        $stmtSel = $this->pdo->prepare($sqlSel);
        $stmtSel->execute([':idPedido' => $idPedido]);
        $pedido = $stmtSel->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            throw new Exception("El pedido no existe");
        }

        $estadoActual = $pedido['estadopedido'] ?? $pedido['estadoPedido'];

        // Si no hay cambio real de estado, no tocamos nada
        if ($estadoActual === $estadoPedido) {
            return [
                'estadoAnterior' => $estadoActual,
                'estadoNuevo'    => $estadoPedido,
                'stocks'         => []
            ];
        }

        $stocks = [];

        try {
            $this->pdo->beginTransaction();

            // Consulta base de detalles + stock
            $sqlDet = "
                SELECT 
                    d.idProducto,
                    SUM(d.cantidad) AS cantidadTotal,
                    e.existencia,
                    e.nombre AS nombreProducto
                FROM detalle_pedido d
                JOIN elemento e ON d.idProducto = e.idElemento
                WHERE d.idPedido = :idPedido
                GROUP BY d.idProducto, e.existencia, e.nombre
            ";

            if ($estadoPedido === 'Confirmado') {
                // 1) Detalles + stock actual
                $stmtDet = $this->pdo->prepare($sqlDet);
                $stmtDet->execute([':idPedido' => $idPedido]);
                $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

                if (empty($detalles)) {
                    throw new Exception("El pedido no tiene detalles, no se puede confirmar.");
                }

                // 2) Validar stock
                foreach ($detalles as $det) {
                    $cant   = (int)($det['cantidadtotal'] ?? $det['cantidadTotal']);
                    $exist  = (int)$det['existencia'];
                    $nombre = $det['nombreproducto'] ?? $det['nombreProducto'] ?? ('Producto #' . $det['idProducto']);

                    if ($exist < $cant) {
                        throw new Exception(
                            "Stock insuficiente para {$nombre}. Stock actual: {$exist}, requerido: {$cant}"
                        );
                    }
                }

                // 3) Descontar stock
                $sqlUpd = "
                    UPDATE elemento
                    SET existencia = existencia - :cantidad
                    WHERE idElemento = :idProducto
                ";
                $stmtUpd = $this->pdo->prepare($sqlUpd);

                foreach ($detalles as $det) {
                    $idProd = (int)($det['idproducto'] ?? $det['idProducto']);
                    $cant   = (int)($det['cantidadtotal'] ?? $det['cantidadTotal']);
                    $exist  = (int)$det['existencia'];
                    $nombre = $det['nombreproducto'] ?? $det['nombreProducto'] ?? ('Producto #' . $idProd);

                    $stmtUpd->execute([
                        ':cantidad'   => $cant,
                        ':idProducto' => $idProd
                    ]);

                    $stocks[] = [
                        'idProducto' => $idProd,
                        'nombre'     => $nombre,
                        'existencia' => $exist - $cant
                    ];
                }

            } elseif ($estadoActual === 'Confirmado' && $estadoPedido === 'Cancelado') {
                // De Confirmado a Cancelado => devolver stock
                $stmtDet = $this->pdo->prepare($sqlDet);
                $stmtDet->execute([':idPedido' => $idPedido]);
                $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

                $sqlUpd = "
                    UPDATE elemento
                    SET existencia = existencia + :cantidad
                    WHERE idElemento = :idProducto
                ";
                $stmtUpd = $this->pdo->prepare($sqlUpd);

                foreach ($detalles as $det) {
                    $idProd = (int)($det['idproducto'] ?? $det['idProducto']);
                    $cant   = (int)($det['cantidadtotal'] ?? $det['cantidadTotal']);
                    $exist  = (int)$det['existencia'];
                    $nombre = $det['nombreproducto'] ?? $det['nombreProducto'] ?? ('Producto #' . $idProd);

                    $stmtUpd->execute([
                        ':cantidad'   => $cant,
                        ':idProducto' => $idProd
                    ]);

                    $stocks[] = [
                        'idProducto' => $idProd,
                        'nombre'     => $nombre,
                        'existencia' => $exist + $cant
                    ];
                }
            }

            // 4) Actualizar estado del pedido
            $sqlUpdPed = "
                UPDATE pedido
                SET estadoPedido = :estado
                WHERE idPedido = :idPedido
            ";
            $stmtUpdPed = $this->pdo->prepare($sqlUpdPed);
            $stmtUpdPed->execute([
                ':estado'   => $estadoPedido,
                ':idPedido' => $idPedido
            ]);

            $this->pdo->commit();

            // 🔍 Auditoría
            $descripcion = sprintf(
                'Cambio de estado del pedido #%d: %s -> %s',
                $idPedido,
                $estadoActual,
                $estadoPedido
            );

            if (!empty($stocks)) {
                $resumen = array_map(function ($s) {
                    return sprintf(
                        '%s (ID %d) stock actual: %d',
                        $s['nombre'],
                        $s['idProducto'],
                        $s['existencia']
                    );
                }, $stocks);

                $descripcion .= '. Stocks afectados: ' . implode(' | ', $resumen);
            }

            $this->auditoria->registrar($usuarioId, 'U', $descripcion);

            return [
                'estadoAnterior' => $estadoActual,
                'estadoNuevo'    => $estadoPedido,
                'stocks'         => $stocks
            ];

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new Exception('Error al cambiar el estado del pedido: ' . $e->getMessage());
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Eliminar pedido (cabecera) – se recomienda que antes borres los detalles con DetallePedidoService
     */
    public function deletePedido(int $idPedido, int $usuarioId): bool
    {
        try {
            // Traer info básica para dejarla registrada antes de borrar
            $sqlInfo = "SELECT idCliente, idCotizacion FROM pedido WHERE idPedido = :idPedido";
            $stmtInfo = $this->pdo->prepare($sqlInfo);
            $stmtInfo->execute([':idPedido' => $idPedido]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC) ?: [];

            $sql = "DELETE FROM pedido WHERE idPedido = :idPedido";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':idPedido' => $idPedido]);

            // 🔍 Auditoría
            $idCliente   = $info['idcliente']   ?? $info['idCliente']   ?? 'N/D';
            $idCotizacion= $info['idcotizacion']?? $info['idCotizacion']?? 'N/D';

            $descripcion = sprintf(
                'Eliminación del pedido #%d (cliente #%s, cotización #%s)',
                $idPedido,
                $idCliente,
                $idCotizacion
            );
            $this->auditoria->registrar($usuarioId, 'D', $descripcion);

            return true;
        } catch (PDOException $e) {
            throw new Exception('Error al eliminar el pedido: ' . $e->getMessage());
        }
    }

    /**
     * Recalcular totalPedido desde detalle_pedido
     * Regla de descuento: si la fechaPedido está en NOV o DIC => 10% sobre el bruto.
     * IVA: 19% sobre el bruto.
     * totalPedido = bruto + IVA - descuento.
     */
    public function recalculateTotals(int $idPedido): bool
{
    // 1. Obtener fechaPedido
    $sqlPed = "SELECT fechaPedido FROM pedido WHERE idPedido = :idPedido";
    $stmtPed = $this->pdo->prepare($sqlPed);
    $stmtPed->execute([':idPedido' => $idPedido]);
    $pedido = $stmtPed->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        throw new Exception("El pedido no existe");
    }

    // En Postgres la key se vuelve 'fechapedido'
    $fechaPedido = new \DateTime($pedido['fechapedido']);

    // 2. Sumar subtotales desde detalle_pedido
    $sqlSum = "SELECT COALESCE(SUM(valorSubtotal), 0) AS totalbruto
               FROM detalle_pedido
               WHERE idPedido = :idPedido";
    $stmtSum = $this->pdo->prepare($sqlSum);
    $stmtSum->execute([':idPedido' => $idPedido]);
    $row = $stmtSum->fetch(PDO::FETCH_ASSOC);

    // OJO: usamos 'totalbruto' en minúscula
    $bruto = isset($row['totalbruto']) ? (float)$row['totalbruto'] : 0.0;

    // 3. Descuento según fecha (noviembre/diciembre)
    $discountRate   = $this->getSeasonalDiscountRate($fechaPedido); // 0.10 en Nov–Dic
    $valorDescuento = round($bruto * $discountRate, 2);

    // 4. IVA 19% sobre el bruto
    $valorIVA = round($bruto * 0.19, 2);

    // 5. totalPedido
    $totalPedido = $bruto + $valorIVA - $valorDescuento;

    $sqlUpd = "UPDATE pedido
               SET totalPedido = :totalPedido
               WHERE idPedido = :idPedido";
    $stmtUpd = $this->pdo->prepare($sqlUpd);
    $stmtUpd->execute([
        ':totalPedido' => $totalPedido,
        ':idPedido'    => $idPedido
    ]); 

    return true;
}

    /**
     * Regla de descuento de temporada (igual que en cotización):
     * - Si mes es 11 o 12 => 10%
     * - Resto del año => 0%
     */
    private function getSeasonalDiscountRate(DateTime $fecha): float
    {
        $mes = (int)$fecha->format('n'); // 1-12
        if ($mes === 11 || $mes === 12) {
            return 0.10;
        }
        return 0.0;
    }

    public function getPedidoWithCliente(int $idPedido): ?array
{
    $sql = "
        SELECT 
            p.*,
            cli.telefono,
            cli.correo,
            cli.razonsocial,
            cli.primernombre,
            cli.primerapellido
        FROM pedido p
        JOIN cliente cli ON p.idCliente = cli.idCliente
        WHERE p.idPedido = :idPedido
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':idPedido' => $idPedido]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);

    return $row ?: null;
}


}
