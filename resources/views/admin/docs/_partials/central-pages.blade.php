@php
    use App\Helpers\AdminNavigation;
    $sections = collect(AdminNavigation::sections());

    $purposes = [
        'admin.dashboard' => 'Top-level KPIs (orders, tenants, revenue snapshot).',
        'admin.catalogs.index' => 'Central product catalogs (groups of products shared with tenants).',
        'admin.products.index' => 'Central product master list.',
        'admin.categories.index' => 'Central category tree.',
        'admin.variations.index' => 'Central variant attributes (size/color/etc).',
        'admin.badges.new-in' => 'Tag products as <em>New In</em>.',
        'admin.badges.best-selling' => 'Tag products as <em>Best Selling</em>.',
        'admin.orders.index' => 'Cross-tenant orders list (read via tenancy iteration).',
        'admin.orders.report' => 'Daily order/revenue report.',
        'admin.shipping.in-delivery' => 'Orders currently in delivery (cross-tenant).',
        'admin.shipping.delivery-charges' => 'Shipping zones & rates (defaults available to tenants).',
        'admin.branches.index' => 'Branches/warehouses for central-managed inventory.',
        'admin.shipping.settings' => 'Global shipping defaults.',
        'admin.store.coupons.index' => 'Central-managed coupons applicable to tenants.',
        'admin.store.flash-sales.index' => 'Central-managed flash sales.',
        'admin.manufacturing.index' => 'Manufacturing requests pipeline.',
        'admin.payment-logs.index' => 'All gateway transactions across tenants for audit.',
        'admin.plans.users' => 'Tenant accounts (signup, plan, status).',
        'admin.plans.index' => 'Subscription packages.',
        'admin.wallets.index' => '<strong>Tenants Ledger</strong> — order-driven balances; see Finance.',
        'admin.invoices.index' => 'Central invoices (plans, language purchases, settlements).',
        'admin.vendor-settlements.index' => 'Tenant→Central settlement records.',
        'admin.finance.reports' => 'Cross-tenant finance reports & breakdowns.',
        'admin.finance.payouts.create' => 'Record-only Central→Tenant payout.',
        'admin.blog.categories' => 'Blog post categories.',
        'admin.blog.posts' => 'Blog posts on the central marketing website.',
        'admin.domains.requests' => 'Custom-domain requests from tenants.',
        'admin.domains.dns-records' => 'DNS records vendors must set for custom domains.',
        'admin.pages.index' => 'Static legal/marketing pages.',
        'admin.faqs.index' => 'FAQ entries on the central website.',
        'admin.settings.general' => 'Brand name/logo/contact info.',
        'admin.settings.templates' => 'Available storefront themes & defaults.',
        'admin.settings.payment-gateways' => 'Central gateway credentials & toggles.',
        'admin.settings.payment-gateway-limits' => 'Gateway transaction limits/restrictions.',
        'admin.settings.email-templates' => 'Reusable email templates with variable placeholders.',
        'admin.settings.email-configuration' => 'SMTP/mailer configuration.',
        'admin.settings.currencies' => 'Currencies + FX rates.',
        'admin.settings.languages' => 'Languages enabled platform-wide.',
        'admin.settings.language-purchases' => 'Language pack purchases by tenants.',
        'admin.settings.translations' => 'Edit translation files.',
        'admin.settings.maintenance' => 'Toggle maintenance mode platform-wide.',
        'admin.admins.index' => 'Central admin user accounts.',
        'admin.admins.roles-permissions' => 'Roles + per-role permission slugs.',
        'admin.cache.tenants' => 'Clear caches for selected tenants.',
        'admin.cache.main' => 'Clear central caches.',
    ];
@endphp

<h2>Central Admin Pages</h2>
<p>The central admin is mounted at <code>/admin</code>. The sidebar is generated from
    <code>App\Helpers\AdminNavigation::sections()</code>. Each entry maps to a Livewire page under
    <code>app/Livewire/Admin/</code> and a route in <code>routes/central.php</code>.</p>

@foreach ($sections as $section)
    <h3>{{ $section['label'] }}</h3>
    <table>
        <tr>
            <th>Page</th>
            <th>Route</th>
            <th>Permission</th>
            <th>Purpose</th>
        </tr>
        @foreach ($section['items'] as $item)
            @if (($item['type'] ?? 'link') === 'link')
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td><code>{{ $item['route'] }}</code></td>
                    <td><code>{{ $item['permission'] ?? '—' }}</code></td>
                    <td>{!! $purposes[$item['route']] ?? '—' !!}</td>
                </tr>
            @else
                <tr>
                    <td colspan="4"><strong>Group: {{ $item['label'] }}</strong></td>
                </tr>
                @foreach ($item['children'] as $child)
                    <tr>
                        <td>↳ {{ $child['label'] }}</td>
                        <td><code>{{ $child['route'] }}</code></td>
                        <td><code>{{ $child['permission'] ?? '—' }}</code></td>
                        <td>{!! $purposes[$child['route']] ?? '—' !!}</td>
                    </tr>
                @endforeach
            @endif
        @endforeach
    </table>
@endforeach

<h3>Calculations performed in admin pages</h3>
<ul>
    <li><strong>Tenants Ledger</strong> (<code>/admin/wallets</code>): see <em>Finance & Calculations</em>.</li>
    <li><strong>Orders Report</strong>: aggregates per-day grand_total and counts. Owner profit uses the
        effective owner profit (vendor_cost when a vendor gateway was used, otherwise owner_profit), not the
        raw owner_profit column. Source: <code>App\Livewire\Admin\Order\OrdersReportPage</code> via
        <code>App\Support\OrderProfitCalculator</code>.</li>
    <li><strong>Plans / Registered Users</strong>: subscription revenue = <code>Σ subscriptions.price</code> for active
        plans across tenants.</li>
    <li><strong>Invoices</strong>: shows central-side invoices issued to tenants for plans, language purchases, and
        vendor settlements.</li>
    <li><strong>Payment Logs</strong>: maps order payment status onto an enum and surfaces the gateway/transaction
        reference for audit.</li>
</ul>