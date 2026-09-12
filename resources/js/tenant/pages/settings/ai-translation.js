// AI Translation settings page.
//
// Language cards let the tenant either run a free AI translation, or open
// the shared payment-gateway modal to buy a paid one. While any language is
// queued/running, the page polls a small JSON status endpoint every 3s
// (only while the tab is visible) and patches each card's progress bar and
// badges in place — there is no full page reload.
import { post, get } from '../../core/http.js';
import { openModal, closeModal } from '../../core/modals.js';

const POLL_INTERVAL = 3000;

function grid() {
    return document.querySelector('[data-ai-translation-cards]');
}

function cardEl(id) {
    return grid()?.querySelector(`[data-ai-card="${id}"]`);
}

function isRunning(status) {
    return status === 'queued' || status === 'running';
}

function patchCard(card) {
    const el = cardEl(card.id);
    if (!el) {
        return;
    }

    const running = isRunning(card.translation_status);
    const button = el.querySelector('[data-ai-card-action]');

    const progressWrap = el.querySelector('[data-ai-card-progress]');
    const progressBar = el.querySelector('[data-ai-card-progress-bar]');
    const progressLabel = el.querySelector('[data-ai-card-progress-label]');
    const completedEl = el.querySelector('[data-ai-card-completed]');
    const failedEl = el.querySelector('[data-ai-card-failed]');

    if (progressWrap) {
        progressWrap.hidden = !running;
    }
    if (running && progressBar) {
        progressBar.style.width = `${card.translation_progress ?? 0}%`;
    }
    if (running && progressLabel) {
        progressLabel.textContent = `${card.translation_status[0].toUpperCase()}${card.translation_status.slice(1)} — ${card.translation_progress ?? 0}%`;
    }

    if (completedEl) {
        completedEl.hidden = card.translation_status !== 'completed';
        if (card.translation_status === 'completed') {
            const badge = completedEl.querySelector('.badge');
            const summary = card.translation_summary ? JSON.parse(card.translation_summary) : {};
            if (badge) {
                badge.textContent = `Completed — ${summary.items_translated ?? 0} items translated`;
            }
        }
    }

    if (failedEl) {
        failedEl.hidden = card.translation_status !== 'failed';
    }

    if (button) {
        const isFree = button.dataset.languageFree === '1';
        const price = button.dataset.languagePrice;
        button.disabled = running || button.disabled;
        if (running) {
            button.textContent = 'Translating…';
        } else if (isFree) {
            button.textContent = 'Run AI Translation';
        } else {
            button.textContent = `Buy AI Translation ($${price})`;
        }
    }
}

let pollTimer = null;

function anyCardRunning() {
    return Array.from(grid()?.querySelectorAll('[data-ai-card]') ?? []).some((el) => {
        const progress = el.querySelector('[data-ai-card-progress]');
        return progress && !progress.hidden;
    });
}

async function pollOnce() {
    const statusUrl = grid()?.dataset.statusUrl;
    if (!statusUrl) {
        return;
    }
    try {
        const { cards } = await get(statusUrl, {}, { silent: true, toast: false });
        (cards ?? []).forEach(patchCard);
    } catch {
        /* silent — next tick retries */
    }
}

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

function startPolling() {
    if (pollTimer || document.visibilityState !== 'visible') {
        return;
    }
    pollTimer = setInterval(async () => {
        await pollOnce();
        if (!anyCardRunning()) {
            stopPolling();
        }
    }, POLL_INTERVAL);
}

function maybeStartPolling() {
    if (anyCardRunning() && document.visibilityState === 'visible') {
        startPolling();
    }
}

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        if (anyCardRunning()) {
            pollOnce().then(() => startPolling());
        }
    } else {
        stopPolling();
    }
});

// ── Card action button: free run confirm modal, or paid purchase modal ────
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-ai-card-action]');
    if (!button || button.disabled) {
        return;
    }

    const isFree = button.dataset.languageFree === '1';
    const languageId = button.dataset.languageId;

    if (isFree) {
        const modal = document.getElementById('ai-translation-run-modal');
        if (modal) {
            modal.querySelector('[data-ai-run-language-name]').textContent = button.dataset.languageName ?? '';
            modal.dataset.runUrl = button.dataset.runUrl;
        }
        openModal('ai-translation-run-modal');
        return;
    }

    const modal = document.getElementById('ai-translation-purchase-modal');
    if (!modal) {
        return;
    }

    const form = modal.querySelector('[data-tenant-form]');
    if (form) {
        form.action = form.action.replace(/\/\d+\/purchase$/, `/${languageId}/purchase`);
    }

    modal.querySelector('[data-ai-purchase-summary-name]').textContent = button.dataset.languageName ?? '';
    modal.querySelector('[data-ai-purchase-summary-code]').textContent = button.dataset.languageCode ?? '';
    modal.querySelector('[data-ai-purchase-summary-native]').textContent = `One-time payment · ${button.dataset.languageNative ?? ''}`;
    modal.querySelector('[data-ai-purchase-summary-price]').textContent = `$${button.dataset.languagePrice ?? '0.00'}`;

    openModal('ai-translation-purchase-modal');
});

document.getElementById('ai-run-confirm-btn')?.addEventListener('click', async () => {
    const modal = document.getElementById('ai-translation-run-modal');
    const runUrl = modal?.dataset.runUrl;
    if (!runUrl) {
        return;
    }

    try {
        await post(runUrl);
        closeModal('ai-translation-run-modal');
        await pollOnce();
        maybeStartPolling();
    } catch {
        /* handled by http interceptor toast */
    }
});

maybeStartPolling();
