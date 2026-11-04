
export const $ = (selector) => document.querySelector(selector);
export const escapar = (valor) =>
    String(valor ?? '').replace(
        /[&<>"']/g,
        (letra) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[letra]
    );

export const fecha = (valor) => {
    if (!valor) return '';
    return new Date(String(valor).replace(' ', 'T') + 'Z').toLocaleString('es-AR');
};

export const iniciales = (nombre) =>
    (nombre || 'Foro Institucional')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((parte) => [...parte][0])
        .join('')
        .toUpperCase();

export function mensaje(texto, error = false) {
    $('#mensaje').textContent = texto;
    $('#mensaje').classList.toggle('error', error);
    $('#mensaje').hidden = !texto;
}
export function guardarFormulario(
    selector,
    tarea,
    alFallar = (error) => mensaje(error.message, true)
) {
    $(selector).addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const formulario = evento.currentTarget;
        const boton = formulario.querySelector('[type="submit"]');
        if (boton.disabled) return;

        boton.disabled = true;
        try {
            await tarea(Object.fromEntries(new FormData(formulario)), formulario);
        } catch (error) {
            alFallar(error);
        } finally {
            boton.disabled = false;
        }
    });
}
