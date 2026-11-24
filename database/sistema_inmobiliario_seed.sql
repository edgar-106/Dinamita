SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

USE `sistema_inmobiliario`;

-- =========================================================================
-- Tablas adicionales requeridas por AdminController (Logística / Transporte)
-- y funcionalidades de Favoritos / Solicitudes de Contacto
-- =========================================================================
CREATE TABLE IF NOT EXISTS `vehiculo` (
  `id_vehiculo` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) NOT NULL,
  `tipo` varchar(40) NOT NULL,
  `marca` varchar(60) DEFAULT NULL,
  `modelo` varchar(60) DEFAULT NULL,
  `capacidad` int(10) unsigned NOT NULL DEFAULT 4,
  `estado` varchar(25) NOT NULL DEFAULT 'DISPONIBLE',
  `observaciones` text DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_vehiculo`),
  UNIQUE KEY `placa` (`placa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asignacion_transporte` (
  `id_asignacion` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_propiedad` int(10) unsigned NOT NULL,
  `id_vehiculo` int(10) unsigned NOT NULL,
  `id_cita` int(10) unsigned DEFAULT NULL,
  `fecha_salida` datetime NOT NULL,
  `fecha_llegada` datetime NOT NULL,
  `estado` varchar(25) NOT NULL DEFAULT 'PROGRAMADA',
  `observaciones` text DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_asignacion`),
  KEY `idx_asig_propiedad` (`id_propiedad`),
  KEY `idx_asig_vehiculo` (`id_vehiculo`),
  KEY `idx_asig_cita` (`id_cita`),
  CONSTRAINT `fk_asig_propiedad` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON UPDATE CASCADE,
  CONSTRAINT `fk_asig_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_vehiculo`) ON UPDATE CASCADE,
  CONSTRAINT `fk_asig_cita` FOREIGN KEY (`id_cita`) REFERENCES `cita` (`id_cita`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `favorito` (
  `id_usuario` int(10) unsigned NOT NULL,
  `id_propiedad` int(10) unsigned NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`, `id_propiedad`),
  KEY `idx_favorito_propiedad` (`id_propiedad`),
  CONSTRAINT `fk_favorito_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_favorito_propiedad` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `solicitud_contacto` (
  `id_solicitud` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_propiedad` int(10) unsigned DEFAULT NULL,
  `id_usuario` int(10) unsigned DEFAULT NULL,
  `tipo` varchar(30) NOT NULL DEFAULT 'INTERES',
  `nombre` varchar(140) NOT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) NOT NULL,
  `horario_preferido` varchar(60) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `estado` varchar(25) NOT NULL DEFAULT 'NUEVA',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_solicitud`),
  KEY `idx_solicitud_propiedad` (`id_propiedad`),
  KEY `idx_solicitud_usuario` (`id_usuario`),
  CONSTRAINT `fk_solicitud_propiedad` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_solicitud_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- Catálogos iniciales
-- =========================================================================
INSERT IGNORE INTO `rol` (`id_rol`, `nombre`, `descripcion`) VALUES
(1, 'ADMIN', 'Administrador único con control total del sistema'),
(2, 'CLIENTE', 'Usuario cliente (comprador, arrendatario o vendedor de inmuebles)'),
(3, 'ASESOR', 'Asesor inmobiliario'),
(4, 'PROPIETARIO', 'Propietario de inmueble');

INSERT IGNORE INTO `categoria` (`id_categoria`, `nombre`, `descripcion`) VALUES
(1, 'CASA', 'Casas residenciales y villas'),
(2, 'DEPARTAMENTO', 'Departamentos, lofts y penthouses'),
(3, 'TERRENO', 'Terrenos residenciales, comerciales e industriales');

INSERT IGNORE INTO `estado_propiedad` (`id_estado`, `nombre`, `descripcion`) VALUES
(1, 'DISPONIBLE', 'Propiedad visible y disponible en el catálogo'),
(2, 'APARTADO', 'Propiedad apartada temporalmente'),
(3, 'VENDIDO', 'Propiedad vendida o rentada');

