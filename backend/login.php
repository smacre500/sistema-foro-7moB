<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
$identidad = campo($datos, 'identidad', 1, 190);
$cuenta = consulta('SELECT * FROM usuarios WHERE correo=? OR nombre_usuario=?', [$identidad, $identidad])->fetch();
$clave = $datos['contrasena'] ?? '';

if (!$cuenta || $cuenta['fecha_baja'] !== null || !is_string($clave) || !password_verify($clave, $cuenta['contrasena_hash'])) {
    error('Usuario o contraseña incorrectos.', 401);
}
if (!correoInstitucional($cuenta['correo'])) {
    error('Solo se admiten cuentas con correo @obralapiedad.com.ar.', 403);
}
session_regenerate_id(true);
$_SESSION['usuario'] = $cuenta['id_usuario'];
$_SESSION['version_sesion'] = (int) $cuenta['version_sesion'];
responder(['ok' => true]);
