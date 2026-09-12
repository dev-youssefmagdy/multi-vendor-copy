export function init(el) {
    const id = el.id;
    const counter = el.closest('.t-field')?.querySelector(`[data-counter-for="${CSS.escape(id)}"]`);
    const max = el.getAttribute('maxlength');

    if (!counter || !max) {
        return;
    }

    const update = () => {
        counter.textContent = `${el.value.length}/${max}`;
    };

    el.addEventListener('input', update);
    update();
}
