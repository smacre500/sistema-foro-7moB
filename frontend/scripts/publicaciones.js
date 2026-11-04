import { estado, esAdmin, esBaneado, esPropio } from './estado.js';
import { $, escapar, fecha, iniciales } from './util.js';
import { api } from './api.js';

export function dibujarPublicacion(publicacion) {
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
        <details><summary>Comentarios (${(publicacion.comentarios || []).length})</summary>${comentarios || '<p class="secundario">Todavía no hay comentarios.</p>'}${estado.usuario ? (esBaneado() ? '<p class="secundario">Tu cuenta está suspendida: no podés comentar.</p>' : `<form class="form-comentario" data-publicacion="${identificador}"><label>Tu comentario<textarea name="contenido" rows="2" maxlength="2000" required></textarea></label><button type="submit">Comentar</button></form>`) : '<p class="secundario">Iniciá sesión para comentar.</p>'}</details>
    </article>`;
}
export async function cargarPublicaciones(autor = 0) {
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
    }
}
const recargar = () => cargarPublicaciones(estado.autor);

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

    const texto = prompt('Editar comentario:', comentario.contenido);
    if (texto === null) return;

    await api('comentar', { id_comentario: Number(id), contenido: texto });
    await recargar();
}

export async function eliminarContenido(tipo, id) {
    if (!confirm('¿Querés eliminar este contenido?')) return;
    await api('eliminar', { tipo, id: Number(id) });
    await recargar();
}
