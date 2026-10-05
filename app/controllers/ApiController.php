<?php
/** Punto de acceso JSON de la aplicación. */

class ApiController extends AIController {
    public function properties(): void {
        $this->json((new Propiedad())->getAll());
    }

    public function property(string $id): void {
        $property = (new Propiedad())->getById((int) $id);
        $this->json($property ?: ['error' => 'Propiedad no encontrada'], $property ? 200 : 404);
    }
}
