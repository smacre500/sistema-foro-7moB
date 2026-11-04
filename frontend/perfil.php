<?php $pagina = 'perfil'; require __DIR__ . '/includes/inicio.php'; ?>
        <section id="vista-perfil">
            <section class="tarjeta" id="datos-perfil"></section>
            <section class="tarjeta" id="editar-perfil" hidden><h2>Editar mi perfil</h2><form id="form-perfil">
                <label>Nombre<input name="nombre" minlength="2" maxlength="100" required></label>
                <label>Sobre mí<textarea name="biografia" rows="3" maxlength="500"></textarea></label>
                <button type="submit">Guardar cambios</button>
            </form></section>
            <h2>Publicaciones del perfil</h2><div id="publicaciones-perfil"></div>
        </section>
<?php require __DIR__ . '/includes/acceso.php'; ?>
