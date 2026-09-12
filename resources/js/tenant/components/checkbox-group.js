export function init(el) {
    const allToggle = el.parentElement?.querySelector('[data-checkbox-group-all] input');
    const boxes = () => Array.from(el.querySelectorAll('input[type="checkbox"]'));

    if (!allToggle) {
        return;
    }

    allToggle.addEventListener('change', () => {
        boxes().forEach((box) => {
            box.checked = allToggle.checked;
        });
        el.dispatchEvent(new Event('change', { bubbles: true }));
    });

    el.addEventListener('change', () => {
        allToggle.checked = boxes().length > 0 && boxes().every((box) => box.checked);
    });
}
