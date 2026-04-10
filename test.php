<?php

// Incluir el autoload de Composer
require_once __DIR__ . '/vendor/autoload.php';

// Probar la clase FastRoute
use FastRoute\RouteCollector;

// Crear un objeto de la clase FastRoute\RouteCollector
$dispatcher = FastRoute\simpleDispatcher(function(RouteCollector $r) {
    $r->addRoute('GET', '/test', function() {
        echo '¡Ruta encontrada!';
    });
});

// Comprobar la ruta /test
$httpMethod = 'GET';
$uri = '/test';

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        echo 'No se encontró la ruta';
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        echo 'Método no permitido';
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $handler();
        break;
}
