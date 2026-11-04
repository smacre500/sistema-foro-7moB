<?php $pagina = 'crear'; require __DIR__ . '/includes/header.php'; ?>
        <section id="vista-crear">
            <h1 id="titulo-editor">Crear publicación</h1><p class="secundario">Compartí una idea, una pregunta o una novedad con la comunidad.</p>
            <section class="tarjeta"><form id="form-publicacion"><input type="hidden" name="id_publicacion">
                <label>Título<input name="titulo" minlength="3" maxlength="150" required></label>
                <label>Categoría<select name="id_categoria" id="categorias" required></select></label>
                <label>Contenido<textarea name="contenido" rows="6" minlength="5" maxlength="10000" required></textarea></label>
                <div class="acciones"><button type="submit" id="guardar-publicacion">Guardar publicación</button><button type="button" class="secundario-boton" id="cancelar-edicion">Cancelar</button></div>
            </form></section>
        </section>
<?php require __DIR__ . '/includes/pie.php'; ?>
