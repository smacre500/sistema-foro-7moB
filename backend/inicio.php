<?php
require __DIR__ . '/comun.php';

requerirGet();

$usuario = usuarioActual();
responder([
    'ok' => true,
    'usuario' => $usuario,
    'csrf' => $_SESSION['csrf'],
    'categorias' => consulta('SELECT * FROM categorias')->fetchAll(),
]);
