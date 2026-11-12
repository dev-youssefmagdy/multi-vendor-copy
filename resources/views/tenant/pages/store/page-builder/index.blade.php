@extends('tenant.layouts.app')

@section('title', __('Page Builder'))

@section('content')
    <x-tenant::page-header :title="__('Page Builder')" :badge="__('Storefront')" :description="__('Reorder and show/hide sections on your storefront Home page.')" />

    <form method="GET" action="{{ route('tenant.store.page-builder') }}" class="pb-switcher-form section-gap" data-pb-switcher-form>
        <x-tenant::select name="theme" :label="__('Theme')" :value="$selectedThemeId"
            :options="$themes->map(fn ($theme) => ['value' => $theme->id, 'label' => $theme->name])->all()"
            data-pb-theme-select />

        @if($availableVariants->isNotEmpty())
            <x-tenant::select name="variant" :label="__('Home Variant')" :value="$selectedHomeVariantId"
                :options="$availableVariants->map(fn ($variant) => ['value' => $variant->id, 'label' => $variant->name])->all()"
                data-pb-variant-select />
        @endif

        <noscript><button type="submit" class="btn btn-secondary">{{ __('Apply') }}</button></noscript>
    </form>

    <div class="card table-card-shell ds-table">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">{{ __('Home Page Sections') }}</h3>
                <p class="panel-copy">{{ __('Drag to reorder sections. Click the eye icon to show or hide a section on the storefront.') }}</p>
            </div>
        </div>

        @if(empty($sections))
            @if($isCustomTheme)
                <x-tenant::empty-state :title="__('No sections found')"
                    :copy="__('Your uploaded theme does not include any section files. Add blade files under pages/home/sections/ in your theme ZIP and re-upload to use the page builder.')" />
            @else
                <x-tenant::empty-state :title="__('No sections available')" :copy="__('This theme has no registered Home page sections yet.')" />
            @endif
        @else
            <x-tenant::sortable-list id="page-builder-sections-sortable"
                :save-url="route('tenant.store.page-builder.order', ['theme_id' => $selectedThemeId, 'home_variant_id' => $selectedHomeVariantId])"
                method="POST" payload-key="ids" handle=".pb-section-row">
                @foreach($sections as $section)
                    <div data-id="{{ $section['section_key'] }}"
                        class="pb-section-row {{ $section['is_visible'] ? '' : 'pb-hidden' }}">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <line x1="4" y1="8" x2="20" y2="8" />
                            <line x1="4" y1="16" x2="20" y2="16" />
                        </svg>
                        <span class="pb-section-label">{{ __($section['label']) }}</span>
                        <x-tenant::switch
                            :checked="$section['is_visible']"
                            :action-url="route('tenant.store.page-builder.sections.visibility', ['section' => $section['section_key'], 'theme_id' => $selectedThemeId, 'home_variant_id' => $selectedHomeVariantId])"
                            action-method="PATCH"
                            payload-key="visible" />
                    </div>
                @endforeach
            </x-tenant::sortable-list>
        @endif
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/page-builder.js')
@endpush
