CREATE DATABASE IF NOT EXISTS foro_itlp
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE foro_itlp;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

DROP TABLE IF EXISTS baneos;
DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS comentarios;
DROP TABLE IF EXISTS publicaciones;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    nombre_usuario VARCHAR(30) NOT NULL,
    correo VARCHAR(190) NOT NULL,
    contrasena_hash VARCHAR(255) NOT NULL,
    rol ENUM('usuario', 'administrador') NOT NULL DEFAULT 'usuario',
    biografia VARCHAR(500) NOT NULL DEFAULT '',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
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