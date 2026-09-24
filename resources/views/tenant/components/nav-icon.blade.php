@props(['item' => []])

{{--
    Sidebar icon. Maps a nav item (by its label) to the design's icon set in
    ./nav-icons/, falling back to the generic x-tenant::icon for anything not
    mapped yet. Presentation only — the navigation data itself is unchanged.
--}}

@php
    $map = [
        'Dashboard' => 'dashboard',
        "Today's chances" => 'todays-chance',
        'Products' => 'products',
        'Orders' => 'orders',
        'Customers' => 'customers',
        'Your Store' => 'store',
        'Ads & Marketing' => 'ads-marketing',
        'Analytics' => 'analytics',
        'Request product' => 'request-product',
        'Partner Program' => 'partner-program',
        'Settings' => 'settings',
        'Website URL' => 'store',
    ];
    $navIcon = $map[$item['label'] ?? ''] ?? null;
@endphp

@if($navIcon)
    @include('tenant.components.nav-icons.'.$navIcon)
@else
    <x-tenant::icon :name="$item['icon'] ?? ''" size="24" />
@endif
