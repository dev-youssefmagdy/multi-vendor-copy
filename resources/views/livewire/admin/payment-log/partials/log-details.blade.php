@php
    $log = $log ?? [];
    $order = $log['order'] ?? [];
    $financials = $order['financials'] ?? [];
@endphp

<div class="details-stack">
    <div class="details-grid">
        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">Payment Snapshot</h4>
                <p class="panel-copy">Gateway result captured from the tenant order callback or synchronous charge.</p>
            </div>
            <div class="details-list">
                <div class="details-kv"><span class="details-label">Store</span><span
                        class="details-value">{{ $log['tenant']['name'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Order</span><span
                        class="details-value">{{ $log['order_number'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Customer</span><span
                        class="details-value">{{ $log['customer_name'] ?? 'Guest' }}</span></div>
                <div class="details-kv"><span class="details-label">Gateway</span><span
                        class="details-value">{{ $log['gateway'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Reference</span><span
                        class="details-value">{{ $log['reference'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Status</span><span
                        class="details-value">{{ $log['status'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Amount</span><span
                        class="details-value">${{ number_format((float) ($log['amount'] ?? 0), 2) }}</span></div>
                <div class="details-kv"><span class="details-label">Paid At</span><span
                        class="details-value">{{ optional($log['paid_at'] ?? null)->format('M d, Y H:i') ?? '-' }}</span>
                </div>
            </div>
        </section>

        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">Linked Order Totals</h4>
                <p class="panel-copy">Order-side billing values associated with this payment entry.</p>
            </div>
            <div class="details-list">
                <div class="details-kv"><span class="details-label">Subtotal</span><span
                        class="details-value">${{ number_format((float) ($financials['subtotal'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Discount</span><span
                        class="details-value">${{ number_format((float) (($financials['items_discount'] ?? 0) + ($financials['discount'] ?? 0)), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Tax</span><span
                        class="details-value">${{ number_format((float) (($financials['items_tax'] ?? 0) + ($financials['tax'] ?? 0)), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Shipping</span><span
                        class="details-value">${{ number_format((float) ($financials['shipping_total'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Grand Total</span><span
                        class="details-value">${{ number_format((float) ($financials['grand_total'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Owner Profit</span><span
                        class="details-value">${{ number_format((float) ($financials['owner_profit'] ?? 0), 2) }}</span>
                </div>
            </div>
        </section>
    </div>

    <x-card-collapse title="Payment Payload" subtitle="Expandable nested payment details captured on the tenant order."
        :start-open="true">
        <x-json-tree :data="$log['meta'] ?? []" :start-open="true" />
    </x-card-collapse>

    @if ($order)
        <x-card-collapse title="Order Snapshot" subtitle="The payment log is linked back to the originating tenant order."
            :start-open="false">
            <div class="metric-list">
                <div class="metric-row"><span class="metric-label">Store</span><span
                        class="metric-value">{{ $order['tenant']['name'] ?? '-' }}</span></div>
                <div class="metric-row"><span class="metric-label">Customer</span><span
                        class="metric-value">{{ $order['customer']['name'] ?? 'Guest' }}</span></div>
                <div class="metric-row"><span class="metric-label">Payment Gateway</span><span
                        class="metric-value">{{ $order['payment_gateway'] ?? '-' }}</span></div>
                <div class="metric-row"><span class="metric-label">Status</span><span
                        class="metric-value">{{ $order['status'] ?? '-' }}</span></div>
                <div class="metric-row"><span class="metric-label">Shipping</span><span
                        class="metric-value">{{ $order['shipping_status'] ?? '-' }}</span></div>
            </div>
        </x-card-collapse>
    @endif
</div>
