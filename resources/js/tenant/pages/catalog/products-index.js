import '@tenant-css/pages/products.css';
import '@tenant-css/components/image-search.css';

import { get, post, put } from '../../core/http.js';
import { openModal, closeModal } from '../../core/modals.js';
import { on, emit } from '../../core/events.js';
import { computePrices, renderPriceTable } from './products-price-list.js';

const PLATFORMS = ['instagram', 'facebook', 'twitter', 'linkedin', 'tiktok', 'generic'];
const PLATFORM_LABELS = {
    instagram: 'Instagram',
    facebook: 'Facebook',
    twitter: 'Twitter / X',
    linkedin: 'LinkedIn',
    tiktok: 'TikTok',
    generic: 'Generic',
};

function findProductId(target) {
    const opener = target.closest('[data-modal-open]');
    return opener?.dataset.productId ? Number(opener.dataset.productId) : null;
}

// ── Social posts modal ──────────────────────────────────────────────────

const socialState = { productId: null, selectedLanguage: 'all', selectedPlatform: 'all', includeImage: true };

function renderSocialPosts(root, posts, activeLang) {
    const tabsEl = root.querySelector('[data-social-tabs]');
    const postsEl = root.querySelector('[data-social-posts]');
    const emptyEl = root.querySelector('[data-social-empty]');
    const langs = Object.keys(posts || {});

    tabsEl.innerHTML = '';
    postsEl.innerHTML = '';

    if (!langs.length) {
        emptyEl.hidden = false;
        return;
    }
    emptyEl.hidden = true;

    const active = langs.includes(activeLang) ? activeLang : langs[0];

    if (langs.length > 1) {
        langs.forEach((lang) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `btn btn-sm ${lang === active ? 'btn-primary' : 'btn-secondary'}`;
            btn.textContent = lang.toUpperCase();
            btn.addEventListener('click', () => renderSocialPosts(root, posts, lang));
            tabsEl.appendChild(btn);
        });
    }

    const activePosts = posts[active] || {};
    const visible = socialState.selectedPlatform === 'all' ? PLATFORMS : [socialState.selectedPlatform];

    visible.forEach((platform) => {
        const caption = activePosts[platform];
        if (!caption) {
            return;
        }

        const card = document.createElement('div');
        card.className = 'social-post-card';
        card.innerHTML = `
            <div class="social-post-header">
                <div class="social-post-header-left">
                    <span>${PLATFORM_LABELS[platform] || platform}</span>
                    <span class="badge badge-cyan">${active.toUpperCase()}</span>
                </div>
                <div class="social-post-actions">
                    <button type="button" class="btn btn-secondary btn-sm" data-copy-caption>Copy</button>
                </div>
            </div>
            <p class="social-post-text"></p>
        `;
        card.querySelector('.social-post-text').textContent = caption;
        card.querySelector('[data-copy-caption]').addEventListener('click', () => {
            navigator.clipboard?.writeText(caption);
        });
        postsEl.appendChild(card);
    });
}

function renderSocialPlatforms(root) {
    const wrap = root.querySelector('[data-social-platforms]');
    wrap.innerHTML = '';

    const allBtn = document.createElement('button');
    allBtn.type = 'button';
    allBtn.className = `social-platform-btn ${socialState.selectedPlatform === 'all' ? 'is-active' : ''}`;
    allBtn.textContent = 'All';
    allBtn.addEventListener('click', () => {
        socialState.selectedPlatform = 'all';
        renderSocialPlatforms(root);
    });
    wrap.appendChild(allBtn);

    PLATFORMS.forEach((platform) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `social-platform-btn ${socialState.selectedPlatform === platform ? 'is-active' : ''}`;
        btn.textContent = PLATFORM_LABELS[platform];
        btn.addEventListener('click', () => {
            socialState.selectedPlatform = platform;
            renderSocialPlatforms(root);
        });
        wrap.appendChild(btn);
    });
}

async function openSocialModal(productId) {
    const root = document.querySelector('[data-social-root]');
    socialState.productId = productId;
    root.querySelector('[data-social-error]').hidden = true;

    let data;
    try {
        const response = await get(`/admin/products/${productId}/social`, {}, { toast: false });
        data = response.data;
    } catch {
        return;
    }

    socialState.selectedLanguage = data.selectedLanguage || 'all';
    socialState.includeImage = data.includeImage !== false;
    socialState.selectedPlatform = data.selectedPlatform || 'all';

    const langSelect = root.querySelector('[data-social-language]');
    langSelect.innerHTML = '<option value="all">All enabled languages</option>';
    Object.entries(data.enabledLanguages || {}).forEach(([code, name]) => {
        const opt = document.createElement('option');
        opt.value = code;
        opt.textContent = `${name} (${code.toUpperCase()})`;
        langSelect.appendChild(opt);
    });
    langSelect.value = socialState.selectedLanguage;

    root.querySelector('[data-social-include-image]').value = socialState.includeImage ? 'on' : 'off';

    renderSocialPlatforms(root);
    renderSocialPosts(root, data.posts, data.activeLang);

    const imageEl = root.querySelector('[data-social-image]');
    if (data.imageB64) {
        imageEl.hidden = false;
        root.querySelector('[data-social-image-el]').src = `data:image/png;base64,${data.imageB64}`;
    } else {
        imageEl.hidden = true;
    }

    openModal('product-social-modal');
}

