// Formulario de publicaciones: crear y editar usan la misma página.
import { $ } from './util.js';
import { api } from './api.js';

// Deja el editor listo para una publicación nueva.
export function limpiarEditor() {
    const formulario = $('#form-publicacion');
    formulario.reset();
    formulario.elements.id_publicacion.value = '';
    $('#titulo-editor').textContent = 'Crear publicación';
}

// Carga en el formulario los datos de una publicación existente.
export function abrirEditor(publicacion) {
    const formulario = $('#form-publicacion');
    ['id_publicacion', 'titulo', 'contenido', 'id_categoria'].forEach((campo) => {
        formulario.elements[campo].value = publicacion[campo];
    });
    $('#titulo-editor').textContent = 'Editar publicación';
    formulario.elements.titulo.focus();
}

export async function guardarPublicacion(datos) {
    await api('publicar', datos);
    limpiarEditor();
}
