<?php

namespace co\edu\unbosque\model;

use PDO;

class Client
{
    /**
     * Crear cliente y devolver id generado
     */
    public static function create(PDO $pdo, array $data): int
    {
        $sql = "
            INSERT INTO cliente (
                idTipoCliente,
                idTipoDocumento,
                numeroDocumento,
                primerNombre,
                segundoNombre,
                primerApellido,
                segundoApellido,
                razonSocial,
                telefono,
                correo,
                direccion,
                saldoActual,
                cupoCredito,
                estado
            )
            VALUES (
                :idTipoCliente,
                :idTipoDocumento,
                :numeroDocumento,
                :primerNombre,
                :segundoNombre,
                :primerApellido,
                :segundoApellido,
                :razonSocial,
                :telefono,
                :correo,
                :direccion,
                :saldoActual,
                :cupoCredito,
                :estado
            )
            RETURNING idCliente
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idTipoCliente'   => $data['idTipoCliente'],
            ':idTipoDocumento' => $data['idTipoDocumento'],
            ':numeroDocumento' => $data['numeroDocumento'],
            ':primerNombre'    => $data['primerNombre'],
            ':segundoNombre'   => $data['segundoNombre'],
            ':primerApellido'  => $data['primerApellido'],
            ':segundoApellido' => $data['segundoApellido'],
            ':razonSocial'     => $data['razonSocial'],
            ':telefono'        => $data['telefono'],
            ':correo'          => $data['correo'],
            ':direccion'       => $data['direccion'],
            ':saldoActual'     => $data['saldoActual'],
            ':cupoCredito'     => $data['cupoCredito'],
            ':estado'          => $data['estado'],
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['idCliente'];
    }

    /**
     * Obtener un cliente por id
     */
    public static function getById(PDO $pdo, int $idCliente): ?array
    {
        $sql = "SELECT * FROM cliente WHERE idCliente = :idCliente";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idCliente' => $idCliente]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Listar todos los clientes activos
     */
    public static function getAll(PDO $pdo): array
    {
        $sql = "
            SELECT c.*, tc.nombreTipoCliente, td.nombreTipoDocumento
            FROM cliente c
            JOIN tipo_cliente tc ON c.idTipoCliente = tc.idTipoCliente
            JOIN tipo_documento td ON c.idTipoDocumento = td.idTipoDocumento
            WHERE c.estado = 'A'
            ORDER BY c.idCliente DESC
        ";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar un cliente
     */
    public static function update(PDO $pdo, int $idCliente, array $data): bool
    {
        $sql = "
            UPDATE cliente
            SET
                idTipoCliente   = :idTipoCliente,
                idTipoDocumento = :idTipoDocumento,
                numeroDocumento = :numeroDocumento,
                primerNombre    = :primerNombre,
                segundoNombre   = :segundoNombre,
                primerApellido  = :primerApellido,
                segundoApellido = :segundoApellido,
                razonSocial     = :razonSocial,
                telefono        = :telefono,
                correo          = :correo,
                direccion       = :direccion,
                saldoActual     = :saldoActual,
                cupoCredito     = :cupoCredito,
                estado          = :estado
            WHERE idCliente = :idCliente
        ";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':idTipoCliente'   => $data['idTipoCliente'],
            ':idTipoDocumento' => $data['idTipoDocumento'],
            ':numeroDocumento' => $data['numeroDocumento'],
            ':primerNombre'    => $data['primerNombre'],
            ':segundoNombre'   => $data['segundoNombre'],
            ':primerApellido'  => $data['primerApellido'],
            ':segundoApellido' => $data['segundoApellido'],
            ':razonSocial'     => $data['razonSocial'],
            ':telefono'        => $data['telefono'],
            ':correo'          => $data['correo'],
            ':direccion'       => $data['direccion'],
            ':saldoActual'     => $data['saldoActual'],
            ':cupoCredito'     => $data['cupoCredito'],
            ':estado'          => $data['estado'],
            ':idCliente'       => $idCliente,
        ]);
    }

    /**
     * Borrado lógico (estado = 'I')
     */
    public static function softDelete(PDO $pdo, int $idCliente): bool
    {
        $sql = "UPDATE cliente SET estado = 'I' WHERE idCliente = :idCliente";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':idCliente' => $idCliente]);
    }
}
