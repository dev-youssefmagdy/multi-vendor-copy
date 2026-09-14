// Languages-manage settings page — the installed/available language tables
// and toggle/default actions are declarative via x-tenant:: components.
// This entry only wires the page-level "Buy Language" payment modal
// singleton, filled per-row from the clicked button's data-* attributes
// (mirrors resources/js/tenant/pages/finance/buy-languages.js exactly).
import '../../../../css/tenant/pages/languages-manage.css';
import { openModal } from '../../core/modals.js';

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-buy-language]');
    if (!button) {
        return;
    }

    const modal = document.getElementById('buy-language-modal');
    if (!modal) {
        return;
    }

    const nameEl = modal.querySelector('[data-buy-language-summary-name]');
    const codeEl = modal.querySelector('[data-buy-language-summary-code]');
    const priceEl = modal.querySelector('[data-buy-language-summary-price]');

    if (nameEl) {
        nameEl.textContent = button.dataset.languageName ?? '';
    }
    if (codeEl) {
        codeEl.textContent = button.dataset.languageCode ?? '';
    }
    if (priceEl) {
        priceEl.textContent = `$${button.dataset.languagePrice ?? '0.00'}`;
    }

    openModal('buy-language-modal', { fill: { language_id: button.dataset.languageId } });
});
