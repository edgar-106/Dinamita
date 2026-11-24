<?php
/**
 * Controlador de las páginas públicas de la aplicación.
 * Reúne las rutas principales del portal inmobiliario.
 */

class AppController extends Controller {
    private function properties(): array {
        return (new Propiedad())->getAll();
    }

    public function index(): void {
        $this->renderView('home/index', [
            'pageTitle'  => APP_TITLE,
            'properties' => (new Propiedad())->getFeatured(),
            'activePage' => 'home',
            'showSplash' => true,
        ]);
    }

    public function sell(): void {
        $this->renderView('vender/index', [
            'pageTitle'  => 'Vende o Publica tu Propiedad | INFONATEC',
            'properties' => $this->properties(),
            'activePage' => 'vender',
            'showSplash' => false,
        ]);
    }

    public function legal(): void {
        $this->renderView('legal/index', [
            'pageTitle'  => 'Términos y Legalidad | INFONATEC',
            'activePage' => 'legal',
            'showSplash' => false,
        ]);
    }

    public function publish(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('vender');
        }

        Security::requireCsrf();

        if (empty(Usuario::current()['id'])) {
            $this->json(['success' => false, 'message' => 'Inicia sesión o crea una cuenta para publicar.'], 401);
        }

        $result = (new Propiedad())->create($_POST, $_FILES);
        $this->json($result, !empty($result['success']) ? 200 : 400);
    }

    public function tour(): void {
        $prop = trim((string) ($_GET['prop'] ?? ($_GET['id'] ?? '')));
        $isApproved = ($_GET['approved'] ?? '') === '1';

        $selectedProperty = null;
        if (ctype_digit($prop)) {
            $selectedProperty = (new Propiedad())->getById((int) $prop);
        }

        $tourUrl = strtolower((string) ($selectedProperty['virtualTour']['url'] ?? ''));
        if ($prop === 'industrial' || in_array($prop, ['6', '10'], true) || str_contains($tourUrl, 'industrial')) {
            $this->renderView('tour/industrial', [
                'tourPreApproved'  => $isApproved,
                'selectedProperty' => $selectedProperty,
            ], false);
            return;
        }

        $this->renderView('tour/index', [
            'tourPreApproved'  => $isApproved,
            'selectedProperty' => $selectedProperty,
        ], false);
    }

    public function tourIndustrial(): void {
        $isApproved = ($_GET['approved'] ?? '') === '1';
        $prop = trim((string) ($_GET['prop'] ?? ($_GET['id'] ?? '6')));
        $selectedProperty = ctype_digit($prop) ? (new Propiedad())->getById((int) $prop) : null;

        $this->renderView('tour/industrial', [
            'tourPreApproved'  => $isApproved,
            'selectedProperty' => $selectedProperty,
        ], false);
    }
}
