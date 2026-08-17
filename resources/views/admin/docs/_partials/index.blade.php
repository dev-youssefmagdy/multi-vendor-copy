<h2>Platform Overview</h2>
<p>
    This is a multi-vendor SaaS platform built on Laravel + Livewire + Stancl Tenancy.
    Every <strong>tenant</strong> gets its own database (and storefront/theme) and
    optionally its own domain. The <strong>central</strong> application hosts the
    public marketing site, signup/billing flow, and the central admin panel.
</p>

<h3>Where to start</h3>
<ul>
    <li><strong>Architecture & Stack</strong> — high-level technology map.</li>
    <li><strong>Multi-Tenancy</strong> — how tenants get their own DB and how to switch contexts.</li>
    <li><strong>Central Admin Pages</strong> — every page on <code>/admin/*</code>.</li>
    <li><strong>Tenant Admin Pages</strong> — every page on the tenant's own panel.</li>
    <li><strong>Finance & Calculations</strong> — exact formulas used across orders, settlements, payouts, ledger.</li>
</ul>

<h3>Conventions</h3>
<ul>
    <li>Livewire components for admin pages live under <code>app/Livewire/Admin/</code> and tenant panel under
        <code>app/Livewire/Tenant/</code>.</li>
    <li>List pages extend <code>App\Livewire\Admin\Base\ListPage</code> and return their data through
        <code>pageData()</code>.</li>
    <li>Permissions are slugs like <code>billing.payouts.manage</code>. They are listed in
        <code>app/Support/AdminPermissions.php</code>.</li>
    <li>Multi-tenant DB switching: <code>tenancy()->initialize($tenant); ... tenancy()->end();</code></li>
    <li>Central-only models use the <code>Stancl\Tenancy\Database\Concerns\CentralConnection</code> trait.</li>
</ul>

<blockquote>
    <strong>Tip:</strong> When writing new central queries that loop tenants, always wrap each iteration in a
    <code>try/finally</code> that calls <code>tenancy()->end()</code>. See
    <code>App\Services\Admin\TenantLedgerService</code>.
</blockquote>