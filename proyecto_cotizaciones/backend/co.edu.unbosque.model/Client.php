<?php
require_once __DIR__ . '/../co.edu.unbosque.config/DbConfig.php';

class Client {
    private $id;
    private $nombre;
    private $documento;
    private $telefono;
    private $email;
    private $direccion;
    private $saldo;
    private $cupoCredito;

    // Constructor
    public function __construct($id, $nombre, $documento, $telefono, $email, $direccion, $saldo, $cupoCredito) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->documento = $documento;
        $this->telefono = $telefono;
        $this->email = $email;
        $this->direccion = $direccion;
        $this->saldo = $saldo;
        $this->cupoCredito = $cupoCredito;
    }

    // Métodos Getter
    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getDocumento() { return $this->documento; }
    public function getTelefono() { return $this->telefono; }
    public function getEmail() { return $this->email; }
    public function getDireccion() { return $this->direccion; }
    public function getSaldo() { return $this->saldo; }
    public function getCupoCredito() { return $this->cupoCredito; }

    // Crear un nuevo cliente
    // Client.php (Modelo)
public static function create($pdo, $data) {
    if ($data['tipoCliente'] == '2') {  // Empresa
        $query = "INSERT INTO cliente (idTipoCliente, idTipoDocumento, numeroDocumento, razonSocial, telefono, correo, direccion, saldoActual, cupoCredito) 
                  VALUES (:idTipoCliente, :idTipoDocumento, :numeroDocumento, :razonSocial, :telefono, :correo, :direccion, :saldoActual, :cupoCredito)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':razonSocial', $data['razonSocial']);
    } else {  // Cliente Natural
        $query = "INSERT INTO cliente (idTipoCliente, idTipoDocumento, numeroDocumento, primerNombre, segundoNombre, primerApellido, segundoApellido, telefono, correo, direccion, saldoActual, cupoCredito) 
                  VALUES (:idTipoCliente, :idTipoDocumento, :numeroDocumento, :primerNombre, :segundoNombre, :primerApellido, :segundoApellido, :telefono, :correo, :direccion, :saldoActual, :cupoCredito)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':primerNombre', $data['primerNombre']);
        $stmt->bindParam(':segundoNombre', $data['segundoNombre']);
        $stmt->bindParam(':primerApellido', $data['primerApellido']);
        $stmt->bindParam(':segundoApellido', $data['segundoApellido']);
    }

    // Resto de las uniones para otros parámetros
    $stmt->bindParam(':idTipoCliente', $data['tipoCliente']);
    $stmt->bindParam(':idTipoDocumento', $data['tipoDocumento']);
    $stmt->bindParam(':numeroDocumento', $data['numeroDocumento']);
    $stmt->bindParam(':telefono', $data['telefono']);
    $stmt->bindParam(':correo', $data['correo']);
    $stmt->bindParam(':direccion', $data['direccion']);
    $stmt->bindParam(':saldoActual', $data['saldoActual']);
    $stmt->bindParam(':cupoCredito', $data['cupoCredito']);
    
    return $stmt->execute();  // Ejecutar la consulta
}


    // Obtener todos los clientes
    public static function getAll($pdo) {
        $query = "SELECT * FROM cliente";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un cliente por ID
    public static function getById($pdo, $id) {
        $query = "SELECT * FROM cliente WHERE idCliente = :idCliente";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':idCliente', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar un cliente
    public static function update($pdo, $id, $data) {
        $query = "UPDATE cliente SET nombre = :nombre, documento = :documento, telefono = :telefono, 
                  email = :email, direccion = :direccion, saldo = :saldo, cupoCredito = :cupoCredito 
                  WHERE idCliente = :idCliente";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':idCliente', $id);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':documento', $data['documento']);
        $stmt->bindParam(':telefono', $data['telefono']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':direccion', $data['direccion']);
        $stmt->bindParam(':saldo', $data['saldo']);
        $stmt->bindParam(':cupoCredito', $data['cupoCredito']);
        $stmt->execute();
    }

    // Eliminar un cliente
    public static function delete($pdo, $id) {
        $query = "DELETE FROM cliente WHERE idCliente = :idCliente";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':idCliente', $id);
        $stmt->execute();
    }
}
?>
