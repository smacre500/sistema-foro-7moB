<?php $pagina = 'perfil'; require __DIR__ . '/includes/header.php'; ?>
        <section id="vista-perfil">
            <section class="tarjeta" id="datos-perfil"></section>
            <section class="tarjeta" id="editar-perfil" hidden><h2>Editar mi perfil</h2><form id="form-perfil">
                <label>Nombre<input name="nombre" minlength="2" maxlength="100" required></label>
                <label>Sobre mí<textarea name="biografia" rows="3" maxlength="500"></textarea></label>
                <button type="submit">Guardar cambios</button>
            </form></section>
            <section class="tarjeta" id="seguridad-cuenta" hidden><h2>Seguridad de la cuenta</h2>
                <p id="aviso-clave-temporal" class="secundario" hidden>Estás usando una contraseña temporal. Cambiala ahora para continuar usando el foro.</p>
                <form id="form-cambiar-clave"><label>Contraseña actual<input type="password" name="actual" autocomplete="current-password" required></label><label>Nueva contraseña<input type="password" name="nueva" minlength="10" maxlength="72" autocomplete="new-password" required></label><button type="submit">Cambiar contraseña</button></form>
                <form id="form-cambiar-correo"><label>Correo nuevo<input type="email" name="correo" maxlength="190" required></label><label>Contraseña actual<input type="password" name="contrasena" autocomplete="current-password" required></label><button type="submit">Solicitar cambio de correo</button><small>Confirmaremos la dirección nueva por correo antes de actualizarla.</small></form>
                <form id="form-dar-baja"><label>Confirmá tu contraseña<input type="password" name="contrasena" autocomplete="current-password" required></label><label><span><input type="checkbox" name="confirmar" value="si" required> Entiendo que mi cuenta quedará desactivada.</span></label><button type="submit" class="peligro">Dar de baja mi cuenta</button></form>
            </section>
            <h2>Publicaciones del perfil</h2><div id="publicaciones-perfil"></div>
            <nav class="paginacion" id="paginacion-perfil" aria-label="Páginas de publicaciones del perfil"></nav>
        </section>
<?php require __DIR__ . '/includes/pie.php'; ?>
