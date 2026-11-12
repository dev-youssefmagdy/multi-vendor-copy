<a href="{{ route('tenant.storefront.cart') }}" class="vendor-cart-icon" aria-label="{{ __('Cart (:value items)', ['value' => $count]) }}">
    🛒
    @if ($count > 0)
        <span class="vendor-cart-badge">{{ $count }}</span>
    @endif
</a>
