-- Migración incremental: Soporte de videos privados y escenas desde video
-- Requiere aplicar previamente database/migrations/20261126_recorridos_fotos.sql.
-- Idempotente y compatible con MySQL 8.0+ / MariaDB 10.4+ (utf8mb4). No altera paseo_virtual ni datos existentes.

USE `sistema_inmobiliario`;

CREATE TABLE IF NOT EXISTS `recorrido_video` (
  `id_video` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_recorrido` int(10) unsigned NOT NULL,
  `id_propiedad` int(10) unsigned NOT NULL,
  `id_propietario` int(10) unsigned NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `archivo_interno` varchar(255) NOT NULL,
  `mime_type` varchar(60) NOT NULL,
  `tamano_bytes` bigint(20) unsigned NOT NULL,
  `duracion_segundos` decimal(8,2) DEFAULT NULL,
  `ancho` smallint(5) unsigned DEFAULT NULL,
  `alto` smallint(5) unsigned DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'LISTO',
  `mensaje_error` varchar(500) DEFAULT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_video`),
  KEY `idx_rv_recorrido` (`id_recorrido`),
  KEY `idx_rv_propiedad` (`id_propiedad`),
  KEY `idx_rv_propietario` (`id_propietario`),
  CONSTRAINT `fk_rv_recorrido_foto` FOREIGN KEY (`id_recorrido`) REFERENCES `recorrido_foto` (`id_recorrido`) ON DELETE CASCADE,
  CONSTRAINT `fk_rv_propiedad` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON DELETE CASCADE,
  CONSTRAINT `fk_rv_propietario` FOREIGN KEY (`id_propietario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
