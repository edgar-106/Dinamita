<?php
/**
 * Punto de entrada principal (Front Controller) - Arquitectura MVC
 * INFONATEC Inmobiliaria
 */

// 1. Carga centralizada de configuración y clases MVC
require_once dirname(__DIR__) . '/app/bootstrap.php';

// 2. Inicialización del Enrutador
$router = new Router();

// --- Definición de Rutas Web ---
$router->get('/', 'AppController@index');
$router->get('/home', 'AppController@index');
$router->get('/explorar', 'ExplorarController@index');
$router->get('/vender', 'AppController@sell');
$router->post('/vender/publicar', 'AppController@publish');
$router->get('/tour', 'AppController@tour');

// --- Rutas de API y Datos ---
$router->get('/api/propiedades', 'ApiController@properties');
$router->get('/api/propiedades/{id}', 'ApiController@property');

// --- Rutas de Inteligencia Artificial ---
$router->post('/api/ai/chat', 'ApiController@chat');
$router->post('/api/ai/generate-description', 'ApiController@generateDescription');
$router->get('/api/ai/status', 'ApiController@status');

// --- Rutas de Autenticación ---
$router->post('/auth/login', 'AuthController@login');
$router->post('/auth/register', 'AuthController@register');
$router->get('/auth/logout', 'AuthController@logout');

// 3. Despacho de la petición
$router->dispatch();
