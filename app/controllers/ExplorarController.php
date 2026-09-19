<?php
/**
 * Controlador para Explorar / Catálogo de Propiedades
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Propiedad.php';

class ExplorarController extends Controller {

    public function index(): void {
        $type = $_GET['tipo'] ?? null;
        $operation = $_GET['operacion'] ?? null;

        $propiedadModel = new Propiedad();
        $properties = $propiedadModel->filter($type, $operation);

        $this->renderView('explorar/index', [
            'pageTitle'        => 'Explorar Propiedades | INFONATEC',
            'properties'       => $properties,
            'activePage'       => 'explorar',
            'currentType'      => $type ?? 'casa',
            'currentOperation' => $operation ?? 'comprar',
            'showSplash'       => false
        ]);
    }
}
