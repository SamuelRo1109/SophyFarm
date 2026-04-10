<?php

namespace co\edu\unbosque\service;

use PDO;
use PDOException;
use Exception;

class AuditoriaService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Registra una acción en la tabla auditoria.
     *
     * @param int    $usuarioId   ID del usuario (columna usuario_auditoria)
     * @param string $accion      I = Insertar, U = Actualizar, D = Eliminar
     * @param string $descripcion Texto explicando qué pasó
     */
    public function registrar(int $usuarioId, string $accion, string $descripcion): void
    {
        try {
            $sql = "
                INSERT INTO auditoria (
                    fcha_auditoria,
                    usuario_auditoria,
                    accion_auditoria,
                    descripcion_accion
                )
                VALUES (
                    NOW(),
                    :usuario,
                    :accion,
                    :descripcion
                )
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':usuario'     => $usuarioId,
                ':accion'      => $accion,
                ':descripcion' => $descripcion
            ]);
        } catch (PDOException $e) {
            // No rompemos la lógica principal si falla el log
            error_log('Error al registrar auditoría: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene el historial de auditoría con filtros opcionales.
     *
     * @param int|null    $usuarioId  Filtrar por id de usuario
     * @param string|null $accion     'I','U','D' o null
     * @param string|null $desde      'YYYY-MM-DD' o null
     * @param string|null $hasta      'YYYY-MM-DD' o null
     *
     * @return array
     * @throws Exception
     */
    public function getAuditoria(
        ?int $usuarioId = null,
        ?string $accion = null,
        ?string $desde = null,
        ?string $hasta = null
    ): array {
        try {
            $sql = "
                SELECT 
                    a.id,
                    a.fcha_auditoria,
                    a.usuario_auditoria,
                    a.accion_auditoria,
                    a.descripcion_accion,
                    u.username
                FROM auditoria a
                INNER JOIN usuario u ON u.id = a.usuario_auditoria
                WHERE 1=1
            ";

            $params = [];

            if ($usuarioId !== null) {
                $sql .= " AND a.usuario_auditoria = :usuario";
                $params[':usuario'] = $usuarioId;
            }

            if ($accion !== null && in_array($accion, ['I','U','D'], true)) {
                $sql .= " AND a.accion_auditoria = :accion";
                $params[':accion'] = $accion;
            }

            if ($desde !== null) {
                // comparamos solo por fecha
                $sql .= " AND DATE(a.fcha_auditoria) >= :desde";
                $params[':desde'] = $desde;
            }

            if ($hasta !== null) {
                $sql .= " AND DATE(a.fcha_auditoria) <= :hasta";
                $params[':hasta'] = $hasta;
            }

            $sql .= " ORDER BY a.fcha_auditoria DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new Exception('Error al obtener la auditoría: ' . $e->getMessage());
        }
    }
}
