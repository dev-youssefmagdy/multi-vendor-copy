// x-tenant::select-menu — brand listbox that writes to a hidden input and
// fires a native `change` event on it.

function close(el, { focusTrigger = false } = {}) {
    const trigger = el.querySelector('[data-select-menu-trigger]');
    el.classList.remove('is-open');
    el.querySelector('[data-select-menu-list]').hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    if (focusTrigger) {
        trigger.focus();
    }
}

function closeAll(except = null) {
    document.querySelectorAll('[data-tenant-select-menu].is-open').forEach((el) => {
        if (el !== except) {
            close(el);
        }
    });
}

function visibleOptions(el) {
    return [...el.querySelectorAll('[role="option"]')].filter((option) => option.getAttribute('aria-selected') !== 'true');
}

function select(el, option) {
    const input = el.querySelector('input[type="hidden"]');
    el.querySelectorAll('[role="option"]').forEach((o) => o.setAttribute('aria-selected', o === option ? 'true' : 'false'));
    el.querySelector('[data-select-menu-label]').textContent = option.textContent.trim();

    if (input.value !== option.dataset.value) {
        input.value = option.dataset.value;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    close(el, { focusTrigger: true });
}

let bound = false;

export function init(el) {
    const trigger = el.querySelector('[data-select-menu-trigger]');
    const list = el.querySelector('[data-select-menu-list]');

    const open = () => {
        closeAll(el);
        el.classList.add('is-open');
        list.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
    };

    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        el.classList.contains('is-open') ? close(el) : open();
    });

    trigger.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            open();
            visibleOptions(el)[0]?.focus();
        }
    });

    list.addEventListener('click', (event) => {
        const option = event.target.closest('[role="option"]');
        if (option) {
            event.stopPropagation();
            select(el, option);
        }
    });

    list.addEventListener('keydown', (event) => {
        const options = visibleOptions(el);
        const index = options.indexOf(document.activeElement);

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const step = event.key === 'ArrowDown' ? 1 : -1;
            options[(index + step + options.length) % options.length]?.focus();
        } else if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            if (index >= 0) {
                select(el, options[index]);
            }
        } else if (event.key === 'Escape' || event.key === 'Tab') {
            close(el, { focusTrigger: event.key === 'Escape' });
        }
    });

    if (!bound) {
        bound = true;
        document.addEventListener('click', () => closeAll());
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAll();
            }
        });
    }
}
