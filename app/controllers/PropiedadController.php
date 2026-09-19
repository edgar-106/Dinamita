<?php
/**
 * Controlador de Propiedad (API y Ficha)
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Propiedad.php';

class PropiedadController extends Controller {

    /**
     * API JSON que retorna todas las propiedades (usada por app.js o llamadas AJAX)
     */
    public function apiList(): void {
        $propiedadModel = new Propiedad();
        $this->json($propiedadModel->getAll());
    }

    /**
     * API JSON de una propiedad específica por ID
     */
    public function apiDetail(string $id): void {
        $propiedadModel = new Propiedad();
        $property = $propiedadModel->getById((int)$id);

        if ($property) {
            $this->json($property);
        } else {
            $this->json(['error' => 'Propiedad no encontrada'], 404);
        }
    }
}
