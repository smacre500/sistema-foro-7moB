// Dirección del proyecto y ruta donde están los endpoints de PHP.
const URL_FRONTEND = 'http://localhost:8080/sistema-foro-7mob/frontend/';
const URL_API = new URL('../backend/', URL_FRONTEND).href;

const $ = (selector) => document.querySelector(selector);
const estado = {
    usuario: null,
    csrf: '',
    registro: false,
    publicaciones: [],
    usuarios: [],
    autor: 0
};

// Las respuestas se escapan antes de armar el HTML para no interpretar etiquetas.
const escapar = (valor) =>
    String(valor ?? '').replace(
        /[&<>"']/g,
        (letra) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[letra]
    );

const fecha = (valor) => {
    if (!valor) return '';
    return new Date(String(valor).replace(' ', 'T') + 'Z').toLocaleString('es-AR');
};

const esAdmin = () => estado.usuario?.rol === 'administrador';
const esPropio = (contenido) => Number(contenido.id_autor) === Number(estado.usuario?.id_usuario);
const iniciales = (nombre) =>
    (nombre || 'Foro Institucional')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((parte) => [...parte][0])
        .join('')
        .toUpperCase();

function mensaje(texto, error = false) {
    $('#mensaje').textContent = texto;
    $('#mensaje').classList.toggle('error', error);
    $('#mensaje').hidden = !texto;
}

// Cada acción del frontend corresponde a un archivo del backend.
const rutas = {
    inicio: 'inicio.php',
    login: 'login.php',
    registro: 'registro.php',
    salir: 'salir.php',
    publicaciones: 'publicaciones.php',
    publicar: 'publicar.php',
    comentar: 'comentar.php',
    me_gusta: 'me_gusta.php',
    eliminar: 'eliminar.php',
    perfil: 'perfil.php',
    perfil_guardar: 'perfil_guardar.php',
    usuarios: 'usuarios.php',
    banear: 'banear.php',
    desbanear: 'desbanear.php'
};

async function api(accion, datos = null, parametros = {}) {
    if (!rutas[accion]) throw new Error('Acción desconocida.');

    const url = new URL(rutas[accion], URL_API);
    const opciones = {
        method: datos === null ? 'GET' : 'POST',
        credentials: 'same-origin',
        headers: {}
    };

    if (datos === null) {
        Object.entries(parametros).forEach(([nombre, valor]) =>
            url.searchParams.set(nombre, valor ?? '')
        );
    } else {
        opciones.headers['Content-Type'] = 'application/json';
        opciones.headers['X-CSRF-Token'] = estado.csrf;
        opciones.body = JSON.stringify(datos);
    }

    let respuesta;
    try {
        respuesta = await fetch(url, opciones);
    } catch {
        throw new Error('No se pudo conectar con el servidor.');
    }

    let resultado;
    try {
        resultado = await respuesta.json();
    } catch {
        throw new Error('El servidor devolvió una respuesta inválida.');
    }

    if (!respuesta.ok || !resultado.ok) {
        throw new Error(resultado.error || 'No se pudo completar la operación.');
    }
    return resultado;
}

function actualizarCuenta() {
    const usuario = estado.usuario;
    $('#acciones-acceso').hidden = Boolean(usuario);
    $('#salir').hidden = !usuario;
    $('#enlace-perfil').hidden = !usuario;
    $('#enlace-admin').hidden = !esAdmin();
    $('#nombre-actual').textContent = usuario?.nombre || 'Tu comunidad';
    $('#detalle-actual').textContent = usuario
        ? `@${usuario.nombre_usuario} · ${esAdmin() ? 'Administrador' : 'Miembro de la comunidad'}`
        : 'Un espacio para compartir y aprender.';
    $('#avatar-perfil').textContent = iniciales(usuario?.nombre);
    $('#avatar-compositor').textContent = iniciales(usuario?.nombre);
}

function guardarFormulario(selector, tarea) {
    $(selector).addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const formulario = evento.currentTarget;
        const boton = formulario.querySelector('[type="submit"]');
        if (boton.disabled) return;

        boton.disabled = true;
        try {
            await tarea(Object.fromEntries(new FormData(formulario)), formulario);
        } catch (error) {
            if (formulario.id === 'form-acceso') {
                $('#error-acceso').textContent = error.message;
            } else {
                mensaje(error.message, true);
            }
        } finally {
            boton.disabled = false;
        }
    });
}

function prepararAcceso(registro) {
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

function abrirAcceso(registro = false) {
    prepararAcceso(registro);
    if (!$('#acceso').open) $('#acceso').showModal();
}

function validarCorreo() {
    const correo = $('#form-acceso').elements.correo;
    correo.setCustomValidity(
        correo.value && !/^[^\s@]+@obralapiedad\.com\.ar$/i.test(correo.value)
            ? 'Usá un correo terminado exactamente en @obralapiedad.com.ar.'
            : ''
    );
}

$('#ingresar').addEventListener('click', () => abrirAcceso(false));
$('#registrarse').addEventListener('click', () => abrirAcceso(true));
$('#cerrar-acceso').addEventListener('click', () => $('#acceso').close());
$('#cambiar-acceso').addEventListener('click', () => prepararAcceso(!estado.registro));
$('#form-acceso').elements.correo.addEventListener('input', validarCorreo);
$('#acceso').addEventListener('close', () => {
    $('#form-acceso').elements.contrasena.value = '';
    $('#error-acceso').textContent = '';
});

guardarFormulario('#form-acceso', async (datos, formulario) => {
    await api(estado.registro ? 'registro' : 'login', datos);
    formulario.reset();
    mensaje(estado.registro ? 'Tu cuenta fue creada.' : 'Ingresaste al foro.');
    await iniciar();
});

async function iniciar() {
    const datos = await api('inicio');
    estado.usuario = datos.usuario;
    estado.csrf = datos.csrf;

    actualizarCuenta();
    $('#crear-publicacion').hidden = true;
    $('#vista-inicio').hidden = false;
    $('#vista-perfil').hidden = true;
    $('#vista-admin').hidden = true;
    $('#categorias').innerHTML = datos.categorias
        .map((categoria) => `<option value="${Number(categoria.id_categoria)}">${escapar(categoria.nombre)}</option>`)
        .join('');

    if (estado.usuario && $('#acceso').open) $('#acceso').close();
}

if (location.protocol === 'file:') {
    mensaje('Abrí el foro desde el servidor de XAMPP.', true);
} else {
    iniciar().catch((error) => mensaje(error.message, true));
}
