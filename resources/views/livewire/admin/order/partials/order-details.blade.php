@php
    $order = $order ?? [];
    $financials = $order['financials'] ?? [];
    $items = $order['items'] ?? [];
    $activities = $order['activities'] ?? [];
    $showAttachments = $showAttachments ?? false;
    $orderAttachments = $orderAttachments ?? [];
@endphp

<div class="details-stack">
    @if (!empty($canManageShipping) && !empty($shippingStatusOptions))
        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">Shipping Control</h4>
                <p class="panel-copy">Update the tenant order lifecycle from pending intake through final delivery.</p>
            </div>
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Shipping Status</label>
                    <x-select wire:model.defer="selectedShippingStatus">
                        @foreach ($shippingStatusOptions as $statusValue => $statusLabel)
                            <option value="{{ $statusValue }}">{{ $statusLabel }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="page-actions compact-actions items-end justify-end">
                    <x-btn type="button" wire:click="updateSelectedOrderShippingStatus">Update Shipping Status</x-btn>
                </div>
            </div>
        </section>
    @endif

    <div class="details-grid">
        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">Order Snapshot</h4>
                <p class="panel-copy">Central marketplace view of the tenant order and storefront customer.</p>
            </div>
            <div class="details-list">
                <div class="details-kv"><span class="details-label">Order</span><span
                        class="details-value">{{ $order['uuid'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Store</span><span
                        class="details-value">{{ $order['tenant']['name'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Store Email</span><span
                        class="details-value">{{ $order['tenant']['email'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Status</span><span
                        class="details-value">{{ $order['status'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Shipping</span><span
                        class="details-value">{{ $order['shipping_status'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Paid</span><span
                        class="details-value">{{ !empty($order['paid']) ? 'Yes' : 'No' }}</span></div>
                <div class="details-kv"><span class="details-label">Customer</span><span
                        class="details-value">{{ $order['customer']['name'] ?? 'Guest' }}</span></div>
                <div class="details-kv"><span class="details-label">Email</span><span
                        class="details-value">{{ $order['customer']['email'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Phone</span><span
                        class="details-value">{{ $order['customer']['phone'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Gateway</span><span
                        class="details-value">{{ $order['payment_gateway'] ?? ($order['payment_method'] ?? '-') }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Reference</span><span
                        class="details-value">{{ $order['payment_reference'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Tracking #</span><span
                        class="details-value">{{ $order['tracking_number'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Placed At</span><span
                        class="details-value">{{ optional($order['created_at'] ?? null)->format('M d, Y H:i') ?? '-' }}</span>
                </div>
            </div>
        </section>

        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">Financial Breakdown</h4>
                <p class="panel-copy">Item-level and order-level totals recalculated from tenant order data.</p>
            </div>
            <div class="details-list">
                <div class="details-kv"><span class="details-label">Subtotal</span><span
                        class="details-value">${{ number_format((float) ($financials['subtotal'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Item Discounts</span><span
                        class="details-value">${{ number_format((float) ($financials['items_discount'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Order Discount</span><span
                        class="details-value">${{ number_format((float) ($financials['discount'] ?? 0), 2) }}</span>
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
                <div class="details-kv"><span class="details-label">Tenant Net</span><span
                        class="details-value">${{ number_format((float) ($financials['vendor_net_total'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">TenantOwnCentral</span><span
                        class="details-value">${{ number_format((float) ($financials['tenant_own_central'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">CentralOwnTenant</span><span
                        class="details-value">${{ number_format((float) ($financials['central_own_tenant'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">RemainingOwnerProfit</span><span
                        class="details-value"
                        style="color:#f59e0b;">${{ number_format((float) ($financials['remaining_owner_profit'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">RemainingTenantProfit</span><span
                        class="details-value"
                        style="color:#22c55e;">${{ number_format((float) ($financials['remaining_tenant_profit'] ?? 0), 2) }}</span>
                </div>
            </div>
        </section>

        <section class="details-panel full">
            <div class="details-header">
                <h4 class="panel-title">Line Items</h4>
                <p class="panel-copy">Storefront product and variant rows contributing to the order total.</p>
            </div>
            <div class="tw">
                <table class="details-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Discount</th>
                            <th>Tax</th>
                            <th>Shipping</th>
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
                                <td>${{ number_format((float) ($item['price'] ?? 0), 2) }}</td>
                                <td>${{ number_format((float) ($item['discount'] ?? 0), 2) }}</td>
                                <td>${{ number_format((float) ($item['tax'] ?? 0), 2) }}</td>
                                <td>${{ number_format((float) ($item['shipping'] ?? 0), 2) }}</td>
                                <td>${{ number_format((float) ($item['line_total'] ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="json-empty">No line items were found for this marketplace order.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <x-card-collapse title="Shipping Address" subtitle="Structured shipping payload captured on the tenant storefront."
        :start-open="true">
        <x-json-tree :data="$order['shipping_address'] ?? []" :start-open="true" />
    </x-card-collapse>

    <x-card-collapse title="Payment Details" subtitle="Gateway response payload with nested JSON nodes kept expandable."
        :start-open="true">
        <x-json-tree :data="$order['payment_details'] ?? []" :start-open="true" />
    </x-card-collapse>

    @if ($showAttachments)
        <section class="details-panel full">
            <div class="details-header">
                <h4 class="panel-title">Order Attachments</h4>
                <p class="panel-copy">Upload images, PDFs, or Excel files related to this shipping order.</p>
            </div>
            <div class="page-stack">
                {{-- Upload form --}}
                <div class="form-grid form-grid-2" style="align-items:flex-end;gap:10px">
                    <div>
                        <label class="field-label">Upload File</label>
                        <div class="file-input-wrap">
                            <input type="file"
                                wire:model="attachmentFile"
                                accept="image/*,video/*,.pdf,.xlsx,.xls,.csv,.doc,.docx"
                                class="field-control"
                                style="padding-top:6px"
                                wire:loading.class="opacity-50" wire:target="attachmentFile">
                            <div class="file-input-loader" wire:loading wire:target="attachmentFile">
                                <svg class="file-input-spinner" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" stroke-dasharray="56.55" stroke-dashoffset="14" stroke-linecap="round"/>
                                </svg>
                                <span>Uploading…</span>
                            </div>
                        </div>
                        @error('attachmentFile') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <button type="button"
                            wire:click="uploadAttachment"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-60 cursor-not-allowed"
                            wire:target="attachmentFile,uploadAttachment"
                            class="btn btn-primary"
                            style="display:inline-flex;align-items:center;gap:7px">
                            {{-- Spinner: visible while tmp upload OR action is running --}}
                            <svg wire:loading wire:target="attachmentFile,uploadAttachment"
                                viewBox="0 0 24 24" fill="none"
                                style="width:14px;height:14px;flex-shrink:0;animation:spin 0.8s linear infinite">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5"
                                    stroke-dasharray="56.55" stroke-dashoffset="14" stroke-linecap="round"/>
                            </svg>
                            <span wire:loading.remove wire:target="attachmentFile,uploadAttachment">Upload</span>
                            <span wire:loading wire:target="attachmentFile,uploadAttachment">Uploading…</span>
                        </button>
                    </div>
                </div>

                {{-- Attachments list --}}
                @if (count($orderAttachments) > 0)
                    <div class="details-list" style="margin-top:8px">
                        @foreach ($orderAttachments as $att)
                            <div class="details-kv" style="align-items:center;gap:10px">
                                <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:0">
                                    @if ($att['is_image'])
                                        <img src="{{ $att['url'] }}" alt="{{ $att['original_name'] }}"
                                            style="width:36px;height:36px;object-fit:cover;border-radius:4px;border:1px solid #e5e7eb;flex-shrink:0">
                                    @else
                                        <div style="width:36px;height:36px;background:#f3f4f6;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" style="color:#6b7280">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <polyline points="14 2 14 8 20 8"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div style="min-width:0">
                                        <div class="entity-title" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                            <a href="{{ $att['url'] }}" target="_blank" class="link-btn">{{ $att['original_name'] }}</a>
                                        </div>
                                        <div class="entity-subtitle">{{ $att['size'] }} · {{ $att['created_at'] }} · {{ $att['uploaded_by'] }}</div>
                                    </div>
                                </div>
                                <button type="button"
                                    wire:click="deleteAttachment({{ $att['id'] }})"
                                    wire:confirm="Delete this attachment?"
                                    class="btn btn-secondary btn-sm btn-danger"
                                    style="flex-shrink:0">
                                    Remove
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="panel-copy" style="color:var(--t3)">No attachments uploaded yet.</p>
                @endif
            </div>
        </section>
    @endif

    @if ($activities)
        <section class="details-panel full">
            <div class="details-header">
                <h4 class="panel-title">Activity</h4>
                <p class="panel-copy">Recorded tenant order lifecycle events.</p>
            </div>
            <div class="details-list">
                @foreach ($activities as $activity)
                    <div class="activity-row">
                        <div class="activity-row-title">{{ $activity['title'] ?? '-' }}</div>
                        @if (!empty($activity['description']))
                            <div class="activity-row-copy">{{ $activity['description'] }}</div>
                        @endif
                        <div class="activity-row-date">
                            {{ optional($activity['created_at'] ?? null)->format('M d, Y H:i') ?? '-' }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
