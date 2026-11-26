CREATE DATABASE IF NOT EXISTS foro_itlp
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE foro_itlp;

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET SQL_SAFE_UPDATES = 0;

DROP TABLE IF EXISTS baneos;
DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS comentarios;
DROP TABLE IF EXISTS publicaciones;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    nombre_usuario VARCHAR(30) NOT NULL UNIQUE,
    correo VARCHAR(190) NOT NULL UNIQUE,
    contrasena_hash VARCHAR(255) NOT NULL,
    rol ENUM('usuario', 'administrador') NOT NULL DEFAULT 'usuario',
    biografia VARCHAR(500) NOT NULL DEFAULT '',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_baja DATETIME NULL,
    version_sesion INT UNSIGNED NOT NULL DEFAULT 1,
    debe_cambiar_clave TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE publicaciones (
    id_publicacion INT AUTO_INCREMENT PRIMARY KEY,
    id_autor INT NOT NULL,
    id_categoria INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    contenido TEXT NOT NULL,
    fecha_publicacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_eliminacion DATETIME NULL,
    id_eliminado_por INT NULL,
    motivo_eliminacion VARCHAR(500) NULL,

    INDEX idx_publicaciones_eliminacion (fecha_eliminacion),

    FOREIGN KEY (id_eliminado_por)
        REFERENCES usuarios(id_usuario),

    FOREIGN KEY (id_autor)
        REFERENCES usuarios(id_usuario),

    FOREIGN KEY (id_categoria)
        REFERENCES categorias(id_categoria)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE comentarios (
    id_comentario INT AUTO_INCREMENT PRIMARY KEY,
    id_publicacion INT NOT NULL,
    id_autor INT NOT NULL,
    contenido VARCHAR(2000) NOT NULL,
    fecha_publicacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_eliminacion DATETIME NULL,
    id_eliminado_por INT NULL,
    motivo_eliminacion VARCHAR(500) NULL,
    id_categoria_eliminacion INT NULL,

    INDEX idx_comentarios_eliminacion (fecha_eliminacion),

    FOREIGN KEY (id_eliminado_por)
        REFERENCES usuarios(id_usuario),

    FOREIGN KEY (id_categoria_eliminacion)
        REFERENCES categorias(id_categoria),

    FOREIGN KEY (id_publicacion)
        REFERENCES publicaciones(id_publicacion)
        ON DELETE CASCADE,

    FOREIGN KEY (id_autor)
        REFERENCES usuarios(id_usuario)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE likes (
    id_like INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_publicacion INT NOT NULL,

    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE,

    FOREIGN KEY (id_publicacion)
        REFERENCES publicaciones(id_publicacion)
        ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE baneos (
    id_baneo INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_administrador INT NOT NULL,
    motivo VARCHAR(500) NOT NULL,
    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME NULL,
    activo TINYINT NOT NULL DEFAULT 1,

    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario),

    FOREIGN KEY (id_administrador)
        REFERENCES usuarios(id_usuario)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO categorias (id_categoria, nombre) VALUES
    (1, 'General'),
    (2, 'Académico'),
    (3, 'Eventos'),
    (4, 'Biblioteca'),
    (5, 'Consultas');
