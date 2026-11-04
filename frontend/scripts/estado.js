// Estado compartido: sesión, token CSRF y datos que se muestran en pantalla.
export const estado = {
    usuario: null,
    csrf: '',
    registro: false,
    publicaciones: [],
    usuarios: [],
    autor: 0
};

export const esAdmin = () => estado.usuario?.rol === 'administrador';
export const esBaneado = () => Boolean(estado.usuario?.baneo);

export const esPropio = (contenido) =>
    Number(contenido.id_autor) === Number(estado.usuario?.id_usuario);
