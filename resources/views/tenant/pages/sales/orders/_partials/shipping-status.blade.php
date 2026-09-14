@php
    $order = $order ?? [];
@endphp
<div id="shipping-status-panel">
    @if(!empty($order['can_update_shipping']))
        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">Shipping Status</h4>
                <p class="panel-copy">Update the delivery status for this order. Available only for your own products orders.</p>
            </div>
            <div class="details-list">
                <div class="details-kv">
                    <span class="details-label">Current Status</span>
                    <span class="details-value" data-shipping-status-current>{{ $order['shipping_status'] ?? '-' }}</span>
                </div>
            </div>

            <x-tenant::form
                action="{{ route('tenant.orders.shipping-status', $orderId) }}"
                method="PATCH"
                validate="{{ route('tenant.orders.shipping-status.validate', $orderId) }}"
                success="emit:tenant:orders:shipping-status-updated"
            >
                <div class="form-row" style="display:flex; gap:0.75rem; align-items:flex-end;">
                    <div style="flex:1;">
                        <x-tenant::select
                            name="shipping_status"
                            label="New Status"
                            :value="$order['shipping_status_value'] ?? ''"
                            :options="collect($shippingStatuses ?? [])->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()"
                            placeholder="— select status —"
                        />
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </div>
            </x-tenant::form>
        </section>
    @endif
</div>
