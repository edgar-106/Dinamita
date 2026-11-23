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
$router->get('/legal', 'AppController@legal');
$router->post('/vender/publicar', 'AppController@publish');
$router->get('/tour', 'AppController@tour');
$router->get('/tour/industrial', 'AppController@tourIndustrial');

// --- Rutas de API y Datos ---
$router->get('/api/propiedades', 'ApiController@properties');
$router->get('/api/propiedades/{id}', 'ApiController@property');

// --- Rutas de Inteligencia Artificial ---
$router->post('/api/ai/chat', 'ApiController@chat');
$router->post('/api/ai/chat/stream', 'ApiController@streamChat');
$router->post('/api/ai/generate-description', 'ApiController@generateDescription');
$router->get('/api/ai/status', 'ApiController@status');

// --- Rutas de Autenticación ---
$router->post('/auth/login', 'AuthController@login');
$router->post('/auth/register', 'AuthController@register');
$router->get('/auth/logout', 'AuthController@logout');
$router->post('/contacto', 'ContactController@interest');
$router->post('/citas', 'ContactController@appointment');

// --- Rutas de Administración ---
$router->get('/admin', 'AdminController@index');
$router->get('/admin/reservas', 'AdminController@reservas');
$router->post('/admin/reservas/{id}/estado', 'AdminController@cambiarEstado');
$router->get('/admin/pagos', 'AdminController@pagos');
$router->post('/admin/pagos/{id}/confirmar', 'AdminController@confirmarPago');
$router->get('/admin/eventos', 'AdminController@eventos');
$router->post('/admin/eventos/tour', 'AdminController@registrarTour');
$router->post('/admin/eventos/tour/{id}/eliminar', 'AdminController@eliminarTour');
$router->get('/admin/logistica', 'AdminController@logistica');
$router->post('/admin/logistica/vehiculo', 'AdminController@crearVehiculo');
$router->post('/admin/logistica/vehiculo/{id}/estado', 'AdminController@cambiarEstadoVehiculo');
$router->post('/admin/logistica/asignar', 'AdminController@asignarTransporte');

// 3. Despacho de la petición
$router->dispatch();
