<?php
/** Controlador de las páginas públicas de la aplicación. */

class AppController extends Controller {
    private function properties(): array {
        return (new Propiedad())->getAll();
    }

    public function index(): void {
        $this->renderView('home/index', [
            'pageTitle' => APP_TITLE,
            'properties' => (new Propiedad())->getFeatured(),
            'activePage' => 'home',
            'showSplash' => true,
        ]);
    }

    public function sell(): void {
        $this->renderView('vender/index', [
            'pageTitle' => 'Vende o Publica tu Propiedad | INFONATEC',
            'properties' => $this->properties(),
            'activePage' => 'vender',
            'showSplash' => false,
        ]);
    }

    public function publish(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('vender');
        }

        $this->json(['success' => true, 'message' => '¡Propiedad publicada con éxito!', 'data' => $_POST]);
    }

    public function tour(): void {
        $this->renderView('tour/index', [], false);
    }
}
