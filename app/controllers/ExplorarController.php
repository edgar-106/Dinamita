<?php
/**
 * Controlador para Explorar / Catálogo Completo de Propiedades
 */

class ExplorarController extends Controller {

    public function index(): void {
        $allowedTypes = ['all', 'casa', 'departamento', 'terreno'];
        $allowedOps   = ['all', 'comprar', 'rentar'];

        $type = strtolower(trim((string) ($_GET['tipo'] ?? 'all')));
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'all';
        }

        $operation = strtolower(trim((string) ($_GET['operacion'] ?? 'all')));
        if (!in_array($operation, $allowedOps, true)) {
            $operation = 'all';
        }

        $location = trim((string) ($_GET['ubicacion'] ?? ''));
        $minPrice = is_numeric($_GET['min_precio'] ?? null) ? (string) $_GET['min_precio'] : '';
        $maxPrice = is_numeric($_GET['max_precio'] ?? null) ? (string) $_GET['max_precio'] : '';
        $onlyFavorites = ($_GET['favoritos'] ?? '') === '1';

        $propiedadModel = new Propiedad();
        $properties = $propiedadModel->getAll();

        $this->renderView('explorar/index', [
            'pageTitle'        => 'Explorar Propiedades | INFONATEC',
            'properties'       => $properties,
            'activePage'       => 'explorar',
            'currentType'      => $type,
            'currentOperation' => $operation,
            'currentLocation'  => $location,
            'currentMinPrice'  => $minPrice,
            'currentMaxPrice'  => $maxPrice,
            'onlyFavorites'    => $onlyFavorites,
            'showSplash'       => false,
        ]);
    }
}
