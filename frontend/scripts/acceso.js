import { estado, esAdmin, esBaneado } from './estado.js';
import { $, iniciales, mensaje } from './util.js';
import { api } from './api.js';

export function actualizarCuenta() {
    const usuario = estado.usuario;
    $('#acciones-acceso').hidden = Boolean(usuario);
    $('#salir').hidden = !usuario;
    $('#enlace-perfil').hidden = !usuario;
    $('#enlace-admin').hidden = !esAdmin();
    $('#enlace-crear').hidden = esBaneado();
    $('#nombre-actual').textContent = usuario?.nombre || 'Tu comunidad';
    $('#detalle-actual').textContent = usuario
        ? `@${usuario.nombre_usuario} · ${esAdmin() ? 'Administrador' : 'Miembro de la comunidad'}`
        : 'Un espacio para compartir y aprender.';
    $('#avatar-perfil').textContent = iniciales(usuario?.nombre);
}
export function prepararAcceso(registro) {
    estado.registro = registro;
    $('#error-acceso').textContent = '';
    $('#campos-registro').hidden = !registro;
    $('#campo-identidad').hidden = registro;

    document.querySelectorAll('#campos-registro input').forEach((input) => {
        input.disabled = !registro;
        input.required = registro;
    });

    const identidad = $('#form-acceso').elements.identidad;
    const contrasena = $('#form-acceso').elements.contrasena;
    identidad.disabled = registro;
    identidad.required = !registro;
    contrasena.minLength = registro ? 10 : 1;
    contrasena.autocomplete = registro ? 'new-password' : 'current-password';

    $('#titulo-acceso').textContent = registro ? 'Crear cuenta' : 'Iniciar sesión';
    $('#boton-acceso').textContent = registro ? 'Registrarme' : 'Ingresar';
    $('#cambiar-acceso').textContent = registro ? 'Ya tengo una cuenta' : 'Crear una cuenta';
    validarCorreo();
}

export function abrirAcceso(registro = false) {
    prepararAcceso(registro);
    if (!$('#acceso').open) $('#acceso').showModal();
}

export function validarCorreo() {
    const correo = $('#form-acceso').elements.correo;
    correo.setCustomValidity(
        correo.value && !/^[^\s@]+@obralapiedad\.com\.ar$/i.test(correo.value)
            ? 'Usá un correo terminado exactamente en @obralapiedad.com.ar.'
            : ''
    );
}

export async function guardarAcceso(datos) {
    await api(estado.registro ? 'registro' : 'login', datos);
    mensaje(estado.registro ? 'Tu cuenta fue creada.' : 'Ingresaste al foro.');
}
