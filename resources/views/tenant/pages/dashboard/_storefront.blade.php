{{--
    Dashboard "Live Store Preview" + "Edit your storefront" shortcuts.
    The preview is the real storefront (same domain as this panel) rendered in
    a scaled-down, non-interactive iframe; resources/js/tenant/pages/dashboard/storefront.js
    keeps it scaled to the frame.

    Country selector: one option per country the store sells in (+ "All countries").
    Picking one reloads the preview as a visitor from that country (?preview_country=XX,
    request-only) and points every shortcut at what that country sees (live theme
    edition, its colors / home layout, its banners). See App\Support\Tenant\StorefrontCountryShortcuts.
--}}

@php
    $storefrontUrl = url('/');
    $storeHost = tenant()?->domains()->first()?->domain ?? request()->getHost();

    $countryOptions = \App\Support\Tenant\StorefrontCountryShortcuts::options();
    $defaultLinks = $countryOptions[0]['links'];

    $shortcuts = [
        ['key' => 'template', 'label' => __('Change template'), 'href' => $defaultLinks['template'],
         'icon' => '<path d="M3.75 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 .75.75v5.25a.75.75 0 0 1-.75.75H4.5a.75.75 0 0 1-.75-.75zM13.5 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-.75.75h-5.25a.75.75 0 0 1-.75-.75zM13.5 13.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 .75.75v6a.75.75 0 0 1-.75.75h-5.25a.75.75 0 0 1-.75-.75zM3.75 15a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75H4.5a.75.75 0 0 1-.75-.75z"/>'],
        ['key' => 'colors', 'label' => __('Changing colors'), 'href' => $defaultLinks['colors'],
         'icon' => '<path d="M12 21.75c-5.38 0-9.75-4.37-9.75-9.75S6.62 2.25 12 2.25s9.75 3.9 9.75 8.7c0 3-2.43 5.45-5.43 5.45h-1.92c-.9 0-1.62.73-1.62 1.62 0 .4.16.77.4 1.05.25.29.4.66.4 1.07 0 .9-.73 1.61-1.58 1.61z"/><circle cx="7.5" cy="12" r="1.25"/><circle cx="10" cy="7.5" r="1.25"/><circle cx="15" cy="8.25" r="1.25"/>'],
        ['key' => 'banners', 'label' => __('Banner editing'), 'href' => $defaultLinks['banners'],
         'icon' => '<rect x="2.25" y="3.75" width="19.5" height="16.5" rx="3"/><circle cx="8.25" cy="9" r="1.75"/><path d="M21.75 15.5l-4.2-4.2a1.5 1.5 0 0 0-2.1 0L6 20.25"/>'],
        ['key' => 'homepage', 'label' => __('Edit Homepage'), 'href' => $defaultLinks['homepage'],
         'icon' => '<path d="M14.5 9.5L20 4a1.4 1.4 0 0 0-2-2l-5.5 5.5"/><path d="M12.5 7.5l2 2c.55.55.55 1.45 0 2l-1 1-4-4 1-1c.55-.55 1.45-.55 2 0z"/><path d="M9.5 8.5l-5.1 5.1a2 2 0 0 0 0 2.8l3.2 3.2a2 2 0 0 0 2.8 0l5.1-5.1"/><path d="M5.5 16.5l2 2"/>'],
        ['key' => 'logo', 'label' => __('Add or edit the logo'), 'href' => $defaultLinks['logo'],
         'icon' => '<path d="M9.5 2.25l1.2 3.3a4 4 0 0 0 2.4 2.4l3.3 1.2-3.3 1.2a4 4 0 0 0-2.4 2.4l-1.2 3.3-1.2-3.3a4 4 0 0 0-2.4-2.4l-3.3-1.2 3.3-1.2a4 4 0 0 0 2.4-2.4z"/><path d="M17.5 14.25l.6 1.65a2 2 0 0 0 1.2 1.2l1.65.6-1.65.6a2 2 0 0 0-1.2 1.2l-.6 1.65-.6-1.65a2 2 0 0 0-1.2-1.2l-1.65-.6 1.65-.6a2 2 0 0 0 1.2-1.2z"/>'],
    ];
@endphp

<section class="db-section db-store fu d2" data-store-country-shortcuts>
    <script type="application/json" data-store-country-map>@json(collect($countryOptions)->mapWithKeys(fn ($o) => [$o['code'] => ['preview' => $o['preview_url'], 'links' => $o['links'], 'iso2' => $o['iso2']]]))</script>
    <div class="db-store-preview">
        <div class="db-store-head">
            <div>
                <h2 class="db-store-title">{{ __('Live Store Preview') }}</h2>
                <p class="db-store-host"><i aria-hidden="true"></i>{{ $storeHost }}</p>
            </div>
            <a href="{{ $storefrontUrl }}" target="_blank" rel="noopener noreferrer" class="db-store-open" data-store-preview-link>
                {{ __('open') }}
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 7L6 18M17 7H8.5M17 7v8.5"/></svg>
            </a>
        </div>

        <div class="db-browser">
            <div class="db-browser-bar" aria-hidden="true">
                <i style="background:#EF4444"></i><i style="background:#F59E0B"></i><i style="background:#10B981"></i>
                <span class="db-browser-url" data-store-preview-url data-host="{{ $storeHost }}">{{ $storeHost }}</span>
            </div>
            <div class="db-browser-view" data-store-preview>
                <iframe src="{{ $storefrontUrl }}" title="{{ __('Live preview of your storefront') }}" loading="lazy" scrolling="no" tabindex="-1" aria-hidden="true" data-store-preview-frame></iframe>
                <a href="{{ $storefrontUrl }}" target="_blank" rel="noopener noreferrer" class="db-browser-cover" aria-label="{{ __('Open your storefront in a new tab') }}" data-store-preview-link></a>
            </div>
        </div>
    </div>

    <div class="db-store-edit">
        <div class="db-store-edit-head">
            <div>
                <h2 class="db-store-title is-bold">{{ __('Edit your storefront') }}</h2>
                <p class="db-store-sub">{{ __('Fundamental changes are always within your reach.') }}</p>
            </div>
            @if(count($countryOptions) > 1)
                <x-tenant::country-picker trigger-class="db-store-country" :options="$countryOptions" :label="__('Select country')">
                    <span data-country-label>{{ __('Select country') }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 9l-6 6-6-6"/></svg>
                </x-tenant::country-picker>
            @endif
        </div>

        <nav class="db-store-links" aria-label="{{ __('Edit your storefront') }}">
            @foreach($shortcuts as $shortcut)
                <a href="{{ $shortcut['href'] }}" class="db-store-link" data-store-shortcut="{{ $shortcut['key'] }}">
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $shortcut['icon'] !!}</svg>
                        {{ __($shortcut['label']) }}
                    </span>
                    <svg class="db-store-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                </a>
            @endforeach
        </nav>
    </div>
</section>
