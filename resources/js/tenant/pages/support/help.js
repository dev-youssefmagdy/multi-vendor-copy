import '@tenant-css/pages/help.css';

import { get } from '../../core/http.js';
import { initComponents } from '../../core/registry.js';

function articleUrlFor(slug) {
    const template = document.querySelector('.docs-sidebar')?.dataset.helpArticlesUrl;
    return template ? template.replace('__slug__', slug) : `/help/articles/${slug}`;
}

async function loadArticle(slug, { pushState = true } = {}) {
    const content = document.querySelector('[data-docs-content]');
    if (!content) {
        return;
    }

    try {
        const response = await get(articleUrlFor(slug), {}, { toast: false });
        content.innerHTML = response.html || '<p class="panel-copy">Article not found.</p>';
        initComponents(content);
        content.scrollIntoView({ behavior: 'smooth', block: 'start' });

        document.querySelectorAll('[data-help-article-link]').forEach((link) => {
            link.classList.toggle('docs-nav-link--active', link.dataset.helpArticleLink === slug);
        });

        if (pushState) {
            const url = new URL(location.href);
            url.searchParams.set('article', slug);
            history.pushState(null, '', `${url.pathname}?${url.searchParams.toString()}`);
        }
    } catch {
        /* handled by http interceptor */
    }
}

function init() {
    const nav = document.querySelector('.docs-sidebar');
    if (!nav) {
        return;
    }

    nav.addEventListener('click', (event) => {
        const link = event.target.closest('[data-help-article-link]');
        if (!link) {
            return;
        }

        event.preventDefault();
        loadArticle(link.dataset.helpArticleLink);
    });

    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(location.search);
        const slug = params.get('article') || 'getting-started';
        loadArticle(slug, { pushState: false });
    });
}

document.addEventListener('DOMContentLoaded', init);
