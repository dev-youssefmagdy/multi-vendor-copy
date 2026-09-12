@extends('tenant.layouts.app')

@section('title', $pageTitle)

@section('content')
    <x-tenant::page-header :title="$pageTitle" badge="Catalog" :description="$pageDescription">
        <x-slot:actions>
            <a href="{{ route('tenant.categories.index') }}" class="btn btn-secondary">Back to Categories</a>
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

        <x-tenant::card-collapse title="Category Details" subtitle="Hierarchy, state, and storefront order.">
            <div class="form-grid form-grid-2">
                <x-tenant::select name="central_category_id" label="Central Category" disabled>
                    @if($centralCategory)
                        <option value="{{ $centralCategory['id'] }}" selected>{{ $centralCategory['name'] }} (#{{ $centralCategory['id'] }})</option>
                    @else
                        <option value="" selected>No linked central category</option>
                    @endif
                </x-tenant::select>
                <input type="hidden" name="central_category_id" value="{{ $centralCategory['id'] ?? '' }}">

                <x-tenant::select2 name="parent_id" label="Parent Category" placeholder="Root" :options="$parents" :value="$category?->parent_id" />
                <x-tenant::input type="number" name="order_number" label="Order Number" :value="$category?->order_number ?? 0" min="0" />
                <x-tenant::switch name="active" label="Category is active" :checked="$category?->active ?? true" />
                <x-tenant::switch name="featured" label="Category is featured" :checked="$category?->featured ?? false" />
            </div>
        </x-tenant::card-collapse>

        <x-tenant::card-collapse title="Category Image" subtitle="Upload a thumbnail for this category. Overrides any central category image.">
            <div class="form-grid form-grid-2">
                <x-tenant::image-upload name="thumb" label="Category Image" :current="$currentThumbUrl ?? ($centralCategory['image_url'] ?? null)" :removable="(bool) $currentThumbUrl" help="JPEG, PNG, WebP — max 4 MB." />
            </div>
        </x-tenant::card-collapse>

        @if($centralCategory)
            <x-tenant::card-collapse title="Linked Central Category" subtitle="Read-only reference from the central catalog." :open="false">
                <div class="form-grid form-grid-2">
                    <div>
                        <label class="field-label">Central Image</label>
                        @if(!empty($centralCategory['image_url']))
                            <img src="{{ $centralCategory['image_url'] }}" alt="{{ $centralCategory['name'] }}" class="entity-thumb-lg">
                        @else
                            <div class="entity-thumb-lg entity-thumb-empty">No central image</div>
                        @endif
                    </div>
                    <div class="page-stack">
                        <div>
                            <div class="entity-title">{{ $centralCategory['name'] }}</div>
                            <div class="entity-subtitle">/{{ $centralCategory['slug'] }} &middot; {{ $centralCategory['status'] }}</div>
                        </div>
                        <div class="form-grid">
                            <div>
                                <label class="field-label">Central Parent</label>
                                <div class="field-control-static">{{ $centralCategory['parent_name'] ?? 'Root' }}</div>
                            </div>
                            <div>
                                <label class="field-label">Featured State</label>
                                <div class="field-control-static">{{ !empty($centralCategory['featured']) ? 'Featured' : 'Standard' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-tenant::card-collapse>
        @endif

        <x-tenant::card-collapse title="Translations" subtitle="Localized storefront content for the selected language.">
            <x-tenant::locale-tabs :languages="$languages" :active="$activeLocale">
                @foreach($languages as $language)
                    <x-tenant::locale-pane :code="$language->code" :active="$language->code === $activeLocale">
                        <div class="form-grid">
                            <x-tenant::input name="translations.{{ $language->code }}.name" label="Name" :value="$translations[$language->code]['name'] ?? ''" />
                            <x-tenant::input name="translations.{{ $language->code }}.slug" label="Slug" placeholder="auto-generated-from-name" :value="$translations[$language->code]['slug'] ?? ''" />
                            <x-tenant::input name="translations.{{ $language->code }}.meta_keywords" label="Meta Keywords" :value="$translations[$language->code]['meta_keywords'] ?? ''" />
                            <x-tenant::input name="translations.{{ $language->code }}.meta_description" label="Meta Description" :value="$translations[$language->code]['meta_description'] ?? ''" />
                            <x-tenant::textarea name="translations.{{ $language->code }}.description" label="Description" rows="8" :value="$translations[$language->code]['description'] ?? ''" />
                        </div>
                    </x-tenant::locale-pane>
                @endforeach
            </x-tenant::locale-tabs>
        </x-tenant::card-collapse>

        <div class="page-actions compact-actions justify-end">
            <a href="{{ route('tenant.categories.index') }}" class="btn btn-secondary">Back to Categories</a>
            <x-tenant::submit>Save Category</x-tenant::submit>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/category-form.js')
@endpush
