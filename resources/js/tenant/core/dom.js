export function onReady(fn) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
        fn();
    }
}

export const qs = (selector, root = document) => root.querySelector(selector);
export const qsa = (selector, root = document) => Array.from(root.querySelectorAll(selector));

export function delegate(root, event, selector, handler) {
    root.addEventListener(event, (e) => {
        const target = e.target.closest(selector);
        if (target && root.contains(target)) {
            handler(e, target);
        }
    });
}

export function debounce(fn, wait = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), wait);
    };
}

export function throttle(fn, wait = 300) {
    let last = 0;
    let timer;
    return (...args) => {
        const now = Date.now();
        const remaining = wait - (now - last);
        if (remaining <= 0) {
            clearTimeout(timer);
            last = now;
            fn(...args);
        } else {
            clearTimeout(timer);
            timer = setTimeout(() => {
                last = Date.now();
                fn(...args);
            }, remaining);
        }
    };
}

export function readJson(id) {
    const el = document.getElementById(id);
    if (!el) {
        return null;
    }

    try {
        return JSON.parse(el.textContent || 'null');
    } catch {
        return null;
    }
}

export function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

export function setBusy(el, busy) {
    if (!el) {
        return;
    }

    el.classList.toggle('is-loading', Boolean(busy));

    if (el instanceof HTMLButtonElement || el instanceof HTMLInputElement) {
        el.disabled = Boolean(busy);
    }
}
