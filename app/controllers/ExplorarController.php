<?php
/**
 * Controlador para Explorar / Catálogo de Propiedades
 */

class ExplorarController extends Controller {

    public function index(): void {
        // Los filtros son opcionales para conservar una URL compartible como
        // /explorar?tipo=casa&operacion=comprar.
        $type = $_GET['tipo'] ?? null;
        $operation = $_GET['operacion'] ?? null;

        $propiedadModel = new Propiedad();
        // El filtrado se mantiene en el modelo para que la vista sólo renderice.
        $properties = $propiedadModel->getAll();

        $this->renderView('explorar/index', [
            'pageTitle'        => 'Explorar Propiedades | INFONATEC',
            'properties'       => $properties,
            'activePage'       => 'explorar',
            'currentType'      => in_array($type, ['casa', 'departamento', 'terreno'], true) ? $type : 'casa',
            'currentOperation' => in_array($operation, ['comprar', 'rentar'], true) ? $operation : 'comprar',
            'showSplash'       => false
        ]);
    }
}
