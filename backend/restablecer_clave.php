<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
administrador();
$idUsuario = id($datos['id_usuario'] ?? null);
$pdo->beginTransaction();
$cuenta = consulta('SELECT rol,fecha_baja FROM usuarios WHERE id_usuario=? FOR UPDATE', [$idUsuario])->fetch();

if (!$cuenta) {
    error('No se encontró la cuenta.', 404);
}

if ($cuenta['rol'] !== 'usuario') {
    error('Solo se pueden restablecer cuentas de estudiantes.', 403);
}

if ($cuenta['fecha_baja'] !== null) {
    error('La cuenta está desactivada.', 403);
}

if (baneoActivo($idUsuario)) {
    error('La cuenta está suspendida. Levantá la suspensión antes de restablecer la contraseña.', 403);
}

$temporal = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
$hash = password_hash($temporal, PASSWORD_DEFAULT);

consulta(
    'UPDATE usuarios SET contrasena_hash=?,debe_cambiar_clave=1,version_sesion=version_sesion+1 WHERE id_usuario=?',
    [$hash, $idUsuario]
);
$pdo->commit();

responder(['ok' => true, 'contrasena_temporal' => $temporal]);
