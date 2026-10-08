<?php
/**
 * Punto de acceso JSON de la aplicación.
 * Hereda las acciones de IA y agrega las rutas de consulta del catálogo.
 */

class ApiController extends AIController {
    public function properties(): void {
        // Entrega la fuente de datos completa que también consume el cliente.
        $this->json((new Propiedad())->getAll());
    }

    public function property(string $id): void {
        // El router entrega parámetros como texto; el modelo trabaja con IDs.
        $property = (new Propiedad())->getById((int) $id);
        $this->json($property ?: ['error' => 'Propiedad no encontrada'], $property ? 200 : 404);
    }
}
