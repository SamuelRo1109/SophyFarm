<?php
// clientRoutes.php

use FastRoute\RouteCollector;

return function(RouteCollector $r) {
    // Definir las rutas
    $r->addRoute('POST', '/clientes', 'ClientController@createClient');  // Ruta para crear cliente
    $r->addRoute('GET', '/clientes', 'ClientController@getAllClients');  // Obtener todos los clientes
    $r->addRoute('GET', '/clientes/{id}', 'ClientController@getClientById');  // Obtener cliente por ID
    $r->addRoute('PUT', '/clientes/{id}', 'ClientController@updateClient');  // Actualizar cliente
    $r->addRoute('DELETE', '/clientes/{id}', 'ClientController@deleteClient');  // Eliminar cliente
};
?>
