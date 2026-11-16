// Dashboard "Live Store Preview": the storefront iframe renders at a desktop
// width and is scaled down to fit its frame, recalculated on resize.
//
// "Edit your storefront" country selector (x-tenant::country-picker): the choice
// (remembered by the picker, shared panel-wide) reloads the preview as a visitor
// from that country and repoints every shortcut at what that country sees. The
// per-country URLs are built server-side (App\Support\Tenant\StorefrontCountryShortcuts)
// and embedded as JSON in [data-store-country-map].
const DESKTOP_WIDTH = 1440;
const STORAGE_KEY = 'tenant:country'; // same key as components/country-picker.js

function readStoredCode() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null')?.code ?? null;
    } catch {
        return null;
    }
}

function mountCountryShortcuts(section) {
    let map = {};
    try {
        map = JSON.parse(section.querySelector('[data-store-country-map]')?.textContent || '{}');
    } catch {
        return;
    }

    const fallback = map.all;
    if (!fallback) return;

    const frame = section.querySelector('[data-store-preview-frame]');
    const urlLabel = section.querySelector('[data-store-preview-url]');
    let current = null;

    const apply = (code) => {
        // A country this store doesn't sell in (e.g. picked in another dashboard picker) => store-wide.
        const entry = map[code] ?? fallback;
        if (entry === current) return;
        current = entry;

        section.querySelectorAll('[data-store-shortcut]').forEach((link) => {
            const href = entry.links?.[link.dataset.storeShortcut];
            if (href) link.href = href;
        });

        section.querySelectorAll('[data-store-preview-link]').forEach((link) => {
            link.href = entry.preview;
        });

        if (urlLabel) {
            const host = urlLabel.dataset.host || '';
            urlLabel.textContent = entry.iso2 ? `${host} · ${entry.iso2}` : host;
        }

        if (frame && frame.getAttribute('src') !== entry.preview) {
            frame.setAttribute('src', entry.preview);
        }
    };

    apply(readStoredCode());
    document.addEventListener('tenant:country-change', (event) => apply(event.detail?.code));
}

export function mountStorePreview(root = document) {
    root.querySelectorAll('[data-store-preview]').forEach((view) => {
        const fit = () => view.style.setProperty('--db-preview-scale', String(view.clientWidth / DESKTOP_WIDTH));
        fit();
        new ResizeObserver(fit).observe(view);
    });

    root.querySelectorAll('[data-store-country-shortcuts]').forEach(mountCountryShortcuts);
}
