@php
    $logoAlt = tenant()?->data['shop_name'] ?? tenant()?->name ?? 'Logo';
@endphp

<section class="ta-brand" aria-label="{{ $logoAlt }}">
    <span class="ta-logo-plate">
        <x-storefront-logo :storeName="$logoAlt" class="ta-logo" />
    </span>
</section>
