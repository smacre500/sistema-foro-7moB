import { estado } from './estado.js';

const URL_FRONTEND = 'http://localhost:8080/sistema-foro-7mob/frontend/';
const URL_API = new URL('../backend/', URL_FRONTEND).href;

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

export async function api(accion, datos = null, parametros = {}) {
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
