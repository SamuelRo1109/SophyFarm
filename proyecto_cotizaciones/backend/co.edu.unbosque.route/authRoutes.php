<?php
use FastRoute\RouteCollector;

return function(RouteCollector $r) {
    $r->addRoute('POST', '/login', 'AuthController@login');  // Ruta para login
};
?>
