<h2>Building a Custom Template</h2>
<p>
    A custom template is a self-contained static website — plain HTML, CSS, and JS —
    packaged as a ZIP. It completely replaces the system theme on your storefront.
    Your customers see your custom HTML; the platform handles cart, checkout, orders,
    and payments through injected snippets.
</p>

{{-- ── Required ZIP structure ─────────────────────────────────── --}}
<h3>Required ZIP structure</h3>
<div class="docs-code-block">
<pre>my-template.zip
├── index.html          ← required, must be at the root
├── css/
│   └── style.css
├── js/
│   └── app.js
└── images/
    ├── logo.png
    └── banner.jpg</pre>
</div>
<ul>
    <li><code>index.html</code> at the root is <strong>required</strong> — it is the entry point for every page</li>
    <li>Only <code>css/</code>, <code>js/</code>, <code>images/</code>, and <code>assets/</code> folders are allowed</li>
    <li>No PHP, no executable files, no server config files</li>
    <li>Maximum ZIP size: <strong>20 MB</strong></li>
</ul>

{{-- ── Referencing assets ──────────────────────────────────────── --}}
<h3>Referencing your CSS, JS, and images</h3>
<p>Use <strong>relative paths</strong> from the allowed folders. The platform rewrites them automatically when serving your template:</p>
<div class="docs-code-block">
<pre>&lt;!-- CSS --&gt;
&lt;link rel="stylesheet" href="css/style.css"&gt;

&lt;!-- JavaScript --&gt;
&lt;script src="js/app.js" defer&gt;&lt;/script&gt;

&lt;!-- Images --&gt;
&lt;img src="images/logo.png" alt="Logo"&gt;
&lt;img src="assets/hero.webp" alt="Hero"&gt;</pre>
</div>
<p>Do <strong>not</strong> use absolute paths like <code>/css/style.css</code> — these will not work.</p>

{{-- ── Platform variables ──────────────────────────────────────── --}}
<h3>Platform variables available in your template</h3>
<p>
    The platform injects a <code>&lt;script&gt;</code> block into your <code>&lt;head&gt;</code>
    before serving your template. These JavaScript variables are always available:
</p>
<div class="docs-code-block">
<pre>window.__store = {
    name:        "Your Store Name",
    logo:        "https://cdn.../logo.png",   // or null
    currency:    "USD",
    currencySymbol: "$",
    locale:      "en",
    dir:         "ltr",                       // or "rtl"
    cartCount:   3,
    customerId:  null,                        // null if not logged in
    csrfToken:   "abc123...",
};</pre>
</div>
<p>Use these in your JS to personalise the template:</p>
<div class="docs-code-block">
<pre>document.querySelector('.store-name').textContent = window.__store.name;
document.querySelector('.cart-count').textContent = window.__store.cartCount;</pre>
</div>

{{-- ── Tracking pixels ─────────────────────────────────────────── --}}
<h3>Pixel tracking in your template</h3>
<p>
    Your configured pixels (Facebook, TikTok, Snapchat, GA4) are injected automatically
    into <code>&lt;head&gt;</code> before your template loads. The same tracking helper
    functions are available:
</p>
<div class="docs-code-block">
<pre>// Call these from your JS:
window.trackViewContent({ content_ids: ['123'], value: 29.99, currency: 'USD' });
window.trackAddToCart({ content_ids: ['123'], value: 29.99, currency: 'USD' });
window.trackInitiateCheckout({ value: 59.98, currency: 'USD' });
window.trackPurchase({ content_ids: ['123'], value: 29.99, currency: 'USD', order_id: 'ORD-001' });</pre>
</div>

{{-- ── Minimal working example ────────────────────────────────── --}}
<h3>Minimal working <code>index.html</code></h3>
<div class="docs-code-block">
<pre>&lt;!DOCTYPE html&gt;
&lt;html lang="en"&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8"&gt;
    &lt;meta name="viewport" content="width=device-width, initial-scale=1"&gt;
    &lt;title id="page-title"&gt;My Store&lt;/title&gt;

    &lt;!-- Your CSS --&gt;
    &lt;link rel="stylesheet" href="css/style.css"&gt;
