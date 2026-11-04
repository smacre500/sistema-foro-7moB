<?php
// Estas páginas varían según la ruta y la sesión; no deben reutilizarse desde caché.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$pagina = $pagina ?? 'inicio';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = preg_replace('#/(?:inicio|crear|admin|perfil(?:/[A-Za-z0-9_]{3,30})?)/?$#', '', $requestPath);
$base = rtrim($base ?? '', '/');
if ($base === '/') $base = '';
function e($valor) { return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foro Institucional</title>
    <link rel="stylesheet" href="<?= e($base) ?>/frontend/estilos.css?v=20261104-nocache2">
    <script type="module" src="<?= e($base) ?>/frontend/scripts/main.js?v=20261104-nocache2"></script>
</head>
<body data-pagina="<?= e($pagina) ?>" data-base="<?= e($base) ?>" data-username="<?= e($_GET['username'] ?? '') ?>">
<header>
    <div class="barra-superior">
        <a href="<?= e($base) ?>/inicio" class="marca" aria-label="Foro Institucional, inicio"><span class="marca-icono" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 4h16v12H9l-5 4V4Z"/><path d="M8 8h8M8 12h5"/></svg></span><span>Foro<small>INSTITUCIONAL</small></span></a>
        <form id="form-buscar" class="busqueda" role="search" action="<?= e($base) ?>/inicio" method="get">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
            <label class="solo-lector" for="buscar">Buscar publicaciones</label>
            <input type="search" id="buscar" name="buscar" maxlength="120" placeholder="Buscar en la comunidad…" value="<?= e($_GET['buscar'] ?? '') ?>">
            <button type="submit">Buscar</button>
        </form>
        <div class="acciones-cuenta"><div id="acciones-acceso"><button type="button" id="ingresar">Ingresar</button><button type="button" id="registrarse">Registrarme</button></div><button id="salir" type="button" hidden>Cerrar sesión</button></div>
    </div>
</header>
<div class="contenedor">
    <aside class="lateral" aria-label="Perfil y navegación">
        <section class="tarjeta perfil-lateral"><div class="avatar grande" id="avatar-perfil" aria-hidden="true">FI</div><h2 id="nombre-actual">Tu comunidad</h2><p class="secundario" id="detalle-actual">Un espacio para compartir y aprender.</p></section>
        <nav class="navegacion" aria-label="Navegación principal">
            <a href="<?= e($base) ?>/inicio"><span aria-hidden="true">⌂</span> Inicio</a>
            <a href="<?= e($base) ?>/perfil" id="enlace-perfil" hidden><span aria-hidden="true">◎</span> Mi perfil</a>
            <a href="<?= e($base) ?>/crear" id="enlace-crear"><span aria-hidden="true">＋</span> Crear publicación</a>
            <a href="<?= e($base) ?>/admin" id="enlace-admin" hidden><span aria-hidden="true">⚑</span> Administración</a>
        </nav>
    </aside>
    <main>
        <p id="mensaje" role="status" hidden></p>