async function generateSocialPosts() {
    const root = document.querySelector('[data-social-root]');
    if (!socialState.productId) {
        return;
    }

    const loading = root.querySelector('[data-social-loading]');
    const errorEl = root.querySelector('[data-social-error]');
    errorEl.hidden = true;
    loading.hidden = false;

    const language = root.querySelector('[data-social-language]').value;
    const includeImage = root.querySelector('[data-social-include-image]').value === 'on';

    try {
        const response = await post(`/admin/products/${socialState.productId}/social/generate`, {
            language,
            platform: socialState.selectedPlatform,
            include_image: includeImage,
        });
        renderSocialPosts(root, response.data.posts, response.data.active_lang);

        const imageEl = root.querySelector('[data-social-image]');
        if (response.data.image_b64) {
            imageEl.hidden = false;
            root.querySelector('[data-social-image-el]').src = `data:image/png;base64,${response.data.image_b64}`;
        }
    } catch (e) {
        errorEl.textContent = e.message || 'Generation failed.';
        errorEl.hidden = false;
    } finally {
        loading.hidden = true;
    }
}

// ── Price finder modal ──────────────────────────────────────────────────

let priceFinderProductId = null;

function renderPriceFinderResults(root, priceData) {
    const resultsEl = root.querySelector('[data-price-finder-results]');
    const emptyEl = root.querySelector('[data-price-finder-empty]');
    const hits = priceData?.hits || [];
    const sampleSize = priceData?.sample_size || 0;

    if (!hits.length || !sampleSize) {
        resultsEl.hidden = true;
        emptyEl.hidden = false;
        root.querySelector('[data-price-finder-hint]').textContent = 'Click Fetch to discover current market prices for this product from the web.';
        return;
    }

    emptyEl.hidden = true;
    resultsEl.hidden = false;
    root.querySelector('[data-price-finder-hint]').textContent = `AI-discovered market prices from ${sampleSize} web sources. Refresh to get updated data.`;

    const statsEl = root.querySelector('[data-price-finder-stats]');
    statsEl.innerHTML = '';
    [
        ['Average', priceData.average_price],
        ['Median', priceData.median_price],
        ['Min', priceData.min_price],
        ['Max', priceData.max_price],
    ].forEach(([label, value]) => {
        const div = document.createElement('div');
        div.className = 'price-finder-stat';
        div.innerHTML = `<div class="entity-subtitle">${label}</div><div class="price-finder-stat-value">${value !== null && value !== undefined ? `$${Number(value).toFixed(2)}` : '—'}</div>`;
        statsEl.appendChild(div);
    });

    const table = root.querySelector('[data-price-finder-hits-table] tbody');
    table.innerHTML = '';
    hits.forEach((hit) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><a href="${hit.url}" target="_blank" rel="noopener noreferrer">${hit.source}</a></td>
            <td style="text-align:right;font-weight:700;color:#4ade80;">$${Number(hit.price).toFixed(2)}</td>
            <td title="${hit.title}">${hit.title}</td>
        `;
        table.appendChild(tr);
    });

    root.querySelector('[data-price-finder-query]').textContent = `Query used: ${priceData.query || ''}`;
}

async function openPriceFinderModal(productId) {
    const root = document.querySelector('[data-price-finder-root]');
    priceFinderProductId = productId;
    root.querySelector('[data-price-finder-error]').hidden = true;

    let data;
    try {
        const response = await get(`/admin/products/${productId}/ai-price`, {}, { toast: false });
        data = response.data;
    } catch {
        return;
    }

    const variantSelect = root.querySelector('[data-price-finder-variant]');
    variantSelect.innerHTML = '<option value="">Main product image</option>';
    Object.entries(data.priceVariants || {}).forEach(([id, label]) => {
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = label;
        variantSelect.appendChild(opt);
    });

    renderPriceFinderResults(root, data.priceData);
    openModal('product-price-finder-modal');
}

async function fetchAiPrice() {
    const root = document.querySelector('[data-price-finder-root]');
    if (!priceFinderProductId) {
        return;
    }

    const loading = root.querySelector('[data-price-finder-loading]');
    const errorEl = root.querySelector('[data-price-finder-error]');
    errorEl.hidden = true;
    loading.hidden = false;

    const useImage = root.querySelector('[data-price-finder-use-image]').checked;
    const variantId = root.querySelector('[data-price-finder-variant]').value || null;

    try {
        const response = await post(`/admin/products/${priceFinderProductId}/ai-price`, {
            use_image: useImage,
            variant_id: variantId,
        });
        renderPriceFinderResults(root, response.data.priceData);
    } catch (e) {
        errorEl.textContent = e.message || 'Fetch failed.';
        errorEl.hidden = false;
    } finally {
        loading.hidden = true;
    }
}

// ── Share modal ──────────────────────────────────────────────────────────

async function openShareModal(productId) {
    const root = document.querySelector('[data-share-root]');

    let data;
    try {
        const response = await get(`/admin/products/${productId}/share`, {}, { toast: false });
        data = response.data;
    } catch {
        return;
    }

    root.querySelector('[data-share-title]').textContent = data.title;
    root.querySelector('[data-share-caption]').textContent = data.caption;
    root.querySelector('[data-share-ai-badge]').hidden = !data.has_ai_content;

    const imageEl = root.querySelector('[data-share-image]');
    if (data.image_url) {
        imageEl.hidden = false;
        imageEl.src = data.image_url;
    } else {
        imageEl.hidden = true;
    }

    const urlRow = root.querySelector('[data-share-url-row]');
    if (data.url) {
        urlRow.hidden = false;
        root.querySelector('[data-share-url]').textContent = data.url;
        root.querySelector('[data-share-copy]').dataset.copyValue = data.url;
    } else {
        urlRow.hidden = true;
    }

    root.querySelector('[data-share-note]').textContent = data.has_ai_content
        ? 'Caption sourced from AI-generated social posts.'
        : 'Using default product details (no AI post generated yet).';

    const url = data.url || '';
    const shortCaption = (data.caption || '').split('\n')[0].slice(0, 120);
    const platforms = [
        ['Facebook', `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`],
        ['X / Twitter', `https://twitter.com/intent/tweet?text=${encodeURIComponent(shortCaption)}&url=${encodeURIComponent(url)}`],
        ['LinkedIn', `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`],
        ['WhatsApp', `https://wa.me/?text=${encodeURIComponent(`*${data.title}*\n${shortCaption}\n\n${url}`)}`],
        ['Telegram', `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(shortCaption)}`],
        ['Pinterest', `https://pinterest.com/pin/create/button/?url=${encodeURIComponent(url)}&description=${encodeURIComponent(shortCaption)}`],
        ['TikTok', `https://www.tiktok.com/share?url=${encodeURIComponent(url)}`],
    ];

    const platformsEl = root.querySelector('[data-share-platforms]');
    platformsEl.innerHTML = '';
    platforms.forEach(([label, href]) => {
        const a = document.createElement('a');
        a.href = href;
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
        a.className = 'btn btn-secondary share-platform-btn';
        a.textContent = label;
        platformsEl.appendChild(a);
    });

    openModal('product-share-modal');
}

