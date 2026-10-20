
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_propiedad_estado_insert
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_propiedad_estado_update
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
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
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
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
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
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
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

