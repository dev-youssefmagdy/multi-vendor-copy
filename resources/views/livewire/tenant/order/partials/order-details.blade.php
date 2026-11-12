@php
    $order = $order ?? [];
    $financials = $order['financials'] ?? [];
    $items = $order['items'] ?? [];
    $activities = $order['activities'] ?? [];
@endphp

<div class="details-stack">
    <div class="details-grid">
        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">{{ __('Order Snapshot') }}</h4>
                <p class="panel-copy">{{ __('High-level tenant order identity, customer data, and payment routing.') }}</p>
            </div>
            <div class="details-list">
                <div class="details-kv"><span class="details-label">{{ __('Order') }}</span><span
                        class="details-value">{{ $order['uuid'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">{{ __('Status') }}</span><span
                        class="details-value">{{ $order['status'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">{{ __('Paid') }}</span><span
                        class="details-value">{{ !empty($order['paid']) ? __('Yes') : __('No') }}</span></div>
                <div class="details-kv"><span class="details-label">{{ __('Customer') }}</span><span
                        class="details-value">{{ $order['customer']['name'] ?? __('Guest') }}</span></div>
                <div class="details-kv"><span class="details-label">{{ __('Email') }}</span><span
                        class="details-value">{{ $order['customer']['email'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">{{ __('Gateway') }}</span><span
                        class="details-value">{{ $order['payment_gateway'] ?? ($order['payment_method'] ?? '-') }}</span>
                </div>
                <div class="details-kv"><span class="details-label">{{ __('Placed At') }}</span><span
                        class="details-value">{{ optional($order['created_at'] ?? null)->format('M d, Y H:i') ?? '-' }}</span>
                </div>
                @if (!empty($order['carrier']))
                    <div class="details-kv"><span class="details-label">{{ __('Carrier') }}</span><span
                            class="details-value">{{ $order['carrier'] }}</span></div>
                @endif
            </div>
        </section>

        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">{{ __('Financial Breakdown') }}</h4>
                <p class="panel-copy">{{ __('All tenant-facing totals are recalculated from order items plus order-level adjustments.') }}</p>
            </div>
            <div class="details-list">
                <div class="details-kv"><span class="details-label">{{ __('Subtotal') }}</span><span
                        class="details-value">${{ number_format((float) ($financials['subtotal'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">{{ __('Item Discounts') }}</span><span
                        class="details-value">${{ number_format((float) ($financials['items_discount'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">{{ __('Order Discount') }}</span><span
                        class="details-value">${{ number_format((float) ($financials['discount'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">{{ __('Tax') }}</span><span
                        class="details-value">${{ number_format((float) (($financials['items_tax'] ?? 0) + ($financials['tax'] ?? 0)), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">{{ __('Shipping') }}</span><span
                        class="details-value">${{ number_format((float) ($financials['shipping_total'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">{{ __('Grand Total') }}</span><span
                        class="details-value">${{ number_format((float) ($financials['grand_total'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">{{ __('Platform Commission') }}</span><span
                        class="details-value">${{ number_format((float) ($financials['owner_profit'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">{{ __('Vendor Commission') }}</span><span
                        class="details-value">${{ number_format((float) ($financials['vendor_net_total'] ?? 0), 2) }}</span>
                </div>
                @if (($order['gateway_source'] ?? 'vendor') === 'central')
                    <div class="details-kv">
                        <span class="details-label">{{ __('CentralOwnTenant ←') }}</span>
                        <span class="details-value"
                            style="color:#22c55e;">+${{ number_format((float) ($financials['remaining_tenant_profit'] ?? 0), 2) }}</span>
                    </div>
                    <div class="details-kv" style="grid-column:1/-1;">
                        <span class="details-label" style="font-size:11px;color:var(--t3)">{{ __('Central collected payment. Your profit will be paid out to you.') }}
                            @if ((float) ($financials['remaining_tenant_profit'] ?? 0) <= 0 && (float) ($financials['central_own_tenant'] ?? 0) > 0)
                                {{ __('Already released.') }}
                            @endif
                        </span>
                    </div>
                @else
                    <div class="details-kv">
                        <span class="details-label">{{ __('TenantOwnCentral →') }}</span>
                        <span class="details-value"
                            style="color:#f59e0b;">${{ number_format((float) ($financials['remaining_owner_profit'] ?? 0), 2) }}</span>
                    </div>
                    <div class="details-kv" style="grid-column:1/-1;">
                        <span class="details-label" style="font-size:11px;color:var(--t3)">
                            @if ((float) ($financials['remaining_owner_profit'] ?? 0) > 0)
                                {{ __('You collected payment. Settle the catalog cost with central.') }}
                            @else
                                {{ __('You collected payment. Catalog cost has been settled with central.') }}
                            @endif
                        </span>
                    </div>
                @endif
            </div>
        </section>

        <section class="details-panel full">
            <div class="details-header">
                <h4 class="panel-title">{{ __('Line Items') }}</h4>
                <p class="panel-copy">{{ __('Item-level quantity, pricing, tax, and shipping contributions for this order.') }}</p>
            </div>
            <div class="tw">
                <table class="details-table">
                    <thead>
                        <tr>
                            <th>{{ __('Item') }}</th>
                            <th>{{ __('Qty') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Discount') }}</th>
                            <th>{{ __('Tax') }}</th>
                            <th>{{ __('Shipping') }}</th>
                            <th>{{ __('Total') }}</th>
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
                                <td>${{ number_format((float) ($item['price'] ?? 0), 2) }}</td>
                                <td>${{ number_format((float) ($item['discount'] ?? 0), 2) }}</td>
                                <td>${{ number_format((float) ($item['tax'] ?? 0), 2) }}</td>
                                <td>${{ number_format((float) ($item['shipping'] ?? 0), 2) }}</td>
                                <td>${{ number_format((float) ($item['line_total'] ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="json-empty">{{ __('No line items were found for this tenant order.') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    {{-- Tracking Summary --}}
    <section class="details-panel full">
        <div class="details-header">
            <h4 class="panel-title">{{ __('Order Tracking') }}</h4>
            <p class="panel-copy">{{ __('Current shipping status and delivery information for this order.') }}</p>
        </div>
        <div class="details-list">
            <div class="details-kv"><span class="details-label">{{ __('Order Status') }}</span><span class="details-value">{{ $order['status'] ?? '-' }}</span></div>
            <div class="details-kv"><span class="details-label">{{ __('Shipping Status') }}</span><span class="details-value">{{ $order['shipping_status'] ?? '-' }}</span></div>
            @if (!empty($order['carrier']))
                <div class="details-kv">
                    <span class="details-label">{{ __('Carrier') }}</span>
                    <span class="details-value">{{ $order['carrier'] }}</span>
                </div>
            @endif
        </div>
    </section>

    <x-card-collapse :title="__('Shipping Address')" :subtitle="__('Expandable structured address payload captured at checkout.')"
        :start-open="true">
        <x-json-tree :data="$order['shipping_address'] ?? []" :start-open="true" />
    </x-card-collapse>

    <x-card-collapse :title="__('Payment Details')" :subtitle="__('Gateway response payload with nested JSON nodes kept expandable.')"
        :start-open="true">
        <x-json-tree :data="$order['payment_details'] ?? []" :start-open="true" />
    </x-card-collapse>

    @if ($activities)
        <section class="details-panel full">
            <div class="details-header">
                <h4 class="panel-title">{{ __('Activity') }}</h4>
                <p class="panel-copy">{{ __('Recorded tenant order lifecycle events.') }}</p>
            </div>
            <div class="details-list">
                @foreach ($activities as $activity)
                    <div class="activity-row">
                        <div class="activity-row-title">{{ $activity['title'] ?? '-' }}</div>
                        @if (!empty($activity['description']))
                            <div class="activity-row-copy">{{ __($activity['description']) }}</div>
                        @endif
                        <div class="activity-row-date">
                            {{ optional($activity['created_at'] ?? null)->format('M d, Y H:i') ?? '-' }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (!empty($order['can_update_shipping']))
        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">{{ __('Shipping Status') }}</h4>
                <p class="panel-copy">{{ __('Update the delivery status for this order. Available only for your own products orders.') }}</p>
            </div>
            <div class="details-list">
                <div class="details-kv">
                    <span class="details-label">{{ __('Current Status') }}</span>
                    <span class="details-value">{{ $order['shipping_status'] ?? '-' }}</span>
                </div>
            </div>
            <div class="form-row" style="margin-top: 1rem; display: flex; gap: 0.75rem; align-items: flex-end;">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label" for="shipping-status-select">{{ __('New Status') }}</label>
                    <x-select wire:model="selectedShippingStatus" :placeholder="__('— select status —')">
                        <option value="">{{ __('— select status —') }}</option>
                        @foreach (($shippingStatuses ?? []) as $shippingStatus)
                            <option value="{{ $shippingStatus->value }}">{{ __($shippingStatus->label()) }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="form-group">
                    <button
                        class="btn btn-primary"
                        wire:click="updateShippingStatus"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="updateShippingStatus">{{ __('Update') }}</span>
                        <span wire:loading wire:target="updateShippingStatus">{{ __('Updating…') }}</span>
                    </button>
                </div>
            </div>
        </section>
    @endif
</div>
