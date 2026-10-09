<div class="entity-title">{{ $refund->currency }} {{ number_format((float) $refund->amount, 2) }}</div>
@if((float) $refund->shipping_amount > 0 || (float) $refund->return_fee > 0)
    <div class="entity-subtitle">
        {{ __('Items') }} {{ number_format((float) $refund->items_amount, 2) }}
        @if((float) $refund->shipping_amount > 0) + {{ __('Shipping') }} {{ number_format((float) $refund->shipping_amount, 2) }}@endif
        @if((float) $refund->return_fee > 0) − {{ __('Fee') }} {{ number_format((float) $refund->return_fee, 2) }}@endif
    </div>
@endif
