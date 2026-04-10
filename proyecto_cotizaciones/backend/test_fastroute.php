<?php

// Asegúrate de que el autoload de Composer está correctamente incluido
require_once __DIR__ . '/../vendor/autoload.php';


// Comprobamos si la clase RouteCollector de FastRoute se puede cargar
if (class_exists('FastRoute\RouteCollector')) {
    echo 'FastRoute está instalado y cargado correctamente.';
} else {
    echo 'FastRoute no está instalado o no se puede cargar.';
}
