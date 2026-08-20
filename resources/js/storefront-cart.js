import { initPhoneInputs } from "./phone-input";

function bootPhoneInputs() {
    initPhoneInputs(document);
}

// Exposed so theme blade views (e.g. auth.blade.php) can (re)init phone
// inputs from inline scripts after their own DOM/tab swaps, without waiting
// on the DOMContentLoaded/livewire:navigated hooks below.
window.initPhoneInputs = initPhoneInputs;
window.bootPhoneInputs = bootPhoneInputs;

document.addEventListener("DOMContentLoaded", bootPhoneInputs);
document.addEventListener("livewire:navigated", bootPhoneInputs);
document.addEventListener("livewire:init", () => {
    window.Livewire?.hook("morphed", ({ el }) => initPhoneInputs(el));
});

// Shared add-to-cart helper used by every storefront theme's product page.
// Posts directly to the cart-add endpoint (no Livewire round trip) and then
// re-uses the existing Livewire event bus so the cart badge/toast keep
// working exactly as they did when addToCart() lived in ProductPage.
window.storefrontCartAdd = function storefrontCartAdd({ url, slug, productId, variantId, qty }) {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            slug: slug ?? null,
            product_id: productId ?? null,
            variant_id: variantId ?? null,
            qty: qty ?? 1,
        }),
    })
        .then(async (res) => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Failed to add to cart');
            }
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('cartUpdated');
                Livewire.dispatch('storefront-cart-added', { itemName: data.itemName, qty: data.qty });
                Livewire.dispatch('storefront-shipping-updated', {
                    shippingThreshold: data.shippingThreshold,
                    cartWeightGrams: data.cartWeightGrams,
                    shippingPct: data.shippingPct,
                    remainingForFreeShipping: data.remainingForFreeShipping,
                });
            }
            if (typeof window.storefrontUpdateShippingProgress === 'function') {
                window.storefrontUpdateShippingProgress(data);
            }
            return data;
        })
        .catch((err) => {
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('storefront-toast', { message: err.message, type: 'error' });
            }
            throw err;
        });
};