// ── Price list modal ──────────────────────────────────────────────────────

let priceListState = null;

function recomputeAndRenderPriceList(root) {
    priceListState.prices = computePrices(priceListState.centralSalePrice, priceListState.profits, priceListState.shippingByCountry);
}

async function openPriceListModal(productId) {
    const root = document.querySelector('[data-price-list-root]');
    root.querySelector('[data-price-list-message]').hidden = true;

    let data;
    try {
        const response = await get(`/admin/products/${productId}/price-list`, {}, { toast: false });
        data = response.data;
    } catch {
        return;
    }

    priceListState = { productId, ...data };

    const productTable = root.querySelector('[data-price-list-product-table] tbody');
    const noPrices = root.querySelector('[data-price-list-no-prices]');
    if (Object.keys(data.prices || {}).length) {
        noPrices.hidden = true;
        renderPriceTable(productTable, {
            basePrice: data.centralSalePrice,
            profits: data.profits,
            shippingByCountry: data.shippingByCountry,
            countryLabels: data.countryLabels,
        }, () => recomputeAndRenderPriceList(root));
    } else {
        noPrices.hidden = false;
        productTable.innerHTML = '';
    }

    const variantsWrap = root.querySelector('[data-price-list-variants]');
    const variantsHeading = root.querySelector('[data-price-list-variants-heading]');
    variantsWrap.innerHTML = '';
    variantsHeading.hidden = !((data.variants || []).length);

    (data.variants || []).forEach((variant) => {
        const block = document.createElement('div');
        block.className = 'price-list-variant-block';
        block.innerHTML = `
            <div class="entity-subtitle price-list-variant-label">${variant.label} <span>cost: $${Number(variant.real_price).toFixed(2)}</span></div>
            <div class="tw"><table class="tb"><thead><tr><th>Country</th><th>Cost Price</th><th>Profit</th><th>Fixed Cost</th><th>Your Price</th></tr></thead><tbody></tbody></table></div>
        `;
        const tbody = block.querySelector('tbody');
        renderPriceTable(tbody, {
            basePrice: variant.real_price,
            profits: variant.profits,
            shippingByCountry: variant.shipping,
            countryLabels: data.countryLabels,
        }, () => {});
        variantsWrap.appendChild(block);
    });

    openModal('product-price-list-modal');
}

