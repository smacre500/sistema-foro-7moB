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

document.querySelectorAll('.abrir-publicacion').forEach((boton) =>
    boton.addEventListener('click', () => {
        if (!estado.usuario) {
            abrirAcceso(false);
            return;
        }
        location.hash = '#inicio';
        $('#crear-publicacion').hidden = false;
        $('#form-publicacion').elements.titulo.focus();
    })
);

guardarFormulario('#form-publicacion', async (datos, formulario) => {
    await api('publicar', datos);
    formulario.reset();
    formulario.elements.id_publicacion.value = '';
    $('#titulo-editor').textContent = 'Crear publicación';
    $('#cancelar-edicion').hidden = true;
    $('#crear-publicacion').hidden = true;
    mensaje('Publicación guardada.');
    await cargarPublicaciones();
});

$('#cancelar-edicion').addEventListener('click', () => {
    $('#form-publicacion').reset();
    $('#form-publicacion').elements.id_publicacion.value = '';
    $('#titulo-editor').textContent = 'Crear publicación';
    $('#cancelar-edicion').hidden = true;
    $('#crear-publicacion').hidden = true;
});

guardarFormulario('#form-perfil', async (datos) => {
    await api('perfil_guardar', datos);
    mensaje('Perfil actualizado.');
    await iniciar();
});

guardarFormulario('#form-baneo', async (datos) => {
    await api('banear', datos);
    $('#panel-baneo').hidden = true;
    mensaje('Baneo aplicado.');
    await cargarUsuarios();
});

$('#cancelar-baneo').addEventListener('click', () => {
    $('#panel-baneo').hidden = true;
});

