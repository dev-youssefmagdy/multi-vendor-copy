// Translations settings page.
//
// The keys table is a server-side datatable filtered by the shared
// filters-card (language_id, search, only_missing). Save / AI-translate are
// per-row actions wired here (not generic forms), and "Translate Store"
// polls a small status endpoint every 3s — only while the tab is visible and
// a translation job is actually running — patching the progress bar in
// place instead of reloading the page.
import { get, post, put } from '../../core/http.js';

const POLL_INTERVAL = 3000;

function toolbar() {
    return document.querySelector('[data-translations-toolbar]');
}

function currentLanguageId() {
    const select = document.getElementById('translations-language-select');
    return select?.value || toolbar()?.dataset.languageId || null;
}

function urlFor(templateAttr, languageId) {
    const template = toolbar()?.dataset[templateAttr];
    if (!template || !languageId) {
        return null;
    }
    return template.replace(/\/0(\/|$)/, `/${languageId}$1`);
}

// ── Per-row save ────────────────────────────────────────────────────────
document.addEventListener('click', async (event) => {
    const saveBtn = event.target.closest('[data-translation-save]');
    if (saveBtn) {
        const row = saveBtn.closest('[data-translation-key]');
        const key = row?.dataset.translationKey;
        const textarea = row?.querySelector('[data-translation-value]');
        const languageId = currentLanguageId();
        const url = urlFor('saveUrlTemplate', languageId);
        if (!key || !url) {
            return;
        }

        try {
            await put(url, { key, value: textarea?.value ?? '' });
        } catch {
            /* toast handled globally */
        }
        return;
    }

    const aiBtn = event.target.closest('[data-translation-ai]');
    if (aiBtn) {
        const key = aiBtn.dataset.translationKey;
        const languageId = currentLanguageId();
        const url = urlFor('aiUrlTemplate', languageId);
        if (!key || !url) {
            return;
        }

        aiBtn.disabled = true;
        try {
            const { data } = await post(url, { key });
            const row = document.querySelector(`[data-translation-key="${CSS.escape(key)}"] [data-translation-value]`);
            if (row && data?.value !== undefined) {
                row.value = data.value;
            }
        } catch {
            /* toast handled globally */
        } finally {
            aiBtn.disabled = false;
        }
    }
});

// ── Bulk "Translate Selected with AI" bar ──────────────────────────────
function selectedKeys() {
    return Array.from(document.querySelectorAll('#translations-table tbody input[data-translation-select]:checked')).map((cb) => cb.value);
}

function updateBulkBar() {
    const bar = document.querySelector('[data-translations-bulk-bar]');
    if (!bar) {
        return;
    }
    const keys = selectedKeys();
    bar.hidden = keys.length === 0;
    const countEl = bar.querySelector('[data-translations-bulk-count]');
    if (countEl) {
        countEl.textContent = `${keys.length} key(s) selected`;
    }
}

document.addEventListener('change', (event) => {
    if (event.target.matches('[data-translation-select]')) {
        updateBulkBar();
    }
});

document.addEventListener('tenant:table:drawn', (event) => {
    if (event.target.id === 'translations-table') {
        updateBulkBar();
    }
});

document.querySelector('[data-translations-bulk-ai]')?.addEventListener('click', async function () {
    const keys = selectedKeys();
    const languageId = currentLanguageId();
    const url = urlFor('aiBulkUrlTemplate', languageId);
    if (!url) {
        return;
    }

    this.disabled = true;
    try {
        await post(url, { keys });
        document.querySelectorAll('#translations-table tbody input[data-translation-select]:checked').forEach((cb) => {
            cb.checked = false;
        });
        updateBulkBar();
        document.getElementById('translations-table')?._dt?.ajax.reload(null, false);
    } catch {
        /* 422 "Select at least one key" surfaces via the global toast */
    } finally {
        this.disabled = false;
    }
});

// ── Translate Store + polling ──────────────────────────────────────────
let pollTimer = null;

function statusPanel() {
    return document.querySelector('[data-translations-status]');
}

function isRunning(status) {
    return status === 'queued' || status === 'running';
}

function patchStatus(data) {
    const panel = statusPanel();
    if (!panel) {
        return;
    }

    const running = isRunning(data.translation_status);
    panel.hidden = !data.translation_status;

    const wrap = panel.querySelector('[data-translations-progress-wrap]');
    const bar = panel.querySelector('[data-translations-progress-bar]');
    const label = panel.querySelector('[data-translations-progress-label]');
    const completedBadge = panel.querySelector('[data-translations-completed-badge]');
    const failedBadge = panel.querySelector('[data-translations-failed-badge]');

    if (wrap) {
        wrap.hidden = !running;
    }
    if (running && bar) {
        bar.style.width = `${data.translation_progress ?? 0}%`;
    }
    if (running && label) {
        const status = data.translation_status;
        label.textContent = `${status[0].toUpperCase()}${status.slice(1)} — ${data.translation_progress ?? 0}%`;
    }

    if (completedBadge) {
        completedBadge.hidden = data.translation_status !== 'completed';
        if (data.translation_status === 'completed') {
            completedBadge.textContent = `Completed — ${data.items_translated ?? 0} items translated`;
        }
    }
    if (failedBadge) {
        failedBadge.hidden = data.translation_status !== 'failed';
    }
}

async function pollOnce() {
    const languageId = currentLanguageId();
    const url = urlFor('statusUrlTemplate', languageId);
    if (!url) {
        return null;
    }
    try {
        const data = await get(url, {}, { silent: true, toast: false });
        patchStatus(data);
        return data;
    } catch {
        return null;
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
        const data = await pollOnce();
        if (!data || !isRunning(data.translation_status)) {
            stopPolling();
        }
    }, POLL_INTERVAL);
}

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        pollOnce().then((data) => {
            if (data && isRunning(data.translation_status)) {
                startPolling();
            }
        });
    } else {
        stopPolling();
    }
});

document.getElementById('translations-language-select')?.addEventListener('change', () => {
    stopPolling();
    pollOnce().then((data) => {
        if (data && isRunning(data.translation_status)) {
            startPolling();
        }
    });
});

document.querySelector('[data-translate-store]')?.addEventListener('click', async function () {
    const languageId = currentLanguageId();
    const url = urlFor('storeAiUrlTemplate', languageId);
    if (!url) {
        return;
    }

    this.disabled = true;
    try {
        await post(url);
        await pollOnce();
        startPolling();
    } catch {
        /* toast handled globally */
    } finally {
        this.disabled = false;
    }
});

pollOnce().then((data) => {
    if (data && isRunning(data.translation_status)) {
        startPolling();
    }
});
