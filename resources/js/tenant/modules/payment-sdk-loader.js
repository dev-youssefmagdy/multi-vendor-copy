// PCI DSS and the providers' terms of service forbid self-hosting these
// tokenisation SDKs — card data must be sent directly from the browser to
// the gateway's own script, never proxied through our bundle. This is the
// single documented exception to the project's "no third-party scripts"
// rule (see GLOBAL INVARIANTS #6 in prompts/prompt_00_master.md). Each
// loader injects its `<script src>` at most once (memoised) and times out
// after 15s so a slow/blocked network doesn't hang the payment form forever.

const cache = new Map();

function loadScript(url, { timeout = 15000 } = {}) {
    if (cache.has(url)) {
        return cache.get(url);
    }

    const promise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = url;
        script.async = true;
        script.crossOrigin = 'anonymous';

        const timer = setTimeout(() => {
            reject(new Error(`Timed out loading ${url}`));
        }, timeout);

        script.addEventListener('load', () => {
            clearTimeout(timer);
            resolve();
        });
        script.addEventListener('error', () => {
            clearTimeout(timer);
            reject(new Error(`Failed to load ${url}`));
        });

        document.head.appendChild(script);
    });

    cache.set(url, promise);
    return promise;
}

export async function loadStripe() {
    await loadScript('https://js.stripe.com/v3/');
    return window.Stripe;
}

export async function loadAcceptJs(sandbox) {
    const url = sandbox ? 'https://jstest.authorize.net/v1/Accept.js' : 'https://js.authorize.net/v1/Accept.js';
    await loadScript(url);
    return window.Accept;
}

export async function load2Pay() {
    await loadScript('https://2pay-js.2checkout.com/v1/2pay.js');
    return window.TCO;
}
