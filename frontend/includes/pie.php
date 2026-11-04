    </main>
</div>
<dialog id="acceso" aria-labelledby="titulo-acceso">
    <div class="cabecera-dialogo"><h2 id="titulo-acceso">Iniciar sesión</h2><button type="button" id="cerrar-acceso" aria-label="Cerrar">×</button></div>
    <p class="secundario">Acceso con correo institucional @obralapiedad.com.ar.</p>
    <form id="form-acceso">
        <div id="campos-registro" hidden>
            <label>Nombre<input name="nombre" autocomplete="name" maxlength="100" minlength="2" disabled></label>
            <label>Usuario<input name="nombre_usuario" autocomplete="username" maxlength="30" pattern="[a-zA-Z0-9_]{3,30}" disabled></label>
            <label>Correo institucional<input name="correo" type="email" autocomplete="email" maxlength="190" placeholder="nombre@obralapiedad.com.ar" disabled><small>Debe terminar exactamente en @obralapiedad.com.ar.</small></label>
        </div>
        <label id="campo-identidad">Usuario o correo<input name="identidad" autocomplete="username" maxlength="190" required></label>
        <label>Contraseña<input name="contrasena" type="password" autocomplete="current-password" maxlength="72" required></label>
        <p id="error-acceso" role="alert"></p><button type="submit" id="boton-acceso">Ingresar</button>
    </form>
    <button type="button" class="enlace" id="cambiar-acceso">Crear una cuenta</button>
</dialog>
<noscript>Activá JavaScript para usar el foro.</noscript>
</body>
</html>
