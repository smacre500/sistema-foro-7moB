import { estado, esBaneado } from './estado.js';
import { $, guardarFormulario, mensaje } from './util.js';
import { api } from './api.js';
import { abrirAcceso, guardarAcceso, prepararAcceso, validarCorreo } from './acceso.js';
import { alternarLike, comentar, editarComentario, eliminarContenido, guardarComentarioEditado, confirmarEliminacion } from './publicaciones.js';
import { guardarPublicacion, limpiarEditor } from './editor.js';
import { guardarPerfil } from './perfil.js';
import { abrirBaneo, aplicarBaneo, buscarUsuario, filtrarUsuarios, levantarBaneo, cargarReportes } from './admin.js';
import { iniciar, irA } from './navegacion.js';

const on = (selector, evento, handler) => $(selector)?.addEventListener(evento, handler);

on('#ingresar', 'click', () => abrirAcceso(false));
on('#registrarse', 'click', () => abrirAcceso(true));
on('#cerrar-acceso', 'click', () => $('#acceso').close());
on('#cambiar-acceso', 'click', () => prepararAcceso(!estado.registro));
on('#form-acceso', 'input', (evento) => { if (evento.target.name === 'correo') validarCorreo(); });
on('#acceso', 'close', () => {
    $('#form-acceso').elements.contrasena.value = '';
    $('#error-acceso').textContent = '';
});

if ($('#form-acceso')) guardarFormulario('#form-acceso', async (datos, formulario) => {
    await guardarAcceso(datos);
    formulario.reset();
    await iniciar();
}, (error) => { $('#error-acceso').textContent = error.message; });

on('#salir', 'click', async () => {
    const boton = $('#salir');
    boton.disabled = true;
    try {
        await api('salir', {});
        mensaje('Cerraste sesión.');
        location.href = `${document.body.dataset.base || ''}/inicio`;
    } catch (error) {
        mensaje(error.message, true);
        boton.disabled = false;
    }
});

on('#enlace-crear', 'click', (evento) => {
    if (!estado.usuario) {
        evento.preventDefault();
        abrirAcceso(false);
    } else if (esBaneado()) {
        evento.preventDefault();
        mensaje('Tu cuenta está suspendida: no podés crear publicaciones.', true);
    }
});

if ($('#form-publicacion')) guardarFormulario('#form-publicacion', async (datos) => {
    await guardarPublicacion(datos);
    location.href = `${document.body.dataset.base || ''}/inicio?mensaje=publicacion`;
});
on('#cancelar-edicion', 'click', () => {
    limpiarEditor();
    irA('#inicio');
});
if ($('#form-perfil')) guardarFormulario('#form-perfil', async (datos) => {
    await guardarPerfil(datos);
    mensaje('Perfil actualizado.');
    await iniciar();
});
if ($('#form-cambiar-clave')) guardarFormulario('#form-cambiar-clave', async (datos) => {
    await api('cambiar_clave', datos);
    location.href = `${document.body.dataset.base || ''}/inicio?mensaje=sesion`;
});
if ($('#form-cambiar-correo')) guardarFormulario('#form-cambiar-correo', async (datos) => {
    const resultado = await api('cambiar_correo', datos);
    mensaje(resultado.mensaje);
});
if ($('#form-dar-baja')) guardarFormulario('#form-dar-baja', async (datos) => {
    await api('dar_baja', datos);
    location.href = `${document.body.dataset.base || ''}/inicio?mensaje=baja`;
});
if ($('#form-reportes')) guardarFormulario('#form-reportes', async (datos) => {
    await cargarReportes(datos);
}, (error) => { $('#error-reportes').textContent = error.message; });
document.querySelectorAll('.cancelar-dialogo, .cerrar-dialogo').forEach((boton) => boton.addEventListener('click', () => boton.closest('dialog').close()));
if ($('#form-editar-comentario')) guardarFormulario('#form-editar-comentario', async (_datos, formulario) => {
    await guardarComentarioEditado(formulario);
}, (error) => { $('#form-editar-comentario .error-formulario').textContent = error.message; });
if ($('#form-eliminar')) guardarFormulario('#form-eliminar', async (_datos, formulario) => {
    await confirmarEliminacion(formulario);
}, (error) => { $('#form-eliminar .error-formulario').textContent = error.message; });
if ($('#form-baneo')) guardarFormulario('#form-baneo', async (datos) => {
    await aplicarBaneo(datos);
    mensaje('Baneo aplicado.');
});
on('#cancelar-baneo', 'click', () => { $('#panel-baneo').hidden = true; });
on('#buscar-usuario', 'input', filtrarUsuarios);

document.addEventListener('click', async (evento) => {
    const boton = evento.target.closest('[data-accion]');
    if (!boton || boton.disabled) return;
    const accion = boton.dataset.accion;
    const identificador = Number(boton.dataset.id);
    boton.disabled = true;
    try {
        if (accion === 'me-gusta') await alternarLike(identificador);
        else if (accion === 'editar-publicacion') location.href = `${document.body.dataset.base || ''}/crear?editar=${identificador}`;
        else if (accion === 'editar-comentario') await editarComentario(identificador);
        else if (accion.startsWith('eliminar-')) await eliminarContenido(accion.replace('eliminar-', ''), identificador);
        else if (accion === 'banear') {
            const persona = buscarUsuario(identificador);
            if (persona) abrirBaneo(persona);
        } else if (accion === 'restablecer-clave') {
            const persona = buscarUsuario(identificador);
            if (!persona || !window.confirm(`¿Restablecer la contraseña de @${persona.nombre_usuario}? La nueva contraseña temporal se mostrará una sola vez.`)) return;
            const resultado = await api('restablecer_clave', { id_usuario: identificador });
            window.alert(`Contraseña temporal para @${persona.nombre_usuario}:\n\n${resultado.contrasena_temporal}\n\nEntrégasela en persona. La persona deberá cambiarla al iniciar sesión.`);
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

const aviso = new URLSearchParams(location.search).get('mensaje');
if (aviso === 'publicacion') mensaje('Publicación guardada.');
if (aviso === 'sesion') mensaje('Las credenciales cambiaron. Iniciá sesión nuevamente.');
if (aviso === 'baja') mensaje('La cuenta quedó desactivada.');
if (new URLSearchParams(location.search).has('acceso')) abrirAcceso(false);
const parametrosInicio = new URLSearchParams(location.search);
const tokenCorreoPendiente = parametrosInicio.get('accion') === 'confirmar-correo'
    ? parametrosInicio.get('token')
    : null;
if (location.protocol === 'file:') mensaje('Abrí el foro desde el servidor de XAMPP.', true);
else iniciar().then(async () => {
    if (tokenCorreoPendiente) {
        try {
            await api('confirmar_cuenta', { token: tokenCorreoPendiente });
            mensaje('Correo actualizado. Iniciá sesión nuevamente.');
        } catch (error) { mensaje(error.message, true); }
    }
}).catch((error) => mensaje(error.message, true));
