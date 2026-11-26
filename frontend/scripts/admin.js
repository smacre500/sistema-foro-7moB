import { estado } from './estado.js';
import { $, escapar, fecha, iniciales } from './util.js';
import { api } from './api.js';

const normalizar = (valor) =>
    String(valor ?? '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

export const buscarUsuario = (id) =>
    estado.usuarios.find((usuario) => Number(usuario.id_usuario) === Number(id));

const celdaEstado = ({ activa, baneo }) => {
    if (!activa) return '<span class="estado estado-suspendido">Desactivada</span>';
    if (!baneo) return '<span class="estado estado-activo">Activo</span>';

    const alcance = baneo.fecha_fin ? `Hasta el ${escapar(fecha(baneo.fecha_fin))}` : 'Permanente';
    return `<span class="estado estado-suspendido">Suspendido</span><small class="detalle-baneo" title="Motivo: ${escapar(baneo.motivo)}">${escapar(baneo.motivo)} · ${alcance}</small>`;
};

const filaUsuario = (usuario) => {
    const identificador = Number(usuario.id_usuario);
    const esUsuario = usuario.rol === 'usuario';
    const baneado = Boolean(usuario.baneo);

    return `
        <tr data-id="${identificador}">
            <td>
                <div class="persona-admin">
                    <span class="avatar" aria-hidden="true">${escapar(iniciales(usuario.nombre))}</span>
                    <div>
                        <strong>${escapar(usuario.nombre)}</strong>
                        <span class="usuario-admin">@${escapar(usuario.nombre_usuario)}</span>
                    </div>
                </div>
            </td>
            <td>
                <span class="correo-persona">${escapar(usuario.correo)}</span>
            </td>
            <td>
                <span class="rol ${esUsuario ? '' : 'rol-administrador'}">${esUsuario ? 'Usuario' : 'Administrador'}</span>
            </td>
            <td>${celdaEstado(usuario)}</td>
            <td class="celda-acciones">
                ${esUsuario && usuario.activa ? `<div class="acciones-tabla">
                    <button type="button" class="accion-tabla ${baneado ? 'secundario-boton' : 'peligro'}" data-accion="${baneado ? 'desbanear' : 'banear'}" data-id="${identificador}">${baneado ? 'Levantar baneo' : 'Banear'}</button>
                    ${baneado ? '' : `<button type="button" class="accion-tabla secundario-boton" data-accion="restablecer-clave" data-id="${identificador}">Restablecer contraseña</button>`}
                </div>` : '<span class="secundario">Sin acciones</span>'}
            </td>
        </tr>`;
};

export async function cargarUsuarios() {
    const datos = await api('usuarios');
    estado.usuarios = datos.usuarios;
    $('#usuarios').innerHTML = datos.usuarios.map(filaUsuario).join('');
    filtrarUsuarios();
}

export async function cargarReportes(datosFormulario = null) {
    const hoy = new Date();
    const formatoLocal = (fecha) => `${fecha.getFullYear()}-${String(fecha.getMonth()+1).padStart(2,'0')}-${String(fecha.getDate()).padStart(2,'0')}`;
    const hasta = formatoLocal(hoy);
    const desde = formatoLocal(new Date(hoy.getTime() - 30 * 86400000));
    const filtros = datosFormulario || { desde, hasta, categoria: 0 };
    if (!datosFormulario) {
        $('#form-reportes').elements.desde.value = desde;
        $('#form-reportes').elements.hasta.value = hasta;
    }
    $('#form-reportes').elements.categoria.value = filtros.categoria || '0';
    const datos = await api('reportes', null, filtros);
    $('#reporte-categorias').innerHTML = `<ul>${datos.por_categoria.map((fila) => `<li>${escapar(fila.nombre)}: ${Number(fila.cantidad)}</li>`).join('')}</ul>`;
    $('#reporte-me-gusta').innerHTML = datos.mas_gustadas.length
        ? `<ol>${datos.mas_gustadas.map((fila) => `<li>${escapar(fila.titulo)} (${escapar(fila.categoria)}): ${Number(fila.me_gusta)} me gusta</li>`).join('')}</ol>`
        : '<p class="secundario">No hay publicaciones en ese período.</p>';
    $('#reporte-eliminaciones').innerHTML = datos.eliminaciones.length
        ? `<ul>${datos.eliminaciones.map((fila) => `<li>${escapar(fila.tipo)} #${Number(fila.id_contenido)} · ${escapar(fila.categoria)} · @${escapar(fila.actor)} · ${escapar(fecha(fila.fecha))} · ${escapar(fila.motivo)}</li>`).join('')}</ul>`
        : '<p class="secundario">No hay eliminaciones en ese período.</p>';
    $('#error-reportes').textContent = '';
}

export function filtrarUsuarios() {
    const consulta = normalizar($('#buscar-usuario').value.trim());
    let visibles = 0;

    $('#usuarios').querySelectorAll('tr').forEach((fila) => {
        const persona = buscarUsuario(fila.dataset.id);
        const coincide =
            !consulta ||
            Boolean(
                persona &&
                    [persona.nombre, persona.nombre_usuario, persona.correo].some((valor) =>
                        normalizar(valor).includes(consulta)
                    )
            );

        fila.hidden = !coincide;
        if (coincide) visibles++;
    });

    const total = estado.usuarios.length;
    $('#conteo-usuarios').textContent = consulta
        ? `${visibles} de ${total} ${total === 1 ? 'cuenta' : 'cuentas'}`
        : `${total} ${total === 1 ? 'cuenta' : 'cuentas'}`;
    $('#sin-usuarios').textContent = total
        ? 'No se encontraron cuentas con esa búsqueda.'
        : 'Todavía no hay cuentas registradas.';
    $('#sin-usuarios').hidden = visibles > 0;
}

export function abrirBaneo(persona) {
    $('#form-baneo').reset();
    $('#form-baneo').elements.id_usuario.value = Number(persona.id_usuario);
    $('#titulo-baneo').textContent = 'Banear a ' + persona.nombre;
    $('#panel-baneo').hidden = false;
    $('#panel-baneo').scrollIntoView({ behavior: 'smooth', block: 'center' });
    $('#form-baneo').elements.motivo.focus();
}

export async function aplicarBaneo(datos) {
    await api('banear', datos);
    $('#panel-baneo').hidden = true;
    await cargarUsuarios();
}

export async function levantarBaneo(persona) {
    if (!persona?.baneo) return;
    await api('desbanear', { id_baneo: persona.baneo.id_baneo });
    await cargarUsuarios();
}
