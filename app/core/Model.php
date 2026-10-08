<?php
/**
 * Modelo Base del patrón MVC
 */

class Model {
    // Los modelos pueden funcionar sin MySQL: reciben null y usan su fallback.
    protected ?PDO $db = null;

    public function __construct() {
        // Centraliza la obtención de la conexión para no duplicar lógica en
        // cada modelo concreto.
        $this->db = Database::getConnection();
    }
}
