import { estado, esAdmin, esBaneado } from './estado.js';
import { $, escapar, mensaje } from './util.js';
import { api } from './api.js';
import { actualizarCuenta } from './acceso.js';
import { cargarPublicaciones } from './publicaciones.js';
import { cargarPerfil } from './perfil.js';
import { cargarUsuarios } from './admin.js';
import { limpiarEditor, abrirEditor } from './editor.js';

const base = () => document.body.dataset.base || '';

export function irA(ruta) {
    const paginas = { '#inicio': '/inicio', '#crear': '/crear', '#admin': '/admin' };
    location.href = `${base()}${paginas[ruta] || ruta}`;
}

export async function navegar() {
    const pagina = document.body.dataset.pagina;
    if (pagina === 'inicio') {
        await cargarPublicaciones();
        return;
    }
    if (pagina === 'crear') {
        if (!estado.usuario) {
            location.href = `${base()}/inicio?acceso=1`;
            return;
        }
        if (esBaneado()) {
            mensaje('Tu cuenta está suspendida: no podés crear publicaciones.', true);
            irA('#inicio');
            return;
        }
        limpiarEditor();
        const id = new URLSearchParams(location.search).get('editar');
        if (id) {
            const datos = await api('publicaciones');
            const publicacion = datos.publicaciones.find((item) => Number(item.id_publicacion) === Number(id));
            if (!publicacion || Number(publicacion.id_autor) !== Number(estado.usuario.id_usuario)) {
                mensaje('No se encontró una publicación tuya para editar.', true);
                return;
            }
            abrirEditor(publicacion);
        } else {
            $('#form-publicacion').elements.titulo.focus();
        }
        return;
    }
    if (pagina === 'perfil') {
        const username = document.body.dataset.username || '';
        const identidad = username || estado.usuario?.nombre_usuario;
        if (!identidad) {
            mensaje('Iniciá sesión para ver tu perfil.', true);
            location.href = `${base()}/inicio?acceso=1`;
            return;
        }
        const perfil = await cargarPerfil(identidad);
        await cargarPublicaciones(Number(perfil.id_usuario));
        return;
    }
    if (pagina === 'admin') {
        if (!esAdmin()) {
            mensaje('Esta sección es exclusiva para administradores.', true);
            irA('#inicio');
            return;
        }
        await cargarUsuarios();
    }
}

export async function iniciar() {
    const datos = await api('inicio');
    estado.usuario = datos.usuario;
    estado.csrf = datos.csrf;
    actualizarCuenta();
    const categorias = $('#categorias');
    if (categorias) {
        categorias.innerHTML = datos.categorias.map((categoria) =>
            `<option value="${Number(categoria.id_categoria)}">${escapar(categoria.nombre)}</option>`
        ).join('');
    }
    if (estado.usuario && $('#acceso')?.open) $('#acceso').close();
    await navegar();
}
