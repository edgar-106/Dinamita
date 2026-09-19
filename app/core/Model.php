<?php
/**
 * Modelo Base del patrón MVC
 */

class Model {
    protected ?PDO $db = null;

    public function __construct() {
        $this->db = Database::getConnection();
    }
}
