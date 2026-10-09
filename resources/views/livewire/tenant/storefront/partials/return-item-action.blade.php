{{--
    Per-item return action on the order page.

    Variables: $order, $item (OrderItem), $returnRequests (the order's requests, newest first),
    optional $returnItems (OrderStatusPage: order_item_id => {remaining, returnable, has_open_request})
    and $returnWindowOpen (legacy fallback when $returnItems isn't passed).

    - Shipped                      → "Return after delivery" hint
    - Cancelled / Rejected / Refunded / not delivered yet → nothing
    - Delivered / Completed        → latest request status (links to the request) and, while units
                                     are left and no request is open, "Request Return".
--}}
@php
    use App\Enums\OrderStatus;

    $__status = $order->status instanceof OrderStatus ? $order->status : OrderStatus::tryFrom((string) $order->status);
    $__requests = $returnRequests ?? collect();
    $__existing = $__requests->first(function ($r) use ($item) {
        if ($r->order_item_id) {
            return (int) $r->order_item_id === (int) $item->id;
        }
        if ($item->product_variant_id) {
            return (int) $r->product_variant_id === (int) $item->product_variant_id;
        }

        return (int) $r->product_id === (int) $item->product_id;
    });
    $__ui = \App\Support\Tenant\Storefront\AfterSalesUi::tokens();
    $__row = isset($returnItems) ? ($returnItems[$item->id] ?? null) : null;

    if (isset($returnItems)) {
        $__canRequest = $__status?->isDelivered() && $__row && $__row['returnable'];
        $__remaining = $__row['remaining'] ?? 0;
    } else {
        $__canRequest = $__status?->isDelivered() && ($returnWindowOpen ?? false) && ! ($__existing && $__existing->status?->isOpen());
        $__remaining = (int) $item->qty;
    }
@endphp

@if ($__status === OrderStatus::Shipped)
    <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full mt-1"
        style="background:#F5F5F5;color:#555">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/>
        </svg>
        {{ __('Return after delivery') }}
    </span>
@elseif ($__status?->isDelivered())
    <div class="flex items-center gap-2 flex-wrap mt-1">
        @if ($__existing)
            @php $__badge = \App\Support\Tenant\Storefront\AfterSalesUi::badge($__existing->status?->color()); @endphp
            <a href="{{ route('tenant.storefront.return-detail', $__existing->id) }}"
                class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full hover:opacity-80 transition-opacity"
                style="background:{{ $__badge['bg'] }};color:{{ $__badge['text'] }}"
                title="{{ __('View return request #:id', ['id' => $__existing->id]) }}">
                {{ $__existing->type === \App\Enums\ReturnType::Exchange ? __('Exchange') : __('Return') }}: {{ $__existing->status?->label() }}
            </a>
        @endif
        @if ($__canRequest)
            <a href="{{ route('tenant.storefront.order-return', ['uuid' => $order->uuid, 'item' => $item->id]) }}"
                class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-3 py-1 hover:opacity-80 transition-opacity"
                style="color:{{ $__ui['accent'] }};background:{{ $__ui['accent_soft'] }};border:1px solid {{ $__ui['accent_border'] }}">
                {{ __('Request Return') }}
                @if ($__existing && $__remaining > 0 && $__remaining < (int) $item->qty)
                    <span class="opacity-75">({{ __(':count left', ['count' => $__remaining]) }})</span>
                @endif
            </a>
        @endif
    </div>
@endif
