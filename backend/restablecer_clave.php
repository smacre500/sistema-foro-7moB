<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
administrador();
$idUsuario = id($datos['id_usuario'] ?? null);
$cuenta = consulta('SELECT id_usuario,rol FROM usuarios WHERE id_usuario=?', [$idUsuario])->fetch();

if (!$cuenta) {
    error('No se encontró la cuenta.', 404);
}

if ($cuenta['rol'] !== 'usuario') {
    error('Solo se pueden restablecer cuentas de estudiantes.', 403);
}

if (!cuentaActiva($idUsuario)) {
    error('La cuenta está desactivada.', 403);
}

if (baneoActivo($idUsuario)) {
    error('La cuenta está suspendida. Levantá la suspensión antes de restablecer la contraseña.', 403);
}

$temporal = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
$hash = password_hash($temporal, PASSWORD_DEFAULT);

modificarEstadoLocal(function (&$estado) use ($idUsuario, $hash) {
    $id = (string) $idUsuario;
    $estado['usuarios'][$id]['contrasena_hash'] = $hash;
    $estado['usuarios'][$id]['debe_cambiar_clave'] = true;
    $estado['usuarios'][$id]['version_sesion'] = (int) ($estado['usuarios'][$id]['version_sesion'] ?? 1) + 1;

    foreach ($estado['tokens'] as $tokenHash => $token) {
        if ((int) ($token['id_usuario'] ?? 0) === $idUsuario) {
            unset($estado['tokens'][$tokenHash]);
        }
    }
});

responder(['ok' => true, 'contrasena_temporal' => $temporal]);
