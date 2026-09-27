// x-tenant::country-picker — "Select your country" dropdown with flags.
// The panel is fixed-positioned under its trigger, so each page keeps its own
// trigger layout. The choice is kept in localStorage and mirrored to every
// picker on the page; `tenant:country-change` fires on document with { code, name }.

const STORAGE_KEY = 'tenant:country';
const pickers = new Set();

function readStored() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
    } catch {
        return null;
    }
}

function store(country) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(country));
    } catch {
        /* private mode / blocked storage: the pick just isn't remembered */
    }
}

function position(el) {
    const trigger = el.querySelector('[data-country-trigger]');
    const panel = el._panel;
    const rect = trigger.getBoundingClientRect();
    const width = Math.max(rect.width, 260);
    const left = Math.min(Math.max(8, rect.left), window.innerWidth - width - 8);
    const below = window.innerHeight - rect.bottom;

    panel.style.width = `${width}px`;
    panel.style.left = `${left}px`;
    // Open upwards when there is no room below.
    if (below < 240 && rect.top > below) {
        panel.style.top = 'auto';
        panel.style.bottom = `${window.innerHeight - rect.top + 8}px`;
    } else {
        panel.style.bottom = 'auto';
        panel.style.top = `${rect.bottom + 8}px`;
    }
}

function close(el, { focusTrigger = false } = {}) {
    const trigger = el.querySelector('[data-country-trigger]');
    el.classList.remove('is-open');
    el._panel.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    if (focusTrigger) trigger.focus();
}

function closeAll(except = null) {
    pickers.forEach((el) => {
        if (el !== except && el.classList.contains('is-open')) close(el);
    });
}

function options(el) {
    return [...el._panel.querySelectorAll('[role="option"]')].filter((o) => !o.hidden);
}

function filter(el, term) {
    const q = term.trim().toLowerCase();
    let shown = 0;
    el._panel.querySelectorAll('[role="option"]').forEach((o) => {
        o.hidden = q !== '' && !o.dataset.name.toLowerCase().includes(q);
        shown += o.hidden ? 0 : 1;
    });
    el._panel.querySelector('[data-country-empty]').hidden = shown > 0;
}

// Show the chosen country on a picker (flag + name in place of the label text).
function apply(el, country) {
    const option = country && el._panel.querySelector(`[role="option"][data-code="${country.code}"]`);
    el._panel.querySelectorAll('[role="option"]').forEach((o) => o.setAttribute('aria-selected', o === option ? 'true' : 'false'));

    const label = el.querySelector('[data-country-label]');
    if (!label || !option) return;

    label.replaceChildren(option.querySelector('.t-country-flag').cloneNode(true), document.createTextNode(option.dataset.name));
    label.classList.add('has-country');
}

function choose(el, option) {
    const country = { code: option.dataset.code, name: option.dataset.name };
    store(country);
    pickers.forEach((picker) => apply(picker, country));
    close(el, { focusTrigger: true });
    document.dispatchEvent(new CustomEvent('tenant:country-change', { detail: country }));
}

let bound = false;

export function init(el) {
    const trigger = el.querySelector('[data-country-trigger]');
    const panel = el.querySelector('[data-country-panel]');
    const search = panel.querySelector('[data-country-search]');

    // The panel lives on <body> so no parent overflow/transform (cards, the
    // .fu entrance animation, sliders) can clip it; keep a reference.
    document.body.append(panel);
    el._panel = panel;

    pickers.add(el);
    apply(el, readStored());

    const open = () => {
        closeAll(el);
        el.classList.add('is-open');
        panel.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        position(el);
        search.value = '';
        filter(el, '');
        search.focus();
    };

    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        el.classList.contains('is-open') ? close(el) : open();
    });

    trigger.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            open();
        }
    });

    panel.addEventListener('click', (event) => {
        event.stopPropagation();
        const option = event.target.closest('[role="option"]');
        if (option) choose(el, option);
    });

    search.addEventListener('input', () => filter(el, search.value));

    panel.addEventListener('keydown', (event) => {
        const list = options(el);
        const index = list.indexOf(document.activeElement);

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const step = event.key === 'ArrowDown' ? 1 : -1;
            list[index === -1 ? (step > 0 ? 0 : list.length - 1) : (index + step + list.length) % list.length]?.focus();
        } else if ((event.key === 'Enter' || event.key === ' ') && index >= 0) {
            event.preventDefault();
            choose(el, list[index]);
        } else if (event.key === 'Enter' && list.length === 1) {
            event.preventDefault();
            choose(el, list[0]);
        } else if (event.key === 'Escape') {
            close(el, { focusTrigger: true });
        } else if (event.key === 'Tab') {
            close(el);
        }
    });

    if (!bound) {
        bound = true;
        document.addEventListener('click', () => closeAll());
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeAll();
        });
        const reposition = () => pickers.forEach((picker) => picker.classList.contains('is-open') && position(picker));
        window.addEventListener('resize', reposition);
        window.addEventListener('scroll', reposition, { passive: true, capture: true });
    }
}
