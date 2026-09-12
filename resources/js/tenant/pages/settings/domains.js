// Domains settings page — the add/edit modals and delete action are fully
// declarative via x-tenant:: components. This entry only wires:
//   1. Patching the shared edit-domain-modal's validate URL per row (the
//      form is a page-level singleton reused by every domain request row).
//   2. The "Check DNS" toggle button, which fetches the DNS check result
//      and renders it into the row's result panel without a page reload.
import '../../../../css/tenant/pages/domains.css';
import { post } from '../../core/http.js';
import { toast } from '../../core/toast.js';

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-modal-open="edit-domain-modal"]');
    if (!opener) {
        return;
    }

    const modal = document.getElementById('edit-domain-modal');
    const form = modal?.querySelector('form');
    if (form) {
        form.dataset.validateUrl = opener.dataset.modalValidateUrl || '';
    }
});

function renderDnsPanel(panel, data) {
    const statusIcon = data.connected
        ? '<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" class="domains-dns-status-icon domains-dns-status-ok"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
        : '<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" class="domains-dns-status-icon domains-dns-status-warn"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

    const statusText = data.connected ? 'Domain is fully connected' : 'DNS not fully configured yet';
    const statusClass = data.connected ? 'domains-dns-status-ok' : 'domains-dns-status-warn';

    const checksHtml = data.checks.length
        ? data.checks.map((check) => {
            const okClass = check.ok ? 'domains-dns-check-ok' : 'domains-dns-check-fail';
            const icon = check.ok
                ? '<svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>'
                : '<svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

            return `<div class="domains-dns-check-row ${okClass}">`
                + `<span class="domains-dns-check-icon">${icon}</span>`
                + `<span class="domains-dns-check-type">${escapeHtml(check.type)}</span>`
                + `<span class="domains-dns-check-detail"><span class="domains-dns-check-name">${escapeHtml(check.name)}</span>`
                + ` <span class="domains-dns-check-arrow">&rarr;</span> <code>${escapeHtml(check.value)}</code></span>`
                + `<span class="domains-dns-check-result">${check.ok ? 'OK' : 'MISSING'}</span>`
                + '</div>';
        }).join('')
        : '<p class="domains-dns-empty">No DNS records defined to check against.</p>';

    const propagationNote = data.connected
        ? ''
        : '<p class="domains-dns-note">DNS propagation can take up to 48 hours. Check again after updating your records.</p>';

    panel.innerHTML = `<div class="domains-dns-box ${statusClass}">`
        + `<div class="domains-dns-head">${statusIcon}<span class="text-strong ${statusClass}">${statusText}</span></div>`
        + `<div class="domains-dns-checks">${checksHtml}</div>`
        + propagationNote
        + '</div>';
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-domain-check-dns]');
    if (!button) {
        return;
    }

    const row = button.closest('[data-domain-request]');
    const panel = row?.querySelector('[data-dns-panel]');
    if (!panel) {
        return;
    }

    if (!panel.hidden) {
        panel.hidden = true;
        panel.innerHTML = '';
        return;
    }

    button.disabled = true;
    try {
        const response = await post(button.dataset.checkDnsUrl, null, { toast: false });
        toast[response.toast_type || 'success'](response.message);
        renderDnsPanel(panel, response.data);
        panel.hidden = false;
    } finally {
        button.disabled = false;
    }
});
