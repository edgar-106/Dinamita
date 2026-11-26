<?php
/**
 * Punto de entrada principal (Front Controller) - Arquitectura MVC
 * INFONATEC Inmobiliaria
 */

// 1. Carga centralizada de configuración, seguridad y clases MVC
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
$router->get('/mis-propiedades', 'PublicacionesController@index');
$router->get('/mis-propiedades/{id}/recorrido', 'RecorridosController@editor');
$router->post('/mis-propiedades/{id}/recorrido/foto', 'RecorridosController@upload');
$router->post('/mis-propiedades/{id}/recorrido/guardar', 'RecorridosController@save');
$router->post('/mis-propiedades/{id}/recorrido/limpiar', 'RecorridosController@cleanup');
$router->get('/recorridos/imagen/{id}', 'RecorridosController@image');
$router->get('/recorridos/{id}', 'RecorridosController@viewer');
$router->get('/mis-propiedades/{id}/editar', 'PublicacionesController@edit');
$router->post('/mis-propiedades/{id}/editar', 'PublicacionesController@update');
$router->post('/mis-propiedades/{id}/retirar', 'PublicacionesController@withdraw');
$router->get('/tour', 'AppController@tour');
$router->get('/tour/industrial', 'AppController@tourIndustrial');

// --- Rutas de Recorridos por Video (integrado al editor/visor compartido) ---
$router->get('/mis-propiedades/{id}/recorrido-video', 'RecorridoVideoController@redirectToEditor');
$router->get('/recorrido/{id}', 'RecorridoVideoController@redirectToViewer');
$router->get('/api/recorridos/procesador/estado', 'RecorridoVideoController@status');
$router->get('/api/mis-propiedades/{id}/recorrido-video/videos', 'RecorridoVideoController@listVideos');
$router->post('/api/mis-propiedades/{id}/recorrido-video/videos', 'RecorridoVideoController@uploadVideo');
$router->get('/api/mis-propiedades/{id}/recorrido-video/videos/{videoId}/stream', 'RecorridoVideoController@streamOwnerVideo');
$router->post('/api/mis-propiedades/{id}/recorrido-video/videos/{videoId}/eliminar', 'RecorridoVideoController@deleteVideo');
$router->post('/api/mis-propiedades/{id}/recorrido-video/escenas', 'RecorridoVideoController@extractScene');
$router->get('/api/recorridos/{id}/escenas/{escenaId}/video', 'RecorridoVideoController@streamSceneVideo');

// --- Rutas de API y Datos ---
$router->get('/api/propiedades', 'ApiController@properties');
$router->get('/api/propiedades/{id}', 'ApiController@property');
$router->post('/api/citas', 'ApiController@createAppointment');
$router->post('/api/contacto', 'ApiController@createContact');
$router->get('/api/favoritos', 'ApiController@favorites');
$router->post('/api/favoritos/toggle', 'ApiController@toggleFavorite');
$router->post('/api/favoritos/sync', 'ApiController@syncFavorites');
$router->get('/api/notificaciones', 'ApiController@notifications');
$router->post('/api/notificaciones/leer', 'ApiController@markNotificationsRead');

// --- Rutas de Inteligencia Artificial ---
$router->post('/api/ai/chat', 'ApiController@chat');
$router->post('/api/ai/chat/stream', 'ApiController@streamChat');
$router->post('/api/ai/generate-description', 'ApiController@generateDescription');
$router->get('/api/ai/status', 'ApiController@status');

// --- Rutas de Autenticación ---
$router->post('/auth/login', 'AuthController@login');
$router->post('/auth/register', 'AuthController@register');
$router->post('/auth/logout', 'AuthController@logout');
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
