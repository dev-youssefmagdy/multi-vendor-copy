@extends('tenant.layouts.app')

@section('title', 'App Themes')

@section('content')
    <div class="theme-library-shell">
        <x-tenant::page-header title="App Themes" badge="Mobile" description="Choose the theme and home layout of your mobile app. This is independent of your web storefront theme." />

        <p class="panel-copy">Current app theme: <strong>{{ $activeLabel ?? 'Follows the web theme until you pick one' }}</strong></p>

        <section class="theme-grid">
            @foreach($cards as $c)
                <article class="theme-card {{ $c['is_active'] ? 'is-active' : '' }}">
                    <div class="theme-card-preview">
                        @if($c['preview'])
                            <img src="{{ $c['preview'] }}" alt="{{ $c['name'] }}">
                        @else
                            <div class="theme-card-fallback"><span>{{ $c['initials'] }}</span></div>
                        @endif
                        @if($c['is_active'])<div class="theme-card-status">Live in app</div>@endif
                    </div>
                    <div class="theme-card-body">
                        <div class="theme-card-copy">
                            <h3 class="theme-card-title">{{ $c['name'] }}</h3>
                            <div class="theme-card-subtitle">{{ ucfirst($c['theme_slug']) }} · {{ $c['key'] }}</div>
                        </div>
                        <div class="theme-card-actions">
                            @if($c['is_active'])
                                <button type="button" class="theme-pill-btn is-disabled" disabled>Active</button>
                            @else
                                <button type="button" class="theme-pill-btn is-primary"
                                    data-action-url="{{ route('tenant.store.mobile-themes.activate', ['themeSlug' => $c['theme_slug'], 'variantKey' => $c['key']]) }}"
                                    data-action-method="POST"
                                    data-success="reload-page">Set Active</button>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        <x-tenant::form id="mobile-colors-form" :action="route('tenant.store.mobile-themes.colors')" method="PUT" success="none">
            <section class="card form-card">
                <div class="panel-head mb-5">
                    <div><h3 class="panel-title">Color overrides (optional)</h3></div>
                    <button type="submit" class="btn btn-primary">Save colors</button>
                </div>
                <div class="form-grid form-grid-2">
                    @foreach($colorTokens as $t)
                        <x-tenant::input :name="'colors['.$t.']'" :label="ucfirst(str_replace('_', ' ', $t))" :value="$overrides[$t] ?? ''" placeholder="#RRGGBB" />
                    @endforeach
                </div>
            </section>
        </x-tenant::form>
    </div>
@endsection
