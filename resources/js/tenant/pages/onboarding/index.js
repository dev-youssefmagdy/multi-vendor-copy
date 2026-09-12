import '@tenant-css/pages/onboarding.css';
import '@tenant/modules/logo-builder.js';
import { post } from '@tenant/core/http.js';
import { on, emit, EVENTS } from '@tenant/core/events.js';
import { qs, qsa, readJson, setBusy } from '@tenant/core/dom.js';

const STORAGE_KEY = 'tenant:onboarding:tour-step';

function initTour(root) {
    const steps = readJson('onboarding-steps-data') || [];
    const template = document.getElementById('onboarding-icons-template');
    const total = steps.length;
    const completeUrl = root.dataset.completeUrl;

    const progressFill = qs('[data-tour-progress-fill]', root);
    const stepPill = qs('[data-tour-step-pill]', root);
    const iconWrap = qs('[data-tour-icon-wrap]', root);
    const titleEl = qs('[data-tour-title]', root);
    const descriptionEl = qs('[data-tour-description]', root);
    const dots = qsa('[data-tour-dot]', root);
    const skipBtn = qs('[data-tour-skip]', root);
    const nextBtn = qs('[data-tour-next]', root);
    const nextLabel = qs('[data-tour-next-label]', root);
    const nextIconMid = qs('[data-tour-next-icon-mid]', root);
    const nextIconLast = qs('[data-tour-next-icon-last]', root);

    let current = 0;
    const stored = parseInt(sessionStorage.getItem(STORAGE_KEY) || '', 10);
    if (!Number.isNaN(stored) && stored >= 0 && stored < total) {
        current = stored;
    }

    function iconMarkup(name) {
        return template?.querySelector(`[data-icon-name="${CSS.escape(name)}"]`)?.innerHTML ?? '';
    }

    function render() {
        const step = steps[current];
        if (!step) {
            return;
        }

        const isLast = current === total - 1;

        progressFill.style.width = `${((current + 1) / total) * 100}%`;
        stepPill.textContent = `Step ${current + 1} of ${total}`;

        iconWrap.className = `ob-icon-wrap ob-icon-${step.color || 'cyan'}`;
        iconWrap.innerHTML = iconMarkup(isLast ? 'done' : step.icon);

        titleEl.textContent = step.title;
        descriptionEl.textContent = step.description;

        dots.forEach((dot, index) => {
            dot.classList.toggle('ob-dot-active', index === current);
            dot.classList.toggle('ob-dot-done', index < current);
        });

        nextLabel.textContent = isLast ? 'Finish' : 'Next';
        nextIconMid.hidden = isLast;
        nextIconLast.hidden = !isLast;

        try {
            sessionStorage.setItem(STORAGE_KEY, String(current));
        } catch {
            /* storage unavailable */
        }
    }

    async function complete(skipped) {
        setBusy(nextBtn, true);
        setBusy(skipBtn, true);
        try {
            await post(completeUrl, skipped ? { skipped: 1 } : {});
            try {
                sessionStorage.removeItem(STORAGE_KEY);
            } catch {
                /* storage unavailable */
            }
        } finally {
            setBusy(nextBtn, false);
            setBusy(skipBtn, false);
        }
    }

    nextBtn?.addEventListener('click', () => {
        if (current === total - 1) {
            complete(false);
            return;
        }
        current += 1;
        render();
    });

    skipBtn?.addEventListener('click', () => complete(true));

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            current = index;
            render();
        });
    });

    render();
}

function renderSetupItem(itemEl, item) {
    itemEl.classList.toggle('ob-setup-done', item.done);

    const status = qs('.ob-setup-item-status', itemEl);
    if (status) {
        status.innerHTML = item.done
            ? '<div class="ob-check-done"><svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12" /></svg></div>'
            : `<div class="ob-check-pending ${item.mandatory ? 'ob-check-mandatory' : 'ob-check-optional'}">${
                  item.mandatory
                      ? '<span class="ob-check-asterisk">*</span>'
                      : '<svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9" /></svg>'
              }</div>`;
    }

    const row = qs('.ob-setup-item-row', itemEl);
    const trailing = row?.querySelector('.ob-setup-action, .ob-setup-item-done-flag');
    if (trailing) {
        if (item.done) {
            trailing.outerHTML =
                '<div class="ob-setup-item-done-flag"><svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12" /></svg>Done</div>';
        } else {
            trailing.outerHTML = `<a href="${item.action_url}" class="ob-setup-action">${item.action_label}<svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6" /></svg></a>`;
        }
    }

    if (item.done) {
        const content = qs('.ob-setup-item-content', itemEl);
        if (content) {
            content.innerHTML = '';
        }
    }
}

function applySetupProgress(root, setup) {
    if (!setup) {
        return;
    }

    setup.items.forEach((item) => {
        const itemEl = root.querySelector(`[data-setup-item="${CSS.escape(item.key)}"]`);
        if (itemEl) {
            renderSetupItem(itemEl, item);
        }
    });

    const doneCount = setup.done_count;
    const totalCount = setup.total_count;
    const pct = totalCount > 0 ? Math.round((doneCount / totalCount) * 100) : 0;
    const remaining = totalCount - doneCount;

    const progressText = qs('[data-setup-progress-text]', root);
    if (progressText) {
        progressText.textContent = `Setup ${pct}% complete — ${remaining} ${remaining === 1 ? 'step' : 'steps'} remaining`;
    }

    const progressFill = qs('[data-setup-progress-fill]', root);
    if (progressFill) {
        progressFill.style.width = `${pct}%`;
    }

    const badge = qs('.t-tab-badge[data-tab-key="setup"], [data-tab-key="setup"] .t-tab-badge');
    if (badge) {
        badge.textContent = `${doneCount}/${totalCount}`;
    }

    root.querySelector('[data-setup-footer-done]')?.toggleAttribute('hidden', !setup.all_done);
    root.querySelector('[data-setup-footer-pending]')?.toggleAttribute('hidden', setup.all_done);
}

function initSetup(root) {
    on('tenant:onboarding:setup-updated', ({ response }) => {
        applySetupProgress(root, response?.data?.setup);
        emit(EVENTS.SETUP_PROGRESS_REFRESH);
    });

    document.querySelector('[data-payment-readiness-skip]')?.addEventListener('click', () => {
        root.querySelector('[data-payment-readiness-block]')?.setAttribute('hidden', '');
        root.querySelector('[data-payment-readiness-done]')?.removeAttribute('hidden');
    });

    const highlightKey = root.dataset.highlightItem;
    if (highlightKey) {
        const target = root.querySelector(`[data-setup-item="${CSS.escape(highlightKey)}"]`);
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.classList.add('ob-setup-item-highlight');
            setTimeout(() => target.classList.remove('ob-setup-item-highlight'), 2000);
        }
    }
}

const tourRoot = document.querySelector('[data-onboarding-tour]');
if (tourRoot) {
    initTour(tourRoot);
}

const setupRoot = document.querySelector('[data-onboarding-setup]');
if (setupRoot) {
    initSetup(setupRoot);
}
