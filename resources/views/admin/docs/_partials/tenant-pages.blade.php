@php
    use App\Helpers\TenantNavigation;
    $sections = collect(TenantNavigation::sections());
@endphp

<h2>Tenant Admin Pages</h2>
<p>The tenant admin is mounted at <code>/admin</code> on the tenant's own domain. Sidebar entries are generated from
    <code>App\Helpers\TenantNavigation::sections()</code>. Pages live under <code>app/Livewire/Tenant/</code>. Tenant DB
    is initialized automatically by the <code>InitializeTenancyByDomain</code> middleware.</p>

@foreach ($sections as $section)
    <h3>{{ $section['label'] ?? '—' }}</h3>
    <table>
        <tr>
            <th>Page</th>
            <th>Route</th>
            <th>Permission</th>
        </tr>
        @foreach ($section['items'] ?? [] as $item)
            @if (($item['type'] ?? 'link') === 'link')
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td><code>{{ $item['route'] }}</code></td>
                    <td><code>{{ $item['permission'] ?? '—' }}</code></td>
                </tr>
            @else
                <tr>
                    <td colspan="3"><strong>Group: {{ $item['label'] }}</strong></td>
                </tr>
                @foreach ($item['children'] ?? [] as $child)
                    <tr>
                        <td>↳ {{ $child['label'] }}</td>
                        <td><code>{{ $child['route'] }}</code></td>
                        <td><code>{{ $child['permission'] ?? '—' }}</code></td>
                    </tr>
                @endforeach
            @endif
        @endforeach
    </table>
@endforeach

<h3>Key tenant-side calculations</h3>
<ul>
    <li><strong>Cart subtotal</strong> =
        <code>items_grand_total = Σ item.grand_total</code>, where
        <code>item.grand_total = sub_total + fixed_shipping − discount + tax + shipping_fee</code> and
        <code>sub_total = price × qty</code>, <code>price = cost + profit_amount</code></li>
    <li><strong>Order grand_total</strong> =
        <code>items_grand_total + shipping_charge − coupon_discount</code></li>
    <li><strong>platform_profit</strong> = Σ (cost × qty + fixed_shipping − central-owned flash discount) + shipping_charge + tax
        − central-owned coupon discount, snapshotted on the order</li>
    <li><strong>vendor_profit</strong> = Σ (profit_amount × qty − vendor-owned flash discount) − vendor-owned coupon
        discount, snapshotted on the order (platform_profit + vendor_profit = grand_total)</li>
    <li><strong>vendor_gateway_fee</strong> = fixed + percentage of total due to the gateway, captured at settlement
        time</li>
    <li><strong>Vendor Purchase page</strong> (<code>/admin/finance/vendor-purchases</code>) lists all paid orders with
        vendor_own_platform > 0 and links to the dedicated settle page.</li>
</ul>