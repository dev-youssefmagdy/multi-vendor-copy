import { toast } from '../core/toast.js';
import { loadStripe, loadAcceptJs, load2Pay } from './payment-sdk-loader.js';

function panelsFor(root) {
    return Array.from(root.querySelectorAll('[data-payment-inline-panel]'));
}

function resetTokenFields(panel) {
    panel.querySelectorAll('[data-token-field]').forEach((input) => {
        input.value = '';
    });
}

function showError(root, id, message) {
    const el = root.querySelector(`#${CSS.escape(id)}`);
    if (el) {
        el.textContent = message;
        el.hidden = !message;
    }
}

function setupCardFormatting(root, formId) {
    const numInput = root.querySelector(`#sp-card-number-${formId}`);
    if (numInput && !numInput.dataset.fmt) {
        numInput.dataset.fmt = '1';
        numInput.addEventListener('input', () => {
            const value = numInput.value.replace(/\D/g, '').slice(0, 16);
            numInput.value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
        });
    }

    const expInput = root.querySelector(`#sp-card-expiry-${formId}`);
    if (expInput && !expInput.dataset.fmt) {
        expInput.dataset.fmt = '1';
        expInput.addEventListener('input', () => {
            let value = expInput.value.replace(/\D/g, '').slice(0, 4);
            if (value.length > 2) {
                value = `${value.slice(0, 2)} / ${value.slice(2)}`;
            }
            expInput.value = value;
        });
    }
}

/**
 * Wires a single x-tenant::payment.gateway-modal instance: gateway
 * selection, lazy SDK loading/mounting, and the card-tokenisation hook the
 * shared form engine runs before submit (see core/forms.js syncComponents).
 */
