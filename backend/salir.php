<?php
require __DIR__ . '/comun.php';

datosPost();
unset($_SESSION['usuario']);
session_regenerate_id(true);
responder(['ok' => true]);
