export function init(el) {
    const wrap = el.closest('.t-input-wrap') || el.parentElement;
    const input = wrap?.querySelector('input');

    if (!input) {
        return;
    }

    el.addEventListener('click', () => {
        input.type = input.type === 'password' ? 'text' : 'password';
    });
}
