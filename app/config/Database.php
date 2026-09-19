<?php
/**
 * Clase para gestión de conexión a la Base de Datos con PDO (Singleton)
 */

class Database {
    private static ?PDO $instance = null;
    private static bool $connectionAttempted = false;

    public static function getConnection(): ?PDO {
        if (self::$instance === null && !self::$connectionAttempted) {
            self::$connectionAttempted = true;
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Si la BD aún no existe o el servicio no está encendido, registramos el error sin romper la aplicación
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                self::$instance = null;
            }
        }
        return self::$instance;
    }
}
