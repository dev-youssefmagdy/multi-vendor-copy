@php
    $existingReturn = ($returnRequests ?? collect())->first(function ($r) use ($item) {
        if ($item->product_variant_id) {
            return $r->product_variant_id === $item->product_variant_id;
        }
        return $r->product_id === $item->product_id;
    });
    $canRequestReturn = ($returnWindowOpen ?? false) && !$existingReturn;
@endphp

@if ($existingReturn)
    <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full mt-1"
        style="background:#F5F5F5;color:#555">
        {{ __('Return') }}: {{ $existingReturn->status->label() }}
    </span>
@elseif ($canRequestReturn)
    <a href="{{ route('tenant.storefront.order-return', ['uuid' => $order->uuid, 'item' => $item->id]) }}"
        class="inline-flex items-center gap-1 text-xs font-medium text-main border border-[#FFAC88] bg-[#FFF5F2] rounded-full px-3 py-1 mt-1 hover:bg-orange-100 transition-colors">
        {{ __('Request Return') }}
    </a>
@endif
