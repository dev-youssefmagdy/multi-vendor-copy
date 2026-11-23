@extends('tenant.layouts.app')

@section('title', $pageTitle)

@section('content')
    <x-tenant::page-header :title="$pageTitle" :badge="__('Storefront')" :description="$pageDescription">
        <x-slot:actions>
            <a href="{{ route('tenant.store.pages') }}" class="btn btn-secondary">{{ __('Back to Pages') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::form
        :action="$page ? route('tenant.store.pages.update', $page) : route('tenant.store.pages.store')"
        :method="$page ? 'PUT' : 'POST'"
        :validate="$page ? route('tenant.store.pages.validate.update', $page) : route('tenant.store.pages.validate')"
        success="redirect"
    >
        @if($platformLocked ?? false)
            <x-tenant::alert type="info" :title="__('Platform page')">{{ __('This page is managed by the platform. Its slug and its texts in the platform languages are read-only; you can still change its visibility and the texts of your own languages.') }}</x-tenant::alert>
        @endif

        <x-tenant::card-collapse :title="__('Page Settings')" :subtitle="__('Slug and visibility.')" :open="true">
            <div class="form-grid form-grid-2">
                @if($platformLocked ?? false)
                    <x-tenant::input name="slug" :label="__('Slug')" :value="$page?->slug" :readonly="true" />
                @else
                    <x-tenant::input name="slug" :label="__('Slug')" :value="$page?->slug" slug-from="translations[{{ $activeLocale }}][title]" placeholder="auto-generated-from-title" />
                @endif
                <x-tenant::switch name="active" :label="__('Page is active')" :checked="$page?->active ?? true" />
            </div>
        </x-tenant::card-collapse>

        <x-tenant::card-collapse :title="__('Translations')" :subtitle="__('Switch locale tabs to manage translated page titles and content.')" :open="true">
            <x-tenant::locale-tabs :languages="$languages" :active="$activeLocale">
                @foreach($languages as $language)
                    <x-tenant::locale-pane :code="$language->code" :active="$language->code === $activeLocale">
                        @php($localeLocked = in_array($language->code, $lockedLocales ?? [], true))
                        @if($localeLocked)
                            {{-- Dedup phase 6: platform text, shown read-only (the save path ignores these locales). --}}
                            <p class="text-muted">{{ __('Managed by the platform in this language: it cannot be changed here.') }}</p>
                            <div class="form-grid">
                                <x-tenant::input name="translations.{{ $language->code }}.title" :label="__('Title')" :value="$translations[$language->code]['title'] ?? ''" :readonly="true" />
                                <x-tenant::textarea name="translations.{{ $language->code }}.body" :label="__('Content')" rows="12" :value="$translations[$language->code]['body'] ?? ''" :readonly="true" />
                            </div>
                        @else
                        <div class="form-grid">
                            <x-tenant::input name="translations.{{ $language->code }}.title" :label="__('Title')" :value="$translations[$language->code]['title'] ?? ''" />
                            <x-tenant::editor name="translations.{{ $language->code }}.body" :label="__('Content')" :value="$translations[$language->code]['body'] ?? ''" :height="520" />
                        </div>
                        @endif
                    </x-tenant::locale-pane>
                @endforeach
            </x-tenant::locale-tabs>
        </x-tenant::card-collapse>

        <div class="page-actions compact-actions justify-end">
            <a href="{{ route('tenant.store.pages') }}" class="btn btn-secondary">{{ __('Back to Pages') }}</a>
            <x-tenant::submit>{{ __('Save Page') }}</x-tenant::submit>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/page-form.js')
@endpush
