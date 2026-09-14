@extends('tenant.layouts.app')

@section('title', $pageTitle)

@section('content')
    <x-tenant::page-header :title="$pageTitle" badge="Storefront" :description="$pageDescription">
        <x-slot:actions>
            <a href="{{ route('tenant.store.pages') }}" class="btn btn-secondary">Back to Pages</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::form
        :action="$page ? route('tenant.store.pages.update', $page) : route('tenant.store.pages.store')"
        :method="$page ? 'PUT' : 'POST'"
        :validate="$page ? route('tenant.store.pages.validate.update', $page) : route('tenant.store.pages.validate')"
        success="redirect"
    >
        <x-tenant::card-collapse title="Page Settings" subtitle="Slug and visibility." :open="true">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="slug" label="Slug" :value="$page?->slug" slug-from="translations[{{ $activeLocale }}][title]" placeholder="auto-generated-from-title" />
                <x-tenant::switch name="active" label="Page is active" :checked="$page?->active ?? true" />
            </div>
        </x-tenant::card-collapse>

        <x-tenant::card-collapse title="Translations" subtitle="Switch locale tabs to manage translated page titles and content." :open="true">
            <x-tenant::locale-tabs :languages="$languages" :active="$activeLocale">
                @foreach($languages as $language)
                    <x-tenant::locale-pane :code="$language->code" :active="$language->code === $activeLocale">
                        <div class="form-grid">
                            <x-tenant::input name="translations.{{ $language->code }}.title" label="Title" :value="$translations[$language->code]['title'] ?? ''" />
                            <x-tenant::editor name="translations.{{ $language->code }}.body" label="Content" :value="$translations[$language->code]['body'] ?? ''" :height="520" />
                        </div>
                    </x-tenant::locale-pane>
                @endforeach
            </x-tenant::locale-tabs>
        </x-tenant::card-collapse>

        <div class="page-actions compact-actions justify-end">
            <a href="{{ route('tenant.store.pages') }}" class="btn btn-secondary">Back to Pages</a>
            <x-tenant::submit>Save Page</x-tenant::submit>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/page-form.js')
@endpush
