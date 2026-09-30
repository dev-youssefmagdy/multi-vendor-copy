// Dashboard product-card slideshows (winning opportunities, new in) and the
// auto-scrolling "best markets" flag ticker inside opportunity cards.
import Swiper from 'swiper';
import { openModal } from '../../core/modals.js';
import { get, request } from '../../core/http.js';
import { A11y, Autoplay, Keyboard, Mousewheel } from 'swiper/modules';
import 'swiper/css';

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function mountFlagTickers(root = document) {
    root.querySelectorAll('[data-flag-ticker]').forEach((el) => {
        new Swiper(el, {
            modules: [Autoplay],
            nested: true,
            slidesPerView: 'auto',
            spaceBetween: 15.6,
            loop: true,
            speed: 3500,
            allowTouchMove: false,
            autoplay: reducedMotion() ? false : { delay: 0, disableOnInteraction: false, pauseOnMouseEnter: true },
        });
    });
}

function mountSlider(el) {
    // Optional mobile layout: data-mobile-view="1.5" shows 1.5 cards (12px apart)
    // below 768px; larger screens keep the cards' own CSS widths.
    const mobileView = parseFloat(el.dataset.mobileView || '');
    const breakpoints = mobileView
        ? { 0: { slidesPerView: mobileView, spaceBetween: 12 }, 768: { slidesPerView: 'auto', spaceBetween: 24 } }
        : undefined;

    new Swiper(el, {
        modules: [A11y, Autoplay, Keyboard, Mousewheel],
        slidesPerView: 'auto',
        spaceBetween: 24,
        breakpoints,
        grabCursor: true,
        rewind: true,
        speed: 600,
        keyboard: { enabled: true, onlyInViewport: true },
        mousewheel: { forceToAxis: true },
        autoplay: reducedMotion() ? false : { delay: 5000, disableOnInteraction: false, pauseOnMouseEnter: true },
        a11y: { prevSlideMessage: 'Previous products', nextSlideMessage: 'Next products' },
        // Clicking a card's buttons / video controls must not be swallowed as a drag.
        noSwipingSelector: 'button, a, video',
    });
}

// Mounts every product-card slider on the page (winning opportunities, new in, …).
export function mountOpportunities(root = document) {
    root.querySelectorAll('[data-opp-slider]').forEach((slider) => {
        // Tickers first so each card's inner swiper exists before the outer one measures the slides.
        mountFlagTickers(slider);
        mountSlider(slider);
    });

    mountOppCardActions(root);
}

function renderFlashSaleList(sales, card) {
    const body = document.getElementById('opp-flash-sale-modal-body');
    if (!body) return;

    if (!sales.length) {
        body.innerHTML = `<p style="color:var(--t3);font-size:14px;">No active flash sales found. <a href="${card.dataset.attachUrl.replace('item/__SALE__/attach-product', '')}" style="color:var(--accent);">Create one →</a></p>`;
        return;
    }

    body.innerHTML = `<ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;">${
        sales.map(sale => `
            <li style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-radius:8px;border:1px solid var(--border);"
                data-sale-id="${sale.id}">
                <div>
                    <span style="font-weight:600;font-size:14px;">${sale.discount} off</span>
                    <span style="color:var(--t3);font-size:12px;margin-left:8px;">${sale.window}</span>
                </div>
                <button type="button" class="btn btn-sm ${sale.attached ? 'btn-danger' : 'btn-primary'}"
                    data-flash-sale-toggle="${sale.id}"
                    data-attached="${sale.attached ? '1' : '0'}">
                    ${sale.attached ? 'Remove' : 'Add'}
                </button>
            </li>`).join('')
    }</ul>`;

    body.querySelectorAll('[data-flash-sale-toggle]').forEach(btn => {
        btn.addEventListener('click', () => toggleFlashSale(btn, card));
    });
}

async function toggleFlashSale(btn, card) {
    const saleId = btn.dataset.flashSaleToggle;
    const productId = card.dataset.productId;
    const url = card.dataset.attachUrl.replace('__SALE__', saleId);

    btn.disabled = true;
    try {
        const json = await request('post', url, { product_id: parseInt(productId, 10) });
        const attached = json.data?.attached ?? false;
        btn.dataset.attached = attached ? '1' : '0';
        btn.className = `btn btn-sm ${attached ? 'btn-danger' : 'btn-primary'}`;
        btn.textContent = attached ? 'Remove' : 'Add';

        // Update the card button state
        const anyAttached = [...document.querySelectorAll(`#opp-flash-sale-modal-body [data-flash-sale-toggle]`)]
            .some(b => b.dataset.attached === '1');
        syncFlashSaleBtn(card, anyAttached);
    } catch {
        // handled by http interceptor
    } finally {
        btn.disabled = false;
    }
}

function syncFlashSaleBtn(card, inFlashSale) {
    const btn = card.querySelector('[data-opp-flash-sale-btn]');
    if (!btn) return;
    btn.classList.toggle('is-active', inFlashSale);
    const label = btn.querySelector('span');
    if (label) label.textContent = inFlashSale ? 'In Flash Sale' : 'Add to Flash Sale';
    card.dataset.inFlashSale = inFlashSale ? '1' : '0';
}

async function openFlashSaleModal(card) {
    const productId = card.dataset.productId;
    const flashSalesUrl = card.dataset.flashSalesUrl;

    const body = document.getElementById('opp-flash-sale-modal-body');
    if (body) body.innerHTML = '<p style="color:var(--t3);font-size:14px;">Loading flash sales…</p>';

    openModal('opp-flash-sale-modal');

    try {
        const json = await get(flashSalesUrl, { product_id: productId });
        renderFlashSaleList(json.data || [], card);
    } catch {
        if (body) body.innerHTML = '<p style="color:var(--danger);font-size:14px;">Could not load flash sales.</p>';
    }
}

async function toggleTrending(card) {
    const productId = card.dataset.productId;
    if (!productId || !card.dataset.toggleFeaturedUrl) return;

    const btn = card.querySelector('[data-opp-trending-btn]');
    if (btn) btn.disabled = true;

    try {
        const url = card.dataset.toggleFeaturedUrl.replace('__PROD__', productId);
        await request('patch', url);
        const isFeatured = card.dataset.featured !== '1';
        card.dataset.featured = isFeatured ? '1' : '0';
        if (btn) {
            btn.classList.toggle('is-active', isFeatured);
            const label = btn.querySelector('span');
            if (label) label.textContent = isFeatured ? 'Trending ✓' : 'Add to trending';
        }
    } catch {
        // handled by http interceptor
    } finally {
        if (btn) btn.disabled = false;
    }
}

export function mountOppCardActions(root = document) {
    root.addEventListener('click', (event) => {
        const flashBtn = event.target.closest('[data-opp-flash-sale-btn]');
        if (flashBtn) {
            const card = flashBtn.closest('[data-opp-card]');
            if (card && card.dataset.productId) openFlashSaleModal(card);
            return;
        }

        const trendBtn = event.target.closest('[data-opp-trending-btn]');
        if (trendBtn) {
            const card = trendBtn.closest('[data-opp-card]');
            if (card && card.dataset.productId) toggleTrending(card);
        }
    });
}
