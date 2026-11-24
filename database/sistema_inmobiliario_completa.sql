-- =========================================================================
-- Base de Datos Completa: INFONATEC Inmobiliaria (sistema_inmobiliario)
-- Contiene: esquema completo (19 tablas, 3 vistas, 2 triggers) y datos
-- iniciales en codificación UTF-8 (utf8mb4).
-- Archivo generado a partir de sistema_inmobiliario_schema.sql y
-- sistema_inmobiliario_seed.sql con compatibilidad sin DEFINER fijo.
-- =========================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sistema_inmobiliario` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `sistema_inmobiliario`;

DROP TABLE IF EXISTS `solicitud_contacto`;
DROP TABLE IF EXISTS `favorito`;
DROP TABLE IF EXISTS `asignacion_transporte`;
DROP TABLE IF EXISTS `vehiculo`;
DROP TABLE IF EXISTS `apartado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apartado` (
  `id_apartado` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del apartado.',
  `id_propiedad` int(10) unsigned NOT NULL COMMENT 'Propiedad que fue apartada.',
  `id_usuario` int(10) unsigned NOT NULL COMMENT 'Usuario que realizó el apartado.',
  `fecha_apartado` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora en que se realizó el apartado.',
  `fecha_vencimiento` datetime DEFAULT NULL COMMENT 'Fecha y hora límite del apartado; permite saber cuándo expira.',
  `estado` varchar(20) NOT NULL DEFAULT 'ACTIVO' COMMENT 'Situación del apartado: ACTIVO, CANCELADO, VENCIDO o CONVERTIDO.',
  `observaciones` text DEFAULT NULL COMMENT 'Notas sobre el apartado, cancelación, vencimiento o conversión a venta.',
  PRIMARY KEY (`id_apartado`),
  KEY `idx_apartado_propiedad` (`id_propiedad`),
  KEY `idx_apartado_usuario` (`id_usuario`),
  CONSTRAINT `fk_apartado_propiedad` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON UPDATE CASCADE,
  CONSTRAINT `fk_apartado_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `chk_apartado_estado` CHECK (`estado` in ('ACTIVO','CANCELADO','VENCIDO','CONVERTIDO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registra el apartado temporal de una propiedad y quién lo realizó.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asesor_inmobiliario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asesor_inmobiliario` (
  `id_asesor` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del asesor inmobiliario.',
  `id_usuario` int(10) unsigned NOT NULL COMMENT 'Usuario del sistema que desempeña el papel de asesor inmobiliario. La relación 1:1 evita duplicar sus datos personales.',
  `cedula_profesional` varchar(50) DEFAULT NULL COMMENT 'Cédula, registro o número profesional del asesor, si aplica.',
  `especialidad` varchar(100) DEFAULT NULL COMMENT 'Área o tipo de inmuebles en los que se especializa el asesor.',
  `porcentaje_comision` decimal(5,2) DEFAULT NULL COMMENT 'Porcentaje de comisión acordado para las operaciones que gestione el asesor.',
  `telefono_contacto` varchar(25) DEFAULT NULL COMMENT 'Teléfono profesional que puede ser distinto al teléfono personal del usuario.',
  `correo_contacto` varchar(150) DEFAULT NULL COMMENT 'Correo profesional para contacto con clientes.',
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Indica si el asesor está disponible para recibir nuevas citas y operaciones.',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora en que se registró al usuario como asesor inmobiliario.',
  PRIMARY KEY (`id_asesor`),
  UNIQUE KEY `id_usuario` (`id_usuario`),
  UNIQUE KEY `cedula_profesional` (`cedula_profesional`),
  KEY `idx_asesor_usuario` (`id_usuario`),
  KEY `idx_asesor_activo` (`activo`),
  CONSTRAINT `fk_asesor_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `chk_asesor_comision` CHECK (`porcentaje_comision` is null or `porcentaje_comision` >= 0 and `porcentaje_comision` <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Datos profesionales de los asesores inmobiliarios; un asesor debe ser también un usuario del sistema.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categoria` (
  `id_categoria` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la categoría de inmueble.',
  `nombre` varchar(80) NOT NULL COMMENT 'Nombre de la categoría, por ejemplo CASA, DEPARTAMENTO, TERRENO, LOCAL u OFICINA.',
  `descripcion` varchar(255) DEFAULT NULL COMMENT 'Descripción general de la categoría.',
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de tipos de propiedad. Una propiedad pertenece a una sola categoría.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cita`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cita` (
  `id_cita` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la cita.',
  `id_usuario` int(10) unsigned NOT NULL COMMENT 'Usuario que solicita o participa como cliente en la cita.',
  `id_propiedad` int(10) unsigned NOT NULL COMMENT 'Propiedad que se desea conocer, visitar o revisar en la cita.',
  `id_asesor` int(10) unsigned DEFAULT NULL COMMENT 'Asesor inmobiliario asignado a la cita. Puede quedar NULL mientras todavía no se asigne un asesor.',
  `id_estado_cita` int(10) unsigned NOT NULL COMMENT 'Estado actual de la cita.',
  `fecha` date NOT NULL COMMENT 'Día programado para la cita.',
  `hora` time NOT NULL COMMENT 'Hora programada para la cita.',
  `medio_contacto` varchar(30) DEFAULT NULL COMMENT 'Medio usado para contactar al cliente, por ejemplo TELEFONO, CORREO o WHATSAPP.',
  `motivo` varchar(255) DEFAULT NULL COMMENT 'Razón por la que se solicita la cita.',
  `observaciones` text DEFAULT NULL COMMENT 'Notas adicionales de la cita, incluyendo indicaciones o acuerdos.',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora en que se creó la solicitud de cita.',
  PRIMARY KEY (`id_cita`),
  UNIQUE KEY `uk_cita_propiedad_fecha_hora` (`id_propiedad`,`fecha`,`hora`),
  KEY `fk_cita_estado` (`id_estado_cita`),
  KEY `idx_cita_usuario` (`id_usuario`),
  KEY `idx_cita_propiedad` (`id_propiedad`),
  KEY `idx_cita_asesor` (`id_asesor`),
  CONSTRAINT `fk_cita_asesor` FOREIGN KEY (`id_asesor`) REFERENCES `asesor_inmobiliario` (`id_asesor`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cita_estado` FOREIGN KEY (`id_estado_cita`) REFERENCES `estado_cita` (`id_estado_cita`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cita_propiedad` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cita_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Solicitudes y programaciones de visitas o atención de una propiedad.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `estado_cita`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estado_cita` (
  `id_estado_cita` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del estado de la cita.',
  `nombre` varchar(30) NOT NULL COMMENT 'Estado de la cita, por ejemplo PENDIENTE, CONFIRMADA, REALIZADA o CANCELADA.',
  `descripcion` varchar(255) DEFAULT NULL COMMENT 'Descripción del significado o comportamiento del estado de la cita.',
  PRIMARY KEY (`id_estado_cita`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de estados para controlar el ciclo de vida de una cita.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `estado_propiedad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estado_propiedad` (
  `id_estado` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del estado de la propiedad.',
  `nombre` varchar(30) NOT NULL COMMENT 'Estado actual de la propiedad, por ejemplo DISPONIBLE, APARTADO o VENDIDO.',
  `descripcion` varchar(255) DEFAULT NULL COMMENT 'Explicación de lo que significa el estado.',
  PRIMARY KEY (`id_estado`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de estados que controlan la disponibilidad de cada propiedad.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historial_estado_propiedad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historial_estado_propiedad` (
  `id_historial_estado` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del movimiento histórico.',
  `id_propiedad` int(10) unsigned NOT NULL COMMENT 'Propiedad cuyo estado cambió.',
  `id_estado` int(10) unsigned NOT NULL COMMENT 'Estado que tuvo la propiedad en ese momento.',
  `fecha_cambio` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora exactas en que ocurrió el cambio.',
  `observaciones` varchar(500) DEFAULT NULL COMMENT 'Motivo o detalle del cambio de estado.',
  PRIMARY KEY (`id_historial_estado`),
  KEY `fk_historial_estado` (`id_estado`),
  KEY `idx_historial_propiedad_fecha` (`id_propiedad`,`fecha_cambio`),
  CONSTRAINT `fk_historial_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado_propiedad` (`id_estado`) ON UPDATE CASCADE,
  CONSTRAINT `fk_historial_propiedad` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de estados para no perder los cambios realizados a una propiedad.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `imagen_propiedad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imagen_propiedad` (
  `id_imagen` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la imagen.',
  `id_propiedad` int(10) unsigned NOT NULL COMMENT 'Propiedad a la que pertenece la imagen.',
  `url_imagen` varchar(500) NOT NULL COMMENT 'Dirección o ruta donde se encuentra almacenada la imagen.',
  `descripcion` varchar(255) DEFAULT NULL COMMENT 'Descripción opcional de la imagen, por ejemplo fachada, cocina o recámara.',
  `orden_visualizacion` smallint(5) unsigned NOT NULL DEFAULT 1 COMMENT 'Orden en que la imagen se mostrará en la galería.',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora en que se registró la imagen.',
  PRIMARY KEY (`id_imagen`),
  KEY `idx_imagen_propiedad` (`id_propiedad`),
  CONSTRAINT `fk_imagen_propiedad` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Galería de imágenes asociadas a cada propiedad.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pago` (
  `id_pago` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del pago.',
  `id_transaccion` int(10) unsigned NOT NULL COMMENT 'Transacción a la que pertenece el pago.',
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora en que se registró el pago.',
  `monto` decimal(14,2) NOT NULL COMMENT 'Cantidad de dinero recibida o registrada en este pago.',
  `metodo_pago` varchar(40) NOT NULL COMMENT 'Forma en que se realizó el pago, por ejemplo EFECTIVO, TRANSFERENCIA o TARJETA.',
  `referencia` varchar(120) DEFAULT NULL COMMENT 'Folio, número de operación o referencia bancaria del pago.',
  `estado` varchar(30) NOT NULL DEFAULT 'PENDIENTE' COMMENT 'Estado del pago, por ejemplo PENDIENTE, APROBADO, RECHAZADO o CANCELADO.',
  `observaciones` varchar(255) DEFAULT NULL COMMENT 'Notas adicionales relacionadas con el pago.',
  PRIMARY KEY (`id_pago`),
  KEY `idx_pago_transaccion` (`id_transaccion`),
  CONSTRAINT `fk_pago_transaccion` FOREIGN KEY (`id_transaccion`) REFERENCES `transaccion` (`id_transaccion`) ON UPDATE CASCADE,
  CONSTRAINT `chk_pago_monto` CHECK (`monto` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pagos parciales o totales asociados a una transacción.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paseo_virtual`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paseo_virtual` (
  `id_paseo` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del paseo virtual.',
  `id_propiedad` int(10) unsigned NOT NULL COMMENT 'Propiedad que tendrá el paseo virtual. UNIQUE permite como máximo un paseo por propiedad.',
  `url_3d` varchar(500) NOT NULL COMMENT 'Dirección o enlace al recorrido virtual o modelo 3D.',
  `descripcion` varchar(500) DEFAULT NULL COMMENT 'Descripción del recorrido virtual.',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora en que se registró el paseo virtual.',
  PRIMARY KEY (`id_paseo`),
  UNIQUE KEY `id_propiedad` (`id_propiedad`),
  CONSTRAINT `fk_paseo_propiedad` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paseo virtual opcional de una propiedad.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `propiedad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `propiedad` (
  `fecha_retiro` datetime DEFAULT NULL,
  `id_propiedad` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la propiedad.',
  `id_propietario` int(10) unsigned NOT NULL COMMENT 'Usuario propietario de la propiedad; permite saber quién es el dueño registrado.',
  `id_categoria` int(10) unsigned NOT NULL COMMENT 'Categoría a la que pertenece la propiedad.',
  `id_estado` int(10) unsigned NOT NULL COMMENT 'Estado actual de la propiedad; se usa para saber si está disponible, apartada o vendida.',
  `titulo` varchar(180) NOT NULL COMMENT 'Título comercial con el que se mostrará la propiedad en el sistema.',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción detallada de la propiedad y sus características.',
  `ubicacion` varchar(300) NOT NULL COMMENT 'Ubicación o dirección de la propiedad.',
  `precio` decimal(14,2) NOT NULL COMMENT 'Precio solicitado por la propiedad.',
  `superficie` decimal(12,2) DEFAULT NULL COMMENT 'Superficie del inmueble o terreno, normalmente expresada en m².',
  `habitaciones` smallint(5) unsigned DEFAULT NULL COMMENT 'Cantidad de habitaciones o recámaras.',
  `banos` smallint(5) unsigned DEFAULT NULL COMMENT 'Cantidad de baños.',
  `operacion` varchar(30) NOT NULL COMMENT 'Tipo de operación ofrecida, por ejemplo VENTA o RENTA.',
  `situacion_legal` varchar(150) DEFAULT NULL COMMENT 'Información resumida sobre la situación legal y documentación de la propiedad.',
  `fecha_publicacion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora en que la propiedad se publicó en el sistema.',
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Fecha y hora de la última modificación del registro.',
  PRIMARY KEY (`id_propiedad`),
  KEY `idx_propiedad_propietario` (`id_propietario`),
  KEY `idx_propiedad_categoria` (`id_categoria`),
  KEY `idx_propiedad_estado` (`id_estado`),
  CONSTRAINT `fk_propiedad_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`) ON UPDATE CASCADE,
  CONSTRAINT `fk_propiedad_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado_propiedad` (`id_estado`) ON UPDATE CASCADE,
  CONSTRAINT `fk_propiedad_propietario` FOREIGN KEY (`id_propietario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `chk_propiedad_precio` CHECK (`precio` >= 0),
  CONSTRAINT `chk_propiedad_superficie` CHECK (`superficie` is null or `superficie` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Información principal de cada inmueble ofrecido en la plataforma.';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER trg_propiedad_estado_insert
AFTER INSERT ON propiedad
FOR EACH ROW
BEGIN
    INSERT INTO historial_estado_propiedad (id_propiedad, id_estado, observaciones)
    VALUES (NEW.id_propiedad, NEW.id_estado, 'Estado inicial de la propiedad');
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER trg_propiedad_estado_update
AFTER UPDATE ON propiedad
FOR EACH ROW
BEGIN
    IF OLD.id_estado <> NEW.id_estado THEN
        INSERT INTO historial_estado_propiedad (id_propiedad, id_estado, observaciones)
        VALUES (NEW.id_propiedad, NEW.id_estado, 'Cambio de estado de la propiedad');
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol` (
  `id_rol` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del rol.',
  `nombre` varchar(30) NOT NULL COMMENT 'Nombre del rol, por ejemplo ADMINISTRADOR, ASESOR, COMPRADOR, VENDEDOR o PROPIETARIO.',
  `descripcion` varchar(255) DEFAULT NULL COMMENT 'Explicación de las funciones o permisos asociados al rol.',
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de roles que puede desempeñar un usuario.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transaccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transaccion` (
  `id_transaccion` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la operación inmobiliaria.',
  `id_propiedad` int(10) unsigned NOT NULL COMMENT 'Propiedad involucrada en la operación.',
  `id_comprador` int(10) unsigned NOT NULL COMMENT 'Usuario que participa como comprador en la operación.',
  `id_vendedor` int(10) unsigned NOT NULL COMMENT 'Usuario que participa como vendedor en la operación. Se conserva aunque el propietario haya sido otro usuario previamente.',
  `id_cita` int(10) unsigned DEFAULT NULL COMMENT 'Cita relacionada con la operación, si existe.',
  `fecha` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora en que se registró la transacción.',
  `monto` decimal(14,2) NOT NULL COMMENT 'Monto total acordado en la operación.',
  `tipo` varchar(30) NOT NULL COMMENT 'Tipo de operación, por ejemplo VENTA o RENTA.',
  `estado` varchar(30) NOT NULL DEFAULT 'PENDIENTE' COMMENT 'Situación de la transacción, por ejemplo PENDIENTE, EN_PROCESO, COMPLETADA o CANCELADA.',
  `observaciones` text DEFAULT NULL COMMENT 'Información adicional, condiciones o notas de la operación.',
  PRIMARY KEY (`id_transaccion`),
  KEY `fk_transaccion_cita` (`id_cita`),
  KEY `idx_transaccion_propiedad` (`id_propiedad`),
  KEY `idx_transaccion_comprador` (`id_comprador`),
  KEY `idx_transaccion_vendedor` (`id_vendedor`),
  CONSTRAINT `fk_transaccion_cita` FOREIGN KEY (`id_cita`) REFERENCES `cita` (`id_cita`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_transaccion_comprador` FOREIGN KEY (`id_comprador`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `fk_transaccion_propiedad` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON UPDATE CASCADE,
  CONSTRAINT `fk_transaccion_vendedor` FOREIGN KEY (`id_vendedor`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `chk_transaccion_monto` CHECK (`monto` >= 0),
  CONSTRAINT `chk_transaccion_partes` CHECK (`id_comprador` <> `id_vendedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro formal de la operación entre comprador y vendedor.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario` (
  `id_usuario` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del usuario. Se utiliza para relacionarlo con propiedades, citas, apartados y transacciones.',
  `nombre` varchar(80) NOT NULL COMMENT 'Nombre o nombres del usuario.',
  `apellido_paterno` varchar(80) NOT NULL COMMENT 'Apellido paterno del usuario.',
  `apellido_materno` varchar(80) DEFAULT NULL COMMENT 'Apellido materno del usuario. Puede quedar vacío si no se proporciona.',
  `correo` varchar(150) NOT NULL COMMENT 'Correo electrónico del usuario. Se utiliza para contacto, avisos y recuperación de cuenta.',
  `telefono` varchar(25) DEFAULT NULL COMMENT 'Número telefónico del usuario para contacto y confirmación de citas.',
  `usuario` varchar(60) NOT NULL COMMENT 'Nombre de usuario utilizado para iniciar sesión.',
  `contrasena` varchar(255) NOT NULL COMMENT 'Contraseña almacenada de forma segura mediante hash; no debe guardarse en texto plano.',
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Indica si la cuenta puede utilizar el sistema. TRUE = activa; FALSE = deshabilitada.',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora en que se creó la cuenta.',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Personas que utilizan el sistema; un mismo usuario puede asumir varios roles.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuario_rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario_rol` (
  `id_usuario` int(10) unsigned NOT NULL COMMENT 'Usuario al que se asigna el rol.',
  `id_rol` smallint(5) unsigned NOT NULL COMMENT 'Rol asignado al usuario.',
  `fecha_asignacion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora en que se asignó el rol.',
  PRIMARY KEY (`id_usuario`,`id_rol`),
  KEY `fk_usuario_rol_rol` (`id_rol`),
  CONSTRAINT `fk_usuario_rol_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_rol_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla puente para permitir que un usuario tenga uno o varios roles.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vw_agenda_asesores`;
/*!50001 DROP VIEW IF EXISTS `vw_agenda_asesores`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_agenda_asesores` AS SELECT
 1 AS `id_asesor`,
  1 AS `id_usuario`,
  1 AS `asesor`,
  1 AS `cedula_profesional`,
  1 AS `especialidad`,
  1 AS `asesor_activo`,
  1 AS `id_cita`,
  1 AS `fecha`,
  1 AS `hora`,
  1 AS `estado_cita`,
  1 AS `id_cliente`,
  1 AS `cliente`,
  1 AS `id_propiedad`,
  1 AS `propiedad`,
  1 AS `medio_contacto`,
  1 AS `motivo`,
  1 AS `observaciones` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vw_propiedades_disponibles`;
/*!50001 DROP VIEW IF EXISTS `vw_propiedades_disponibles`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_propiedades_disponibles` AS SELECT
 1 AS `id_propiedad`,
  1 AS `titulo`,
  1 AS `descripcion`,
  1 AS `ubicacion`,
  1 AS `precio`,
  1 AS `superficie`,
  1 AS `habitaciones`,
  1 AS `banos`,
  1 AS `operacion`,
  1 AS `categoria`,
  1 AS `estado`,
  1 AS `propietario` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vw_resumen_transacciones`;
/*!50001 DROP VIEW IF EXISTS `vw_resumen_transacciones`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_resumen_transacciones` AS SELECT
 1 AS `id_transaccion`,
  1 AS `fecha`,
  1 AS `tipo`,
  1 AS `monto`,
  1 AS `estado`,
  1 AS `id_propiedad`,
  1 AS `propiedad`,
  1 AS `id_comprador`,
  1 AS `comprador`,
  1 AS `id_vendedor`,
  1 AS `vendedor`,
  1 AS `total_pagado`,
  1 AS `saldo` */;
SET character_set_client = @saved_cs_client;

USE `sistema_inmobiliario`;
/*!50001 DROP VIEW IF EXISTS `vw_agenda_asesores`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `vw_agenda_asesores` AS select `a`.`id_asesor` AS `id_asesor`,`a`.`id_usuario` AS `id_usuario`,concat(`u`.`nombre`,' ',`u`.`apellido_paterno`,' ',coalesce(`u`.`apellido_materno`,'')) AS `asesor`,`a`.`cedula_profesional` AS `cedula_profesional`,`a`.`especialidad` AS `especialidad`,`a`.`activo` AS `asesor_activo`,`c`.`id_cita` AS `id_cita`,`c`.`fecha` AS `fecha`,`c`.`hora` AS `hora`,`ec`.`nombre` AS `estado_cita`,`c`.`id_usuario` AS `id_cliente`,concat(`cli`.`nombre`,' ',`cli`.`apellido_paterno`,' ',coalesce(`cli`.`apellido_materno`,'')) AS `cliente`,`c`.`id_propiedad` AS `id_propiedad`,`p`.`titulo` AS `propiedad`,`c`.`medio_contacto` AS `medio_contacto`,`c`.`motivo` AS `motivo`,`c`.`observaciones` AS `observaciones` from (((((`asesor_inmobiliario` `a` join `usuario` `u` on(`u`.`id_usuario` = `a`.`id_usuario`)) left join `cita` `c` on(`c`.`id_asesor` = `a`.`id_asesor`)) left join `usuario` `cli` on(`cli`.`id_usuario` = `c`.`id_usuario`)) left join `propiedad` `p` on(`p`.`id_propiedad` = `c`.`id_propiedad`)) left join `estado_cita` `ec` on(`ec`.`id_estado_cita` = `c`.`id_estado_cita`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vw_propiedades_disponibles`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `vw_propiedades_disponibles` AS select `p`.`id_propiedad` AS `id_propiedad`,`p`.`titulo` AS `titulo`,`p`.`descripcion` AS `descripcion`,`p`.`ubicacion` AS `ubicacion`,`p`.`precio` AS `precio`,`p`.`superficie` AS `superficie`,`p`.`habitaciones` AS `habitaciones`,`p`.`banos` AS `banos`,`p`.`operacion` AS `operacion`,`c`.`nombre` AS `categoria`,`e`.`nombre` AS `estado`,concat(`u`.`nombre`,' ',`u`.`apellido_paterno`,' ',coalesce(`u`.`apellido_materno`,'')) AS `propietario` from (((`propiedad` `p` join `categoria` `c` on(`c`.`id_categoria` = `p`.`id_categoria`)) join `estado_propiedad` `e` on(`e`.`id_estado` = `p`.`id_estado`)) join `usuario` `u` on(`u`.`id_usuario` = `p`.`id_propietario`)) where `e`.`nombre` = 'DISPONIBLE' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vw_resumen_transacciones`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `vw_resumen_transacciones` AS select `t`.`id_transaccion` AS `id_transaccion`,`t`.`fecha` AS `fecha`,`t`.`tipo` AS `tipo`,`t`.`monto` AS `monto`,`t`.`estado` AS `estado`,`t`.`id_propiedad` AS `id_propiedad`,`p`.`titulo` AS `propiedad`,`t`.`id_comprador` AS `id_comprador`,concat(`c`.`nombre`,' ',`c`.`apellido_paterno`,' ',coalesce(`c`.`apellido_materno`,'')) AS `comprador`,`t`.`id_vendedor` AS `id_vendedor`,concat(`v`.`nombre`,' ',`v`.`apellido_paterno`,' ',coalesce(`v`.`apellido_materno`,'')) AS `vendedor`,coalesce(sum(`pg`.`monto`),0) AS `total_pagado`,`t`.`monto` - coalesce(sum(`pg`.`monto`),0) AS `saldo` from ((((`transaccion` `t` join `propiedad` `p` on(`p`.`id_propiedad` = `t`.`id_propiedad`)) join `usuario` `c` on(`c`.`id_usuario` = `t`.`id_comprador`)) join `usuario` `v` on(`v`.`id_usuario` = `t`.`id_vendedor`)) left join `pago` `pg` on(`pg`.`id_transaccion` = `t`.`id_transaccion` and `pg`.`estado` = 'APROBADO')) group by `t`.`id_transaccion`,`t`.`fecha`,`t`.`tipo`,`t`.`monto`,`t`.`estado`,`t`.`id_propiedad`,`p`.`titulo`,`t`.`id_comprador`,`c`.`nombre`,`c`.`apellido_paterno`,`c`.`apellido_materno`,`t`.`id_vendedor`,`v`.`nombre`,`v`.`apellido_paterno`,`v`.`apellido_materno` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

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
