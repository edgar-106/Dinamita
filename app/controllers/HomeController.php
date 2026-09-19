<?php
/**
 * Controlador de la Página Principal (Home)
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Propiedad.php';

class HomeController extends Controller {

    public function index(): void {
        $propiedadModel = new Propiedad();
        $properties = $propiedadModel->getFeatured();

        $this->renderView('home/index', [
            'pageTitle'  => 'INFONATEC | Tu hogar, tu nido',
            'properties' => $properties,
            'activePage' => 'home',
            'showSplash' => true
        ]);
    }
}
