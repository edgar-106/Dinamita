<?php
/**
 * Endpoint Universal de Generación de Descripciones con IA (INFONATEC)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/controllers/AIController.php';

$controller = new AIController();
$controller->generateDescription();
