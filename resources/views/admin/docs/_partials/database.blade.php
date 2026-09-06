<h2>Database</h2>

<h3>Connections</h3>
<ul>
    <li><strong>central</strong> (<code>config/database.php</code>): primary central DB. Models that should always read
        from central use the <code>Stancl\Tenancy\Database\Concerns\CentralConnection</code> trait.</li>
    <li><strong>tenant</strong>: connection swapped at runtime by <code>stancl/tenancy</code> middleware (or manually
        via <code>tenancy()->initialize($tenant)</code>).</li>
</ul>

<h3>Migrations</h3>
<ul>
    <li>Central migrations live in <code>database/migrations/</code> root.</li>
    <li>Tenant migrations live in <code>database/migrations/tenant/</code>. They are run for every tenant DB by
        stancl/tenancy.</li>
    <li>Run all central migrations with <code>php artisan migrate</code>.</li>
    <li>Run tenant migrations with <code>php artisan tenants:migrate</code> (or via the package's tenant lifecycle hooks
        on creation).</li>
</ul>

<h3>Key central tables</h3>
<table>
    <tr>
        <th>Table</th>
        <th>Description</th>
    </tr>
    <tr>
        <td><code>tenants</code></td>
        <td>Stancl tenant root record (id, data, created_at).</td>
    </tr>
    <tr>
        <td><code>domains</code></td>
        <td>Tenant domains.</td>
    </tr>
    <tr>
        <td><code>admin_users</code> / <code>admin_roles</code></td>
        <td>Central admins and their permission slugs.</td>
    </tr>
    <tr>
        <td><code>plans</code> / <code>subscriptions</code></td>
        <td>Tenant subscription plans and active subscriptions.</td>
    </tr>
    <tr>
        <td><code>currencies</code> / <code>languages</code></td>
        <td>Platform-wide currencies and languages.</td>
    </tr>
    <tr>
        <td><code>payment_gateways</code></td>
        <td>Both tenant and central gateway credentials (tenant_id NULL = central).</td>
    </tr>
    <tr>
        <td><code>vendor_settlements</code></td>
        <td>Tenant→Central settlement records.</td>
    </tr>
    <tr>
        <td><code>tenant_payouts</code></td>
        <td>Central→Tenant payout records (record-only).</td>
    </tr>
    <tr>
        <td><code>blog_posts</code> / <code>blog_categories</code></td>
        <td>Central marketing blog.</td>
    </tr>
    <tr>
        <td><code>pages</code> / <code>faqs</code></td>
        <td>Static central website content.</td>
    </tr>
    <tr>
        <td><code>language_purchases</code></td>
        <td>Tenant language pack purchases.</td>
    </tr>
    <tr>
        <td><code>email_templates</code> / <code>email_settings</code></td>
        <td>Central mail configuration.</td>
    </tr>
</table>

<h3>Key tenant tables</h3>
<table>
    <tr>
        <th>Table</th>
        <th>Description</th>
    </tr>
    <tr>
        <td><code>orders</code></td>
        <td>Customer orders with full financial breakdown (see Finance section).</td>
    </tr>
    <tr>
        <td><code>order_items</code></td>
        <td>Line items with price, qty, discount, tax, real_price.</td>
    </tr>
    <tr>
        <td><code>order_activities</code></td>
        <td>Audit log of status/payment/shipping transitions.</td>
    </tr>
    <tr>
        <td><code>products</code> / <code>categories</code> / <code>variations</code></td>
        <td>Tenant catalog (subset linked back to central catalog).</td>
    </tr>
    <tr>
        <td><code>customers</code></td>
        <td>Tenant customers (per-tenant scope).</td>
    </tr>
    <tr>
        <td><code>coupons</code> / <code>flash_sales</code></td>
        <td>Tenant promotions (some are mirrored from central).</td>
    </tr>
    <tr>
        <td><code>shipping_zones</code> / <code>shipping_rates</code></td>
        <td>Tenant shipping config.</td>
    </tr>
    <tr>
        <td><code>tenant_admin_users</code> / <code>tenant_admin_roles</code></td>
        <td>Tenant-side admins (separate from central <code>admin_users</code>).</td>
    </tr>
    <tr>
        <td><code>payment_gateways</code></td>
        <td>Tenant's own gateway credentials.</td>
    </tr>
</table>

<h3>Schema files</h3>
<p><code>database/schema/</code> contains squashed schema dumps used to bootstrap fresh installs. Do not edit by hand —
    regenerate via <code>php artisan schema:dump</code>.</p>