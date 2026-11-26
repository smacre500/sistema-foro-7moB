<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
$usuario = usuarioActual(true);
$correo = strtolower(campo($datos, 'correo', 5, 190));
$clave = $datos['actual'] ?? '';

if (!correoInstitucional($correo)) {
    error('Usá un correo terminado en @obralapiedad.com.ar.');
}
if ($correo === strtolower($usuario['correo'])) {
    error('Ingresá un correo distinto al actual.');
}

$cuenta = consulta(
    'SELECT contrasena_hash FROM usuarios WHERE id_usuario=? AND version_sesion=? AND fecha_baja IS NULL',
    [$usuario['id_usuario'], $_SESSION['version_sesion']]
)->fetch();
if (!$cuenta || !is_string($clave) || !password_verify($clave, $cuenta['contrasena_hash'])) {
    error('La contraseña actual no es correcta.', 403);
}

try {
    $cambio = consulta(
        'UPDATE usuarios SET correo=?,version_sesion=version_sesion+1
         WHERE id_usuario=? AND version_sesion=? AND fecha_baja IS NULL',
        [$correo, $usuario['id_usuario'], $_SESSION['version_sesion']]
    );
} catch (PDOException $e) {
    if (($e->errorInfo[1] ?? null) !== 1062) throw $e;
    error('Ese correo ya está registrado.', 409);
}
if (!$cambio->rowCount()) error('La sesión venció. Iniciá sesión nuevamente.', 401);

unset($_SESSION['usuario'], $_SESSION['version_sesion']);
responder(['ok' => true]);
