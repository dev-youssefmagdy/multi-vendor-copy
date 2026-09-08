{{-- ── Header ────────────────────────────────────────────
  $storeName        — string
  $logoPath          — string|null (already a resolved, browser-ready URL)
  $rootCategories    — Collection<Category> (->slug, ->translationValue('name'), ->children)
─────────────────────────────────────────────────────── --}}
<header>
    <a href="/">
        @if ($logoPath)
            <img src="{{ $logoPath }}" alt="{{ $storeName }}">
        @else
            {{ $storeName }}
        @endif
    </a>

    {{-- Navigation — loop real categories to build the menu --}}
    <nav>
        <a href="/">Home</a>
        @foreach ($rootCategories as $cat)
            <a href="{{ route('tenant.storefront.category', $cat->slug) }}">
                {{ $cat->translationValue('name') ?? $cat->slug }}
            </a>
        @endforeach
    </nav>

    {{-- Drop-in cart icon with live badge --}}
    @livewire('storefront.cart-icon')
</header>
