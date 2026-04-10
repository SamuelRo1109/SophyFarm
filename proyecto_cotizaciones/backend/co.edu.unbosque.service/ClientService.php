<?php
// ClientService.php

require_once __DIR__ . '/../co.edu.unbosque.model/Client.php';

class ClientService {

    public function createClient($data) {
        $pdo = DbConfig::getConnection();
        Client::create($pdo, $data);
        return ['message' => 'Cliente creado exitosamente'];
    }

    public function getAllClients() {
        $pdo = DbConfig::getConnection();
        return Client::getAll($pdo);
    }

    public function getClientById($id) {
        $pdo = DbConfig::getConnection();
        return Client::getById($pdo, $id);
    }

    public function updateClient($id, $data) {
        $pdo = DbConfig::getConnection();
        Client::update($pdo, $id, $data);
        return ['message' => 'Cliente actualizado exitosamente'];
    }

    public function deleteClient($id) {
        $pdo = DbConfig::getConnection();
        Client::delete($pdo, $id);
        return ['message' => 'Cliente eliminado exitosamente'];
    }
}
?>
