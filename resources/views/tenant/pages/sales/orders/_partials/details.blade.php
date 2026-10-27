{{-- Shared order-details body: snapshot, financials, line items, tracking, address, payment payload, activity.
     Reused by Sales > Orders show and Finance > Billing show — keep both callers in sync when editing. --}}
@php
    $financials = $order['financials'] ?? [];
@endphp
<div class="details-grid">
    <section class="details-panel">
        <div class="details-header">
            <h4 class="panel-title">Order Snapshot</h4>
            <p class="panel-copy">High-level tenant order identity, customer data, and payment routing.</p>
        </div>
        <div class="details-list">
            <div class="details-kv"><span class="details-label">Order</span><span class="details-value">{{ $order['uuid'] ?? '-' }}</span></div>
            <div class="details-kv"><span class="details-label">Status</span><span class="details-value">{{ $order['status'] ?? '-' }}</span></div>
            <div class="details-kv">
                <span class="details-label">Paid</span>
                <span class="details-value">
                    {{ !empty($order['paid']) ? 'Yes' : 'No' }}
                    @if(in_array($order['payment_state'] ?? null, ['refunded', 'partially_refunded'], true))
                        <span class="badge badge-{{ $order['payment_state_color'] ?? 'violet' }}" data-payment-state><span class="badge-dot"></span>{{ $order['payment_state_label'] }}</span>
                    @endif
                </span>
            </div>
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
            <p class="panel-copy">Totals follow the order formulas: items grand total + shipping - coupon = order grand total.</p>
        </div>
        @php $money = fn ($key) => '$'.number_format((float) ($financials[$key] ?? 0), 2); @endphp
        <div class="details-list">
            <div class="details-kv"><span class="details-label">Items Grand Total</span><span class="details-value">{{ $money('items_grand_total') }}</span></div>
            <div class="details-kv"><span class="details-label">Shipping Charge</span><span class="details-value">+ {{ $money('shipping_charge') }}</span></div>
            <div class="details-kv"><span class="details-label">Coupon Discount</span><span class="details-value">- {{ $money('coupon_discount') }}</span></div>
            <div class="details-kv"><span class="details-label">Order Grand Total</span><span class="details-value"><strong>{{ $money('grand_total') }}</strong></span></div>
            <div class="details-kv"><span class="details-label">Platform Profit</span><span class="details-value">{{ $money('platform_profit') }}</span></div>
            <div class="details-kv"><span class="details-label">Vendor Profit</span><span class="details-value">{{ $money('vendor_profit') }}</span></div>
            @if(($financials['collected_by'] ?? 'vendor') === 'vendor')
                <div class="details-kv"><span class="details-label">Settlements Paid to Platform</span><span class="details-value">{{ $money('vendor_settlements_paid') }}</span></div>
                <div class="details-kv">
                    <span class="details-label">Vendor Owes Platform</span>
                    <span class="details-value details-value-warning">{{ $money('vendor_own_platform') }}
                        @if((float) ($financials['vendor_own_platform'] ?? 0) > 0 && !empty($order['id']))
                            <a href="{{ route('tenant.finance.vendor-purchase-settle', $order['id']) }}" class="btn btn-primary btn-sm">Pay platform</a>
                        @endif
                    </span>
                </div>
                <div class="details-kv details-kv-full">
                    <span class="details-note">
                        @if((float) ($financials['vendor_own_platform'] ?? 0) > 0)
                            You collected payment. Pay the platform its share.
                        @else
                            You collected payment. The platform share has been settled.
                        @endif
                    </span>
                </div>
            @else
                <div class="details-kv"><span class="details-label">Payouts Received from Platform</span><span class="details-value">{{ $money('platform_payouts_paid') }}</span></div>
                <div class="details-kv">
                    <span class="details-label">Platform Owes Vendor</span>
                    <span class="details-value details-value-positive">{{ $money('platform_own_vendor') }}</span>
                </div>
                <div class="details-kv details-kv-full">
                    <span class="details-note">
                        @if((float) ($financials['platform_own_vendor'] ?? 0) > 0)
                            The platform collected payment. Your profit will be paid out to you.
                        @else
                            The platform collected payment. Your profit has been paid out.
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </section>

    <section class="details-panel full">
        <div class="details-header">
            <h4 class="panel-title">Line Items</h4>
            <p class="panel-copy">Item-level cost, profit, pricing, shipping, tax, and totals for this order.</p>
        </div>
        <x-tenant::table :headers="['Item', 'Qty', 'Cost', 'Profit', 'Price', 'Sub Total', 'Fixed Shipping', 'Discount', 'Tax', 'Shipping Fee', 'Weight', 'Grand Total']">
            @forelse(($order['items'] ?? []) as $item)
                <tr>
                    <td>
                        <div class="details-inline-title">{{ $item['name'] ?? '-' }}</div>
                        @if(!empty($item['variant']))
                            <div class="details-inline-copy">{{ $item['variant'] }}</div>
                        @endif
                    </td>
                    <td>{{ $item['qty'] ?? 0 }}</td>
                    <td>${{ number_format((float) ($item['cost'] ?? 0), 2) }}</td>
                    <td>${{ number_format((float) ($item['profit_amount'] ?? 0), 2) }}</td>
                    <td>${{ number_format((float) ($item['price'] ?? 0), 2) }}</td>
                    <td>${{ number_format((float) ($item['sub_total'] ?? 0), 2) }}</td>
                    <td>${{ number_format((float) ($item['fixed_shipping'] ?? 0), 2) }}</td>
                    <td>${{ number_format((float) ($item['discount'] ?? 0), 2) }}</td>
                    <td>${{ number_format((float) ($item['tax'] ?? 0), 2) }}</td>
                    <td>${{ number_format((float) ($item['shipping_fee'] ?? 0), 2) }}</td>
                    <td>{{ rtrim(rtrim(number_format((float) ($item['weight'] ?? 0), 2), '0'), '.') }}</td>
                    <td>${{ number_format((float) ($item['grand_total'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12">
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
    @php
        $shippingAddr = $order['shipping_address'] ?? [];
        if (isset($shippingAddr['country_id'])) {
            $countryName = \App\Models\Country::find((int) $shippingAddr['country_id'])?->name;
            $shippingAddr = collect($shippingAddr)
                ->mapWithKeys(fn ($v, $k) => $k === 'country_id' ? ['country' => $countryName ?? $v] : [$k => $v])
                ->all();
        }
    @endphp
    <x-tenant::json-tree :nodes="\App\Support\Tenant\JsonTreeBuilder::nodes($shippingAddr)" :show-types="false" />
</section>

@php
    $payment = app(\App\Support\Tenant\Payments\PaymentDetailsPresenter::class)
        ->present($order['payment_details'] ?? [], $order['payment_method'] ?? null);
@endphp
<section class="details-panel full">
    <div class="details-header">
        <h4 class="panel-title">Payment Details</h4>
        <p class="panel-copy">Key payment information reported by the payment gateway.</p>
    </div>
    @if(!$payment['has_data'])
        <div class="json-empty">No payment details were recorded for this order.</div>
    @else
        <div class="details-list">
            <div class="details-kv">
                <span class="details-label">Gateway</span>
                <span class="details-value">{{ $payment['gateway_label'] }}</span>
            </div>
            @if($payment['status'])
                <div class="details-kv">
                    <span class="details-label">Gateway status</span>
                    <span class="pay-status pay-status-{{ $payment['status']['tone'] }}">{{ $payment['status']['label'] }}</span>
                </div>
            @endif
            @foreach($payment['headline'] as $row)
                @continue(strtolower($row['label'] ?? '') === 'card')
                <div class="details-kv">
                    <span class="details-label">{{ $row['label'] }}</span>
                    @if(!empty($row['href']))
                        <a class="details-value" href="{{ $row['href'] }}" target="_blank" rel="noopener noreferrer">{{ $row['value'] }}</a>
                    @else
                        <span class="details-value {{ !empty($row['mono']) ? 'pay-mono' : '' }}">{{ $row['value'] }}</span>
                    @endif
                </div>
            @endforeach
            @if($payment['note'])
                <div class="details-kv">
                    <span class="details-label">Note</span>
                    <span class="details-value">{{ $payment['note'] }}</span>
                </div>
            @endif
        </div>

        @if(!empty($payment['raw']))
            <details class="json-collapse">
                <summary class="json-collapse-summary">
                    <div>
                        <div class="details-inline-title">Full gateway response</div>
                        <div class="details-inline-copy">Technical payload for support and reconciliation (sensitive values masked).</div>
                    </div>
                    <span class="json-collapse-badge">Expand</span>
                </summary>
                <div class="json-collapse-body">
                    <x-tenant::json-tree :nodes="\App\Support\Tenant\JsonTreeBuilder::nodes($payment['raw'])" :open-depth="0" />
                </div>
            </details>
        @endif
    @endif
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
