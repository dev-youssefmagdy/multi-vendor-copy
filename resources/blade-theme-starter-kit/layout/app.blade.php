<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $storeName ?? config('app.name') }}</title>
    @include('layout.styles')
</head>
<body>

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

    <main>
        @yield('content')
    </main>

    {{-- ── Footer ────────────────────────────────────────────
      $footerCopyright   — string
      $socialLinks       — Collection<SocialLink> (->url, ->icon)
    ─────────────────────────────────────────────────────── --}}
    <footer>
        <p>{{ $footerCopyright ?? '' }}</p>
        @foreach ($socialLinks as $link)
            <a href="{{ $link->url }}" target="_blank" rel="noopener">{{ $link->icon?->name }}</a>
        @endforeach
    </footer>

    @include('layout.scripts')
</body>
</html>
