{{-- Client-side pager shared by the favorites grids (elora, souqify, souqify profile wishlist).
     Raw JS, include it inside an existing <script>. The favorites list lives in localStorage
     (StorefrontFavorites), so it is paged in the browser rather than by a Laravel paginator. --}}
window.StorefrontPager = window.StorefrontPager || {
    // 8 per page on phones (4 rows of the 2-column grid), 10 on desktop: small lists still get a pager.
    get size() { return window.innerWidth >= 1024 ? 10 : 8; },

    /** Returns the requested page of `list` plus the clamped page number and page count. */
    slice(list, page) {
        const pages = Math.max(1, Math.ceil(list.length / this.size));
        const current = Math.min(Math.max(1, page || 1), pages);
        return { page: current, pages, items: list.slice((current - 1) * this.size, current * this.size) };
    },

    /** Paints the numbered pager into `el`; `onChange(page)` is called when a page is picked. */
    render(el, state, onChange, accent) {
        if (!el) return;
        if (state.pages <= 1) {
            el.innerHTML = '';
            return;
        }
        const color = accent || 'var(--color-primary)';
        const base = 'min-width:36px;height:36px;padding:0 12px;border-radius:999px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;justify-content:center;border:1px solid #e5e5e5;background:#fff;color:#444;cursor:pointer;';
        const active = 'border-color:' + color + ';background:' + color + ';color:#fff;cursor:default;';
        const off = 'opacity:.45;cursor:not-allowed;';

        const numbers = [];
        for (let p = 1; p <= state.pages; p++) {
            if (p === 1 || p === state.pages || Math.abs(p - state.page) <= 1) numbers.push(p);
            else if (numbers[numbers.length - 1] !== '…') numbers.push('…');
        }

        const btn = (label, page, style, disabled) =>
            '<button type="button" data-pager-page="' + page + '"' + (disabled ? ' disabled' : '') + ' style="' + base + style + '">' + label + '</button>';

        el.innerHTML =
            '<nav aria-label="Pagination" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:8px;margin-top:32px">' +
            btn(@json(__('Previous')), state.page - 1, state.page === 1 ? off : '', state.page === 1) +
            numbers.map(n => n === '…'
                ? '<span style="padding:0 4px;color:#999">…</span>'
                : btn(n, n, n === state.page ? active : '', n === state.page)).join('') +
            btn(@json(__('Next')), state.page + 1, state.page === state.pages ? off : '', state.page === state.pages) +
            '</nav>';

        el.querySelectorAll('[data-pager-page]').forEach(b => b.addEventListener('click', () => onChange(Number(b.dataset.pagerPage))));
    },
};
