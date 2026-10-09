{{-- Admin order page: cancellation info + refunds panel (RETURN_EXCHANGE_REFUND_PLAN.md B.8 Admin).
     $afterSales comes from OrderAfterSalesService::orderContext(). --}}
@php
    $afterSales = $afterSales ?? [];
    $cancellation = $afterSales['cancellation'] ?? null;
    $refundable = (float) ($afterSales['refundable_amount'] ?? 0);
@endphp

@if ($cancellation)
    <div class="card fu d2 section-gap" id="order-cancellation-panel">
        <div class="details-header">
            <h4 class="panel-title">Cancellation</h4>
            <p class="panel-copy">This order was cancelled and will not be fulfilled.</p>
        </div>
        <div class="details-list">
            <div class="details-kv"><span class="details-label">Status</span><span class="details-value"><span class="badge badge-red">{{ $cancellation['status'] }}</span></span></div>
            <div class="details-kv"><span class="details-label">Reason</span><span class="details-value">{{ $cancellation['reason'] ?? '—' }}</span></div>
            <div class="details-kv"><span class="details-label">Cancelled by</span><span class="details-value">{{ $cancellation['cancelled_by'] ?? '—' }}</span></div>
            <div class="details-kv"><span class="details-label">Cancelled at</span><span class="details-value">{{ $cancellation['cancelled_at'] ?? '—' }}</span></div>
            @if (filled($cancellation['note'] ?? null))
                <div class="details-kv details-kv-full"><span class="details-label">Note</span><span class="details-value">{{ $cancellation['note'] }}</span></div>
            @endif
        </div>
    </div>
@endif

@if (!empty($afterSales))
    <div class="card fu d2 section-gap" id="order-refunds-panel">
        <div class="details-header" style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap">
            <div>
                <h4 class="panel-title">Refunds</h4>
                <p class="panel-copy">Refunds issued for this order and what can still be refunded.</p>
            </div>
            @if (!empty($afterSales['can_refund']))
                <x-btn type="button" variant="secondary" wire:click="openRefundModal" id="admin-refund-order">Refund</x-btn>
            @endif
        </div>
        <div class="details-list">
            <div class="details-kv">
                <span class="details-label">Payment state</span>
                <span class="details-value"><span class="badge badge-{{ $afterSales['payment_state_color'] ?? 'gray' }}">{{ $afterSales['payment_state_label'] ?? '—' }}</span></span>
            </div>
            <div class="details-kv"><span class="details-label">Refunded amount</span><span class="details-value">${{ number_format((float) ($afterSales['refunded_amount'] ?? 0), 2) }}</span></div>
            <div class="details-kv"><span class="details-label">Still refundable</span><span class="details-value">${{ number_format($refundable, 2) }}</span></div>
        </div>

        <div style="margin-top:12px">
            @include('livewire.admin.order.partials.refunds-table', [
                'refunds' => $afterSales['refunds'] ?? [],
                'canManage' => !empty($afterSales['can_manage']),
            ])
        </div>
    </div>
@endif