async function savePriceList() {
    if (!priceListState) {
        return;
    }

    const payload = {
        profits: priceListState.profits,
        variants: (priceListState.variants || []).map((v) => ({
            id: v.id,
            real_price: v.real_price,
            profits: v.profits,
        })),
    };

    try {
        await put(`/admin/products/${priceListState.productId}/price-list`, payload);
        closeModal('product-price-list-modal');
        emit('tenant:table:reload', { selector: '#products-table' });
    } catch {
        /* handled globally */
    }
}

// ── Video ad modal (client-only) ─────────────────────────────────────────

function openVideoAdModal() {
    openModal('product-video-ad-modal');
}

// ── Image search chip + filter ────────────────────────────────────────────

function updateImageSearchChip(ids) {
    const status = document.querySelector('[data-products-image-search-status]');
    const input = document.querySelector('[data-products-image-ids-input]');

    if (ids && ids.length) {
        input.value = ids.join(',');
        status.hidden = false;
        status.innerHTML = `
            <span class="products-image-search-chip">
                Visual match &mdash; ${ids.length} results
                <button type="button" data-clear-image-search aria-label="Clear">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </span>
        `;
    } else {
        input.value = '';
        status.hidden = true;
        status.innerHTML = '';
    }

    // The hidden `image_ids` field isn't one of the input types the
    // filters-card component listens on, so merge it into the table's
    // cached filter set directly before asking it to reload.
    const table = document.getElementById('products-table');
    if (table) {
        table._filters = { ...(table._filters || {}) };
        if (ids && ids.length) {
            table._filters.image_ids = ids.join(',');
        } else {
            delete table._filters.image_ids;
        }
    }

    emit('tenant:table:reload', { selector: '#products-table' });
}

on('tenant:image-search:results', ({ ids }) => updateImageSearchChip(ids || []));

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-clear-image-search]')) {
        updateImageSearchChip([]);
    }
});

// ── Dropdown modal openers ────────────────────────────────────────────────

document.addEventListener('click', (event) => {
    const socialOpener = event.target.closest('[data-modal-open="product-social-modal"]');
    if (socialOpener) {
        openSocialModal(findProductId(event.target));
        return;
    }

    const priceOpener = event.target.closest('[data-modal-open="product-price-finder-modal"]');
    if (priceOpener) {
        openPriceFinderModal(findProductId(event.target));
        return;
    }

    const shareOpener = event.target.closest('[data-modal-open="product-share-modal"]');
    if (shareOpener) {
        openShareModal(findProductId(event.target));
        return;
    }

    const priceListOpener = event.target.closest('[data-modal-open="product-price-list-modal"]');
    if (priceListOpener) {
        openPriceListModal(findProductId(event.target));
        return;
    }

    const videoOpener = event.target.closest('[data-modal-open="product-video-ad-modal"]');
    if (videoOpener) {
        openVideoAdModal();
    }
});

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-social-generate]')) {
        generateSocialPosts();
    }
    if (event.target.closest('[data-price-finder-fetch]')) {
        fetchAiPrice();
    }
    if (event.target.closest('[data-price-list-save]')) {
        savePriceList();
    }
});

document.addEventListener('change', (event) => {
    if (event.target.matches('[data-social-language], [data-social-include-image]')) {
        const root = document.querySelector('[data-social-root]');
        socialState.selectedLanguage = root.querySelector('[data-social-language]').value;
        socialState.includeImage = root.querySelector('[data-social-include-image]').value === 'on';
    }
    if (event.target.matches('[data-price-finder-use-image]')) {
        const root = document.querySelector('[data-price-finder-root]');
        root.querySelector('[data-price-finder-variant]').hidden = !event.target.checked;
    }
});
