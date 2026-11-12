@extends('tenant.layouts.app')

@section('title', $pageTitle)

@section('content')
    <x-tenant::page-header :title="$pageTitle" :badge="__('Catalog')" :description="$pageDescription">
        <x-slot:actions>
            <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">{{ __('Back to Products') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    @if($pendingEditRequest)
        <x-tenant::alert type="warning" :title="__('Edit request pending review.')">
            {{ __('Your requested changes to the product name/description are awaiting admin approval. The fields below show your current live values.') }}
            <a href="{{ route('tenant.products.edit-requests') }}">{{ __('View requests →') }}</a>
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

        <x-tenant::card-collapse :title="__('Core Details')" :subtitle="__('Vendor pricing and assignment details.')">
            <div class="form-grid form-grid-1">
                <x-tenant::select2
                    name="central_product_id"
                    :label="__('Central Product')"
                    ajax-url="{{ route('tenant.products.central-search') }}"
                    :selected="$centralProduct ? [$centralProduct['id'] => $centralProduct['name']] : []"
                    :placeholder="__('Search central catalog products…')"
                    :disabled="(bool) $centralProduct"
                    :help="$centralProduct ? __('The central product cannot be changed once set.') : null"
                    data-central-product-select
                />
                @if($centralProduct)
                    <input type="hidden" name="central_product_id" value="{{ $centralProduct['id'] }}">
                @endif
                <x-tenant::input type="text" name="slug" :label="__('Slug')" :value="$product?->slug" />
                <div>
                    <x-tenant::input type="number" step="0.01" name="price" :label="__('Sale Price')" :value="$product ? number_format((float) ($product->default_price ?? 0), 2, '.', '') : '0.00'" />
                    @if($centralProduct)
                        <p class="entity-subtitle mt-2" data-central-current-price>{{ __('Central current price: $:currentprice', ['currentprice' => number_format((float) $centralProduct['current_price'], 2)]) }}</p>
                    @else
                        <p class="entity-subtitle mt-2" data-central-current-price hidden></p>
                    @endif
                </div>
                <x-tenant::select2 name="category_ids" :label="__('Categories')" multiple tree :placeholder="__('Search and select categories')"
                    :options="collect($categoryTree)->map(fn ($row) => ['value' => $row['id'], 'label' => $row['name'], 'level' => $row['depth']])->all()"
                    :selected="$product?->categories->pluck('id')->all() ?? []" />
                <x-tenant::select2 name="badge_ids" :label="__('Badges')" multiple :placeholder="__('Search and select badges')"
                    :options="$badges->mapWithKeys(fn ($badge) => [$badge->id => __(ucfirst(str_replace('-', ' ', $badge->text)))])->all()"
                    :selected="$product?->badges->pluck('id')->all() ?? []" />
                <x-tenant::switch name="active" :label="__('Product is active')" :checked="$product?->active ?? true" />
                <x-tenant::switch name="featured" :label="__('Product is featured')" :checked="$product?->featured ?? false" />
            </div>
        </x-tenant::card-collapse>

        <div data-central-product-panels {{ $centralProduct ? '' : 'hidden' }}>
            <x-tenant::card-collapse :title="__('Central Product Snapshot')" :subtitle="__('Read-only product details synced from the central catalog.')">
                <div class="form-grid form-grid-2" data-central-snapshot-body>
                    @include('tenant.pages.catalog.products._central-snapshot', ['centralProduct' => $centralProduct])
                </div>
            </x-tenant::card-collapse>

            <x-tenant::card-collapse :title="__('Synced Variations')" :subtitle="__('Central variant price and image are read-only. Only vendor sell price is editable.')">
                <div data-variants-body>
                    @include('tenant.pages.catalog.products._variants-table', ['variants' => $variants])
                </div>
            </x-tenant::card-collapse>

            {{-- PANELS-UPDATES-VARIANT-MARGIN: delegated handlers (the table is re-rendered via AJAX when the central
                 product changes, so listeners live on the stable wrapper). margin% <-> sell price over the central price. --}}
            <script>
            (function () {
                var body = document.querySelector('[data-variants-body]');
                if (!body || body.dataset.marginBound) return;
                body.dataset.marginBound = '1';

                var round = function (n) { return Math.round(n * 100) / 100; };
                var row = function (el) { return el.closest('tr'); };

                function syncFromMargin(input) {
                    var real = parseFloat(input.dataset.realPrice) || 0;
                    var sell = row(input).querySelector('[data-sell-input]');
                    var m = parseFloat(input.value);
                    if (sell && !isNaN(m)) sell.value = round(real * (1 + m / 100)).toFixed(2);
                }
                function syncFromSell(input) {
                    var real = parseFloat(input.dataset.realPrice) || 0;
                    var margin = row(input).querySelector('[data-margin-input]');
                    var s = parseFloat(input.value);
                    if (margin && real > 0 && !isNaN(s)) margin.value = round((s - real) / real * 100).toFixed(2);
                }

                body.addEventListener('input', function (e) {
                    if (e.target.matches('[data-margin-input]')) syncFromMargin(e.target);
                    else if (e.target.matches('[data-sell-input]')) syncFromSell(e.target);
                });

                body.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-bulk-margin-apply]');
                    if (!btn) return;
                    var value = parseFloat((body.querySelector('[data-bulk-margin-input]') || {}).value);
                    if (isNaN(value) || value < 0) return;
                    body.querySelectorAll('[data-margin-input]').forEach(function (input) {
                        input.value = round(value).toFixed(2);
                        syncFromMargin(input);
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                });
            })();
            </script>
        </div>

        <x-tenant::card-collapse :title="__('Translations')" :subtitle="__('Storefront copy for each active tenant language.')">
            <x-tenant::locale-tabs :languages="$languages" :active="$activeLocale">
                @foreach($languages as $language)
                    <x-tenant::locale-pane :code="$language->code" :active="$language->code === $activeLocale">
                        <div class="form-grid">
                            <x-tenant::input name="translations.{{ $language->code }}.name" :label="__('Name')" :value="$translations[$language->code]['name'] ?? ''" />
                            <x-tenant::input name="translations.{{ $language->code }}.meta_keywords" :label="__('Meta Keywords')" :value="$translations[$language->code]['meta_keywords'] ?? ''" />
                            <x-tenant::textarea name="translations.{{ $language->code }}.meta_description" :label="__('Meta Description')" rows="3" :placeholder="__('SEO-friendly page description (≤ 160 chars)')" :value="$translations[$language->code]['meta_description'] ?? ''" />
                            <x-tenant::editor name="translations.{{ $language->code }}.description" :label="__('Description')" :height="320" :value="$translations[$language->code]['description'] ?? ''" />
                        </div>
                    </x-tenant::locale-pane>
                @endforeach
            </x-tenant::locale-tabs>
        </x-tenant::card-collapse>

        <div class="page-actions compact-actions justify-end">
            <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">{{ __('Back to Products') }}</a>
            <x-tenant::submit>{{ __('Save Product') }}</x-tenant::submit>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/product-form.js')
@endpush
