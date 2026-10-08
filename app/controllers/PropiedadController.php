<?php
/**
 * Controlador de Propiedad (API y Ficha)
 */

class PropiedadController extends Controller {

    /**
     * API JSON que retorna todas las propiedades (usada por app.js o llamadas AJAX)
     */
    public function apiList(): void {
        // Mantiene compatibilidad con la API anterior del catálogo.
        $propiedadModel = new Propiedad();
        $this->json($propiedadModel->getAll());
    }

    /**
     * API JSON de una propiedad específica por ID
     */
    public function apiDetail(string $id): void {
        // Responder 404 permite que el cliente distinga un ID inexistente de
        // un fallo de red.
        $propiedadModel = new Propiedad();
        $property = $propiedadModel->getById((int)$id);

        if ($property) {
            $this->json($property);
        } else {
            $this->json(['error' => 'Propiedad no encontrada'], 404);
        }
    }
}
