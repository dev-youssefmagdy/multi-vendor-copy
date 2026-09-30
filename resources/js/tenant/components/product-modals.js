// Shared product modal logic — social posts + video ad.
// Imported by products-index.js and dashboard/index.js so both pages
// get the same modals with identical behaviour.

import { get, post } from '../core/http.js';
import { openModal } from '../core/modals.js';

const PLATFORMS = ['instagram', 'facebook', 'twitter', 'linkedin', 'tiktok', 'generic'];
const PLATFORM_LABELS = {
    instagram: 'Instagram',
    facebook: 'Facebook',
    twitter: 'Twitter / X',
    linkedin: 'LinkedIn',
    tiktok: 'TikTok',
    generic: 'Generic',
};

const socialState = { productId: null, selectedLanguage: 'all', selectedPlatform: 'all', includeImage: true };

function findProductId(target) {
    const opener = target.closest('[data-modal-open]');
    return opener?.dataset.productId ? Number(opener.dataset.productId) : null;
}

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
        if (!caption) return;

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

export async function openSocialModal(productId) {
    const root = document.querySelector('[data-social-root]');
    if (!root) return;

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

export async function generateSocialPosts() {
    const root = document.querySelector('[data-social-root]');
    if (!root || !socialState.productId) return;

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

export function openVideoAdModal() {
    openModal('product-video-ad-modal');
}

let mounted = false;

// Wire up click/change delegates for these modals.
// Call once per page that includes the modal markup.
export function mountProductModals() {
    if (mounted) return;
    mounted = true;

    document.addEventListener('click', (event) => {
        const socialOpener = event.target.closest('[data-modal-open="product-social-modal"]');
        if (socialOpener) {
            openSocialModal(findProductId(event.target));
            return;
        }

        const videoOpener = event.target.closest('[data-modal-open="product-video-ad-modal"]');
        if (videoOpener) {
            openVideoAdModal();
            return;
        }

        if (event.target.closest('[data-social-generate]')) {
            generateSocialPosts();
        }
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('[data-social-language], [data-social-include-image]')) {
            const root = document.querySelector('[data-social-root]');
            if (!root) return;
            socialState.selectedLanguage = root.querySelector('[data-social-language]').value;
            socialState.includeImage = root.querySelector('[data-social-include-image]').value === 'on';
        }
    });
}
