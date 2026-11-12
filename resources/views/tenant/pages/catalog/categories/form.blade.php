@extends('tenant.layouts.app')

@section('title', $pageTitle)

@section('content')
    <x-tenant::page-header :title="$pageTitle" :badge="__('Catalog')" :description="$pageDescription">
        <x-slot:actions>
            <a href="{{ route('tenant.categories.index') }}" class="btn btn-secondary">{{ __('Back to Categories') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::form
        :action="$category ? route('tenant.categories.update', $category) : route('tenant.categories.store')"
        :method="$category ? 'PUT' : 'POST'"
        :validate="$category ? route('tenant.categories.validate.update', $category) : route('tenant.categories.validate')"
        success="redirect"
        files
    >
        <input type="hidden" name="active_locale" value="{{ $activeLocale }}" data-locale-input>

        <x-tenant::card-collapse :title="__('Category Details')" :subtitle="__('Hierarchy, state, and storefront order.')">
            <div class="form-grid form-grid-2">
                <x-tenant::select name="central_category_id" :label="__('Central Category')" disabled>
                    @if($centralCategory)
                        <option value="{{ $centralCategory['id'] }}" selected>{{ $centralCategory['name'] }} (#{{ $centralCategory['id'] }})</option>
                    @else
                        <option value="" selected>{{ __('No linked central category') }}</option>
                    @endif
                </x-tenant::select>
                <input type="hidden" name="central_category_id" value="{{ $centralCategory['id'] ?? '' }}">

                <x-tenant::select2 name="parent_id" :label="__('Parent Category')" :placeholder="__('Root')" :options="$parents" :value="$category?->parent_id" />
                <x-tenant::input type="number" name="order_number" :label="__('Order Number')" :value="$category?->order_number ?? 0" min="0" />
                <x-tenant::switch name="active" :label="__('Category is active')" :checked="$category?->active ?? true" />
                <x-tenant::switch name="featured" :label="__('Category is featured')" :checked="$category?->featured ?? false" />
            </div>
        </x-tenant::card-collapse>

        <x-tenant::card-collapse :title="__('Category Image')" :subtitle="__('Upload a thumbnail for this category. Overrides any central category image.')">
            <div class="form-grid form-grid-2">
                <x-tenant::image-upload name="thumb" :label="__('Category Image')" :current="$currentThumbUrl ?? ($centralCategory['image_url'] ?? null)" :removable="(bool) $currentThumbUrl" :help="__('JPEG, PNG, WebP — max 4 MB.')" />
            </div>
        </x-tenant::card-collapse>

        @if($centralCategory)
            <x-tenant::card-collapse :title="__('Linked Central Category')" :subtitle="__('Read-only reference from the central catalog.')" :open="false">
                <div class="form-grid form-grid-2">
                    <div>
                        <label class="field-label">{{ __('Central Image') }}</label>
                        @if(!empty($centralCategory['image_url']))
                            <img src="{{ $centralCategory['image_url'] }}" alt="{{ $centralCategory['name'] }}" class="entity-thumb-lg">
                        @else
                            <div class="entity-thumb-lg entity-thumb-empty">{{ __('No central image') }}</div>
                        @endif
                    </div>
                    <div class="page-stack">
                        <div>
                            <div class="entity-title">{{ $centralCategory['name'] }}</div>
                            <div class="entity-subtitle">/{{ $centralCategory['slug'] }} &middot; {{ $centralCategory['status'] }}</div>
                        </div>
                        <div class="form-grid">
                            <div>
                                <label class="field-label">{{ __('Central Parent') }}</label>
                                <div class="field-control-static">{{ $centralCategory['parent_name'] ?? __('Root') }}</div>
                            </div>
                            <div>
                                <label class="field-label">{{ __('Featured State') }}</label>
                                <div class="field-control-static">{{ !empty($centralCategory['featured']) ? __('Featured') : __('Standard') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-tenant::card-collapse>
        @endif

        <x-tenant::card-collapse :title="__('Translations')" :subtitle="__('Localized storefront content for the selected language.')">
            <x-tenant::locale-tabs :languages="$languages" :active="$activeLocale">
                @foreach($languages as $language)
                    <x-tenant::locale-pane :code="$language->code" :active="$language->code === $activeLocale">
                        <div class="form-grid">
                            <x-tenant::input name="translations.{{ $language->code }}.name" :label="__('Name')" :value="$translations[$language->code]['name'] ?? ''" />
                            <x-tenant::input name="translations.{{ $language->code }}.slug" :label="__('Slug')" placeholder="auto-generated-from-name" :value="$translations[$language->code]['slug'] ?? ''" />
                            <x-tenant::input name="translations.{{ $language->code }}.meta_keywords" :label="__('Meta Keywords')" :value="$translations[$language->code]['meta_keywords'] ?? ''" />
                            <x-tenant::input name="translations.{{ $language->code }}.meta_description" :label="__('Meta Description')" :value="$translations[$language->code]['meta_description'] ?? ''" />
                            <x-tenant::textarea name="translations.{{ $language->code }}.description" :label="__('Description')" rows="8" :value="$translations[$language->code]['description'] ?? ''" />
                        </div>
                    </x-tenant::locale-pane>
                @endforeach
            </x-tenant::locale-tabs>
        </x-tenant::card-collapse>

        <div class="page-actions compact-actions justify-end">
            <a href="{{ route('tenant.categories.index') }}" class="btn btn-secondary">{{ __('Back to Categories') }}</a>
            <x-tenant::submit>{{ __('Save Category') }}</x-tenant::submit>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/category-form.js')
@endpush
