@extends('tenant.layouts.app')

@section('title', 'Themes')

@section('content')
    <div class="theme-library-shell">
        <x-tenant::page-header title="Themes" badge="Storefront" description="Switch the active storefront theme for the current tenant." />

        <section class="theme-hero fu d1">
            <div class="theme-hero-grid">
                <div class="theme-hero-copy">
                    <span class="theme-hero-kicker">Theme Gallery</span>
                    <h2 class="theme-hero-title">Choose the storefront look that fits your catalog.</h2>
                    <p>Browse live preview cards, compare visual direction at a glance, and activate the design you want customers to see right now.</p>
                </div>

                <div class="theme-hero-stat">
                    <div class="eyebrow">Current Live Theme</div>
                    <div class="theme-hero-stat-value">{{ $activeThemeName }}</div>
                    <p class="panel-copy">Use Preview to inspect the theme asset, then activate it when you are ready to switch the storefront.</p>
                </div>
            </div>
        </section>

        <x-tenant::stats-grid :stats="$stats" />

        @if($variantCards)
            <section class="theme-grid">
                @foreach($variantCards as $theme)
                    <article class="theme-card {{ $theme['is_active'] ? 'is-active' : '' }} fu d{{ ($loop->index % 6) + 1 }}">
                        <div class="theme-card-preview">
                            <div class="theme-card-fallback">
                                <span>{{ $theme['initials'] }}</span>
                            </div>

                            @if($theme['is_active'])
                                <div class="theme-card-status">Live Theme</div>
                            @endif
                        </div>

                        <div class="theme-card-body">
                            <div class="theme-card-copy">
                                <h3 class="theme-card-title">{{ $theme['name'] }}</h3>
                                <div class="theme-card-subtitle">{{ $theme['theme_name'] }}</div>
                                <div class="theme-card-subtitle">
                                    <span class="theme-scope-badge theme-scope-{{ $theme['is_universal'] ? 'universal' : 'specific' }}">
                                        {{ $theme['scope_label'] }}
                                    </span>
                                    <span>{{ $theme['countries_label'] }}</span>
                                </div>
                            </div>

                            <div class="theme-card-actions">
                                @if($theme['action_method'] === 'activateVariant')
                                    <button type="button" class="{{ $theme['action_class'] }}"
                                        data-action-url="{{ route('tenant.store.themes.variants.activate', ['theme' => $theme['theme_id'], 'variant' => $theme['variant_id']]) }}"
                                        data-action-method="POST"
                                        data-success="emit:tenant:setup-progress:refresh reload-page">
                                        {{ $theme['action_label'] }}
                                    </button>
                                @elseif($theme['action_method'] === 'activateTheme')
                                    <button type="button" class="{{ $theme['action_class'] }}"
                                        data-action-url="{{ route('tenant.store.themes.activate', $theme['theme_id']) }}"
                                        data-action-method="POST"
                                        data-success="emit:tenant:setup-progress:refresh reload-page">
                                        {{ $theme['action_label'] }}
                                    </button>
                                @elseif($theme['action_method'] === 'deactivateTheme')
                                    <button type="button" class="{{ $theme['action_class'] }}"
                                        data-action-url="{{ route('tenant.store.themes.deactivate', $theme['theme_id']) }}"
                                        data-action-method="POST"
                                        data-success="emit:tenant:setup-progress:refresh reload-page">
                                        {{ $theme['action_label'] }}
                                    </button>
                                @else
                                    <button type="button" class="{{ $theme['action_class'] }}" disabled>
                                        {{ $theme['action_label'] }}
                                    </button>
                                @endif

                                <a href="{{ $theme['preview_path'] }}" target="_blank" rel="noopener noreferrer" class="theme-pill-btn">Preview</a>

                                @if(!empty($theme['storefront_url']))
                                    <a href="{{ $theme['storefront_url'] }}" target="_blank" rel="noopener noreferrer" class="theme-pill-btn theme-pill-btn-live">View Live</a>
                                @endif

                                @if($theme['has_countries'])
                                    <button type="button" class="theme-pill-btn"
                                        data-theme-countries-open
                                        data-theme-id="{{ $theme['theme_id'] }}"
                                        data-theme-name="{{ $theme['theme_name'] }}"
                                        data-countries-url="{{ route('tenant.store.themes.countries', $theme['theme_id']) }}"
                                        data-countries-action="{{ route('tenant.store.themes.countries.update', $theme['theme_id']) }}">
                                        Countries
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
        @else
            <div class="theme-empty-state">
                <div class="empty-state-title">No variants available</div>
                <p class="empty-state-copy">Variant cards will appear here after home page variants are published for your themes.</p>
            </div>
        @endif
    </div>

    @include('tenant.pages.store.themes._countries-modal')
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/themes.js')
@endpush
