@extends('tenant.layouts.app')

@section('title', $pageTitle)

@section('content')
    <x-tenant::page-header :title="$pageTitle" badge="Catalog" :description="$pageDescription">
        <x-slot:actions>
            <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">Back to Products</a>
        </x-slot:actions>
    </x-tenant::page-header>

    @if($pendingEditRequest)
        <x-tenant::alert type="warning" title="Edit request pending review.">
            Your requested changes to the product name/description are awaiting admin approval. The fields below show your current live values.
            <a href="{{ route('tenant.products.edit-requests') }}">View requests &rarr;</a>
        </x-tenant::alert>
    @endif

    <x-tenant::form
        :action="$product ? route('tenant.products.update', $product) : route('tenant.products.store')"
        :method="$product ? 'PUT' : 'POST'"
        :validate="$product ? route('tenant.products.validate.update', $product) : route('tenant.products.validate')"
        success="redirect"
        id="product-form"
        data-central-snapshot-url-template="{{ route('tenant.products.central-snapshot', ['centralProduct' => '__ID__']) }}"
    >
        <input type="hidden" name="active_locale" value="{{ $activeLocale }}">

        <x-tenant::card-collapse title="Core Details" subtitle="Vendor pricing and assignment details.">
            <div class="form-grid form-grid-1">
                <x-tenant::select2
                    name="central_product_id"
                    label="Central Product"
                    ajax-url="{{ route('tenant.products.central-search') }}"
                    :selected="$centralProduct ? [$centralProduct['id'] => $centralProduct['name']] : []"
                    placeholder="Search central catalog products…"
                    data-central-product-select
                />
                <x-tenant::input type="text" name="slug" label="Slug" :value="$product?->slug" />
                <div>
                    <x-tenant::input type="number" step="0.01" name="price" label="Vendor Sale Price" :value="$product ? number_format((float) ($product->default_price ?? 0), 2, '.', '') : '0.00'" />
                    @if($centralProduct)
                        <p class="entity-subtitle mt-2" data-central-current-price>Central current price: ${{ number_format((float) $centralProduct['current_price'], 2) }}</p>
                    @else
                        <p class="entity-subtitle mt-2" data-central-current-price hidden></p>
                    @endif
                </div>
                <x-tenant::select2 name="category_ids" label="Categories" multiple tree placeholder="Search and select categories"
                    :options="collect($categoryTree)->map(fn ($row) => ['value' => $row['id'], 'label' => $row['name'], 'level' => $row['depth']])->all()"
                    :selected="$product?->categories->pluck('id')->all() ?? []" />
                <x-tenant::select2 name="badge_ids" label="Badges" multiple placeholder="Search and select badges"
                    :options="$badges->mapWithKeys(fn ($badge) => [$badge->id => ucfirst(str_replace('-', ' ', $badge->text))])->all()"
                    :selected="$product?->badges->pluck('id')->all() ?? []" />
                <x-tenant::switch name="active" label="Product is active" :checked="$product?->active ?? true" />
                <x-tenant::switch name="featured" label="Product is featured" :checked="$product?->featured ?? false" />
            </div>
        </x-tenant::card-collapse>

        <div data-central-product-panels {{ $centralProduct ? '' : 'hidden' }}>
            <x-tenant::card-collapse title="Central Product Snapshot" subtitle="Read-only product details synced from the central catalog.">
                <div class="form-grid form-grid-2" data-central-snapshot-body>
                    @include('tenant.pages.catalog.products._central-snapshot', ['centralProduct' => $centralProduct])
                </div>
            </x-tenant::card-collapse>

            <x-tenant::card-collapse title="Synced Variations" subtitle="Central variant price and image are read-only. Only vendor sell price is editable.">
                <div data-variants-body>
                    @include('tenant.pages.catalog.products._variants-table', ['variants' => $variants])
                </div>
            </x-tenant::card-collapse>
        </div>

        <x-tenant::card-collapse title="Translations" subtitle="Storefront copy for each active tenant language.">
            <x-tenant::locale-tabs :languages="$languages" :active="$activeLocale">
                @foreach($languages as $language)
                    <x-tenant::locale-pane :code="$language->code" :active="$language->code === $activeLocale">
                        <div class="form-grid">
                            <x-tenant::input name="translations.{{ $language->code }}.name" label="Name" :value="$translations[$language->code]['name'] ?? ''" />
                            <x-tenant::input name="translations.{{ $language->code }}.meta_keywords" label="Meta Keywords" :value="$translations[$language->code]['meta_keywords'] ?? ''" />
                            <x-tenant::textarea name="translations.{{ $language->code }}.meta_description" label="Meta Description" rows="3" placeholder="SEO-friendly page description (≤ 160 chars)" :value="$translations[$language->code]['meta_description'] ?? ''" />
                            <x-tenant::editor name="translations.{{ $language->code }}.description" label="Description" :height="320" :value="$translations[$language->code]['description'] ?? ''" />
                        </div>
                    </x-tenant::locale-pane>
                @endforeach
            </x-tenant::locale-tabs>
        </x-tenant::card-collapse>

        <div class="page-actions compact-actions justify-end">
            <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">Back to Products</a>
            <x-tenant::submit>Save Product</x-tenant::submit>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/product-form.js')
@endpush
