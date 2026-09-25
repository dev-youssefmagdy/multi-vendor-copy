{{--
    Mobile "nav island" (≤ 767px): four quick links in a white pill + a black
    Store button that opens the storefront. Hidden on larger screens
    (resources/css/tenant/components/mobile-nav.css).
--}}

@php
    $route = $shell['currentRoute'] ?? (request()->route()?->getName() ?? '');
    $islandItems = [
        ['label' => 'Dashboard', 'route' => 'tenant.dashboard', 'match' => ['tenant.dashboard'],
         'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="M15.25 2.25h3c1.7 0 2.5.8 2.5 2.5v3.5c0 1.7-.8 2.5-2.5 2.5h-3c-1.7 0-2.5-.8-2.5-2.5v-3.5c0-1.7.8-2.5 2.5-2.5zM15.25 14.25h3c1.7 0 2.5.8 2.5 2.5v1.5c0 1.7-.8 2.5-2.5 2.5h-3c-1.7 0-2.5-.8-2.5-2.5v-1.5c0-1.7.8-2.5 2.5-2.5zM5.75 11.25h3c1.7 0 2.5.8 2.5 2.5v4.5c0 1.7-.8 2.5-2.5 2.5h-3c-1.7 0-2.5-.8-2.5-2.5v-4.5c0-1.7.8-2.5 2.5-2.5zM5.75 2.25h3c1.7 0 2.5.8 2.5 2.5v1.5c0 1.7-.8 2.5-2.5 2.5h-3c-1.7 0-2.5-.8-2.5-2.5v-1.5c0-1.7.8-2.5 2.5-2.5z"/></svg>'],
        ['label' => 'Analytics', 'route' => 'tenant.analytics', 'match' => ['tenant.analytics', 'tenant.insights.'],
         'icon' => view('tenant::components.nav-icons.analytics')->render()],
        ['label' => 'Appearance', 'route' => 'tenant.store.appearance', 'match' => ['tenant.store.appearance'],
         'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 6.5a4.25 4.25 0 1 0 8.5 0 4.25 4.25 0 1 0-8.5 0z"/><path d="M13.25 4.25c0-1 .4-2 2-2h4.5c1.6 0 2 1 2 2v4.5c0 1-.4 2-2 2h-4.5c-1.6 0-2-1-2-2z"/><path d="M2.25 15.25c0-1 .4-2 2-2h4.5c1.6 0 2 1 2 2v4.5c0 1-.4 2-2 2h-4.5c-1.6 0-2-1-2-2z"/><path d="M17.5 13.75l4 7h-8z"/></svg>'],
        ['label' => 'Finance', 'route' => 'tenant.finance.wallet', 'match' => ['tenant.finance.'],
         'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><ellipse cx="15.5" cy="10" rx="6.25" ry="2.75"/><path d="M21.75 10v3.25c0 1.5-2.8 2.75-6.25 2.75s-6.25-1.25-6.25-2.75V10"/><path d="M21.75 13.25v3.5c0 1.5-2.8 2.75-6.25 2.75s-6.25-1.25-6.25-2.75v-3.5"/><ellipse cx="8.5" cy="4.5" rx="6.25" ry="2.75"/><path d="M2.25 4.5v3.25c0 1.5 2.8 2.75 6.25 2.75.6 0 1.2 0 1.75-.1M2.25 7.75v3.5c0 1.5 2.8 2.75 6.25 2.75"/></svg>'],
    ];
    $isActive = fn (array $item) => collect($item['match'])->contains(fn ($m) => $route === $m || str_starts_with($route, $m));

    // Store button → the storefront (same link as the sidebar's "Website URL").
    $storefront = collect($shell['sections'] ?? [])->flatMap(fn ($s) => $s['items'] ?? [])->firstWhere('label', 'Website URL');
    $storefrontUrl = $storefront ? \App\Helpers\TenantNavigation::href($storefront) : null;
@endphp

<nav class="mnav" aria-label="Quick navigation">
    <div class="mnav-pill">
        @foreach($islandItems as $item)
            @continue(!\Illuminate\Support\Facades\Route::has($item['route']))
            @php($active = $isActive($item))
            <a href="{{ route($item['route']) }}" class="mnav-item {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif>
                {!! $item['icon'] !!}
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>

    @if($storefrontUrl)
        <a href="{{ $storefrontUrl }}" class="mnav-store" target="_blank" rel="noopener noreferrer" aria-label="Open your store">
            <x-tenant::nav-icons.store />
            <span>Store</span>
        </a>
    @endif
</nav>
