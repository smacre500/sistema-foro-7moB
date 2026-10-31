<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
administrador();

consulta('UPDATE baneos SET activo=0 WHERE id_baneo=?', [id($datos['id_baneo'] ?? null)]);
responder(['ok' => true]);
