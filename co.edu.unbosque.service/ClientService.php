<?php

namespace co\edu\unbosque\service;

use co\edu\unbosque\model\Client;
use PDO;
use InvalidArgumentException;
use co\edu\unbosque\service\AuditoriaService;

class ClientService
{
    private PDO $pdo;
    private AuditoriaService $auditoria;

    // Ajusta estos IDs según tu tabla tipo_cliente
    private int $TIPO_NATURAL = 1;
    private int $TIPO_EMPRESA = 2;

    public function __construct(PDO $pdo)
    {
        $this->pdo       = $pdo;
        $this->auditoria = new AuditoriaService($pdo);
    }

    /**
     * Normaliza y valida datos de entrada
     */
    private function normalizeAndValidate(array $data, bool $isUpdate = false): array
    {
        $idTipoCliente   = (int)($data['idTipoCliente']   ?? 0);
        $idTipoDocumento = (int)($data['idTipoDocumento'] ?? 0);
        $numeroDocumento = trim($data['numeroDocumento']  ?? '');
        $primerNombre    = trim($data['primerNombre']     ?? '');
        $segundoNombre   = trim($data['segundoNombre']    ?? '');
        $primerApellido  = trim($data['primerApellido']   ?? '');
        $segundoApellido = trim($data['segundoApellido']  ?? '');
        $razonSocial     = trim($data['razonSocial']      ?? '');
        $telefono        = trim($data['telefono']         ?? '');
        $correo          = trim($data['correo']           ?? '');
        $direccion       = trim($data['direccion']        ?? '');
        $saldoActual     = $data['saldoActual'] ?? 0;
        $cupoCredito     = $data['cupoCredito'] ?? 0;
        $estado          = $data['estado']      ?? 'A';

        if ($idTipoCliente <= 0) {
            throw new InvalidArgumentException('El tipo de cliente es obligatorio.');
        }
        if ($idTipoDocumento <= 0) {
            throw new InvalidArgumentException('El tipo de documento es obligatorio.');
        }
        if ($numeroDocumento === '') {
            throw new InvalidArgumentException('El número de documento es obligatorio.');
        }

        $esEmpresa = ($idTipoCliente === $this->TIPO_EMPRESA);

        if ($esEmpresa) {
            if ($razonSocial === '') {
                throw new InvalidArgumentException('La razón social es obligatoria para clientes empresa.');
            }
            $primerNombre   = null;
            $segundoNombre  = null;
            $primerApellido = null;
            $segundoApellido= null;
        } else {
            if ($primerNombre === '' || $primerApellido === '') {
                throw new InvalidArgumentException('Primer nombre y primer apellido son obligatorios para clientes naturales.');
            }
            if ($razonSocial === '') {
                $razonSocial = null;
            }
        }

        $saldoActual = is_numeric($saldoActual) ? (float)$saldoActual : 0.0;
        $cupoCredito = is_numeric($cupoCredito) ? (float)$cupoCredito : 0.0;

        if (!in_array($estado, ['A', 'I'], true)) {
            $estado = 'A';
        }

        return [
            'idTipoCliente'   => $idTipoCliente,
            'idTipoDocumento' => $idTipoDocumento,
            'numeroDocumento' => $numeroDocumento,
            'primerNombre'    => $primerNombre,
            'segundoNombre'   => $segundoNombre,
            'primerApellido'  => $primerApellido,
            'segundoApellido' => $segundoApellido,
            'razonSocial'     => $razonSocial,
            'telefono'        => $telefono,
            'correo'          => $correo,
            'direccion'       => $direccion,
            'saldoActual'     => $saldoActual,
            'cupoCredito'     => $cupoCredito,
            'estado'          => $estado,
        ];
    }

    /**
     * Crear cliente (natural o empresa) con auditoría
     */
    public function createClient(array $data, int $usuarioId): int
    {
        // Campos base
        $requiredFields = [
            'idTipoCliente',
            'idTipoDocumento',
            'numeroDocumento',
            'telefono',
            'correo',
            'direccion',
            'saldoActual',
            'cupoCredito'
        ];

        if (($data['idTipoCliente'] ?? null) == $this->TIPO_NATURAL) {
            $requiredFields = array_merge(
                $requiredFields,
                ['primerNombre', 'segundoNombre', 'primerApellido', 'segundoApellido']
            );
        }

        if (($data['idTipoCliente'] ?? null) == $this->TIPO_EMPRESA) {
            $requiredFields = array_merge($requiredFields, ['razonSocial']);
        }

        foreach ($requiredFields as $field) {
            if (empty($data[$field]) && $data[$field] !== '0') {
                throw new InvalidArgumentException("El campo $field es obligatorio.");
            }
        }

        $sql = "INSERT INTO cliente (
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
                ) VALUES (
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
                    'A'
                )";

        $stmt = $this->pdo->prepare($sql);

        $stmt->bindParam(':idTipoCliente',   $data['idTipoCliente']);
        $stmt->bindParam(':idTipoDocumento', $data['idTipoDocumento']);
        $stmt->bindParam(':numeroDocumento', $data['numeroDocumento']);

