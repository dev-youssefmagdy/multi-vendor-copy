// Dashboard brand banner: the two availability options behave as a single-choice radio group.
export function mountBrandOptions(root = document) {
    root.querySelectorAll('[data-brand-options]').forEach((group) => {
        const options = [...group.querySelectorAll('[data-brand-option]')];

        const select = (option) => {
            options.forEach((o) => {
                const on = o === option;
                o.setAttribute('aria-checked', on ? 'true' : 'false');
                o.tabIndex = on ? 0 : -1;
            });
        };

        options.forEach((option, i) => {
            option.tabIndex = i === 0 ? 0 : -1;
            option.addEventListener('click', () => select(option));
            option.addEventListener('keydown', (event) => {
                if (!['ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp'].includes(event.key)) return;
                event.preventDefault();
                const step = event.key === 'ArrowRight' || event.key === 'ArrowDown' ? 1 : -1;
                const next = options[(i + step + options.length) % options.length];
                select(next);
                next.focus();
            });
        });
    });
}
