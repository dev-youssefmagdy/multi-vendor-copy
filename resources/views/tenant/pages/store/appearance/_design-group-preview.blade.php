{{-- Small live illustration shown at the top of each color group, plus live swatches for every color of the group. --}}
@php($slug = \Illuminate\Support\Str::slug($groupName))
<div class="t-pv-group" data-pv-group="{{ $slug }}">
    <div class="t-pv-mini">
        @switch($slug)
            @case('brand-accents')
                <div class="t-pv-mini-row"><span class="t-pv-btn" data-pv="brand">{{ __('Primary button') }}</span><span class="t-pv-btn t-pv-btn-ghost" data-pv="brand">{{ __('Outline') }}</span><span class="t-pv-link" data-pv="brand">{{ __('Text link') }}</span></div>
                <div class="t-pv-mini-row"><span class="t-pv-tab" data-pv="brand">{{ __('Active tab') }}</span><span class="t-pv-pill" data-pv="brand-alt">{{ __('Badge') }}</span><span class="t-pv-dot" data-pv="brand"></span></div>
                @break
            @case('header-top-bar')
                <div class="t-pv-mini-bar" data-pv="topbar-bg topbar-text"><span>{{ __('Free delivery') }}</span><span>{{ __('EN · USD') }}</span></div>
                <div class="t-pv-mini-bar t-pv-mini-brand" data-pv="brand"><b>{{ __('store') }}</b><span class="t-pv-search-sm" data-pv="surface text-muted">{{ __('Search…') }}</span><span>{{ __('Cart') }}</span></div>
                @break
            @case('text')
                <div class="t-pv-mini-text" data-pv="page-bg"><b data-pv="text">{{ __('Heading text') }}</b><span data-pv="text">{{ __('Body text looks like this on cards and pages.') }}</span><small data-pv="text-muted">{{ __('Muted caption and helper text') }}</small></div>
                @break
            @case('backgrounds-surfaces')
                <div class="t-pv-mini-surfaces" data-pv="page-bg"><div class="t-pv-card t-pv-card-sm" data-pv="surface border"><span data-pv="text">{{ __('Card on page background') }}</span><span class="t-pv-input" data-pv="surface border text-muted">{{ __('Input field') }}</span></div></div>
                @break
            @case('borders-lines')
                <div class="t-pv-mini-surfaces" data-pv="page-bg"><div class="t-pv-card t-pv-card-sm" data-pv="surface border"><span data-pv="text">{{ __('Card border') }}</span><hr data-pv="border"><div class="t-pv-progress" data-pv="border"><i data-pv="brand"></i></div></div></div>
                @break
            @case('status-prices-badges')
                <div class="t-pv-mini-row"><span class="t-pv-price" data-pv="price brand">$49.00</span><s data-pv="text-muted">$69.00</s><em class="t-pv-chip-off" data-pv="discount">-30%</em></div>
                <div class="t-pv-mini-row"><span class="t-pv-ok" data-pv="success">{{ __('✓ In stock') }}</span><span class="t-pv-bad" data-pv="error">{{ __('✕ Out of stock') }}</span><span class="t-pv-pill" data-pv="brand-alt">{{ __('New') }}</span></div>
                @break
            @case('flash-sale')
                <div class="t-pv-flash" data-pv="flash-bg flash-accent"><b>{{ __('Flash sale') }}</b><span class="t-pv-timer" data-pv="flash-accent"><i>02</i><i>14</i><i>37</i></span></div>
                @break
            @case('hero-banner')
                <div class="t-pv-hero t-pv-hero-sm" data-pv="page-bg hero-title hero-sub"><div class="t-pv-hero-copy"><b data-pv="hero-title text">{{ __('Summer collection') }}</b><small data-pv="hero-sub text-muted">{{ __('Up to 40% off') }}</small><span class="t-pv-btn t-pv-btn-sm" data-pv="brand">{{ __('Shop now') }}</span></div></div>
                @break
            @case('footer')
                <div class="t-pv-footer" data-pv="footer-bg footer-text"><div class="t-pv-footer-cols"><span>{{ __('Help') }}</span><span>{{ __('Shipping') }}</span><span>{{ __('Returns') }}</span></div><div class="t-pv-footer-bar" data-pv="footer-bar footer-text">{{ __('© 2026 store') }}</div></div>
                @break
            @case('neutrals')
                <div class="t-pv-mini-row t-pv-neutrals"><span data-pv="black" style="background:var(--pv-black)">{{ __('Black') }}</span><span data-pv="text" style="background:var(--pv-text);color:#fff">{{ __('Dark') }}</span><span data-pv="border" style="background:var(--pv-border);color:#111;text-shadow:none">{{ __('Light') }}</span><span data-pv="white" style="background:var(--pv-white);color:#111;border:1px solid var(--pv-border)">{{ __('White') }}</span></div>
                @break
            @default
                <div class="t-pv-mini-row"><span class="t-pv-ribbon" data-pv="brand-alt">{{ __('Ribbon') }}</span><span class="t-pv-dot" data-pv="brand"></span><span class="t-pv-dot t-pv-dot-off" data-pv="border"></span><span class="t-pv-tile" data-pv="brand-alt"></span></div>
        @endswitch
    </div>
    <div class="t-pv-chips" aria-label="{{ __('Colors in this group') }}">
        @foreach($tokens as $property => $token)
            <button type="button" class="t-pv-chip" data-chip-for="{{ $property }}" data-chip-focus="{{ $property }}" style="--c: {{ $section['values'][$property] ?? $token['default'] }}" title="{{ __($token['label']) }}"><i></i><span>{{ __($token['label']) }}</span></button>
        @endforeach
    </div>
</div>