document.addEventListener('click', async (evento) => {
    const boton = evento.target.closest('[data-accion]');
    if (!boton || boton.disabled) return;

    const accion = boton.dataset.accion;
    const identificador = Number(boton.dataset.id);
    boton.disabled = true;

    try {
        if (accion === 'me-gusta') {
            if (!estado.usuario) throw new Error('Iniciá sesión para dar me gusta.');
            await api('me_gusta', { id_publicacion: identificador });
            await cargarPublicaciones(estado.autor);
        } else if (accion === 'editar-publicacion') {
            const publicacion = estado.publicaciones.find(
                (elemento) => Number(elemento.id_publicacion) === identificador
            );
            if (!publicacion) return;

            location.hash = '#inicio';
            $('#crear-publicacion').hidden = false;
            const formulario = $('#form-publicacion');
            ['id_publicacion', 'titulo', 'contenido', 'id_categoria'].forEach((campo) => {
                formulario.elements[campo].value = publicacion[campo];
            });
            $('#titulo-editor').textContent = 'Editar publicación';
            $('#cancelar-edicion').hidden = false;
            formulario.elements.titulo.focus();
        } else if (accion === 'editar-comentario') {
            const comentario = estado.publicaciones
                .flatMap((publicacion) => publicacion.comentarios || [])
                .find((elemento) => Number(elemento.id_comentario) === identificador);
            if (!comentario) return;

            const texto = prompt('Editar comentario:', comentario.contenido);
            if (texto !== null) {
                await api('comentar', { id_comentario: identificador, contenido: texto });
                await cargarPublicaciones(estado.autor);
            }
        } else if (accion.startsWith('eliminar-')) {
            if (confirm('¿Querés eliminar este contenido?')) {
                await api('eliminar', {
                    tipo: accion.replace('eliminar-', ''),
                    id: identificador
                });
                await cargarPublicaciones(estado.autor);
            }
        } else if (accion === 'banear') {
            const persona = estado.usuarios.find(
                (usuario) => Number(usuario.id_usuario) === identificador
            );
            if (!persona) return;

            $('#form-baneo').reset();
            $('#form-baneo').elements.id_usuario.value = identificador;
            $('#titulo-baneo').textContent = 'Banear a ' + persona.nombre;
            $('#panel-baneo').hidden = false;
            $('#form-baneo').elements.motivo.focus();
        } else if (accion === 'desbanear') {
            const persona = estado.usuarios.find(
                (usuario) => Number(usuario.id_usuario) === identificador
            );
            if (!persona?.baneo) return;

            await api('desbanear', { id_baneo: persona.baneo.id_baneo });
            mensaje('Baneo levantado.');
            await cargarUsuarios();
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
        await api('comentar', {
            id_publicacion: formulario.dataset.publicacion,
            contenido: formulario.elements.contenido.value
        });
        mensaje('Comentario publicado.');
        await cargarPublicaciones(estado.autor);
    } catch (error) {
        mensaje(error.message, true);
    } finally {
        boton.disabled = false;
    }
});

function dibujarPublicacion(publicacion) {
    const identificador = Number(publicacion.id_publicacion);
    const comentarios = (publicacion.comentarios || [])
        .map(
            (comentario) =>
                `<div class="comentario"><strong>${escapar(comentario.nombre)}</strong><p class="contenido">${escapar(comentario.contenido)}</p>${esPropio(comentario) ? `<button class="enlace" data-accion="editar-comentario" data-id="${Number(comentario.id_comentario)}">Editar</button>` : ''}${esPropio(comentario) || esAdmin() ? `<button class="enlace" data-accion="eliminar-comentario" data-id="${Number(comentario.id_comentario)}">Eliminar</button>` : ''}</div>`
        )
        .join('');

    return `<article class="tarjeta">
        <div class="cabecera-publicacion"><div class="avatar" aria-hidden="true">${escapar(iniciales(publicacion.nombre))}</div><div class="datos-autor"><a href="#perfil/${Number(publicacion.id_autor)}">${escapar(publicacion.nombre)}</a><small class="secundario">@${escapar(publicacion.nombre_usuario)} · ${escapar(fecha(publicacion.fecha_publicacion))}</small></div><span class="categoria">${escapar(publicacion.categoria)}</span></div>
        <h3>${escapar(publicacion.titulo)}</h3><p class="contenido">${escapar(publicacion.contenido)}</p>
        <div class="acciones pie-publicacion"><button class="me-gusta ${Number(publicacion.mi_like) ? 'activo' : ''}" data-accion="me-gusta" data-id="${identificador}" aria-pressed="${Boolean(Number(publicacion.mi_like))}">♡ Me gusta (${Number(publicacion.likes)})</button>${esPropio(publicacion) ? `<button class="enlace" data-accion="editar-publicacion" data-id="${identificador}">Editar</button>` : ''}${esPropio(publicacion) || esAdmin() ? `<button class="enlace" data-accion="eliminar-publicacion" data-id="${identificador}">Eliminar</button>` : ''}</div>
        <details><summary>Comentarios (${(publicacion.comentarios || []).length})</summary>${comentarios || '<p class="secundario">Todavía no hay comentarios.</p>'}${estado.usuario ? `<form class="form-comentario" data-publicacion="${identificador}"><label>Tu comentario<textarea name="contenido" rows="2" maxlength="2000" required></textarea></label><button type="submit">Comentar</button></form>` : '<p class="secundario">Iniciá sesión para comentar.</p>'}</details>
    </article>`;
}

async function cargarPublicaciones(autor = 0) {
    const datos = await api('publicaciones', null, {
        buscar: autor ? '' : $('#buscar').value.trim(),
        autor
    });
    estado.publicaciones = datos.publicaciones;

    const contenedor = autor ? '#publicaciones-perfil' : '#publicaciones';
    $(contenedor).innerHTML =
        datos.publicaciones.map(dibujarPublicacion).join('') ||
        '<div class="tarjeta vacio">No hay publicaciones para mostrar.</div>';

    if (!autor) {
        $('#cantidad-publicaciones').textContent = `${datos.publicaciones.length} publicaciones en este espacio`;
        $('#total-publicaciones').textContent = datos.publicaciones.length;
        $('#total-comentarios').textContent = datos.publicaciones.reduce(
            (total, publicacion) => total + (publicacion.comentarios || []).length,
            0
        );
    }
}

async function cargarUsuarios() {
    const datos = await api('usuarios');
    estado.usuarios = datos.usuarios;

    $('#usuarios').innerHTML = datos.usuarios
        .map(
            (usuario) =>
                `<article class="tarjeta usuario"><div><strong>${escapar(usuario.nombre)}</strong><p class="secundario">${escapar(usuario.correo)} · ${escapar(usuario.rol)}</p>${usuario.baneo ? `<p class="suspendido">Suspendido: ${escapar(usuario.baneo.motivo)}. ${usuario.baneo.fecha_fin ? 'Hasta ' + escapar(fecha(usuario.baneo.fecha_fin)) : 'Permanente'}.</p>` : ''}</div>${usuario.rol === 'usuario' ? `<button class="${usuario.baneo ? 'secundario-boton' : 'peligro'}" data-accion="${usuario.baneo ? 'desbanear' : 'banear'}" data-id="${Number(usuario.id_usuario)}">${usuario.baneo ? 'Levantar baneo' : 'Banear'}</button>` : ''}</article>`
        )
        .join('');
}

async function navegar() {
    const ruta = location.hash || '#inicio';
    const enPerfil = ruta.startsWith('#perfil');
    const enAdmin = ruta === '#admin';

    $('#vista-inicio').hidden = enPerfil || enAdmin;
    $('#vista-perfil').hidden = !enPerfil;
    $('#vista-admin').hidden = !enAdmin;
    estado.autor = 0;

    if (enAdmin) {
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

        const datos = await api('perfil', null, { id: identificador });
        const perfil = datos.perfil;
        estado.autor = Number(identificador);

        $('#datos-perfil').innerHTML = `<h1>${escapar(perfil.nombre)}</h1><p class="secundario">@${escapar(perfil.nombre_usuario)}</p><p class="contenido">${escapar(perfil.biografia || 'Sin descripción.')}</p><small class="secundario">Miembro desde ${escapar(fecha(perfil.fecha_registro))}</small>`;

        const propio = Number(estado.usuario?.id_usuario) === Number(identificador);
        $('#editar-perfil').hidden = !propio;
        if (propio) {
            $('#form-perfil').elements.nombre.value = perfil.nombre;
            $('#form-perfil').elements.biografia.value = perfil.biografia;
        }

        await cargarPublicaciones(estado.autor);
    } else {
        await cargarPublicaciones();
    }
}

async function iniciar() {
    const datos = await api('inicio');
    estado.usuario = datos.usuario;
    estado.csrf = datos.csrf;

    actualizarCuenta();
    $('#crear-publicacion').hidden = true;
    $('#categorias').innerHTML = datos.categorias
        .map((categoria) => `<option value="${Number(categoria.id_categoria)}">${escapar(categoria.nombre)}</option>`)
        .join('');

    if (estado.usuario && $('#acceso').open) $('#acceso').close();
    await navegar();
}

guardarFormulario('#form-buscar', async () => {
    if (location.hash && location.hash !== '#inicio') location.hash = '#inicio';
    else await cargarPublicaciones();
});

window.addEventListener('hashchange', () => navegar().catch((error) => mensaje(error.message, true)));

if (location.protocol === 'file:') {
    mensaje('Abrí el foro desde el servidor de XAMPP.', true);
} else {
    iniciar().catch((error) => mensaje(error.message, true));
}