export function initPaymentModal(root) {
    const formId = root.dataset.modalId || root.closest('[data-payment-modal-root]')?.id;
    const modalRoot = root.closest('[data-payment-modal-root]') || root;
    const select = root.querySelector('[data-payment-gateway-select]');
    const readyMarker = root.querySelector('[data-payment-ready]');

    if (!select || !readyMarker) {
        return;
    }

    let stripe = null;
    let stripeCard = null;

    const sdkFlags = {
        stripe: modalRoot.dataset.sdkStripe === '1',
        authorizeNet: modalRoot.dataset.sdkAuthorizeNet || null,
        twoCheckout: modalRoot.dataset.sdk2checkout === '1',
    };

    async function mountStripe(panel) {
        if (stripe && stripeCard) {
            return;
        }

        const key = panel.dataset.stripeKey;
        if (!key) {
            return;
        }

        const Stripe = await loadStripe();
        if (!Stripe) {
            return;
        }

        const isDark = document.documentElement.dataset.theme === 'dark'
            || (!document.documentElement.dataset.theme && window.matchMedia('(prefers-color-scheme: dark)').matches);

        stripe = Stripe(key);
        const elements = stripe.elements();
        stripeCard = elements.create('card', {
            style: {
                base: {
                    fontSize: '13px',
                    color: isDark ? '#e5e7eb' : '#1f2937',
                    fontFamily: 'inherit',
                    backgroundColor: 'transparent',
                    '::placeholder': { color: isDark ? '#6b7280' : '#9ca3af' },
                    iconColor: isDark ? '#9ca3af' : '#6b7280',
                },
                invalid: { color: '#f87171' },
            },
            hidePostalCode: true,
        });
        stripeCard.mount(`#sp-stripe-card-element-${formId}`);
        stripeCard.on('change', (event) => {
            showError(root, `sp-stripe-card-errors-${formId}`, event.error?.message ?? '');
        });
    }

    async function activateGateway(code) {
        panelsFor(root).forEach((panel) => {
            const isActive = panel.dataset.gateway === code;
            panel.hidden = !isActive;
            if (!isActive) {
                resetTokenFields(panel);
            }
        });

        const panel = panelsFor(root).find((p) => p.dataset.gateway === code);
        if (!panel) {
            return;
        }

        if (code === 'stripe') {
            await mountStripe(panel);
        } else if (code === 'authorize_net') {
            await loadAcceptJs(sdkFlags.authorizeNet === 'sandbox');
            setupCardFormatting(root, formId);
        } else if (code === '2checkout') {
            await load2Pay();
            setupCardFormatting(root, formId);
        }
    }

    select.addEventListener('change', (event) => {
        const input = event.target.closest('input[type="radio"]');
        if (input) {
            activateGateway(input.value);
        }
    });

    const initiallyChecked = select.querySelector('input[type="radio"]:checked');
    if (initiallyChecked) {
        activateGateway(initiallyChecked.value);
    }

    async function tokeniseStripe(panel) {
        if (!stripe || !stripeCard) {
            toast.error('Stripe.js is still loading. Please try again in a moment.');
            return false;
        }

        const { token, error } = await stripe.createToken(stripeCard);
        if (error) {
            showError(root, `sp-stripe-card-errors-${formId}`, error.message);
            return false;
        }

        panel.querySelector('[data-token-field="stripe_token"]').value = token.id;
        return true;
    }

    async function tokeniseAuthorizeNet(panel) {
        const Accept = window.Accept;
        if (typeof Accept === 'undefined') {
            toast.error('Accept.js is still loading. Please try again.');
            return false;
        }

        const rawExpiry = root.querySelector(`#sp-card-expiry-${formId}`)?.value.replace(/\s/g, '') ?? '';
        const [month, year] = rawExpiry.split('/');

        const secureData = {
            authData: {
                apiLoginID: panel.dataset.authLogin,
                clientKey: panel.dataset.authClient,
            },
            cardData: {
                cardNumber: root.querySelector(`#sp-card-number-${formId}`)?.value.replace(/\s/g, '') ?? '',
                month: (month ?? '').trim(),
                year: `20${(year ?? '').trim()}`,
                cardCode: root.querySelector(`#sp-card-cvc-${formId}`)?.value ?? '',
            },
        };

        return new Promise((resolve) => {
            Accept.dispatchData(secureData, (response) => {
                if (response.messages.resultCode === 'Error') {
                    showError(root, `sp-card-errors-${formId}`, response.messages.message?.[0]?.text ?? 'Card error');
                    resolve(false);
                    return;
                }

                panel.querySelector('[data-token-field="authnet_desc"]').value = response.opaqueData.dataDescriptor;
                panel.querySelector('[data-token-field="authnet_value"]').value = response.opaqueData.dataValue;
                resolve(true);
            });
        });
    }

    async function tokenise2Checkout(panel) {
        const TCO = window.TCO;
        if (typeof TCO === 'undefined') {
            toast.error('2Pay.js is still loading. Please try again.');
            return false;
        }

        const rawExpiry = root.querySelector(`#sp-card-expiry-${formId}`)?.value.replace(/\s/g, '') ?? '';
        const [month, year] = rawExpiry.split('/');
        const sellerId = panel.dataset['2coSeller'];

        return new Promise((resolve) => {
            TCO.requestToken({
                sellerId,
                publishableKey: sellerId,
                ccNo: root.querySelector(`#sp-card-number-${formId}`)?.value.replace(/\s/g, '') ?? '',
                cvv: root.querySelector(`#sp-card-cvc-${formId}`)?.value ?? '',
                expMonth: (month ?? '').trim(),
                expYear: `20${(year ?? '').trim()}`,
            }, (data) => {
                if (data.errorCode > 0) {
                    showError(root, `sp-card-errors-${formId}`, data.errorMsg ?? 'Card error');
                    resolve(false);
                    return;
                }

                panel.querySelector('[data-token-field="twoco_token"]').value = data.token.token;
                resolve(true);
            });
        });
    }

    readyMarker._tenantBeforeSubmit = async () => {
        const checked = select.querySelector('input[type="radio"]:checked');
        const code = checked?.value;
        const panel = panelsFor(root).find((p) => p.dataset.gateway === code);

        if (!panel) {
            return true;
        }

        if (code === 'stripe') {
            return tokeniseStripe(panel);
        }
        if (code === 'authorize_net') {
            return tokeniseAuthorizeNet(panel);
        }
        if (code === '2checkout') {
            return tokenise2Checkout(panel);
        }

        return true;
    };
    readyMarker.dataset.tenantReady = '1';
}

export { initPaymentModal as init };
