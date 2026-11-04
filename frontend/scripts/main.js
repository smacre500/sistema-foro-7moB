import { estado, esBaneado } from './estado.js';
import { $, guardarFormulario, mensaje } from './util.js';
import { api } from './api.js';
import {
    abrirAcceso,
    guardarAcceso,
    prepararAcceso,
    validarCorreo
} from './acceso.js';
import {
    alternarLike,
    cargarPublicaciones,
    comentar,
    editarComentario,
    eliminarContenido
} from './publicaciones.js';
import { abrirEditor, guardarPublicacion, limpiarEditor } from './editor.js';
import { guardarPerfil } from './perfil.js';
import {
    abrirBaneo,
    aplicarBaneo,
    buscarUsuario,
    filtrarUsuarios,
    levantarBaneo
} from './admin.js';
import { iniciar, irA, navegar } from './navegacion.js';
$('#ingresar').addEventListener('click', () => abrirAcceso(false));
$('#registrarse').addEventListener('click', () => abrirAcceso(true));
$('#cerrar-acceso').addEventListener('click', () => $('#acceso').close());
$('#cambiar-acceso').addEventListener('click', () => prepararAcceso(!estado.registro));
$('#form-acceso').elements.correo.addEventListener('input', validarCorreo);
$('#acceso').addEventListener('close', () => {
    $('#form-acceso').elements.contrasena.value = '';
    $('#error-acceso').textContent = '';
});

guardarFormulario(
    '#form-acceso',
    async (datos, formulario) => {
        await guardarAcceso(datos);
        formulario.reset();
        await iniciar();
    },
    (error) => {
        $('#error-acceso').textContent = error.message;
    }
);

$('#salir').addEventListener('click', async () => {
    const boton = $('#salir');
    boton.disabled = true;
    try {
        await api('salir', {});
        location.hash = '#inicio';
        mensaje('Cerraste sesión.');
        await iniciar();
    } catch (error) {
        mensaje(error.message, true);
    } finally {
        boton.disabled = false;
    }
});
const enlaceCrear = $('#enlace-crear');
if (enlaceCrear) {
    enlaceCrear.addEventListener('click', (evento) => {
        if (!estado.usuario) {
            evento.preventDefault();
            abrirAcceso(false);
        } else if (esBaneado()) {
            evento.preventDefault();
            mensaje('Tu cuenta está suspendida: no podés crear publicaciones.', true);
        }
    });
}

guardarFormulario('#form-publicacion', async (datos) => {
    await guardarPublicacion(datos);
    mensaje('Publicación guardada.');
    await irA('#inicio');
});

$('#cancelar-edicion').addEventListener('click', async () => {
    limpiarEditor();
    await irA('#inicio');
});
guardarFormulario('#form-perfil', async (datos) => {
    await guardarPerfil(datos);
    mensaje('Perfil actualizado.');
    await iniciar();
});

guardarFormulario('#form-baneo', async (datos) => {
    await aplicarBaneo(datos);
    mensaje('Baneo aplicado.');
});

$('#cancelar-baneo').addEventListener('click', () => {
    $('#panel-baneo').hidden = true;
});

$('#buscar-usuario').addEventListener('input', filtrarUsuarios);
document.addEventListener('click', async (evento) => {
    const boton = evento.target.closest('[data-accion]');
    if (!boton || boton.disabled) return;

    const accion = boton.dataset.accion;
    const identificador = Number(boton.dataset.id);

    boton.disabled = true;

    try {
        if (accion === 'me-gusta') {
            await alternarLike(identificador);
        } else if (accion === 'editar-publicacion') {
            const publicacion = estado.publicaciones.find(
                (elemento) => Number(elemento.id_publicacion) === identificador
            );
            if (!publicacion) return;

            await irA('#crear');
            abrirEditor(publicacion);
        } else if (accion === 'editar-comentario') {
            await editarComentario(identificador);
        } else if (accion.startsWith('eliminar-')) {
            await eliminarContenido(accion.replace('eliminar-', ''), identificador);
        } else if (accion === 'banear') {
            const persona = buscarUsuario(identificador);
            if (persona) abrirBaneo(persona);
        } else if (accion === 'desbanear') {
            const persona = buscarUsuario(identificador);
            if (!persona) return;

            await levantarBaneo(persona);
            mensaje('Baneo levantado.');
        }
    } catch (error) {
        mensaje(error.message, true);
    } finally {
        boton.disabled = false;
    }
});

document.addEventListener('submit', async (evento) => {
    const formulario = evento.target.closest('.form-comentario');
    if (!formulario) return;

    evento.preventDefault();
    const boton = formulario.querySelector('button');
    if (boton.disabled) return;
    boton.disabled = true;

    try {
        await comentar(formulario);
        mensaje('Comentario publicado.');
    } catch (error) {
        mensaje(error.message, true);
    } finally {
        boton.disabled = false;
    }
});
guardarFormulario('#form-buscar', async () => {
    if (location.hash && location.hash !== '#inicio') location.hash = '#inicio';
    else await cargarPublicaciones();
});

window.addEventListener('hashchange', () =>
    navegar().catch((error) => mensaje(error.message, true))
);

if (location.protocol === 'file:') {
    mensaje('Abrí el foro desde el servidor de XAMPP.', true);
} else {
    iniciar().catch((error) => mensaje(error.message, true));
}
