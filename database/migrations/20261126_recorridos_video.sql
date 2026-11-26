-- Migración incremental: Módulo de recorridos interactivos por escenas y video
-- Compatible con MySQL 8.0+ / MariaDB 10.4+ (utf8mb4). No altera paseo_virtual ni datos existentes.

USE `sistema_inmobiliario`;

CREATE TABLE IF NOT EXISTS `recorrido` (
  `id_recorrido` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del recorrido interactivo.',
  `id_propiedad` int(10) unsigned NOT NULL COMMENT 'Propiedad asociada; máximo un recorrido por escenas por propiedad.',
  `titulo` varchar(180) NOT NULL COMMENT 'Título del recorrido interactivo.',
  `descripcion` varchar(500) DEFAULT NULL COMMENT 'Descripción breve del recorrido.',
  `estado` varchar(20) NOT NULL DEFAULT 'BORRADOR' COMMENT 'BORRADOR, PUBLICADO o INACTIVO.',
  `escena_inicial_id` int(10) unsigned DEFAULT NULL COMMENT 'Escena inicial sugerida para abrir el recorrido.',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_recorrido`),
  UNIQUE KEY `uk_recorrido_propiedad` (`id_propiedad`),
  KEY `idx_recorrido_estado` (`estado`),
  CONSTRAINT `fk_recorrido_propiedad` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_recorrido_estado` CHECK (`estado` IN ('BORRADOR', 'PUBLICADO', 'INACTIVO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Recorrido interactivo por escenas compartido entre módulos de video y fotografías.';

CREATE TABLE IF NOT EXISTS `recorrido_video` (
  `id_video` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del video protegido.',
  `id_recorrido` int(10) unsigned NOT NULL COMMENT 'Recorrido al que pertenece el video.',
  `id_propiedad` int(10) unsigned NOT NULL COMMENT 'Propiedad asociada al video.',
  `id_propietario` int(10) unsigned NOT NULL COMMENT 'Propietario autenticado que subió el video.',
  `nombre_original` varchar(255) NOT NULL COMMENT 'Nombre original sanitizado para referencia visual.',
  `archivo_interno` varchar(255) NOT NULL COMMENT 'Nombre de archivo aleatorio generado por el servidor dentro de storage/recorridos_videos/.',
  `mime_type` varchar(60) NOT NULL COMMENT 'Tipo MIME real verificado en servidor.',
  `tamano_bytes` bigint(20) unsigned NOT NULL COMMENT 'Tamaño del archivo en bytes.',
  `duracion_segundos` decimal(8,2) DEFAULT NULL COMMENT 'Duración del video en segundos.',
  `ancho` smallint(5) unsigned DEFAULT NULL COMMENT 'Ancho en píxeles.',
  `alto` smallint(5) unsigned DEFAULT NULL COMMENT 'Alto en píxeles.',
  `estado` varchar(20) NOT NULL DEFAULT 'LISTO' COMMENT 'SUBIENDO, PROCESANDO, LISTO, ERROR o ELIMINADO.',
  `mensaje_error` varchar(500) DEFAULT NULL COMMENT 'Detalle de error de procesamiento si ocurrió.',
  `fecha_subida` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_video`),
  KEY `idx_rv_recorrido` (`id_recorrido`),
  KEY `idx_rv_propiedad` (`id_propiedad`),
  KEY `idx_rv_propietario` (`id_propietario`),
  CONSTRAINT `fk_rv_recorrido` FOREIGN KEY (`id_recorrido`) REFERENCES `recorrido` (`id_recorrido`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rv_propiedad` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rv_propietario` FOREIGN KEY (`id_propietario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `chk_rv_estado` CHECK (`estado` IN ('SUBIENDO', 'PROCESANDO', 'LISTO', 'ERROR', 'ELIMINADO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Videos privados cargados por propietarios para extraer escenas y reproducir fragmentos.';

CREATE TABLE IF NOT EXISTS `recorrido_escena` (
  `id_escena` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la escena.',
  `id_recorrido` int(10) unsigned NOT NULL COMMENT 'Recorrido al que pertenece la escena.',
  `nombre` varchar(120) NOT NULL COMMENT 'Nombre asignado manualmente: Sala, Cocina, Habitación, Patio, etc.',
  `tipo_visual` varchar(20) NOT NULL DEFAULT 'imagen' COMMENT 'Tipo visual acorde al contrato: imagen o panorama.',
  `imagen_url` varchar(500) NOT NULL COMMENT 'Ruta relativa pública dentro de public/ de la imagen extraída o cargada.',
  `video_id` int(10) unsigned DEFAULT NULL COMMENT 'Video de origen cuando la escena proviene de un video.',
  `momento_segundos` decimal(8,2) DEFAULT NULL COMMENT 'Segundo exacto del fotograma extraído.',
  `inicio_segundos` decimal(8,2) DEFAULT NULL COMMENT 'Segundo inicial opcional del fragmento de video asociado.',
  `fin_segundos` decimal(8,2) DEFAULT NULL COMMENT 'Segundo final opcional del fragmento de video asociado.',
  `orden` smallint(5) unsigned NOT NULL DEFAULT 1 COMMENT 'Orden de visualización en el recorrido.',
  `origen_extraccion` varchar(20) NOT NULL DEFAULT 'manual' COMMENT 'Origen de la imagen: ffmpeg, navegador, foto o manual.',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_escena`),
  KEY `idx_re_recorrido_orden` (`id_recorrido`, `orden`, `id_escena`),
  KEY `idx_re_video` (`video_id`),
  CONSTRAINT `fk_re_recorrido` FOREIGN KEY (`id_recorrido`) REFERENCES `recorrido` (`id_recorrido`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_re_video` FOREIGN KEY (`video_id`) REFERENCES `recorrido_video` (`id_video`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_re_tipo_visual` CHECK (`tipo_visual` IN ('imagen', 'panorama')),
  CONSTRAINT `chk_re_origen` CHECK (`origen_extraccion` IN ('ffmpeg', 'navegador', 'foto', 'manual')),
  CONSTRAINT `chk_re_inicio` CHECK (`inicio_segundos` IS NULL OR `inicio_segundos` >= 0),
  CONSTRAINT `chk_re_fragmento` CHECK (`fin_segundos` IS NULL OR `inicio_segundos` IS NULL OR `fin_segundos` > `inicio_segundos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Escenas individuales del recorrido compartidas entre video y fotografías.';

CREATE TABLE IF NOT EXISTS `recorrido_conexion` (
  `id_conexion` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la conexión entre escenas.',
  `id_recorrido` int(10) unsigned NOT NULL COMMENT 'Recorrido al que pertenece la conexión.',
  `escena_origen_id` int(10) unsigned NOT NULL COMMENT 'Escena donde se coloca el punto interactivo.',
  `escena_destino_id` int(10) unsigned NOT NULL COMMENT 'Escena hacia la que navega el punto interactivo.',
  `etiqueta` varchar(120) NOT NULL COMMENT 'Etiqueta visible del punto interactivo.',
  `posicion_sistema` varchar(30) NOT NULL DEFAULT 'plano_xy' COMMENT 'Sistema de coordenadas: plano_xy o panorama_yaw_pitch.',
  `posicion_json` text NOT NULL COMMENT 'Coordenadas en JSON según el sistema acordado.',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_conexion`),
  KEY `idx_rc_recorrido` (`id_recorrido`),
  KEY `idx_rc_origen` (`escena_origen_id`),
  KEY `idx_rc_destino` (`escena_destino_id`),
  CONSTRAINT `fk_rc_recorrido` FOREIGN KEY (`id_recorrido`) REFERENCES `recorrido` (`id_recorrido`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rc_origen` FOREIGN KEY (`escena_origen_id`) REFERENCES `recorrido_escena` (`id_escena`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rc_destino` FOREIGN KEY (`escena_destino_id`) REFERENCES `recorrido_escena` (`id_escena`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_rc_sistema` CHECK (`posicion_sistema` IN ('plano_xy', 'panorama_yaw_pitch')),
  CONSTRAINT `chk_rc_distintas` CHECK (`escena_origen_id` <> `escena_destino_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Puntos interactivos que conectan escenas dentro de un mismo recorrido.';
