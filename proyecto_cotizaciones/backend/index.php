<?php
require_once __DIR__ . '/../vendor/autoload.php';  // Corregir la constante
require_once __DIR__ . '/co.edu.unbosque.config/DbConfig.php';  // Ruta corregida

use FastRoute\RouteCollector;

// Crear el despachador de rutas
$dispatcher = FastRoute\simpleDispatcher(function(RouteCollector $r) {
    // Ruta de autenticación (login)
    $r->addRoute('POST', '/auth/login', 'AuthController@login');

    // Rutas protegidas (requieren autenticación)
    $r->addRoute('GET', '/clientes', 'ClientController@getAllClients');
    $r->addRoute('POST', '/clientes', 'ClientController@createClient');
    // Puedes agregar más rutas para otras funcionalidades
});

// Obtener el método HTTP y la URI
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Limpiar la URI (eliminar parámetros de consulta si los hay)
if (false !== strpos($uri, '?')) {
    $uri = substr($uri, 0, strpos($uri, '?'));
}

// Obtener la información de la ruta
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

// Manejo de la respuesta según la ruta encontrada
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        echo "404 Not Found";
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        echo "405 Method Not Allowed";
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        // Aquí puedes llamar al controlador correspondiente
        // Por ejemplo, si la ruta es de login, llamar a AuthController@login
        call_user_func_array($handler, $vars);
        break;
}
?>
