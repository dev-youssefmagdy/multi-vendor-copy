{{-- Order detail: cancellation info, refunds panel and the cancel / manual refund modals
     (RETURN_EXCHANGE_REFUND_PLAN.md B.8 Vendor). $afterSales comes from OrdersController::afterSalesContext(). --}}
@php
    $afterSales = $afterSales ?? [];
    $cancellation = $afterSales['cancellation'] ?? null;
    $refundable = (float) ($afterSales['refundable_amount'] ?? 0);
    $canRefund = !empty($afterSales['can_manage_refunds']) && $refundable > 0;
@endphp

@if($cancellation)
    <section class="details-panel full" id="order-cancellation-panel">
        <div class="details-header">
            <h4 class="panel-title">{{ __('Cancellation') }}</h4>
            <p class="panel-copy">{{ __('This order was cancelled and will not be fulfilled.') }}</p>
        </div>
        <div class="details-list">
            <div class="details-kv"><span class="details-label">{{ __('Status') }}</span><span class="details-value"><span class="badge badge-red"><span class="badge-dot"></span>{{ $cancellation['status'] }}</span></span></div>
            <div class="details-kv"><span class="details-label">{{ __('Reason') }}</span><span class="details-value">{{ $cancellation['reason'] ?? '—' }}</span></div>
            <div class="details-kv"><span class="details-label">{{ __('Cancelled by') }}</span><span class="details-value">{{ $cancellation['cancelled_by'] ?? '—' }}</span></div>
            <div class="details-kv"><span class="details-label">{{ __('Cancelled at') }}</span><span class="details-value">{{ $cancellation['cancelled_at'] ?? '—' }}</span></div>
            @if(filled($cancellation['note'] ?? null))
                <div class="details-kv details-kv-full"><span class="details-label">{{ __('Note') }}</span><span class="details-value">{{ $cancellation['note'] }}</span></div>
            @endif
        </div>
    </section>
@endif

<section class="details-panel full" id="order-refunds-panel">
    <div class="details-header" style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap">
        <div>
            <h4 class="panel-title">{{ __('Refunds') }}</h4>
            <p class="panel-copy">{{ __('Refunds issued for this order and what can still be refunded.') }}</p>
        </div>
        @if($canRefund)
            <button type="button" class="btn btn-secondary" data-modal-open="order-refund-modal">{{ __('Refund') }}</button>
        @endif
    </div>
    <div class="details-list">
        <div class="details-kv">
            <span class="details-label">{{ __('Payment state') }}</span>
            <span class="details-value"><span class="badge badge-{{ $afterSales['payment_state_color'] ?? 'gray' }}"><span class="badge-dot"></span>{{ $afterSales['payment_state_label'] ?? '—' }}</span></span>
        </div>
        <div class="details-kv"><span class="details-label">{{ __('Refunded amount') }}</span><span class="details-value">${{ number_format((float) ($afterSales['refunded_amount'] ?? 0), 2) }}</span></div>
        <div class="details-kv"><span class="details-label">{{ __('Still refundable') }}</span><span class="details-value">${{ number_format($refundable, 2) }}</span></div>
    </div>

    <div style="margin-top:12px">
        @include('tenant.pages.sales.orders._partials.refund-list', [
            'refunds' => $afterSales['refunds'] ?? [],
            'canManage' => !empty($afterSales['can_manage_refunds']),
        ])
    </div>
</section>

@if(!empty($afterSales['can_cancel']))
    <x-tenant::modal id="cancel-order-modal" :title="__('Cancel order')" :description="__('Stock is restored and paid orders are refunded automatically according to your policy.')">
        <x-tenant::form
            action="{{ route('tenant.orders.cancel', $orderId) }}"
            validate="{{ route('tenant.orders.cancel.validate', $orderId) }}"
            success="reload-page"
            :confirm="__('Are you sure you want to cancel this order? This cannot be undone.')"
            confirm-danger
        >
            <x-tenant::select name="reason" :label="__('Reason')" :options="$afterSales['cancel_reasons'] ?? []" :placeholder="__('— select a reason —')" required />
            <div style="margin-top:12px">
                <x-tenant::textarea name="note" :label="__('Note')" rows="3" maxlength="1000" :help="__('Required when the reason is “Other”. Shown to the customer.')" />
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Back') }}</button>
                <button type="submit" class="btn" style="background:var(--red);color:#fff">{{ __('Cancel order') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endif

@if($canRefund)
    <x-tenant::modal id="order-refund-modal" :title="__('Refund')" :description="__('Up to :amount can still be refunded on this order.', ['amount' => '$'.number_format($refundable, 2)])">
        <x-tenant::form action="{{ route('tenant.orders.refunds.store', $orderId) }}" validate="{{ route('tenant.orders.refunds.validate', $orderId) }}" success="reload-page">
            <x-tenant::input name="amount" type="number" :label="__('Amount ($)')" :value="number_format($refundable, 2, '.', '')" min="0.01" :max="number_format($refundable, 2, '.', '')" step="0.01" required />
            <div style="margin-top:12px">
                <x-tenant::input name="reason" :label="__('Reason')" maxlength="255" required />
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Issue refund') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endif
