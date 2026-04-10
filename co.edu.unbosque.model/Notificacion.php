<?php

namespace co\edu\unbosque\model;

use PDO;

class Notificacion
{
    /**
     * Inserta una notificación y devuelve el id generado
     */
    public static function crear(PDO $pdo, array $data): int
    {
        $sql = "
            INSERT INTO notificacion (
                idCliente,
                tipoNotificacion,
                contenido,
                fechaEnvio,
                estado
            )
            VALUES (
                :idCliente,
                :tipoNotificacion,
                :contenido,
                :fechaEnvio,
                :estado
            )
            RETURNING idNotificacion
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idCliente'        => $data['idCliente'],
            ':tipoNotificacion' => $data['tipoNotificacion'],
            ':contenido'        => $data['contenido'],
            ':fechaEnvio'       => $data['fechaEnvio'],
            ':estado'           => $data['estado'],
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($row['idNotificacion']) ? (int)$row['idNotificacion'] : 0;
    }

    /**
     * Convierte un row en array controlado (evita warnings)
     */
    public static function fromRow(array $row): array
    {
        return [
            'idNotificacion' => isset($row['idNotificacion']) ? (int)$row['idNotificacion'] : 0,
            'idCliente'      => isset($row['idCliente']) ? (int)$row['idCliente'] : 0,
            'tipoNotificacion' => $row['tipoNotificacion'] ?? '',
            'contenido'      => $row['contenido'] ?? '',
            'fechaEnvio'     => $row['fechaEnvio'] ?? null,
            'estado'         => $row['estado'] ?? 'P',
        ];
    }

    /**
     * (Opcional) Obtener una notificación por id
     */
    public static function getById(PDO $pdo, int $idNotificacion): ?array
    {
        $sql = "SELECT * FROM notificacion WHERE idNotificacion = :idNotificacion";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idNotificacion' => $idNotificacion]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::fromRow($row) : null;
    }
}
