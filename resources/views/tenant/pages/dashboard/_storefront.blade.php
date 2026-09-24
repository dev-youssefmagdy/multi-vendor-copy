{{--
    Dashboard "Live Store Preview" + "Edit your storefront" shortcuts.
    The preview is the real storefront (same domain as this panel) rendered in
    a scaled-down, non-interactive iframe; resources/js/tenant/pages/dashboard/storefront.js
    keeps it scaled to the frame. The country select is UI only for now.
--}}

@php
    $storefrontUrl = url('/');
    $storeHost = tenant()?->domains()->first()?->domain ?? request()->getHost();

    $shortcuts = [
        ['label' => 'Change template', 'href' => route('tenant.store.themes'),
         'icon' => '<path d="M3.75 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 .75.75v5.25a.75.75 0 0 1-.75.75H4.5a.75.75 0 0 1-.75-.75zM13.5 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-.75.75h-5.25a.75.75 0 0 1-.75-.75zM13.5 13.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 .75.75v6a.75.75 0 0 1-.75.75h-5.25a.75.75 0 0 1-.75-.75zM3.75 15a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75H4.5a.75.75 0 0 1-.75-.75z"/>'],
        ['label' => 'Changing colors', 'href' => route('tenant.store.appearance', ['tab' => 'colors']),
         'icon' => '<path d="M12 21.75c-5.38 0-9.75-4.37-9.75-9.75S6.62 2.25 12 2.25s9.75 3.9 9.75 8.7c0 3-2.43 5.45-5.43 5.45h-1.92c-.9 0-1.62.73-1.62 1.62 0 .4.16.77.4 1.05.25.29.4.66.4 1.07 0 .9-.73 1.61-1.58 1.61z"/><circle cx="7.5" cy="12" r="1.25"/><circle cx="10" cy="7.5" r="1.25"/><circle cx="15" cy="8.25" r="1.25"/>'],
        ['label' => 'Banner editing', 'href' => route('tenant.store.banners.index'),
         'icon' => '<rect x="2.25" y="3.75" width="19.5" height="16.5" rx="3"/><circle cx="8.25" cy="9" r="1.75"/><path d="M21.75 15.5l-4.2-4.2a1.5 1.5 0 0 0-2.1 0L6 20.25"/>'],
        ['label' => 'Edit Homepage', 'href' => route('tenant.store.page-builder'),
         'icon' => '<path d="M14.5 9.5L20 4a1.4 1.4 0 0 0-2-2l-5.5 5.5"/><path d="M12.5 7.5l2 2c.55.55.55 1.45 0 2l-1 1-4-4 1-1c.55-.55 1.45-.55 2 0z"/><path d="M9.5 8.5l-5.1 5.1a2 2 0 0 0 0 2.8l3.2 3.2a2 2 0 0 0 2.8 0l5.1-5.1"/><path d="M5.5 16.5l2 2"/>'],
        ['label' => 'Add or edit the logo', 'href' => route('tenant.store.appearance', ['tab' => 'general']),
         'icon' => '<path d="M9.5 2.25l1.2 3.3a4 4 0 0 0 2.4 2.4l3.3 1.2-3.3 1.2a4 4 0 0 0-2.4 2.4l-1.2 3.3-1.2-3.3a4 4 0 0 0-2.4-2.4l-3.3-1.2 3.3-1.2a4 4 0 0 0 2.4-2.4z"/><path d="M17.5 14.25l.6 1.65a2 2 0 0 0 1.2 1.2l1.65.6-1.65.6a2 2 0 0 0-1.2 1.2l-.6 1.65-.6-1.65a2 2 0 0 0-1.2-1.2l-1.65-.6 1.65-.6a2 2 0 0 0 1.2-1.2z"/>'],
    ];
@endphp

<section class="db-section db-store fu d2">
    <div class="db-store-preview">
        <div class="db-store-head">
            <div>
                <h2 class="db-store-title">Live Store Preview</h2>
                <p class="db-store-host"><i aria-hidden="true"></i>{{ $storeHost }}</p>
            </div>
            <a href="{{ $storefrontUrl }}" target="_blank" rel="noopener noreferrer" class="db-store-open">
                open
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 7L6 18M17 7H8.5M17 7v8.5"/></svg>
            </a>
        </div>

        <div class="db-browser">
            <div class="db-browser-bar" aria-hidden="true">
                <i style="background:#EF4444"></i><i style="background:#F59E0B"></i><i style="background:#10B981"></i>
                <span class="db-browser-url">{{ $storeHost }}</span>
            </div>
            <div class="db-browser-view" data-store-preview>
                <iframe src="{{ $storefrontUrl }}" title="Live preview of your storefront" loading="lazy" scrolling="no" tabindex="-1" aria-hidden="true"></iframe>
                <a href="{{ $storefrontUrl }}" target="_blank" rel="noopener noreferrer" class="db-browser-cover" aria-label="Open your storefront in a new tab"></a>
            </div>
        </div>
    </div>

    <div class="db-store-edit">
        <div class="db-store-edit-head">
            <div>
                <h2 class="db-store-title is-bold">Edit your storefront</h2>
                <p class="db-store-sub">Fundamental changes are always within your reach.</p>
            </div>
            <button type="button" class="db-store-country">
                Select country
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 9l-6 6-6-6"/></svg>
            </button>
        </div>

        <nav class="db-store-links" aria-label="Edit your storefront">
            @foreach($shortcuts as $shortcut)
                <a href="{{ $shortcut['href'] }}" class="db-store-link">
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $shortcut['icon'] !!}</svg>
                        {{ $shortcut['label'] }}
                    </span>
                    <svg class="db-store-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                </a>
            @endforeach
        </nav>
    </div>
</section>
