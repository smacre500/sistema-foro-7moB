<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
$nombre = campo($datos, 'nombre', 2, 100);
$alias = campo($datos, 'nombre_usuario', 3, 30);
$correo = strtolower(campo($datos, 'correo', 5, 190));
$clave = $datos['contrasena'] ?? '';

if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $alias)) {
    error('El usuario solo admite letras sin acentos, números y guion bajo.');
}
if (!correoInstitucional($correo)) {
    error('Usá un correo terminado en @obralapiedad.com.ar.');
}
if (!is_string($clave) || strlen($clave) < 10 || strlen($clave) > 72) {
    error('La contraseña debe tener entre 10 y 72 bytes.');
}
if (consulta('SELECT id_usuario FROM usuarios WHERE correo=? OR nombre_usuario=?', [$correo, $alias])->fetch()) {
    error('Ese correo o usuario ya está registrado.', 409);
}

consulta(
    "INSERT INTO usuarios(nombre,nombre_usuario,correo,contrasena_hash,rol) VALUES (?,?,?,?,'usuario')",
    [$nombre, $alias, $correo, password_hash($clave, PASSWORD_DEFAULT)]
);
$_SESSION['usuario'] = (int) $pdo->lastInsertId();
session_regenerate_id(true);
responder(['ok' => true]);
