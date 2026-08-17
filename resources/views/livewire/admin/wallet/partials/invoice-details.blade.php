@php
    $invoice = $invoice ?? [];
    $meta = $invoice['meta'] ?? [];
    $financials = $meta['financials'] ?? [];
    $items = $meta['items'] ?? [];
@endphp

<div class="details-stack">
    <div class="details-grid">
        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">Invoice Snapshot</h4>
                <p class="panel-copy">Central invoice registry entry generated from a paid tenant storefront order.</p>
            </div>
            <div class="details-list">
                <div class="details-kv"><span class="details-label">Invoice</span><span
                        class="details-value">{{ $invoice['invoice_number'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Order</span><span
                        class="details-value">{{ $invoice['order_number'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Store</span><span
                        class="details-value">{{ $invoice['tenant_name'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Tenant Email</span><span
                        class="details-value">{{ $invoice['tenant_email'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Customer</span><span
                        class="details-value">{{ $invoice['customer_name'] ?? 'Guest' }}</span></div>
                <div class="details-kv"><span class="details-label">Customer Email</span><span
                        class="details-value">{{ $invoice['customer_email'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Status</span><span
                        class="details-value">{{ $invoice['status'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Amount</span><span
                        class="details-value">{{ $invoice['currency'] ?? 'USD' }}
                        {{ number_format((float) ($invoice['amount'] ?? 0), 2) }}</span></div>
                <div class="details-kv"><span class="details-label">Payment Method</span><span
                        class="details-value">{{ $invoice['payment_method'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Reference</span><span
                        class="details-value">{{ $invoice['payment_reference'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Issued At</span><span
                        class="details-value">{{ optional($invoice['issued_at'] ?? null)->format('M d, Y H:i') ?? '-' }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Paid At</span><span
                        class="details-value">{{ optional($invoice['paid_at'] ?? null)->format('M d, Y H:i') ?? '-' }}</span>
                </div>
            </div>

            @if (!empty($invoice['pdf_url']))
                <div class="page-actions compact-actions mt-4">
                    <a href="{{ $invoice['pdf_url'] }}" target="_blank" rel="noopener" class="btn btn-primary">Open PDF</a>
                </div>
            @endif
        </section>

        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">Financial Breakdown</h4>
                <p class="panel-copy">Stored financial snapshot from the originating paid order.</p>
            </div>
            <div class="details-list">
                <div class="details-kv"><span class="details-label">Subtotal</span><span
                        class="details-value">{{ $invoice['currency'] ?? 'USD' }}
                        {{ number_format((float) ($financials['subtotal'] ?? 0), 2) }}</span></div>
                <div class="details-kv"><span class="details-label">Discount</span><span
                        class="details-value">{{ $invoice['currency'] ?? 'USD' }}
                        {{ number_format((float) (($financials['items_discount'] ?? 0) + ($financials['discount'] ?? 0)), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Tax</span><span
                        class="details-value">{{ $invoice['currency'] ?? 'USD' }}
                        {{ number_format((float) (($financials['items_tax'] ?? 0) + ($financials['tax'] ?? 0)), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Shipping</span><span
                        class="details-value">{{ $invoice['currency'] ?? 'USD' }}
                        {{ number_format((float) ($financials['shipping_total'] ?? 0), 2) }}</span></div>
                <div class="details-kv"><span class="details-label">Owner Profit</span><span
                        class="details-value">{{ $invoice['currency'] ?? 'USD' }}
                        {{ number_format((float) ($financials['owner_profit'] ?? 0), 2) }}</span></div>
                <div class="details-kv"><span class="details-label">Tenant Net</span><span
                        class="details-value">{{ $invoice['currency'] ?? 'USD' }}
                        {{ number_format((float) ($financials['vendor_net_total'] ?? 0), 2) }}</span></div>
                <div class="details-kv"><span class="details-label">Grand Total</span><span
                        class="details-value">{{ $invoice['currency'] ?? 'USD' }}
                        {{ number_format((float) ($invoice['amount'] ?? 0), 2) }}</span></div>
                <div class="details-kv"><span class="details-label">Email Sent</span><span
                        class="details-value">{{ !empty($meta['email_sent_at']) ? 'Yes' : 'No' }}</span></div>
            </div>
        </section>

        <section class="details-panel full">
            <div class="details-header">
                <h4 class="panel-title">Line Items</h4>
                <p class="panel-copy">Item rows persisted in the central invoice snapshot.</p>
            </div>
            <div class="tw">
                <table class="details-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>
                                    <div class="details-inline-title">{{ $item['name'] ?? '-' }}</div>
                                    @if (!empty($item['variant']))
                                        <div class="details-inline-copy">{{ $item['variant'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $item['qty'] ?? 0 }}</td>
                                <td>{{ $invoice['currency'] ?? 'USD' }}
                                    {{ number_format((float) ($item['price'] ?? 0), 2) }}</td>
                                <td>{{ $invoice['currency'] ?? 'USD' }}
                                    {{ number_format((float) ($item['total'] ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="json-empty">No line items were stored on this invoice snapshot.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <x-card-collapse title="Shipping Address" subtitle="Structured shipping details persisted with the invoice."
        :start-open="true">
        <x-json-tree :data="$meta['shipping_address'] ?? []" :start-open="true" />
    </x-card-collapse>

    <x-card-collapse title="Payment Details" subtitle="Gateway response payload stored with the generated invoice."
        :start-open="true">
        <x-json-tree :data="$meta['payment_details'] ?? []" :start-open="true" />
    </x-card-collapse>
</div>
