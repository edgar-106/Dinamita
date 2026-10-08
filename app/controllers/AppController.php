<?php
/**
 * Controlador de las páginas públicas de la aplicación.
 * Reúne las rutas que no requieren un controlador especializado.
 */

class AppController extends Controller {
    private function properties(): array {
        // Se reutiliza en Vender para que el chat y las fichas tengan el mismo
        // catálogo que la página de inicio.
        return (new Propiedad())->getAll();
    }

    public function index(): void {
        // El splash solo debe mostrarse en el primer acceso al inicio.
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
        // La ruta únicamente acepta envíos del formulario; una visita directa
        // vuelve a la sección Vender.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('vender');
        }

        $this->json(['success' => true, 'message' => '¡Propiedad publicada con éxito!', 'data' => $_POST]);
    }

    public function tour(): void {
        // El recorrido ocupa toda la ventana y por ello no usa el layout común.
        $this->renderView('tour/index', [], false);
    }
}
