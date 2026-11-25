<?php
require __DIR__ . '/comun.php';

datosPost();
unset($_SESSION['usuario'], $_SESSION['version_sesion']);
session_regenerate_id(true);
responder(['ok' => true]);
