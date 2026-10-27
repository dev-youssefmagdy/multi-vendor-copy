// Products module header: the module tabs are a native horizontally scrollable
// strip (touch / trackpad scroll works out of the box). This adds mouse drag,
// vertical-wheel -> horizontal scroll, edge fades and starts at the active tab.
import '@tenant-css/pages/dashboard.css'; // header title/description (db-welcome)
import '@tenant-css/pages/products-module.css';

document.querySelectorAll('[data-pm-tabs]').forEach((el) => {
    const updateFades = () => {
        const max = el.scrollWidth - el.clientWidth;
        const pos = Math.abs(el.scrollLeft); // negative in RTL
        el.classList.toggle('has-start', pos > 2);
        el.classList.toggle('has-end', pos < max - 2);
    };

    // Bring the active tab into view without animating on load.
    const active = el.querySelector('.is-active');
    if (active) {
        const target = active.offsetLeft - el.offsetLeft - 16;
        el.scrollLeft = document.dir === 'rtl' ? -Math.max(0, target) : Math.max(0, target);
    }
    updateFades();

    el.addEventListener('scroll', updateFades, { passive: true });
    window.addEventListener('resize', updateFades);

    // Vertical wheel scrolls the strip sideways, but only while it can still move
    // that way so the page keeps scrolling normally at either edge.
    el.addEventListener('wheel', (e) => {
        if (Math.abs(e.deltaY) <= Math.abs(e.deltaX)) return;
        const max = el.scrollWidth - el.clientWidth;
        const next = el.scrollLeft + e.deltaY;
        const canMove = document.dir === 'rtl' ? (next < 0 && next > -max) : (next > 0 && next < max);
        if (max > 0 && canMove) {
            e.preventDefault();
            el.scrollLeft = next;
        }
    }, { passive: false });

    // Mouse drag (touch/pen use native scrolling).
    let startX = 0;
    let startScroll = 0;
    let dragging = false;
    let moved = false;

    el.addEventListener('pointerdown', (e) => {
        if (e.pointerType !== 'mouse' || e.button !== 0) return;
        dragging = true;
        moved = false;
        startX = e.clientX;
        startScroll = el.scrollLeft;
    });

    window.addEventListener('pointermove', (e) => {
        if (!dragging) return;
        const dx = e.clientX - startX;
        if (!moved && Math.abs(dx) > 4) {
            moved = true;
            el.classList.add('is-dragging');
        }
        if (moved) el.scrollLeft = startScroll - dx;
    });

    const stop = () => {
        if (!dragging) return;
        dragging = false;
        // Keep pointer-events off the tabs until after the click that ends the drag.
        setTimeout(() => el.classList.remove('is-dragging'), 0);
    };
    window.addEventListener('pointerup', stop);
    window.addEventListener('pointercancel', stop);

});
