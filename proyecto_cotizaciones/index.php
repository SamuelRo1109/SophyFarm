<?php

require_once __DIR__ . '/vendor/autoload.php';                         // Composer
require_once __DIR__ . '/co.edu.unbosque.config/DbConfig.php';        // $pdo

use FastRoute\RouteCollector;
use FastRoute\Dispatcher;
use co\edu\unbosque\controller\AuthController;
use co\edu\unbosque\controller\ClientController;
use co\edu\unbosque\controller\VendedorController;
use co\edu\unbosque\controller\CotizacionController;
use co\edu\unbosque\controller\ElementoController;
use co\edu\unbosque\controller\AuditoriaController; 
use co\edu\unbosque\controller\PedidoController;
use co\edu\unbosque\controller\NotificacionController;

// Crear dispatcher con TODAS las rutas
$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {

    // Ruta de prueba raíz
    $r->addRoute('GET', '/', function () {
        echo 'API funcionando';
    });

    // LOGIN (desde el form)
    $r->addRoute('POST', '/co.edu.unbosque.auth/login', 'AuthController@login');

    // =======================
    // CLIENTES
    // =======================
    $r->addRoute('POST',   '/co.edu.unbosque.cliente',        'ClientController@create');   // Crear cliente
    $r->addRoute('GET',    '/co.edu.unbosque.cliente/{id}',   'ClientController@getOne');   // Obtener cliente
    $r->addRoute('PUT',    '/co.edu.unbosque.cliente/{id}',   'ClientController@update');   // Actualizar cliente
    $r->addRoute('GET',    '/co.edu.unbosque.cliente',        'ClientController@getAll');   // Todos los clientes
    $r->addRoute('DELETE', '/co.edu.unbosque.cliente/{id}',   'ClientController@delete');   // Eliminar (lógico)

    // =======================
    // VENDEDORES
    // =======================
    $r->addRoute('GET', '/co.edu.unbosque.vendedor', 'VendedorController@getAll');

    // =======================
    // COTIZACIONES
    // =======================
    $r->addRoute('POST',   '/co.edu.unbosque.cotizacion',            'CotizacionController@create');          // Crear
    $r->addRoute('GET',    '/co.edu.unbosque.cotizacion',            'CotizacionController@getAll');          // Listar todas
    $r->addRoute('PUT',    '/co.edu.unbosque.cotizacion/{id}',       'CotizacionController@changeStatus');    // Cambiar estado
    $r->addRoute('DELETE', '/co.edu.unbosque.cotizacion/{id}',       'CotizacionController@delete');          // Eliminar
    $r->addRoute('PUT',    '/co.edu.unbosque.cotizacion/edit/{id}',  'CotizacionController@editCotizacion');  // Recalcular totales

    // ====== DETALLE COTIZACION ======
    $r->addRoute('POST',   '/co.edu.unbosque.detallecotizacion',        'CotizacionController@addDetail');
    $r->addRoute('GET',    '/co.edu.unbosque.detallecotizacion/{id}',   'CotizacionController@getDetailsByCotizacion');
    $r->addRoute('PUT',    '/co.edu.unbosque.detallecotizacion/{id}',   'CotizacionController@updateDetail');
    $r->addRoute('DELETE', '/co.edu.unbosque.detallecotizacion/{id}',   'CotizacionController@deleteDetail');

    // =======================
    // PEDIDOS
    // =======================
    // Pedido (cabecera)
    $r->addRoute('POST',   '/co.edu.unbosque.pedido',        'PedidoController@create');
    $r->addRoute('GET',    '/co.edu.unbosque.pedido',        'PedidoController@getAll');
    $r->addRoute('PUT',    '/co.edu.unbosque.pedido/{id}',   'PedidoController@changeStatus');
    $r->addRoute('DELETE', '/co.edu.unbosque.pedido/{id}',   'PedidoController@delete');

    // Detalle de pedido
    $r->addRoute('POST',   '/co.edu.unbosque.detallepedido',       'PedidoController@addDetail');
    $r->addRoute('GET',    '/co.edu.unbosque.detallepedido/{id}',  'PedidoController@getDetailsByPedido');
    $r->addRoute('PUT',    '/co.edu.unbosque.detallepedido/{id}',  'PedidoController@updateDetail');
    $r->addRoute('DELETE', '/co.edu.unbosque.detallepedido/{id}',  'PedidoController@deleteDetail');

    // =======================
    // PRODUCTOS (ELEMENTO)
    // =======================
    $r->addRoute('GET', '/co.edu.unbosque.elemento', 'ElementoController@getAll');

    // =======================
    // AUDITORÍA
    // =======================
    $r->addRoute('GET', '/co.edu.unbosque.auditoria', 'AuditoriaController@getAll');

    // =======================
    // NOTIFICACIONES
    // =======================
    // Cotización -> Email
    $r->addRoute(
        'POST',
        '/co.edu.unbosque.notificacion/cotizacion/{id}/email',
        'NotificacionController@sendCotizacionEmail'
    );

    // Cotización -> WhatsApp
    $r->addRoute(
        'POST',
        '/co.edu.unbosque.notificacion/cotizacion/{id}/whatsapp',
        'NotificacionController@sendCotizacionWhatsApp'
    );

    // Pedido -> Email
    $r->addRoute(
        'POST',
        '/co.edu.unbosque.notificacion/pedido/{id}/email',
        'NotificacionController@sendPedidoEmail'
    );

    // Pedido -> WhatsApp
    $r->addRoute(
        'POST',
        '/co.edu.unbosque.notificacion/pedido/{id}/whatsapp',
        'NotificacionController@sendPedidoWhatsApp'
    );
});

// Método y URI
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri        = $_SERVER['REQUEST_URI'];

// Quitar prefijo del proyecto
$uri = str_replace('/proyectoFinalIS2', '', $uri);

// Quitar query string
if (false !== strpos($uri, '?')) {
    $uri = substr($uri, 0, strpos($uri, '?'));
}

// Normalizar: quitar / final, pero mantener '/'
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo 'Ruta no encontrada';
        break;

    case Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo 'Método no permitido';
        break;

    case Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars    = $routeInfo[2];

        // Si el handler es un callback anónimo
        if (is_callable($handler)) {
            call_user_func($handler, $vars);
            break;
        }

        // Si el handler es tipo "AuthController@login"
        if (is_string($handler)) {
            [$classShort, $method] = explode('@', $handler);

            // Namespace completo
            $fullClass = 'co\\edu\\unbosque\\controller\\' . $classShort;

            if (!class_exists($fullClass)) {
                http_response_code(500);
                echo "No existe la clase controlador: $fullClass";
                break;
            }

            $controller = new $fullClass();

            if (!method_exists($controller, $method)) {
                http_response_code(500);
                echo "No existe el método '$method' en $fullClass";
                break;
            }

            $controller->$method($vars);
        }

        break;
}
?>
