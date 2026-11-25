<?php $pagina = 'inicio';
require __DIR__ . '/includes/header.php'; ?>
<section id="vista-inicio">
    <div class="titulo-publicaciones">
        <h2>Publicaciones recientes</h2>
        <p class="secundario" id="cantidad-publicaciones">Conversaciones de nuestra comunidad</p>
    </div>
    <div id="publicaciones" aria-live="polite">Cargando publicaciones…</div>
    <nav class="paginacion" id="paginacion-inicio" aria-label="Páginas de publicaciones"></nav>
</section>
<?php require __DIR__ . '/includes/pie.php'; ?>
