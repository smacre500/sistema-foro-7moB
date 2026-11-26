-- Ejecutar una vez sobre la base existente, después de hacer un respaldo.
USE foro_itlp;
SET NAMES utf8mb4;

ALTER TABLE usuarios
    ADD UNIQUE (nombre_usuario),
    ADD UNIQUE (correo),
    ADD fecha_baja DATETIME NULL,
    ADD version_sesion INT UNSIGNED NOT NULL DEFAULT 1,
    ADD debe_cambiar_clave TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE publicaciones
    ADD fecha_eliminacion DATETIME NULL,
    ADD id_eliminado_por INT NULL,
    ADD motivo_eliminacion VARCHAR(500) NULL,
    ADD INDEX idx_publicaciones_eliminacion (fecha_eliminacion),
    ADD FOREIGN KEY (id_eliminado_por) REFERENCES usuarios(id_usuario);

ALTER TABLE comentarios
    ADD fecha_eliminacion DATETIME NULL,
    ADD id_eliminado_por INT NULL,
    ADD motivo_eliminacion VARCHAR(500) NULL,
    ADD id_categoria_eliminacion INT NULL,
    ADD INDEX idx_comentarios_eliminacion (fecha_eliminacion),
    ADD FOREIGN KEY (id_eliminado_por) REFERENCES usuarios(id_usuario),
    ADD FOREIGN KEY (id_categoria_eliminacion) REFERENCES categorias(id_categoria);
