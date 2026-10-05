<?php
/**
 * Controlador de la Página Principal (Home)
 */

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
