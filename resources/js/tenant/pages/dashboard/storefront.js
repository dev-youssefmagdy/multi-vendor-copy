// Dashboard "Live Store Preview": the storefront iframe renders at a desktop
// width and is scaled down to fit its frame, recalculated on resize.
const DESKTOP_WIDTH = 1440;

export function mountStorePreview(root = document) {
    root.querySelectorAll('[data-store-preview]').forEach((view) => {
        const fit = () => view.style.setProperty('--db-preview-scale', String(view.clientWidth / DESKTOP_WIDTH));
        fit();
        new ResizeObserver(fit).observe(view);
    });
}
