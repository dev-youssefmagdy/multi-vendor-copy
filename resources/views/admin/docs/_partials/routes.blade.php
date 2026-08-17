<h2>Routes</h2>

<h3>Files</h3>
<ul>
    <li><code>routes/central.php</code> — central marketing site + <code>/admin</code> panel. Loaded for the central
        domain.</li>
    <li><code>routes/tenant.php</code> — storefront + tenant <code>/admin</code> panel. Wrapped in
        <code>InitializeTenancyByDomain</code> + <code>PreventAccessFromCentralDomains</code>.</li>
    <li><code>routes/web.php</code> — fallback web routes (rarely used in this app).</li>
    <li><code>routes/console.php</code> — Artisan closure commands.</li>
</ul>

<h3>Central highlights</h3>
<table>
    <tr>
        <th>Path</th>
        <th>Component / Controller</th>
    </tr>
    <tr>
        <td><code>/</code> (and other marketing pages)</td>
        <td>Livewire components in <code>App\Livewire\Website\</code></td>
    </tr>
    <tr>
        <td><code>/admin</code></td>
        <td>Admin login → dashboard. Routes are grouped under
            <code>->middleware(['web','admin.auth'])-&gt;prefix('admin')-&gt;name('admin.')</code>.</td>
    </tr>
    <tr>
        <td><code>/admin/wallets</code></td>
        <td>Tenants Ledger page.</td>
    </tr>
    <tr>
        <td><code>/admin/finance/reports</code></td>
        <td>Cross-tenant finance reports (5 tabs).</td>
    </tr>
    <tr>
        <td><code>/admin/finance/payouts/create/&lbrace;tenant&rbrace;</code></td>
        <td>Record-only Central→Tenant payout form.</td>
    </tr>
    <tr>
        <td><code>/admin/vendor-settlements</code></td>
        <td>Tenant→Central settlement listing.</td>
    </tr>
    <tr>
        <td><code>/admin/dev-docs/&lbrace;topic?&rbrace;</code></td>
        <td>This in-app developer documentation.</td>
    </tr>
    <tr>
        <td><code>/register</code></td>
        <td>Tenant signup wizard.</td>
    </tr>
    <tr>
        <td><code>/register/payment/...</code></td>
        <td>Signup payment controller.</td>
    </tr>
</table>

<h3>Tenant highlights</h3>
<table>
    <tr>
        <th>Path</th>
        <th>Purpose</th>
    </tr>
    <tr>
        <td><code>/</code></td>
        <td>Theme homepage.</td>
    </tr>
    <tr>
        <td><code>/category/&lbrace;slug&rbrace;</code></td>
        <td>Theme category page.</td>
    </tr>
    <tr>
        <td><code>/product/&lbrace;slug&rbrace;</code></td>
        <td>Theme product page.</td>
    </tr>
    <tr>
        <td><code>/cart</code> / <code>/checkout</code></td>
        <td>Cart + checkout flow.</td>
    </tr>
    <tr>
        <td><code>/checkout/payment/...</code></td>
        <td>Tenant payment controller (charge/verify/cancel).</td>
    </tr>
    <tr>
        <td><code>/admin</code></td>
        <td>Tenant admin login + panel.</td>
    </tr>
    <tr>
        <td><code>/admin/finance/vendor-purchases</code></td>
        <td>Tenant lists paid orders with vendor_cost &gt; 0.</td>
    </tr>
    <tr>
        <td><code>/admin/finance/vendor-purchases/&lbrace;order&rbrace;/settle</code></td>
        <td>Settle a vendor cost via central gateway.</td>
    </tr>
    <tr>
        <td><code>/admin/finance/vendor-purchases/&lbrace;order&rbrace;/charge</code></td>
        <td>Vendor settlement charge controller.</td>
    </tr>
</table>

<h3>Middleware aliases</h3>
<ul>
    <li><code>admin.auth</code> — central admin auth guard.</li>
    <li><code>admin.permission:&lt;slug&gt;[,&lt;slug2&gt;]</code> — checks current admin's permissions.</li>
    <li><code>tenant.admin.auth</code> — tenant admin auth guard.</li>
    <li><code>tenant.admin.permission:&lt;slug&gt;</code> — tenant-side permission guard.</li>
</ul>
<p>Middleware aliases are registered in <code>bootstrap/app.php</code>.</p>