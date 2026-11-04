<?php $pagina = 'inicio'; require __DIR__ . '/includes/inicio.php'; ?>
        <section id="vista-inicio">
            <div class="bienvenida"><div><span class="etiqueta">CONECTAMOS IDEAS</span><h1>Hola, comunidad<span>.</span></h1><p>Novedades, preguntas y conversaciones de nuestra institución.</p></div><span class="simbolo" aria-hidden="true">✳</span></div>
            <div class="titulo-publicaciones"><h2>Publicaciones recientes</h2><p class="secundario" id="cantidad-publicaciones">Conversaciones de nuestra comunidad</p></div>
            <div id="publicaciones" aria-live="polite">Cargando publicaciones…</div>
        </section>
<?php require __DIR__ . '/includes/acceso.php'; ?>
