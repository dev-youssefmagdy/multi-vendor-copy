@extends('tenant.layouts.app')

@section('title', $order['uuid'] ?? 'Order Details')

@section('content')
    <x-tenant::page-header :title="$order['uuid'] ?? 'Order Details'" badge="Sales" description="Full order detail — financials, line items, shipping address, and gateway payload.">
        <x-slot:actions>
            <a class="btn btn-secondary" href="{{ route('tenant.orders.index') }}">← Orders</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <div class="page-stack section-gap">
        <div class="details-grid">
            <section class="details-panel">
                <div class="details-header">
                    <h4 class="panel-title">Order Snapshot</h4>
                    <p class="panel-copy">High-level tenant order identity, customer data, and payment routing.</p>
                </div>
                <div class="details-list">
                    <div class="details-kv"><span class="details-label">Order</span><span class="details-value">{{ $order['uuid'] ?? '-' }}</span></div>
                    <div class="details-kv"><span class="details-label">Status</span><span class="details-value">{{ $order['status'] ?? '-' }}</span></div>
                    <div class="details-kv"><span class="details-label">Paid</span><span class="details-value">{{ !empty($order['paid']) ? 'Yes' : 'No' }}</span></div>
                    <div class="details-kv"><span class="details-label">Customer</span><span class="details-value">{{ $order['customer']['name'] ?? 'Guest' }}</span></div>
                    <div class="details-kv"><span class="details-label">Email</span><span class="details-value">{{ $order['customer']['email'] ?? '-' }}</span></div>
                    <div class="details-kv"><span class="details-label">Gateway</span><span class="details-value">{{ $order['payment_gateway'] ?? ($order['payment_method'] ?? '-') }}</span></div>
                    <div class="details-kv"><span class="details-label">Placed At</span><span class="details-value">{{ optional($order['created_at'] ?? null)->format('M d, Y H:i') ?? '-' }}</span></div>
                    @if(!empty($order['carrier']))
                        <div class="details-kv"><span class="details-label">Carrier</span><span class="details-value">{{ $order['carrier'] }}</span></div>
                    @endif
                </div>
            </section>

            <section class="details-panel">
                <div class="details-header">
                    <h4 class="panel-title">Financial Breakdown</h4>
                    <p class="panel-copy">All tenant-facing totals are recalculated from order items plus order-level adjustments.</p>
                </div>
                @php($financials = $order['financials'] ?? [])
                <div class="details-list">
                    <div class="details-kv"><span class="details-label">Subtotal</span><span class="details-value">${{ number_format((float) ($financials['subtotal'] ?? 0), 2) }}</span></div>
                    <div class="details-kv"><span class="details-label">Item Discounts</span><span class="details-value">${{ number_format((float) ($financials['items_discount'] ?? 0), 2) }}</span></div>
                    <div class="details-kv"><span class="details-label">Order Discount</span><span class="details-value">${{ number_format((float) ($financials['discount'] ?? 0), 2) }}</span></div>
                    <div class="details-kv"><span class="details-label">Tax</span><span class="details-value">${{ number_format((float) (($financials['items_tax'] ?? 0) + ($financials['tax'] ?? 0)), 2) }}</span></div>
                    <div class="details-kv"><span class="details-label">Shipping</span><span class="details-value">${{ number_format((float) ($financials['shipping_total'] ?? 0), 2) }}</span></div>
                    <div class="details-kv"><span class="details-label">Grand Total</span><span class="details-value">${{ number_format((float) ($financials['grand_total'] ?? 0), 2) }}</span></div>
                    <div class="details-kv"><span class="details-label">Platform Commission</span><span class="details-value">${{ number_format((float) ($financials['owner_profit'] ?? 0), 2) }}</span></div>
                    <div class="details-kv"><span class="details-label">Vendor Commission</span><span class="details-value">${{ number_format((float) ($financials['vendor_net_total'] ?? 0), 2) }}</span></div>
                    @if(($order['gateway_source'] ?? 'vendor') === 'central')
                        <div class="details-kv">
                            <span class="details-label">CentralOwnTenant ←</span>
                            <span class="details-value details-value-positive">+${{ number_format((float) ($financials['remaining_tenant_profit'] ?? 0), 2) }}</span>
                        </div>
                        <div class="details-kv details-kv-full">
                            <span class="details-note">Central collected payment. Your profit will be paid out to you.
                                @if((float) ($financials['remaining_tenant_profit'] ?? 0) <= 0 && (float) ($financials['central_own_tenant'] ?? 0) > 0)
                                    Already released.
                                @endif
                            </span>
                        </div>
                    @else
                        <div class="details-kv">
                            <span class="details-label">TenantOwnCentral →</span>
                            <span class="details-value details-value-warning">${{ number_format((float) ($financials['remaining_owner_profit'] ?? 0), 2) }}</span>
                        </div>
                        <div class="details-kv details-kv-full">
                            <span class="details-note">
                                @if((float) ($financials['remaining_owner_profit'] ?? 0) > 0)
                                    You collected payment. Settle the catalog cost with central.
                                @else
                                    You collected payment. Catalog cost has been settled with central.
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </section>

            <section class="details-panel full">
                <div class="details-header">
                    <h4 class="panel-title">Line Items</h4>
                    <p class="panel-copy">Item-level quantity, pricing, tax, and shipping contributions for this order.</p>
                </div>
                <x-tenant::table :headers="['Item', 'Qty', 'Price', 'Discount', 'Tax', 'Shipping', 'Total']">
                    @forelse(($order['items'] ?? []) as $item)
                        <tr>
                            <td>
                                <div class="details-inline-title">{{ $item['name'] ?? '-' }}</div>
                                @if(!empty($item['variant']))
                                    <div class="details-inline-copy">{{ $item['variant'] }}</div>
                                @endif
                            </td>
                            <td>{{ $item['qty'] ?? 0 }}</td>
                            <td>${{ number_format((float) ($item['price'] ?? 0), 2) }}</td>
                            <td>${{ number_format((float) ($item['discount'] ?? 0), 2) }}</td>
                            <td>${{ number_format((float) ($item['tax'] ?? 0), 2) }}</td>
                            <td>${{ number_format((float) ($item['shipping'] ?? 0), 2) }}</td>
                            <td>${{ number_format((float) ($item['line_total'] ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="json-empty">No line items were found for this tenant order.</div>
                            </td>
                        </tr>
                    @endforelse
                </x-tenant::table>
            </section>
        </div>

        <section class="details-panel full">
            <div class="details-header">
                <h4 class="panel-title">Order Tracking</h4>
                <p class="panel-copy">Current shipping status and delivery information for this order.</p>
            </div>
            <div class="details-list">
                <div class="details-kv"><span class="details-label">Order Status</span><span class="details-value">{{ $order['status'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Shipping Status</span><span class="details-value">{{ $order['shipping_status'] ?? '-' }}</span></div>
                @if(!empty($order['carrier']))
                    <div class="details-kv"><span class="details-label">Carrier</span><span class="details-value">{{ $order['carrier'] }}</span></div>
                @endif
            </div>
        </section>

        <section class="details-panel full">
            <div class="details-header">
                <h4 class="panel-title">Shipping Address</h4>
                <p class="panel-copy">Expandable structured address payload captured at checkout.</p>
            </div>
            <x-tenant::json-tree :nodes="\App\Support\Tenant\JsonTreeBuilder::nodes($order['shipping_address'] ?? [])" />
        </section>

        <section class="details-panel full">
            <div class="details-header">
                <h4 class="panel-title">Payment Details</h4>
                <p class="panel-copy">Gateway response payload with nested JSON nodes kept expandable.</p>
            </div>
            <x-tenant::json-tree :nodes="\App\Support\Tenant\JsonTreeBuilder::nodes($order['payment_details'] ?? [])" />
        </section>

        @if(!empty($order['activities']))
            <section class="details-panel full">
                <div class="details-header">
                    <h4 class="panel-title">Activity</h4>
                    <p class="panel-copy">Recorded tenant order lifecycle events.</p>
                </div>
                <div class="details-list">
                    @foreach($order['activities'] as $activity)
                        <div class="activity-row">
                            <div class="activity-row-title">{{ $activity['title'] ?? '-' }}</div>
                            @if(!empty($activity['description']))
                                <div class="activity-row-copy">{{ $activity['description'] }}</div>
                            @endif
                            <div class="activity-row-date">{{ optional($activity['created_at'] ?? null)->format('M d, Y H:i') ?? '-' }}</div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @include('tenant.pages.sales.orders._partials.shipping-status', ['order' => $order, 'orderId' => $orderId, 'shippingStatuses' => $shippingStatuses ?? []])
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/order-show.js')
@endpush
