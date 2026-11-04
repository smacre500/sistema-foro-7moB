<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
$identidad = campo($datos, 'identidad', 1, 190);
$cuenta = consulta('SELECT * FROM usuarios WHERE correo=? OR nombre_usuario=?', [$identidad, $identidad])->fetch();
$clave = $datos['contrasena'] ?? '';

if (!$cuenta || !is_string($clave) || !password_verify($clave, $cuenta['contrasena_hash'])) {
    error('Usuario o contraseña incorrectos.', 401);
}
if (!correoInstitucional($cuenta['correo'])) {
    error('Solo se admiten cuentas con correo @obralapiedad.com.ar.', 403);
}
// Las cuentas suspendidas pueden ingresar para consultar el foro, pero no publicar ni comentar.

session_regenerate_id(true);
$_SESSION['usuario'] = $cuenta['id_usuario'];
responder(['ok' => true]);
