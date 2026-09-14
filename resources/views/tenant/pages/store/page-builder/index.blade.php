@extends('tenant.layouts.app')

@section('title', 'Page Builder')

@section('content')
    <x-tenant::page-header title="Page Builder" badge="Storefront" description="Reorder and show/hide sections on your storefront Home page." />

    <form method="GET" action="{{ route('tenant.store.page-builder') }}" class="pb-switcher-form section-gap" data-pb-switcher-form>
        <x-tenant::select name="theme" label="Theme" :value="$selectedThemeId"
            :options="$themes->map(fn ($theme) => ['value' => $theme->id, 'label' => $theme->name])->all()"
            data-pb-theme-select />

        @if($availableVariants->isNotEmpty())
            <x-tenant::select name="variant" label="Home Variant" :value="$selectedHomeVariantId"
                placeholder="Theme default"
                :options="$availableVariants->map(fn ($variant) => ['value' => $variant->id, 'label' => $variant->name])->all()"
                data-pb-variant-select />
        @endif

        <noscript><button type="submit" class="btn btn-secondary">Apply</button></noscript>
    </form>

    <div class="card table-card-shell">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Home Page Sections</h3>
                <p class="panel-copy">Drag to reorder sections. Click the eye icon to show or hide a section on the storefront.</p>
            </div>
        </div>

        @if(empty($sections))
            <x-tenant::empty-state title="No sections available" copy="This theme has no registered Home page sections yet." />
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
                        <span class="pb-section-label">{{ $section['label'] }}</span>
                        <x-tenant::switch
                            :checked="$section['is_visible']"
                            action-url="{{ route('tenant.store.page-builder.sections.visibility', ['section' => $section['section_key'], 'theme_id' => $selectedThemeId, 'home_variant_id' => $selectedHomeVariantId]) }}"
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
