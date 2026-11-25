<?php
require __DIR__ . '/comun.php';
$datos = datosPost();
$usuario = usuarioActual(true);
$correo = strtolower(campo($datos, 'correo', 5, 190));
$clave = $datos['contrasena'] ?? '';
$cuenta = consulta('SELECT contrasena_hash,correo FROM usuarios WHERE id_usuario=?', [$usuario['id_usuario']])->fetch();
if (!is_string($clave) || !password_verify($clave, claveCuenta($usuario['id_usuario'], $cuenta['contrasena_hash']))) {
    error('La contraseña actual no es correcta.', 403);
}

if (!correoInstitucional($correo)) {
    error('Usá un correo terminado en @obralapiedad.com.ar.');
}

if (consulta('SELECT id_usuario FROM usuarios WHERE correo=?', [$correo])->fetch() || buscarCorreoLocal($correo)) {
    error('Ese correo ya está registrado.', 409);
}

$token = bin2hex(random_bytes(32));
$hash = hash('sha256', $token);
$id = (int) $usuario['id_usuario'];
$ahora = time();

modificarEstadoLocal(function (&$estado) use ($id, $hash, $correo, $ahora) {
    foreach ($estado['tokens'] as $clave => $registro) {
        if ((int) ($registro['id_usuario'] ?? 0) === $id && ($registro['tipo'] ?? '') === 'cambiar_correo') {
            unset($estado['tokens'][$clave]);
        }
    }

    $estado['tokens'][$hash] = [
        'id_usuario' => $id,
        'tipo' => 'cambiar_correo',
        'correo_nuevo' => $correo,
        'vence' => $ahora + 1800,
        'creado' => $ahora,
    ];
});

$url = rtrim($urlForo, '/') . '/inicio?token=' . rawurlencode($token) . '&accion=confirmar-correo';
$cabeceras = "From: foro@obralapiedad.com.ar\r\nContent-Type: text/plain; charset=UTF-8";
if (!mail($correo, 'Confirmar correo del foro', "Abrí este enlace para confirmar tu nuevo correo (vence en 30 minutos):\n\n$url", $cabeceras)) {
    error('No se pudo enviar el correo. Revisá la configuración del servidor.', 503);
}

responder(['ok' => true, 'mensaje' => 'Te enviamos un enlace de confirmación al correo nuevo.']);
