// Buy Languages page — the payment modal is a page-level singleton
// (#buy-language-modal) shared by every row's "Buy Language" button. Each
// click fills the modal's hidden `language_id` field and summary text for
// the specific language before opening it.
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
