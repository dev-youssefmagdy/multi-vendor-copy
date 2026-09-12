function closeAll() {
    document.querySelectorAll('[data-tenant-dropdown].is-open').forEach((el) => {
        el.classList.remove('is-open');
        el.querySelector('[data-dropdown-menu]').hidden = true;
        el.querySelector('[data-dropdown-trigger]')?.setAttribute('aria-expanded', 'false');
    });
}

function positionMenu(el) {
    const menu = el.querySelector('[data-dropdown-menu]');
    if (el.dataset.align === 'end') {
        menu.style.right = '0';
        menu.style.left = 'auto';
    }

    const rect = menu.getBoundingClientRect();
    if (rect.bottom > window.innerHeight) {
        menu.classList.add('is-above');
    } else {
        menu.classList.remove('is-above');
    }
}

let bound = false;

export function init(el) {
    const trigger = el.querySelector('[data-dropdown-trigger]');
    const menu = el.querySelector('[data-dropdown-menu]');

    trigger?.addEventListener('click', (event) => {
        event.stopPropagation();
        const willOpen = !el.classList.contains('is-open');
        closeAll();

        if (willOpen) {
            el.classList.add('is-open');
            menu.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            positionMenu(el);
        }
    });

    trigger?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            trigger.click();
        } else if (event.key === 'Escape') {
            closeAll();
        } else if (event.key === 'ArrowDown' && el.classList.contains('is-open')) {
            event.preventDefault();
            menu.querySelector('[role="menuitem"]')?.focus();
        }
    });

    if (!bound) {
        bound = true;
        document.addEventListener('click', closeAll);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeAll();
            }
        });
        document.addEventListener('tenant:table:drawn', closeAll);
    }
}
