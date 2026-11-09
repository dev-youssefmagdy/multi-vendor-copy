{{-- Display helpers shared by the favorites grids (elora, souqify, souqify profile wishlist).
     Raw JS, include it inside an existing <script>. Inline rather than in the Vite bundle so it
     ships with the Blade views. --}}
window.StorefrontFavoritesDisplay = window.StorefrontFavoritesDisplay || {
    PLACEHOLDER: @json(asset('elora/assets/images/product-default.png')),

    /** Escapes a value for an HTML text node or a quoted attribute. */
    esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    },

    /** Finite number for a stored numeric field, or `fallback` when it cannot be read as one. */
    num(value, fallback = 0) {
        const number = parseFloat(value);
        return Number.isFinite(number) ? number : fallback;
    },

    /** Absolute http(s) URL for a stored value (relative paths resolve same-origin), or '' when it cannot be one. */
    safeUrl(value) {
        if (typeof value !== 'string' || !value.trim()) return '';
        try {
            const url = new URL(value.trim().replace(/&amp;/g, '&').replace(/\\\//g, '/'), window.location.origin);
            return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
        } catch {
            return '';
        }
    },

    /** Href for a stored product link: the safe URL, or a dead '#' for anything else. */
    link(value) {
        return this.safeUrl(value) || '#';
    },

    /** Swaps a failed image for the placeholder (once, so a missing placeholder cannot loop). */
    fallback(img) {
        img.onerror = null;
        img.src = this.PLACEHOLDER;
    },

    /** Escaped <img> for a stored image value; a bad URL or a failed load shows the placeholder. */
    imgTag(image, classes) {
        const src = this.safeUrl(image) || this.PLACEHOLDER;
        return `<img loading="lazy" src="${this.esc(src)}" alt="" class="${this.esc(classes)}" onerror="window.StorefrontFavoritesDisplay.fallback(this)"/>`;
    },

    /**
     * Overwrites the stored snapshots with current server data, matched by slug (most writers
     * store no id; id-only legacy entries match by id). Anything the server cannot resolve keeps
     * its stored data, and a failed request changes nothing, so guests and offline pages still work.
     *
     * @param fetchDisplay (slugs, ids) => Promise<Array> resolving to the current display rows
     * @param onChanged    called after the stored list was updated
     */
    async refresh(fetchDisplay, onChanged) {
        // These pages' scripts can run before Livewire has picked up the page's component.
        if (!window.Livewire?.all?.().length) {
            await new Promise((resolve) => document.addEventListener('livewire:initialized', resolve, { once: true }));
        }

        const wanted = window.StorefrontFavorites.get().filter((f) => f.slug || f.id != null);
        const rows = [];
        try {
            for (let i = 0; i < wanted.length; i += 100) {
                const chunk = wanted.slice(i, i + 100);
                rows.push(...((await fetchDisplay(
                    chunk.filter((f) => f.slug).map((f) => f.slug),
                    chunk.filter((f) => !f.slug).map((f) => f.id)
                )) || []));
            }
        } catch (e) {
            console.error(e);
            return;
        }

        const bySlug = new Map(rows.map((r) => [r.slug, r]));
        const byId = new Map(rows.map((r) => [String(r.id), r]));
        let changed = false;
        // Re-read: the customer may have removed a favorite while the request was running.
        const merged = window.StorefrontFavorites.get().map((item) => {
            const fresh = item.slug ? bySlug.get(item.slug) : byId.get(String(item.id));
            if (!fresh) return item;
            const next = { ...item, ...fresh };
            if (JSON.stringify(next) !== JSON.stringify(item)) changed = true;
            return next;
        });

        if (changed) {
            window.StorefrontFavorites.save(merged);
            if (onChanged) onChanged();
        }
    },
};
