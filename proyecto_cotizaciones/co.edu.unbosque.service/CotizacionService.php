<?php

namespace co\edu\unbosque\service;

use PDO;
use Exception;
use PDOException;

class CotizacionService
{
    private PDO $pdo;
    private AuditoriaService $auditoriaService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        // Instanciamos el servicio de auditoría reutilizando el mismo PDO
        $this->auditoriaService = new AuditoriaService($pdo);
    }

    // =========================
    // CREAR COTIZACIÓN
    // =========================
    /**
     * @param array $data       Datos de la cotización
     * @param int   $usuarioId  Usuario que ejecuta la acción (para auditoría)
     */
    public function createCotizacion(array $data, int $usuarioId): int
    {
        if (
            empty($data['idCliente']) ||
            empty($data['idVendedor']) ||
            empty($data['fechaCotizacion'])
        ) {
            throw new Exception("Faltan campos requeridos");
        }

        $sql = "INSERT INTO cotizacion (
                    idCliente,
                    idVendedor,
                    fechaCotizacion,
                    valorBruto,
                    valorDescuento,
                    valorIVA,
                    valorNeto,
                    estadoCotizacion
                )
                VALUES (
                    :idCliente,
                    :idVendedor,
                    :fechaCotizacion,
                    0,
                    0,
                    0,
                    0,
                    'P'
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idCliente'       => $data['idCliente'],
            ':idVendedor'      => $data['idVendedor'],
            ':fechaCotizacion' => $data['fechaCotizacion']
        ]);

        $idCotizacion = (int) $this->pdo->lastInsertId();

        // 🔹 Auditoría: insertar
        $descripcion = sprintf(
            'Creó cotización #%d para el cliente %d (vendedor %d, fecha %s)',
            $idCotizacion,
            $data['idCliente'],
            $data['idVendedor'],
            $data['fechaCotizacion']
        );
        $this->auditoriaService->registrar($usuarioId, 'I', $descripcion);

        return $idCotizacion;
    }

    // =========================
    // OBTENER TODAS
    // =========================
    public function getAllCotizaciones(): array
    {
        try {
            $sql = "SELECT *
                    FROM cotizacion
                    ORDER BY idCotizacion DESC";
            $stmt = $this->pdo->query($sql);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Error al obtener las cotizaciones: ' . $e->getMessage());
        }
    }

    // =========================
    // OBTENER UNA POR ID
    // =========================
    public function getCotizacionById(int $idCotizacion): ?array
    {
        $sql = "SELECT *
                FROM cotizacion
                WHERE idCotizacion = :idCotizacion";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idCotizacion' => $idCotizacion]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    // =========================
    // CAMBIAR ESTADO (P, A, C)
    // =========================
    /**
     * @param int    $idCotizacion
     * @param string $estadoCotizacion  P, A, C
     * @param int    $usuarioId         Usuario que cambia el estado
     */
    public function changeStatus(int $idCotizacion, string $estadoCotizacion, int $usuarioId): bool
    {
        $validStatuses = ['P', 'A', 'C']; // Pendiente, Aprobada, Cancelada

        if (!in_array($estadoCotizacion, $validStatuses, true)) {
            throw new Exception("Estado no válido");
        }

        $sql = "UPDATE cotizacion
                SET estadoCotizacion = :estado
                WHERE idCotizacion = :idCotizacion";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':estado'       => $estadoCotizacion,
            ':idCotizacion' => $idCotizacion
        ]);

        // 🔹 Auditoría: actualización de estado
        $descripcion = sprintf(
            'Actualizó estado de la cotización #%d a %s',
            $idCotizacion,
            $estadoCotizacion
        );
        $this->auditoriaService->registrar($usuarioId, 'U', $descripcion);

        return true;
    }

    // =========================
    // ELIMINAR COTIZACIÓN
    // =========================
    /**
     * @param int $idCotizacion
     * @param int $usuarioId    Usuario que elimina
     */
    public function deleteCotizacion(int $idCotizacion, int $usuarioId): bool
    {
        try {
            $sql = "DELETE FROM cotizacion WHERE idCotizacion = :idCotizacion";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':idCotizacion' => $idCotizacion]);

            // 🔹 Auditoría: eliminar
            $descripcion = sprintf('Eliminó la cotización #%d', $idCotizacion);
            $this->auditoriaService->registrar($usuarioId, 'D', $descripcion);

            return true;
        } catch (PDOException $e) {
            throw new Exception('Error al eliminar la cotización: ' . $e->getMessage());
        }
    }

    // =========================
    // RE-CALCULAR TOTALES
    // =========================
    /**
     * @param int $idCotizacion
     * @param int $usuarioId    Usuario que dispara el recálculo
     */
    public function recalculateTotals(int $idCotizacion, int $usuarioId): bool
    {
        // 1. Sumar subtotales y traer fecha de la cotización
        $sqlSum = "SELECT 
                        c.fechaCotizacion     AS fechacotizacion,
                        COALESCE(SUM(d.valorSubtotal), 0) AS valorbruto
                   FROM cotizacion c
                   LEFT JOIN detalle_cotizacion d
                        ON c.idCotizacion = d.idCotizacion
                   WHERE c.idCotizacion = :idCotizacion
                   GROUP BY c.fechaCotizacion";

        $stmt = $this->pdo->prepare($sqlSum);
        $stmt->execute([':idCotizacion' => $idCotizacion]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception("Cotización no encontrada al recalcular totales");
        }

        $valorBruto = isset($row['valorbruto']) ? (float)$row['valorbruto'] : 0.0;

        // 2. Regla de DESCUENTO (noviembre/diciembre 10%)
        $fechaCotizacion = new \DateTime($row['fechacotizacion']);
        $mes             = (int)$fechaCotizacion->format('m');

        $porcentajeDescuento = 0.0;
        if ($mes === 11 || $mes === 12) {
            $porcentajeDescuento = 0.10;
        }

        $valorDescuento = round($valorBruto * $porcentajeDescuento, 2);

        // 3. IVA (19%)
        $baseGravable = $valorBruto - $valorDescuento;
        if ($baseGravable < 0) {
            $baseGravable = 0;
        }

        $valorIVA  = round($baseGravable * 0.19, 2);
        $valorNeto = $baseGravable + $valorIVA;

        // 4. Actualizar la cotización
        $sqlUpdate = "UPDATE cotizacion
                      SET valorBruto     = :valorBruto,
                          valorDescuento = :valorDescuento,
                          valorIVA       = :valorIVA,
                          valorNeto      = :valorNeto
                      WHERE idCotizacion = :idCotizacion";

        $stmt = $this->pdo->prepare($sqlUpdate);
        $stmt->execute([
            ':valorBruto'     => $valorBruto,
            ':valorDescuento' => $valorDescuento,
            ':valorIVA'       => $valorIVA,
            ':valorNeto'      => $valorNeto,
            ':idCotizacion'   => $idCotizacion
        ]);

        // 🔹 Auditoría: actualización de totales
        $descripcion = sprintf(
            'Recalculó totales de la cotización #%d (Bruto: %.2f, Descuento: %.2f, IVA: %.2f, Neto: %.2f)',
            $idCotizacion,
            $valorBruto,
            $valorDescuento,
            $valorIVA,
            $valorNeto
        );
        $this->auditoriaService->registrar($usuarioId, 'U', $descripcion);

        return true;
    }

  public function getCotizacionWithCliente(int $idCotizacion): ?array
{
    $sql = "
        SELECT 
            c.*,
            cli.idCliente,
            cli.telefono,
            cli.correo,
            cli.razonSocial,
            cli.primerNombre,
            cli.primerApellido
        FROM cotizacion c
        JOIN cliente cli ON c.idCliente = cli.idCliente
        WHERE c.idCotizacion = :idCotizacion
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':idCotizacion' => $idCotizacion]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}


}
