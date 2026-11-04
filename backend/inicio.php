<?php
require __DIR__ . '/comun.php';

requerirGet();

$usuario = usuarioActual();
if ($usuario) {
    $usuario['baneo'] = baneoActivo($usuario['id_usuario']) ?: null;
}
responder([
    'ok' => true,
    'usuario' => $usuario,
    'csrf' => $_SESSION['csrf'],
    'categorias' => consulta('SELECT * FROM categorias')->fetchAll(),
]);