&lt;/head&gt;
&lt;body&gt;

    &lt;!-- Header --&gt;
    &lt;header&gt;
        &lt;img id="store-logo" src="" alt="" style="height:48px;display:none"&gt;
        &lt;span id="store-name"&gt;&lt;/span&gt;
        &lt;span&gt;Cart: &lt;span id="cart-count"&gt;0&lt;/span&gt;&lt;/span&gt;
    &lt;/header&gt;

    &lt;!-- Main content --&gt;
    &lt;main&gt;
        &lt;h1&gt;Welcome to our store&lt;/h1&gt;
        &lt;p&gt;Explore our products below.&lt;/p&gt;
    &lt;/main&gt;

    &lt;!-- Footer --&gt;
    &lt;footer&gt;
        &lt;p id="footer-name"&gt;&lt;/p&gt;
    &lt;/footer&gt;

    &lt;!-- Your JS --&gt;
    &lt;script src="js/app.js" defer&gt;&lt;/script&gt;
&lt;/body&gt;
&lt;/html&gt;</pre>
</div>

<div class="docs-code-block">
<pre>// js/app.js — runs after DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    var s = window.__store || {};

    if (s.name) {
        document.title = s.name;
        document.getElementById('page-title').textContent = s.name;
        document.getElementById('store-name').textContent = s.name;
        document.getElementById('footer-name').textContent = '© ' + s.name;
    }

    if (s.logo) {
        var logoEl = document.getElementById('store-logo');
        logoEl.src = s.logo;
        logoEl.alt = s.name;
        logoEl.style.display = 'block';
    }

    document.getElementById('cart-count').textContent = s.cartCount || 0;
});</pre>
</div>

{{-- ── What you cannot do ──────────────────────────────────────── --}}
<h3>Limitations</h3>
<ul>
    <li>
        <strong>No server-side code</strong> — your template is pure static HTML/CSS/JS.
        You cannot use PHP, server includes, or dynamic templating languages.
    </li>
    <li>
        <strong>No external fetch to your own backend</strong> — you cannot make API calls
        to other servers. Assets must be bundled inside the ZIP or referenced from a CDN
        using absolute URLs (e.g. <code>https://cdn.jsdelivr.net/...</code>).
    </li>
    <li>
        <strong>CDN assets are fine</strong> — you may reference fonts, icon libraries,
        or JS frameworks from public CDNs.
    </li>
    <li>
        <strong>Admin review required</strong> — every upload goes through a security scan
        before you can activate it. This typically takes less than 24 hours.
    </li>
</ul>

{{-- ── Tips ────────────────────────────────────────────────────── --}}
<h3>Tips for a great template</h3>
<ul>
    <li>Test your template locally first by opening <code>index.html</code> in a browser before zipping</li>
    <li>Inline critical CSS in <code>&lt;style&gt;</code> tags for faster first paint</li>
    <li>Use <code>defer</code> on all <code>&lt;script&gt;</code> tags</li>
    <li>Optimise images — compress PNGs and use WEBP where possible</li>
    <li>Keep the total ZIP under 5 MB for fast upload and review</li>
    <li>Test on mobile — your storefront customers are mostly on phones</li>
</ul>

<div style="margin-top:24px;padding:16px;background:rgba(99,102,241,0.08);border-radius:12px;border:1px solid rgba(99,102,241,0.2)">
    <strong style="color:var(--t1);font-size:14px">Ready to upload?</strong>
    <p style="margin:6px 0 0;font-size:13px;color:var(--t2)">
        Go to <strong>Store → Custom Template</strong>, upload your ZIP, and preview it
        before submitting for admin review.
    </p>
</div>
