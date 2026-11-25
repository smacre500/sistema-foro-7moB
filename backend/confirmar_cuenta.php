<?php
require __DIR__ . '/comun.php';
$datos = datosPost();
$token = campo($datos, 'token', 64, 64);
$hash = hash('sha256', $token);
$estado = leerEstadoLocal();
$registro = $estado['tokens'][$hash] ?? null;
if (!$registro || (int) $registro['vence'] < time()) {
    error('El enlace venció o ya fue utilizado.', 400);
}

if (($registro['tipo'] ?? '') !== 'cambiar_correo') {
    error('Tipo de enlace inválido.');
}

$idUsuario = (int) $registro['id_usuario'];
$cuenta = consulta('SELECT id_usuario FROM usuarios WHERE id_usuario=?', [$idUsuario])->fetch();
if (!$cuenta || !cuentaActiva($idUsuario)) {
    error('La cuenta no está disponible.', 404);
}

if ($registro['tipo'] === 'cambiar_correo') {
    $correoPendiente = strtolower((string) ($registro['correo_nuevo'] ?? ''));
    if (!correoInstitucional($correoPendiente)) {
        error('El correo debe pertenecer al dominio institucional.');
    }

    if (consulta('SELECT id_usuario FROM usuarios WHERE correo=? AND id_usuario<>?', [$correoPendiente, $idUsuario])->fetch()) {
        error('Ese correo ya está registrado.', 409);
    }

    $idAlternativo = buscarCorreoLocal($correoPendiente);
    if ($idAlternativo && $idAlternativo !== $idUsuario) {
        error('Ese correo ya está registrado.', 409);
    }
}

modificarEstadoLocal(function (&$estado) use ($hash, $registro, $idUsuario) {
    if (!isset($estado['tokens'][$hash]) || (int) $estado['tokens'][$hash]['vence'] < time()) {
        error('El enlace venció o ya fue utilizado.', 400);
    }

    if ($registro['tipo'] === 'cambiar_correo') {
        $correo = strtolower((string) ($registro['correo_nuevo'] ?? ''));
        $estado['usuarios'][(string)$idUsuario]['correo'] = $correo;
        $estado['usuarios'][(string)$idUsuario]['version_sesion'] = (int)($estado['usuarios'][(string)$idUsuario]['version_sesion'] ?? 1) + 1;
    } else {
        error('Tipo de enlace inválido.');
    }

    unset($estado['tokens'][$hash]);
    foreach ($estado['tokens'] as $clave => $otro) {
        if ((int) ($otro['id_usuario'] ?? 0) === $idUsuario) {
            unset($estado['tokens'][$clave]);
        }
    }
});

unset($_SESSION['usuario'], $_SESSION['version_sesion']);
responder(['ok' => true, 'mensaje' => 'Cambios confirmados. Iniciá sesión nuevamente.']);
