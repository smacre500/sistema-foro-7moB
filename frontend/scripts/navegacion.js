import { estado, esAdmin, esBaneado } from './estado.js';
import { $, escapar, mensaje } from './util.js';
import { api } from './api.js';
import { abrirAcceso, actualizarCuenta } from './acceso.js';
import { cargarPublicaciones } from './publicaciones.js';
import { cargarPerfil } from './perfil.js';
import { cargarUsuarios } from './admin.js';
import { limpiarEditor } from './editor.js';
export async function irA(ruta) {
    if (location.hash !== ruta) history.replaceState(null, '', ruta);
    await navegar();
}

export async function navegar() {
    const ruta = location.hash || '#inicio';
    const enPerfil = ruta.startsWith('#perfil');
    const enAdmin = ruta === '#admin';
    const enCrear = ruta === '#crear';

    if (enCrear && (!estado.usuario || esBaneado())) {
        if (!estado.usuario) abrirAcceso(false);
        else mensaje('Tu cuenta está suspendida: no podés crear publicaciones.', true);
        await irA('#inicio');
        return;
    }

    $('#vista-inicio').hidden = enPerfil || enAdmin || enCrear;
    $('#vista-crear').hidden = !enCrear;
    $('#vista-perfil').hidden = !enPerfil;
    $('#vista-admin').hidden = !enAdmin;
    estado.autor = 0;

    if (enCrear) {
        limpiarEditor();
        $('#form-publicacion').elements.titulo.focus();
    } else if (enAdmin) {
        if (!esAdmin()) {
            location.hash = '#inicio';
            throw new Error('Esta sección es exclusiva para administradores.');
        }
        await cargarUsuarios();
    } else if (enPerfil) {
        const coincidencia = ruta.match(/^#perfil(?:\/(\d+))?$/);
        const identificador = coincidencia?.[1] || estado.usuario?.id_usuario;
        if (!identificador) {
            location.hash = '#inicio';
            throw new Error('Iniciá sesión para ver tu perfil.');
        }

        await cargarPerfil(identificador);
        await cargarPublicaciones(estado.autor);
    } else {
        await cargarPublicaciones();
    }
}
export async function iniciar() {
    const datos = await api('inicio');
    estado.usuario = datos.usuario;
    estado.csrf = datos.csrf;

    actualizarCuenta();
    $('#categorias').innerHTML = datos.categorias
        .map(
            (categoria) =>
                `<option value="${Number(categoria.id_categoria)}">${escapar(categoria.nombre)}</option>`
        )
        .join('');

    if (estado.usuario && $('#acceso').open) $('#acceso').close();
    await navegar();
}
