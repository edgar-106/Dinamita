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
        $showSplash = empty($_SESSION['intro_seen']);
        $_SESSION['intro_seen'] = true;
        $this->renderView('home/index', [
            'pageTitle' => APP_TITLE,
            'properties' => (new Propiedad())->getFeatured(),
            'activePage' => 'home',
            'showSplash' => $showSplash,
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

    /** Muestra los términos sin obligar a volver a la página de inicio. */
    public function legal(): void {
        $this->renderView('legal/index', [
            'pageTitle' => 'Términos y Legalidad | INFONATEC',
            'properties' => $this->properties(),
            'activePage' => 'legal',
            'showSplash' => false,
        ]);
    }

    public function publish(): void {
        // La ruta únicamente acepta envíos del formulario; una visita directa
        // vuelve a la sección Vender.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('vender');
        }

        $result = (new Propiedad())->create($_POST, $_FILES);
        $this->json($result, $result['success'] ? 201 : 400);
    }

    public function tour(): void {
        // El recorrido ocupa toda la ventana y por ello no usa el layout común.
        $prop = $_GET['prop'] ?? $_GET['id'] ?? null;
        if ($prop === 'industrial' || $prop === '6' || $prop === '10') {
            $this->renderView('tour/industrial', [], false);
            return;
        }
        $this->renderView('tour/index', [], false);
    }

    public function tourIndustrial(): void {
        $this->renderView('tour/industrial', [], false);
    }
}
