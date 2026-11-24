-- Ejecutar una sola vez sobre una instalación existente. No elimina datos.
USE sistema_inmobiliario;
ALTER TABLE propiedad ADD COLUMN fecha_retiro DATETIME DEFAULT NULL;