        if ($data['idTipoCliente'] == $this->TIPO_NATURAL) {
            $stmt->bindParam(':primerNombre',   $data['primerNombre']);
            $stmt->bindParam(':segundoNombre',  $data['segundoNombre']);
            $stmt->bindParam(':primerApellido', $data['primerApellido']);
            $stmt->bindParam(':segundoApellido',$data['segundoApellido']);
            $stmt->bindValue(':razonSocial',    null);
        } else {
            $stmt->bindValue(':razonSocial',    $data['razonSocial']);
            $stmt->bindValue(':primerNombre',   null);
            $stmt->bindValue(':segundoNombre',  null);
            $stmt->bindValue(':primerApellido', null);
            $stmt->bindValue(':segundoApellido',null);
        }

        $stmt->bindParam(':telefono',    $data['telefono']);
        $stmt->bindParam(':correo',      $data['correo']);
        $stmt->bindParam(':direccion',   $data['direccion']);
        $stmt->bindParam(':saldoActual', $data['saldoActual']);
        $stmt->bindParam(':cupoCredito', $data['cupoCredito']);

        if (!$stmt->execute()) {
            throw new \PDOException("Error al insertar el cliente en la base de datos.");
        }

        $id = (int)$this->pdo->lastInsertId();

        // Auditoría
        $tipo = ((int)$data['idTipoCliente'] === $this->TIPO_EMPRESA) ? 'Empresa' : 'Persona natural';
        $doc  = $data['numeroDocumento'] ?? '';
        $this->auditoria->registrar(
            $usuarioId,
            'I',
            "Creó cliente ID {$id} ({$tipo}, documento: {$doc})"
        );

        return $id;
    }

    public function updateClient(int $idCliente, array $data, int $usuarioId): bool
    {
        if (($data['idTipoCliente'] ?? null) == $this->TIPO_NATURAL) {
            if (empty($data['primerNombre']) || empty($data['primerApellido'])) {
                throw new InvalidArgumentException('Primer nombre y primer apellido son obligatorios.');
            }
        }

        if (($data['idTipoCliente'] ?? null) == $this->TIPO_EMPRESA && empty($data['razonSocial'])) {
            throw new InvalidArgumentException('Razón social es obligatoria para clientes de tipo empresa.');
        }

        $clean = $this->normalizeAndValidate($data, true);

        $ok = Client::update($this->pdo, $idCliente, $clean);

        if ($ok) {
            $tipo = ($clean['idTipoCliente'] === $this->TIPO_EMPRESA) ? 'Empresa' : 'Persona natural';
            $doc  = $clean['numeroDocumento'];
            $this->auditoria->registrar(
                $usuarioId,
                'U',
                "Actualizó cliente ID {$idCliente} ({$tipo}, documento: {$doc})"
            );
        }

        return $ok;
    }

    public function getClient(int $idCliente): ?array
    {
        return Client::getById($this->pdo, $idCliente);
    }

    public function getAllClients($limit = 0, $offset = 0, $search = '')
    {
        $searchQuery = '';
        if ($search !== '') {
            $searchQuery = "WHERE c.primerNombre LIKE :search
                            OR c.segundoNombre LIKE :search
                            OR c.primerApellido LIKE :search
                            OR c.segundoApellido LIKE :search
                            OR c.razonSocial LIKE :search";
        }

        $sql = "
            SELECT c.*, tc.nombreTipoCliente, td.nombreTipoDocumento
            FROM cliente c
            JOIN tipo_cliente   tc ON c.idTipoCliente   = tc.idTipoCliente
            JOIN tipo_documento td ON c.idTipoDocumento = td.idTipoDocumento
            $searchQuery
            ORDER BY c.idCliente DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        if ($search !== '') {
            $search = "%$search%";
            $stmt->bindParam(':search', $search);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClientsCount($search = ''): int
    {
        $searchQuery = '';
        if ($search !== '') {
            $searchQuery = "WHERE c.primerNombre LIKE :search
                            OR c.segundoNombre LIKE :search
                            OR c.primerApellido LIKE :search
                            OR c.segundoApellido LIKE :search
                            OR c.razonSocial LIKE :search";
        }

        $sql = "SELECT COUNT(*) FROM cliente c $searchQuery";
        $stmt = $this->pdo->prepare($sql);

        if ($search !== '') {
            $search = "%$search%";
            $stmt->bindParam(':search', $search);
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function deleteClient(int $idCliente, int $usuarioId): bool
    {
        $sql  = "DELETE FROM cliente WHERE idCliente = :idCliente";
        $stmt = $this->pdo->prepare($sql);
        $ok   = $stmt->execute([':idCliente' => $idCliente]);

        if ($ok && $stmt->rowCount() > 0) {
            $this->auditoria->registrar(
                $usuarioId,
                'D',
                "Eliminó cliente ID {$idCliente}"
            );
            return true;
        }

        return false;
    }
}
