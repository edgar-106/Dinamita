<?php
/**
 * Punto de entrada principal (Front Controller) - Arquitectura MVC
 * INFONATEC Inmobiliaria
 */

// 1. Carga de configuración y base de datos
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/Database.php';

// 2. Carga de clases base del núcleo
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/core/Model.php';
require_once __DIR__ . '/app/core/Router.php';

// 3. Inicialización del Enrutador
$router = new Router();

// --- Definición de Rutas Web ---
$router->get('/', 'HomeController@index');
$router->get('/home', 'HomeController@index');
$router->get('/explorar', 'ExplorarController@index');
$router->get('/vender', 'VenderController@index');
$router->post('/vender/publicar', 'VenderController@publicar');

// --- Rutas de API y Datos ---
$router->get('/api/propiedades', 'PropiedadController@apiList');
$router->get('/api/propiedades/{id}', 'PropiedadController@apiDetail');

// --- Rutas de Autenticación ---
$router->post('/auth/login', 'AuthController@login');
$router->post('/auth/register', 'AuthController@register');
$router->get('/auth/logout', 'AuthController@logout');

// 4. Despacho de la petición
$router->dispatch();