INSERT IGNORE INTO `estado_cita` (`id_estado_cita`, `nombre`, `descripcion`) VALUES
(1, 'PENDIENTE', 'Cita solicitada pendiente de confirmación'),
(2, 'CONFIRMADA', 'Cita confirmada por administración'),
(3, 'REALIZADA', 'Visita realizada'),
(4, 'CANCELADA', 'Cita cancelada');

-- =========================================================================
-- Usuarios iniciales (ADMIN único: 123@gmail.com / contraseña: 123)
-- =========================================================================
INSERT INTO `usuario` (`id_usuario`, `nombre`, `apellido_paterno`, `apellido_materno`, `correo`, `telefono`, `usuario`, `contrasena`, `activo`) VALUES
(1, 'Administrador', 'INFONATEC', NULL, '123@gmail.com', '9510000000', 'admin_infonatec', '$2y$10$tcW1Thyc4AmxyDWlReDEeuRagF0wr.heAPf9oloORrLhagaduJekK', 1),
(2, 'Juan', 'Pérez', 'López', 'cliente@infonatec.com', '9511234567', 'juan_cliente', '$2y$10$tcW1Thyc4AmxyDWlReDEeuRagF0wr.heAPf9oloORrLhagaduJekK', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `apellido_paterno` = VALUES(`apellido_paterno`),
  `apellido_materno` = VALUES(`apellido_materno`),
  `contrasena` = VALUES(`contrasena`),
  `activo` = 1;

-- Asegurar que SOLO 123@gmail.com tenga el rol ADMIN
DELETE FROM `usuario_rol` WHERE `id_rol` = 1 AND `id_usuario` <> 1;
INSERT IGNORE INTO `usuario_rol` (`id_usuario`, `id_rol`) VALUES
(1, 1),
(2, 2);

-- =========================================================================
-- Catálogo completo de 10 propiedades en UTF-8 limpio
-- =========================================================================
INSERT INTO `propiedad` (`id_propiedad`, `id_propietario`, `id_categoria`, `id_estado`, `titulo`, `descripcion`, `ubicacion`, `precio`, `superficie`, `habitaciones`, `banos`, `operacion`, `situacion_legal`) VALUES
(1, 2, 3, 1, 'Lote Montebello', 'Terreno residencial plano de 450 m² (15 m de frente por 30 m de fondo) ubicado en zona consolidada al norte de Mérida, con servicios subterráneos a pie de lote, alumbrado LED y vialidades pavimentadas.', 'Mérida, YUC', 1850000.00, 450.00, NULL, NULL, 'VENTA', 'Propiedad privada con escritura pública, libre de gravamen y uso de suelo residencial H2'),
(2, 2, 1, 1, 'Residencia Las Cumbres', 'Residencia contemporánea de dos niveles con 340 m² de construcción, sala a doble altura con ventanales térmicos, cocina integral con cubierta de cuarzo, 4 recámaras con baño propio, alberca privada, terraza techada y cochera para 3 autos.', 'Monterrey, NL', 6800000.00, 340.00, 4, 5, 'VENTA', 'Escritura pública vigente, predial al corriente, apta para crédito bancario, Infonavit Cofinavit o contado'),
(3, 2, 2, 1, 'Penthouse Polanco Sky View', 'Exclusivo penthouse de 160 m² en piso alto con vista panorámica de la ciudad, 3 recámaras, 2.5 baños, cocina equipada, terraza privada, 2 cajones de estacionamiento techados y vigilancia 24/7 con acceso controlado.', 'CDMX', 38000.00, 160.00, 3, 3, 'RENTA', 'Contrato de arrendamiento anual con póliza jurídica, mantenimiento incluido en torre'),
(4, 2, 1, 1, 'Casa Bosques del Valle', 'Residencia de lujo con 520 m² de construcción en San Pedro Garza García, acabados en mármol italiano y madera de nogal, 5 recámaras con vestidor, 6 baños, estudio, jardín amplio con asador y seguridad privada.', 'San Pedro, NL', 12500000.00, 520.00, 5, 6, 'VENTA', 'Título de propiedad inscrito en RPP sin adeudos ni gravámenes, acepta crédito hipotecario'),
(5, 2, 2, 1, 'Loft Santa Fe', 'Moderno loft de 75 m² estilo industrial contemporáneo a minutos del corredor corporativo de Santa Fe, con 1 recámara en tapanco abierto, 1.5 baños, ventanales piso-techo y amenidades completas (gimnasio, alberca techada y coworking).', 'Cuajimalpa, CDMX', 3950000.00, 75.00, 1, 2, 'VENTA', 'Escritura individual lista para escriturar, reglamento de condominio vigente y apto para crédito'),
(6, 2, 3, 1, 'Terreno Industrial Querétaro', 'Lote industrial regular de 1,200 m² dentro de parque logístico consolidado en Querétaro. Cuenta con plataforma nivelada al 95% Proctor, mecánica de suelos, factibilidad de media y alta tensión, agua, drenaje pluvial y acceso para tráileres de 53 pies.', 'Querétaro, QRO', 4100000.00, 1200.00, NULL, NULL, 'VENTA', 'Uso de suelo industrial I-3 autorizado, escritura inmediata y factibilidades de servicios vigentes'),
(7, 2, 2, 1, 'Departamento Condesa Garden', 'Luminoso departamento de 95 m² con balcón arbolado a dos cuadras del Parque México. Dispone de 2 recámaras, 2 baños completos, cocina abierta, área de lavado, edificio pet friendly y 1 estacionamiento.', 'Cuauhtémoc, CDMX', 26000.00, 95.00, 2, 2, 'RENTA', 'Contrato por 12 meses, requiere depósito en garantía, primer mes y fiador o póliza jurídica'),
(8, 2, 1, 1, 'Villa Paraíso', 'Villa frente a canal navegable en Cancún con 380 m² de construcción, muelle privado, alberca infinity, 4 recámaras, 4.5 baños, terraza lounge y seguridad controlada las 24 horas.', 'Cancún, QROO', 8200000.00, 380.00, 4, 5, 'VENTA', 'Documentación en regla apta para compradores nacionales o extranjeros mediante fideicomiso'),
(9, 2, 2, 1, 'Estudio Roma Norte', 'Acogedor estudio totalmente amueblado y equipado de 50 m² en el corazón de Roma Norte, con 1 recámara, 1 baño completo, cocina integral, internet de alta velocidad y acceso a roof garden común.', 'Roma Norte, CDMX', 19500.00, 50.00, 1, 1, 'RENTA', 'Contrato mínimo 1 año, mantenimiento e internet incluidos, póliza jurídica simplificada'),
(10, 2, 3, 1, 'Macrolote Industrial Apodaca', 'Macrolote estratégico de 5,000 m² divisible hasta 12,500 m² en corredor industrial de Apodaca con acceso directo a autopista. Pavimento hidráulico MR-45, subestación eléctrica 115 kV, gas natural, cisterna contra incendio NFPA y caseta blindada 24/7.', 'Apodaca, NL', 12500000.00, 5000.00, NULL, NULL, 'VENTA', 'Propiedad industrial escriturable de inmediato, permisos ambientales MIA y factibilidad energética vigente')
ON DUPLICATE KEY UPDATE
  `id_categoria` = VALUES(`id_categoria`),
  `titulo` = VALUES(`titulo`),
  `descripcion` = VALUES(`descripcion`),
  `ubicacion` = VALUES(`ubicacion`),
  `precio` = VALUES(`precio`),
  `superficie` = VALUES(`superficie`),
  `habitaciones` = VALUES(`habitaciones`),
  `banos` = VALUES(`banos`),
  `operacion` = VALUES(`operacion`),
  `situacion_legal` = VALUES(`situacion_legal`);

-- =========================================================================
-- Galería completa (4 a 5 imágenes por propiedad) en UTF-8 limpio
-- =========================================================================
INSERT INTO `imagen_propiedad` (`id_imagen`, `id_propiedad`, `url_imagen`, `descripcion`, `orden_visualizacion`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=85', 'Vista panorámica del Lote Montebello', 1),
(2, 1, 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=85', 'Entorno arbolado y acceso principal', 2),
(3, 1, 'https://images.unsplash.com/photo-1448375240586-882707db888b?auto=format&fit=crop&w=1200&q=85', 'Vegetación y zona residencial norte', 3),
(4, 1, 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1200&q=85', 'Vista general del polígono de terreno', 4),

(5, 2, 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1400&q=90', 'Fachada principal de Residencia Las Cumbres', 1),
(6, 2, 'tour/panoramas/sala.jpg', 'Sala principal de doble altura con ventanales térmicos', 2),
(7, 2, 'tour/panoramas/comedor.jpg', 'Comedor y cocina integral con isla de cuarzo', 3),
(8, 2, 'tour/panoramas/recamara.jpg', 'Recámara principal con iluminación natural', 4),
(9, 2, 'tour/panoramas/terraza.jpg', 'Terraza techada y jardín privado', 5),

(10, 3, 'https://images.unsplash.com/photo-1600607688969-a5bfcd646154?auto=format&fit=crop&w=1400&q=90', 'Sala panorámica del Penthouse Polanco Sky View', 1),
(11, 3, 'https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1200&q=85', 'Cocina moderna con acabados en piedra natural', 2),
(12, 3, 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1200&q=85', 'Área social y comedor con vista urbana', 3),
(13, 3, 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=85', 'Recámara principal equipada en penthouse', 4),

(14, 4, 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=85', 'Exterior residencial de Casa Bosques del Valle', 1),
(15, 4, 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=85', 'Estancia principal con acabados en mármol', 2),
(16, 4, 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=85', 'Terraza y jardín trasero con asador', 3),
(17, 4, 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1200&q=85', 'Cocina de autor y barra familiar', 4),

(18, 5, 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=85', 'Interior de Loft Santa Fe estilo industrial', 1),
(19, 5, 'https://images.unsplash.com/photo-1502672023488-70e25813eb80?auto=format&fit=crop&w=1200&q=85', 'Estancia luminosa con ventanales piso-techo', 2),
(20, 5, 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1200&q=85', 'Zona de descanso y vestidor integrado', 3),
(21, 5, 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=85', 'Cocina equipada y barra desayunadora', 4),

(22, 6, 'tour/panoramas/industrial/aerea.jpg', 'Vista aérea del Parque y Terreno Industrial Querétaro', 1),
(23, 6, 'tour/panoramas/industrial/lote.jpg', 'Plataforma nivelada lista para construcción', 2),
(24, 6, 'tour/panoramas/industrial/acceso.jpg', 'Acceso controlado y caseta de seguridad', 3),
(25, 6, 'tour/panoramas/industrial/subestacion.jpg', 'Subestación eléctrica de media y alta tensión', 4),
(26, 6, 'tour/panoramas/industrial/maniobras.jpg', 'Patio logístico y vialidad de concreto hidráulico', 5),

(27, 7, 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=85', 'Edificio contemporáneo en La Condesa', 1),
(28, 7, 'https://images.unsplash.com/photo-1493809842364-78817add7ffb?auto=format&fit=crop&w=1200&q=85', 'Sala iluminada con salida a balcón arbolado', 2),
(29, 7, 'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=1200&q=85', 'Cocina abierta con acabados modernos', 3),
(30, 7, 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=1200&q=85', 'Recámara principal tranquila y ventilada', 4),

(31, 8, 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=85', 'Fachada y alberca infinity de Villa Paraíso', 1),
(32, 8, 'https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=1200&q=85', 'Terraza frente al canal con muelle privado', 2),
(33, 8, 'https://images.unsplash.com/photo-1600585154363-67eb9e2e2099?auto=format&fit=crop&w=1200&q=85', 'Sala abierta con vista al jardín tropical', 3),
(34, 8, 'https://images.unsplash.com/photo-1600573472591-ee6b68d14c68?auto=format&fit=crop&w=1200&q=85', 'Suite principal con balcón privado', 4),

(35, 9, 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=85', 'Interior amueblado de Estudio Roma Norte', 1),
(36, 9, 'https://images.unsplash.com/photo-1536376072261-38c75010e6c9?auto=format&fit=crop&w=1200&q=85', 'Área de trabajo y descanso equipada', 2),
(37, 9, 'https://images.unsplash.com/photo-1502005229762-cf1b2da7c5d6?auto=format&fit=crop&w=1200&q=85', 'Baño moderno con cancelería templada', 3),
(38, 9, 'https://images.unsplash.com/photo-1560185007-cde436f6a4d0?auto=format&fit=crop&w=1200&q=85', 'Roof garden común con vista a la colonia Roma', 4),

(39, 10, 'tour/panoramas/industrial/lote.jpg', 'Macrolote Industrial Apodaca de 5,000 m² a 12,500 m²', 1),
(40, 10, 'tour/panoramas/industrial/aerea.jpg', 'Vista aérea del corredor industrial y accesos', 2),
(41, 10, 'tour/panoramas/industrial/maniobras.jpg', 'Patio de maniobras de 50 m para tráileres 53 pies', 3),
(42, 10, 'tour/panoramas/industrial/subestacion.jpg', 'Infraestructura eléctrica de 115 kV y gas natural', 4),
(43, 10, 'tour/panoramas/industrial/acceso.jpg', 'Caseta blindada 24/7 con báscula de 80 toneladas', 5)
ON DUPLICATE KEY UPDATE
  `id_propiedad` = VALUES(`id_propiedad`),
  `url_imagen` = VALUES(`url_imagen`),
  `descripcion` = VALUES(`descripcion`),
  `orden_visualizacion` = VALUES(`orden_visualizacion`);

-- =========================================================================
-- Paseos virtuales 360° en UTF-8 limpio
-- =========================================================================
INSERT INTO `paseo_virtual` (`id_paseo`, `id_propiedad`, `url_3d`, `descripcion`) VALUES
(1, 2, 'tour', 'Recorrido Interactivo 360° — Residencia Minimalista Las Cumbres'),
(2, 6, 'tour/industrial', 'Recorrido Interactivo 360° — Terreno Industrial Querétaro'),
(3, 10, 'tour/industrial', 'Recorrido Interactivo 360° — Macrolote Industrial Apodaca')
ON DUPLICATE KEY UPDATE
  `id_propiedad` = VALUES(`id_propiedad`),
  `url_3d` = VALUES(`url_3d`),
  `descripcion` = VALUES(`descripcion`);

-- =========================================================================
-- Datos de prueba para el panel administrativo (citas, pagos, logística)
-- =========================================================================
INSERT IGNORE INTO `cita` (`id_cita`, `id_usuario`, `id_propiedad`, `id_asesor`, `id_estado_cita`, `fecha`, `hora`, `medio_contacto`, `motivo`, `observaciones`) VALUES
(1, 2, 2, NULL, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '11:00:00', 'CORREO', 'Visita presencial', 'Interesado en conocer acabados y alberca'),
(2, 2, 3, NULL, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '16:30:00', 'TELEFONO', 'Recorrido de departamento', 'Confirmada por el cliente');

INSERT IGNORE INTO `transaccion` (`id_transaccion`, `id_propiedad`, `id_comprador`, `id_vendedor`, `id_cita`, `monto`, `tipo`, `estado`, `observaciones`) VALUES
(1, 2, 2, 1, 1, 50000.00, 'VENTA', 'PENDIENTE', 'Apartado inicial de Residencia Las Cumbres');

INSERT IGNORE INTO `pago` (`id_pago`, `id_transaccion`, `monto`, `metodo_pago`, `referencia`, `estado`, `observaciones`) VALUES
(1, 1, 50000.00, 'TRANSFERENCIA', 'REF-INF-2026-001', 'PENDIENTE', 'Comprobante en validación');

INSERT IGNORE INTO `vehiculo` (`id_vehiculo`, `placa`, `tipo`, `marca`, `modelo`, `capacidad`, `estado`, `observaciones`) VALUES
(1, 'OAX-482-A', 'SUV', 'Toyota', 'RAV4', 5, 'DISPONIBLE', 'Unidad para traslados a recorridos residenciales'),
(2, 'OAX-915-B', 'SEDAN', 'Nissan', 'Sentra', 4, 'DISPONIBLE', 'Unidad ejecutiva');

INSERT IGNORE INTO `asignacion_transporte` (`id_asignacion`, `id_propiedad`, `id_vehiculo`, `id_cita`, `fecha_salida`, `fecha_llegada`, `estado`, `observaciones`) VALUES
(1, 2, 1, 1, DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 2 DAY), INTERVAL 2 HOUR), 'PROGRAMADA', 'Traslado de cliente para visita');
