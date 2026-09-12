export function init(el) {
    const resize = () => {
        el.style.height = 'auto';
        el.style.height = `${el.scrollHeight}px`;
    };

    el.addEventListener('input', resize);
    resize();
}
