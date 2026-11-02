// Single favorites store shared by every storefront theme (elora, souqify, ecommet).
// Theme toggles/pages only paint their own heart icons; storage, identity and the
// change event live here so a product favorited in one theme/page shows everywhere.
const KEY = 'storefront_favorites';
const LEGACY_KEYS = ['elora_favorites', 'souqify_favorites'];

function read(key) {
    try {
        const list = JSON.parse(localStorage.getItem(key) || '[]');
        return Array.isArray(list) ? list : [];
    } catch {
        return [];
    }
}

function same(a, b) {
    if (a.slug && b.slug) return a.slug === b.slug;
    return a.id != null && b.id != null && String(a.id) === String(b.id);
}

function migrateLegacy() {
    try {
        let list = read(KEY);
        let changed = false;
        LEGACY_KEYS.forEach((legacy) => {
            if (localStorage.getItem(legacy) === null) return;
            read(legacy).forEach((item) => {
                if (!list.some((f) => same(f, item))) {
                    list.push(item);
                    changed = true;
                }
            });
            localStorage.removeItem(legacy);
        });
        if (changed) localStorage.setItem(KEY, JSON.stringify(list));
    } catch { /* storage unavailable */ }
}

function save(list) {
    try { localStorage.setItem(KEY, JSON.stringify(list)); } catch { /* storage unavailable */ }
    window.dispatchEvent(new CustomEvent('storefront-favorites-changed'));
}

const StorefrontFavorites = {
    KEY,
    get: () => read(KEY),
    save,
    has: (item) => read(KEY).some((f) => same(f, item)),
    /** Adds or removes `item` ({slug|id, ...}); returns true when it is now favorited. */
    toggle(item) {
        const list = read(KEY);
        const idx = list.findIndex((f) => same(f, item));
        const adding = idx < 0;
        if (adding) list.unshift({ ...item, added: Math.floor(Date.now() / 1000) });
        else list.splice(idx, 1);
        save(list);
        return adding;
    },
    remove(item) {
        save(read(KEY).filter((f) => !same(f, item)));
    },
};

migrateLegacy();
window.StorefrontFavorites = StorefrontFavorites;
