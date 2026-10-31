<?php
require __DIR__ . '/comun.php';

requerirGet();
administrador();

$usuarios = consulta('SELECT id_usuario,nombre,nombre_usuario,correo,rol FROM usuarios ORDER BY id_usuario')->fetchAll();
foreach ($usuarios as &$persona) {
    $persona['baneo'] = baneoActivo($persona['id_usuario']) ?: null;
}

responder(['ok' => true, 'usuarios' => $usuarios]);
