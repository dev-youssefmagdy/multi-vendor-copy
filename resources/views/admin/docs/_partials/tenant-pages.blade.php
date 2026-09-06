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
        <code>Σ (item.price × qty − item.discount + item.tax + item.shipping_fee)</code></li>
    <li><strong>Order grand_total</strong> =
        <code>subtotal − items_discount − discount_amount + items_tax + tax_amount + shipping_charge</code></li>
    <li><strong>owner_profit</strong> = central commission, snapshotted on the order from product/plan rules</li>
    <li><strong>vendor_cost</strong> = <code>Σ items.real_price × qty</code> at order time (price tenant owes central)
    </li>
    <li><strong>vendor_gateway_fee</strong> = fixed + percentage of total due to the gateway, captured at settlement
        time</li>
    <li><strong>Vendor Purchase page</strong> (<code>/admin/finance/vendor-purchases</code>) lists all paid orders with
        vendor_cost > 0 and links to the dedicated settle page.</li>
</ul>