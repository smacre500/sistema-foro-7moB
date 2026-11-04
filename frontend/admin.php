<?php

require_once __DIR__ . '/../backend/comun.php';

$usuario = usuarioActual(false);

// Con restringir el rol administrador desde el servidor ya es suficiente para que nadie pueda acceder
if (!$usuario || $usuario['rol'] !== 'administrador') {
    header('Location: inicio');
    exit;
}

header('Content-Type: text/html; charset=utf-8');

$pagina = 'admin';
require __DIR__ . '/includes/inicio.php'; ?>
<section id="vista-admin">
    <div class="cabecera-admin"><span class="etiqueta">GESTIÓN DE LA COMUNIDAD</span>
        <h1>Administración de usuarios</h1>
        <p class="secundario">Buscá una cuenta por usuario, nombre o correo para revisarla o aplicar una sanción.</p>
    </div>
    <section class="tarjeta" id="panel-baneo" hidden>
        <h2 id="titulo-baneo">Banear usuario</h2>
        <form id="form-baneo"><input type="hidden" name="id_usuario">
            <label>Motivo<textarea name="motivo" minlength="5" maxlength="500" rows="3" required></textarea></label>
            <label>Duración<select name="dias">
                    <option value="1">1 día</option>
                    <option value="7">7 días</option>
                    <option value="30">30 días</option>
                    <option value="0">Permanente</option>
                </select></label>
            <div class="acciones"><button type="submit" class="peligro">Aplicar baneo</button><button type="button" class="secundario-boton" id="cancelar-baneo">Cancelar</button></div>
        </form>
    </section>
    <section class="tarjeta tabla-admin">
        <div class="barra-admin">
            <div class="buscador-usuarios"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <circle cx="10.5" cy="10.5" r="6.5" />
                    <path d="m16 16 5 5" />
                </svg><label class="solo-lector" for="buscar-usuario">Buscar usuarios</label><input type="search" id="buscar-usuario" maxlength="120" autocomplete="off" placeholder="Buscar por usuario, nombre o correo…"></div>
            <p class="secundario" id="conteo-usuarios" aria-live="polite"></p>
        </div>
        <p class="pista-tabla secundario">Deslizá la tabla para ver todas las columnas.</p>
        <div class="tabla-envoltura">
            <table class="tabla-usuarios">
                <caption class="solo-lector">Cuentas registradas en el foro</caption>
                <thead>
                    <tr>
                        <th scope="col">Persona</th>
                        <th scope="col">Correo</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="celda-acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody id="usuarios">
                    <tr>
                        <td colspan="5" class="vacio">Cargando cuentas…</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p id="sin-usuarios" class="vacio" hidden>No se encontraron cuentas con esa búsqueda.</p>
    </section>
</section>
<?php require __DIR__ . '/includes/acceso.php'; ?>