<?php
/**
 * Endpoint Universal de Chat con IA (INFONATEC)
 * Compatible tanto para invocaciones directas como para MVC
 */

// Headers CORS y JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Adaptador temporal para clientes antiguos. La lógica vive en AIController.
require_once __DIR__ . '/../app/bootstrap.php';

// Despachar llamada
$controller = new AIController();
$controller->chat();
