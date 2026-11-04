import { estado } from './estado.js';
import { $, escapar, fecha } from './util.js';
import { api } from './api.js';
export async function cargarPerfil(identificador) {
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

    return perfil;
}

export async function guardarPerfil(datos) {
    await api('perfil_guardar', datos);
}
