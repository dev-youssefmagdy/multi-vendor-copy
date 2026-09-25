{{--
    Dashboard hero: welcome row + video banner with the "Store Power" ring.
    Values come from data the page/shell already has:
      - Store Power  → setup-checklist completion (same as the sidebar widget)
      - Domain       → storefront launched
      - Payment      → an active, connected payment gateway
      - Profit       → the "My Total Net Profit" card
--}}

@php
    use App\Helpers\TenantNavigation;

    $setup = TenantNavigation::setupProgress();
    $domainReady = TenantNavigation::storefrontLaunched();
    $paymentReady = TenantNavigation::paymentGatewayIsConfigured();
    $profit = collect($cards)->firstWhere('label', 'My Total Net Profit')['value'] ?? null;
    $storeDomain = tenant()?->domains()->first()?->domain;
    $storeUrl = $storeDomain ? (str_starts_with($storeDomain, 'http') ? '' : 'https://').$storeDomain : null;

    $eye = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.54 10.95c.3.43.46.64.46 1.05s-.15.62-.46 1.05C20.19 14.95 16.72 19 12 19s-8.19-4.05-9.54-5.95C2.15 12.62 2 12.4 2 12s.15-.62.46-1.05C3.81 9.05 7.28 5 12 5s8.19 4.05 9.54 5.95z"/><circle cx="12" cy="12" r="3"/></svg>';
    $check = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="8" r="6.67"/><path d="M5.33 8.33l1.67 1.67 3.67-3.67"/></svg>';
@endphp

<section class="db-hero-wrap fu d0">
    <div class="db-welcome">
        <div class="db-welcome-copy">
            <h1 class="db-welcome-title">Welcome back, {{ tenant('name') ?? 'your store' }}</h1>
            <p class="db-welcome-sub">These are your store's most important opportunities, actions, and results all in one place.</p>
        </div>
        <div class="db-welcome-actions">
            <a href="{{ route('tenant.store.appearance') }}" class="btn btn-primary btn-lg db-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16.21 4.04l1.4-1.4a1.98 1.98 0 1 1 2.8 2.8l-1.4 1.4m-2.8-2.8l-8.25 8.25c-.68.68-1.13 1.53-1.3 2.47L6 18l3.24-.66c.94-.17 1.79-.62 2.47-1.3l8.25-8.25m-2.8-2.8l2.8 2.8"/><path d="M21 12c0 4.24 0 6.36-1.32 7.68C18.36 21 16.24 21 12 21s-6.36 0-7.68-1.32C3 18.36 3 16.24 3 12s0-6.36 1.32-7.68C5.64 3 7.76 3 12 3"/></svg>
                Customize store
            </a>
            @if($storeUrl)
                <a href="{{ $storeUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-lg db-btn">{!! $eye !!} View store</a>
            @endif
        </div>
    </div>

    <div class="db-hero">
        <video class="db-hero-video" autoplay muted loop playsinline preload="metadata" aria-hidden="true">
            {{-- Temporary stock clip (Mixkit, free license) — replace with the final video. --}}
            {{-- FOR DESIGN PURPOSE --}}
            <source src="https://assets.mixkit.co/videos/4705/4705-720.mp4" type="video/mp4">
        </video>
        <div class="db-hero-shade" aria-hidden="true"></div>

        <div class="db-hero-content">
            <div class="db-hero-copy">
                <h2 class="db-hero-title">Your store is full of products, and daily opportunities are simply smart choices from them.</h2>
                <p class="db-hero-text">Browse the entire catalog, search, filter, and adjust the appearance of any product in the homepage, Flash Sale, or Trending section.</p>
                <ul class="db-hero-points">
                    {{-- FOR DESIGN PURPOSE --}}
                    <li>{!! $check !!} dummy active products</li>
                    <li>{!! $check !!} Automatic inventory update</li>
                    <li>{!! $check !!} Direct source prices</li>
                </ul>
                <div class="db-hero-actions">
                    <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary btn-lg db-hero-btn">{!! $eye !!} view all products</a>
                    <a href="{{ route('tenant.categories.index') }}" class="btn btn-primary btn-lg db-hero-btn">
                        All Categories
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12H4m16 0l-6 6m6-6l-6-6"/></svg>
                    </a>
                </div>
            </div>

            <div class="db-power" role="img" aria-label="Store power {{ $setup['percent'] }}%">
                <div class="db-power-inner">
                    <span class="db-power-dot is-top"></span>
                    <span class="db-power-dot is-right"></span>
                    <span class="db-power-dot is-bottom"></span>
                    <span class="db-power-dot is-left"></span>
                </div>
                <div class="db-power-center">
                    <span class="db-power-value">{{ $setup['percent'] }}%</span>
                    <span class="db-power-label">Store Power</span>
                </div>

                <div class="db-power-chip is-top">
                    <span class="db-power-chip-title">Domain</span>
                    <span class="db-power-chip-value {{ $domainReady ? 'is-good' : 'is-warn' }}">{{ $domainReady ? 'Ready' : 'Pending' }}</span>
                </div>
                <div class="db-power-chip is-right">
                    <span class="db-power-chip-title">Payment</span>
                    <span class="db-power-chip-value {{ $paymentReady ? 'is-good' : 'is-warn' }}">{{ $paymentReady ? 'Ready' : 'Pending' }}</span>
                </div>
                <div class="db-power-chip is-left">
                    <span class="db-power-chip-title">Inventory</span>
                    {{-- FOR DESIGN PURPOSE --}}
                    <span class="db-power-chip-value is-warn">dummy</span>
                </div>
                <div class="db-power-chip is-bottom">
                    <span class="db-power-chip-title">Profit</span>
                    <span class="db-power-chip-value is-good">{{ $profit ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</section>
