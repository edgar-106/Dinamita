<?php
/**
 * Controlador para Vender / Publicar Propiedades
 */

require_once __DIR__ . '/../core/Controller.php';

class VenderController extends Controller {

    public function index(): void {
        $this->renderView('vender/index', [
            'pageTitle'  => 'Vende o Publica tu Propiedad | INFONATEC',
            'activePage' => 'vender',
            'showSplash' => false
        ]);
    }

    public function publicar(): void {
        // Endpoint para procesar el formulario de publicación
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            // Aquí se procesaría el guardado en base de datos o subida de archivos
            $this->json([
                'success' => true,
                'message' => '¡Propiedad publicada con éxito!',
                'data'    => $data
            ]);
        }
        $this->redirect('vender');
    }
}
