-- Complementos del esquema recibido. Puede ejecutarse varias veces.
USE sistema_inmobiliario;
INSERT IGNORE INTO categoria (nombre) VALUES ('CASA'), ('DEPARTAMENTO'), ('TERRENO');
INSERT IGNORE INTO estado_propiedad (nombre) VALUES ('DISPONIBLE'), ('APARTADA'), ('VENDIDA'), ('RENTADA');
INSERT IGNORE INTO estado_cita (nombre) VALUES ('PENDIENTE'), ('CONFIRMADA'), ('CANCELADA'), ('COMPLETADA');
INSERT IGNORE INTO rol (nombre) VALUES ('ADMIN'), ('COMPRADOR'), ('PROPIETARIO'), ('ASESOR');

CREATE TABLE IF NOT EXISTS vehiculo (
    id_vehiculo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(25) NOT NULL UNIQUE,
    tipo VARCHAR(30) NOT NULL,
    marca VARCHAR(80) DEFAULT NULL,
    modelo VARCHAR(80) DEFAULT NULL,
    capacidad SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    estado VARCHAR(25) NOT NULL DEFAULT 'DISPONIBLE',
    observaciones TEXT DEFAULT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (capacidad > 0),
    CHECK (estado IN ('DISPONIBLE', 'EN_USO', 'MANTENIMIENTO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asignacion_transporte (
    id_asignacion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_propiedad INT UNSIGNED NOT NULL,
    id_vehiculo INT UNSIGNED NOT NULL,
    id_cita INT UNSIGNED DEFAULT NULL,
    fecha_salida DATETIME NOT NULL,
    fecha_llegada DATETIME NOT NULL,
    estado VARCHAR(25) NOT NULL DEFAULT 'PROGRAMADA',
    observaciones TEXT DEFAULT NULL,
    FOREIGN KEY (id_propiedad) REFERENCES propiedad(id_propiedad),
    FOREIGN KEY (id_vehiculo) REFERENCES vehiculo(id_vehiculo),
    FOREIGN KEY (id_cita) REFERENCES cita(id_cita),
    CHECK (fecha_llegada > fecha_salida),
    CHECK (estado IN ('PROGRAMADA', 'EN_CAMINO', 'COMPLETADA', 'CANCELADA')),
    INDEX (id_vehiculo, fecha_salida, fecha_llegada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS favorito (
    id_usuario INT UNSIGNED NOT NULL,
    id_propiedad INT UNSIGNED NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario, id_propiedad),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_propiedad) REFERENCES propiedad(id_propiedad) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS solicitud_contacto (
    id_solicitud INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_propiedad INT UNSIGNED DEFAULT NULL,
    id_usuario INT UNSIGNED DEFAULT NULL,
    tipo VARCHAR(30) NOT NULL DEFAULT 'INTERES',
    nombre VARCHAR(160) NOT NULL,
    correo VARCHAR(150) DEFAULT NULL,
    telefono VARCHAR(30) NOT NULL,
    horario_preferido VARCHAR(80) DEFAULT NULL,
    mensaje TEXT DEFAULT NULL,
    estado VARCHAR(25) NOT NULL DEFAULT 'NUEVA',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_propiedad) REFERENCES propiedad(id_propiedad),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
