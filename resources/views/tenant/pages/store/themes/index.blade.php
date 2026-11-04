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

        @php
            // One group (tab) per base theme; its home-page variants are the items.
            $themeGroups = collect($variantCards)->groupBy('theme_id')->map(function ($cards) {
                $first = $cards->first();

                return [
                    'theme_id' => $first['theme_id'],
                    'name' => $first['theme_name'],
                    'initials' => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) $first['theme_name'], 0, 2)),
                    'is_universal' => $first['is_universal'],
                    'scope_label' => $first['scope_label'],
                    'countries_label' => $first['countries_label'],
                    'has_countries' => $first['has_countries'],
                    'has_active' => $cards->contains('is_active', true),
                    'cards' => $cards->values(),
                ];
            })->values();
        @endphp

        @if($themeGroups->isNotEmpty())
            <div class="theme-groups">
                @foreach($themeGroups as $group)
                    <section class="theme-group-panel" id="theme-{{ $group['theme_id'] }}" aria-labelledby="theme-title-{{ $group['theme_id'] }}">

                        <header class="theme-group-header">
                            <div class="theme-group-identity">
                                <span class="theme-group-avatar" aria-hidden="true">{{ $group['initials'] }}</span>
                                <div class="theme-group-copy">
                                    <h3 class="theme-group-title" id="theme-title-{{ $group['theme_id'] }}">
                                        {{ $group['name'] }}
                                        @if($group['has_active'])
                                            <span class="theme-group-live">Live</span>
                                        @endif
                                    </h3>
                                    <div class="theme-group-meta">
                                        <span class="theme-scope-badge theme-scope-{{ $group['is_universal'] ? 'universal' : 'specific' }}">{{ $group['scope_label'] }}</span>
                                        <span>{{ $group['countries_label'] }}</span>
                                        <span aria-hidden="true">·</span>
                                        <span>{{ $group['cards']->count() }} {{ \Illuminate\Support\Str::plural('variant', $group['cards']->count()) }}</span>
                                    </div>
                                </div>
                            </div>

                            @if($group['has_countries'])
                                <div class="theme-group-actions">
                                    <button type="button" class="theme-pill-btn"
                                        data-theme-countries-open
                                        data-theme-id="{{ $group['theme_id'] }}"
                                        data-theme-name="{{ $group['name'] }}"
                                        data-countries-url="{{ route('tenant.store.themes.countries', $group['theme_id']) }}"
                                        data-countries-action="{{ route('tenant.store.themes.countries.update', $group['theme_id']) }}">
                                        Countries
                                    </button>
                                    <button type="button" class="theme-pill-btn"
                                        data-theme-cv-open
                                        data-theme-id="{{ $group['theme_id'] }}"
                                        data-theme-name="{{ $group['name'] }}"
                                        data-cv-url="{{ route('tenant.store.themes.country-variants', $group['theme_id']) }}"
                                        data-cv-action="{{ route('tenant.store.themes.country-variants.update', $group['theme_id']) }}">
                                        Country Variants
                                    </button>
                                </div>
                            @endif
                        </header>

                        <div class="theme-grid">
                            @foreach($group['cards'] as $theme)
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
                                        </div>

                                        <div class="theme-card-actions">
@include('tenant.pages.store.themes._variant-card-actions', ['theme' => $theme])
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @else
            <div class="theme-empty-state">
                <div class="empty-state-title">No variants available</div>
                <p class="empty-state-copy">Variant cards will appear here after home page variants are published for your themes.</p>
            </div>
        @endif
    </div>

    @include('tenant.pages.store.themes._countries-modal')
    @include('tenant.pages.store.themes._country-variants-modal')
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/themes.js')
@endpush
