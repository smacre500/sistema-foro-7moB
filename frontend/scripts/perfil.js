import { estado } from './estado.js';
import { $, escapar, fecha } from './util.js';
import { api } from './api.js';
export async function cargarPerfil(identificador) {
    const parametro = typeof identificador === 'string'
        ? { username: identificador }
        : { id: identificador };
    const datos = await api('perfil', null, parametro);
    const perfil = datos.perfil;
    estado.autor = Number(perfil.id_usuario);

    $('#datos-perfil').innerHTML = `<h1>${escapar(perfil.nombre)}</h1><p class="secundario">@${escapar(perfil.nombre_usuario)}</p><p class="contenido">${escapar(perfil.biografia || 'Sin descripción.')}</p><small class="secundario">Miembro desde ${escapar(fecha(perfil.fecha_registro))}</small>`;

    const propio = Number(estado.usuario?.id_usuario) === Number(perfil.id_usuario);
    const debeCambiarClave = Boolean(estado.usuario?.debe_cambiar_clave);
    $('#editar-perfil').hidden = !propio || debeCambiarClave;
    $('#seguridad-cuenta').hidden = !propio;
    $('#aviso-clave-temporal').hidden = !propio || !debeCambiarClave;
    $('#form-cambiar-correo').hidden = debeCambiarClave;
    if (propio) {
        $('#form-perfil').elements.nombre.value = perfil.nombre;
        $('#form-perfil').elements.biografia.value = perfil.biografia;
    }

    return perfil;
}

export async function guardarPerfil(datos) {
    await api('perfil_guardar', datos);
}
