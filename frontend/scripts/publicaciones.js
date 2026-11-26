import { estado, esAdmin, esBaneado, esPropio } from './estado.js';
import { $, escapar, fecha, iniciales } from './util.js';
import { api } from './api.js';

export function dibujarPublicacion(publicacion, comentariosAbiertos) {
    const identificador = Number(publicacion.id_publicacion);
    const comentarios = (publicacion.comentarios || [])
        .map(
            (comentario) =>
                `<div class="comentario"><strong>${escapar(comentario.nombre)}</strong><p class="contenido">${escapar(comentario.contenido)}</p>${esPropio(comentario) ? `<button class="enlace" data-accion="editar-comentario" data-id="${Number(comentario.id_comentario)}">Editar</button>` : ''}${esPropio(comentario) || esAdmin() ? `<button class="enlace" data-accion="eliminar-comentario" data-id="${Number(comentario.id_comentario)}">Eliminar</button>` : ''}</div>`
        )
        .join('');

    return `<article class="tarjeta publicacion">
        <div class="cabecera-publicacion"><div class="avatar" aria-hidden="true">${escapar(iniciales(publicacion.nombre))}</div><div class="datos-autor"><div class="linea-autor"><a class="autor-nombre" href="${document.body.dataset.base || ''}/perfil/${encodeURIComponent(publicacion.nombre_usuario)}">${escapar(publicacion.nombre)}</a><span class="autor-usuario">@${escapar(publicacion.nombre_usuario)}</span><span class="autor-fecha">· ${escapar(fecha(publicacion.fecha_publicacion))}</span></div></div><span class="categoria">${escapar(publicacion.categoria)}</span></div>
        <h3>${escapar(publicacion.titulo)}</h3><p class="contenido">${escapar(publicacion.contenido)}</p>
        <div class="acciones pie-publicacion"><button class="me-gusta ${Number(publicacion.mi_like) ? 'activo' : ''}" data-accion="me-gusta" data-id="${identificador}" aria-pressed="${Boolean(Number(publicacion.mi_like))}">♡ Me gusta (${Number(publicacion.likes)})</button>${esPropio(publicacion) ? `<button class="enlace" data-accion="editar-publicacion" data-id="${identificador}">Editar</button>` : ''}${esPropio(publicacion) || esAdmin() ? `<button class="enlace" data-accion="eliminar-publicacion" data-id="${identificador}">Eliminar</button>` : ''}</div>
        <details data-publicacion="${identificador}"${comentariosAbiertos ? ' open' : ''}><summary>Comentarios (${(publicacion.comentarios || []).length})</summary>${comentarios || '<p class="secundario">Todavía no hay comentarios.</p>'}${estado.usuario ? (esBaneado() ? '<p class="secundario">Tu cuenta está suspendida: no podés comentar.</p>' : `<form class="form-comentario" data-publicacion="${identificador}"><label>Tu comentario<textarea name="contenido" rows="2" maxlength="2000" required></textarea></label><button type="submit">Comentar</button></form>`) : '<p class="secundario">Iniciá sesión para comentar.</p>'}</details>
    </article>`;
}
function dibujarPaginacion(autor, pagina, hayMas) {
    const nav = $(autor ? '#paginacion-perfil' : '#paginacion-inicio');
    if (!nav) return;
    const baseQuery = new URLSearchParams(location.search);
    const enlazar = (numero, texto, deshabilitado = false) => {
        if (deshabilitado) return `<span aria-disabled="true">${texto}</span>`;
        const query = new URLSearchParams(baseQuery);
        query.set('pagina', numero);
        return `<a href="?${query.toString()}" ${numero === pagina ? 'aria-current="page"' : ''}>${texto}</a>`;
    };
    nav.innerHTML = `${enlazar(Math.max(1, pagina - 1), 'Anterior', pagina <= 1)}<span>Página ${pagina}</span>${enlazar(pagina + 1, 'Siguiente', !hayMas)}`;
}

export async function cargarPublicaciones(autor = 0, paginaSolicitada = null) {
    const pagina = Number(paginaSolicitada ?? new URLSearchParams(location.search).get('pagina') ?? 1);
    const datos = await api('publicaciones', null, {
        buscar: autor ? '' : $('#buscar').value.trim(),
        autor,
        pagina
    });
    estado.publicaciones = datos.publicaciones;
    estado.pagina = datos.pagina;

    const contenedor = $(autor ? '#publicaciones-perfil' : '#publicaciones');
    const abiertos = new Set(
        Array.from(contenedor.querySelectorAll('details[open]'), (detalle) => detalle.dataset.publicacion)
    );
    contenedor.innerHTML =
        datos.publicaciones.map((publicacion) =>
            dibujarPublicacion(publicacion, abiertos.has(String(publicacion.id_publicacion)))
        ).join('') ||
        '<div class="tarjeta vacio">No hay publicaciones para mostrar.</div>';

    if (!autor) {
        $('#cantidad-publicaciones').textContent = `${datos.publicaciones.length} publicaciones en esta página`;
    }
    dibujarPaginacion(autor, datos.pagina, datos.hay_mas);
}
const recargar = () => cargarPublicaciones(estado.autor, estado.pagina);

export async function alternarLike(id) {
    if (!estado.usuario) throw new Error('Iniciá sesión para dar me gusta.');
    await api('me_gusta', { id_publicacion: Number(id) });
    await recargar();
}

export async function comentar(formulario) {
    await api('comentar', {
        id_publicacion: formulario.dataset.publicacion,
        contenido: formulario.elements.contenido.value
    });
    await recargar();
}

export async function editarComentario(id) {
    const comentario = estado.publicaciones
        .flatMap((publicacion) => publicacion.comentarios || [])
        .find((elemento) => Number(elemento.id_comentario) === Number(id));
    if (!comentario) return;

    const dialogo = $('#dialogo-editar-comentario');
    const formulario = dialogo.querySelector('form');
    formulario.elements.id_comentario.value = id;
    formulario.elements.contenido.value = comentario.contenido;
    formulario.querySelector('.error-formulario').textContent = '';
    dialogo.showModal();
    formulario.elements.contenido.focus();
}

export async function guardarComentarioEditado(formulario) {
    await api('comentar', {
        id_comentario: Number(formulario.elements.id_comentario.value),
        contenido: formulario.elements.contenido.value
    });
    formulario.closest('dialog').close();
    await recargar();
}

export async function eliminarContenido(tipo, id) {
    const esMio = tipo === 'publicacion'
        ? estado.publicaciones.find((item) => Number(item.id_publicacion) === Number(id))?.id_autor == estado.usuario?.id_usuario
        : estado.publicaciones.flatMap((item) => item.comentarios || []).find((item) => Number(item.id_comentario) === Number(id))?.id_autor == estado.usuario?.id_usuario;
    const dialogo = $('#dialogo-eliminar');
    const formulario = dialogo.querySelector('form');
    formulario.elements.tipo.value = tipo;
    formulario.elements.id.value = id;
    formulario.elements.motivo.value = '';
    formulario.querySelector('.error-formulario').textContent = '';
    const requiereMotivo = !esMio && esAdmin();
    formulario.elements.motivo.required = requiereMotivo;
    $('#campo-motivo-eliminacion').hidden = !requiereMotivo;
    if (!dialogo.open) dialogo.showModal();
}

export async function confirmarEliminacion(formulario) {
    await api('eliminar', {
        tipo: formulario.elements.tipo.value,
        id: Number(formulario.elements.id.value),
        motivo: formulario.elements.motivo.value
    });
    formulario.closest('dialog').close();
    await recargar();
}
