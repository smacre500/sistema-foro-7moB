<?php
require __DIR__ . '/comun.php';
$datos = datosPost();
$usuario = usuarioActual(true);
$clave = $datos['contrasena'] ?? '';
$cuenta = consulta('SELECT contrasena_hash FROM usuarios WHERE id_usuario=?', [$usuario['id_usuario']])->fetch();
if (!is_string($clave) || !password_verify($clave, claveCuenta($usuario['id_usuario'], $cuenta['contrasena_hash']))) {
    error('La contraseña actual no es correcta.', 403);
}

if (($datos['confirmar'] ?? '') !== 'si') {
    error('Confirmá la baja de la cuenta.');
}

if ($usuario['rol'] === 'administrador') {
    $admins = consulta("SELECT id_usuario FROM usuarios WHERE rol='administrador'")->fetchAll();
    $activos = array_filter($admins, fn($admin) => cuentaActiva($admin['id_usuario']));
    if (count($activos) <= 1) {
        error('No se puede dar de baja al último administrador activo.', 409);
    }
}

$id = (string) $usuario['id_usuario'];
modificarEstadoLocal(function (&$estado) use ($id) {
    $estado['usuarios'][$id]['activa'] = false;
    $estado['usuarios'][$id]['version_sesion'] = (int)($estado['usuarios'][$id]['version_sesion'] ?? 1) + 1;

    foreach ($estado['tokens'] as $hash => $token) {
        if ((string) ($token['id_usuario'] ?? '') === $id) {
            unset($estado['tokens'][$hash]);
        }
    }
});

unset($_SESSION['usuario'], $_SESSION['version_sesion']);
responder(['ok' => true]);
