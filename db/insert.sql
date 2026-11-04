use foro_itlp;

INSERT INTO categorias (nombre) VALUES ('General'), ('Academico'), ('Eventos'), ('Biblioteca'), ('Consultas');

SET time_zone = '+00:00';

INSERT INTO usuarios
    (id_usuario, nombre, nombre_usuario, correo, contrasena_hash, biografia)
VALUES
    (1, 'Administracion de ejemplo', 'admin_demo', 'admin.demo@obralapiedad.com.ar', '$2y$10$0V6H3QH0kqW26woZMfCb2uxMJLP9voPcMJ09cBLgzNW1hY89reqwW', 'Cuenta ficticia para mostrar las tareas de administracion.'),
    (2, 'Lucia Fernandez', 'estudiante_demo', 'estudiante.demo@obralapiedad.com.ar', '$2y$10$0V6H3QH0kqW26woZMfCb2uxMJLP9voPcMJ09cBLgzNW1hY89reqwW', 'Estudiante de 7B. Me interesan la programacion y los proyectos en equipo.'),
    (3, 'Martin Lopez', 'docente_demo', 'docente.demo@obralapiedad.com.ar', '$2y$10$0V6H3QH0kqW26woZMfCb2uxMJLP9voPcMJ09cBLgzNW1hY89reqwW', 'Docente. Compartimos recursos y novedades de las materias.'),
    (4, 'Usuario suspendido de ejemplo', 'suspendido_demo', 'suspendido.demo@obralapiedad.com.ar', '$2y$10$0V6H3QH0kqW26woZMfCb2uxMJLP9voPcMJ09cBLgzNW1hY89reqwW', 'Cuenta ficticia para mostrar un baneo activo.'),
    (5, 'Ana Gomez', 'egresado_demo', 'egresado.demo@obralapiedad.com.ar', '$2y$10$0V6H3QH0kqW26woZMfCb2uxMJLP9voPcMJ09cBLgzNW1hY89reqwW', 'Cuenta ficticia con una sancion anterior ya levantada.');

UPDATE usuarios
SET rol = 'administrador'
WHERE nombre_usuario = 'admin_demo';

INSERT INTO publicaciones
    (id_publicacion, id_autor, id_categoria, titulo, contenido, fecha_publicacion)
VALUES
    (1, 1, 1, 'Bienvenidos al foro de nuestra institucion', ' Podes compartir publicaciones, comentar y dar Me gusta. Para participar, registrate con tu correo institucional.', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 4 DAY)),
    (2, 3, 2, 'Proyecto Final', 'La entrega incluye el codigo fuente, el script MySQL y la documentacion del sistema.', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 3 DAY)),
    (3, 2, 4, 'SQL', 'SELECT, INSERT y UPDATE. Me aburri y no sabia que poner', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY)),
    (4, 3, 3, 'Propuestas', 'Esta semana nos reunimos a compartir propuestas.', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)),
    (5, 2, 5, 'Intertribus', 'Estamos preparando las intertribus.', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 HOUR));

INSERT INTO comentarios
    (id_comentario, id_publicacion, id_autor, contenido, fecha_publicacion)
VALUES
    (1, 1, 2, 'Ya podemos compartir las novedades del curso.', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 3 DAY)),
    (2, 2, 2, 'Vamos a organizar los archivos por frontend y backend.', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY)),
    (3, 3, 3, 'metemos datos ficticios', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)),
    (4, 4, 2, 'Nuestro grupo quiere presentar el foro institucional.', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 12 HOUR)),
    (5, 5, 3, 'Hay intertribus en la escuela', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR));

INSERT INTO likes (id_usuario, id_publicacion)
VALUES (2, 1), (3, 1), (1, 2), (2, 4), (3, 5), (1, 5);

INSERT INTO baneos
    (id_usuario, id_administrador, motivo, fecha_inicio, fecha_fin, activo)
VALUES
    (4, 1, 'Ejemplo de sancion permanente para  la administracion.', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY), NULL, 1),
    (5, 1, 'Ejemplo de sancion anterior que ya fue levantada.', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 DAY), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 3 DAY), 0);

