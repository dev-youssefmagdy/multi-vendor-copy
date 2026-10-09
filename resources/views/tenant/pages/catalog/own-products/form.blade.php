@extends('tenant.layouts.app')

@section('title', $pageTitle)

@php
    $arrowLeft = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h16M4 12l6-6M4 12l6 6"/></svg>';
    $uploadIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 13v8M8.5 16.5 12 13l3.5 3.5"/><path d="M7 18.5A5 5 0 1 1 8.2 8.6 6 6 0 0 1 19.6 11a4 4 0 0 1-2.1 7.5"/></svg>';

    // Countries the product is sold in (OwnProductController::COUNTRY_OPTIONS), posted as
    // countries[] ISO2 codes and saved as central ids in products.allowed_country_ids.
    $selectedCountries = old('countries', $selectedCountries);
@endphp

@section('content')
    <div class="op-page">
    <x-tenant::page-header :title="$pageTitle" :description="$pageDescription">
        <x-slot:actions>
            <a href="{{ route('tenant.own-products.index') }}" class="btn btn-secondary op-back">{!! $arrowLeft !!} Back to products</a>
            <button type="submit" form="own-product-form" class="btn btn-primary op-save">Save product</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::form
        :action="$product ? route('tenant.own-products.update', $product) : route('tenant.own-products.store')"
        :method="$product ? 'PUT' : 'POST'"
        :validate="$product ? route('tenant.own-products.validate.update', $product) : route('tenant.own-products.validate')"
        success="redirect"
        files
        id="own-product-form"
    >
        {{-- ── Basics ─────────────────────────────────────────────────────── --}}
        <x-tenant::card title="Basics" subtitle="Identifiers, publication state, and catalog flags." class="op-card">
            <div class="op-stack">
                <div class="form-grid op-grid-4">
                    <x-tenant::input name="sku" label="SKU" placeholder="PRD 001" required :value="$productData['sku'] ?? ''" />
                    <x-tenant::input name="slug" label="Slug" placeholder="Auto-generated" :value="$productData['slug'] ?? ''" />
                    <x-tenant::input type="number" name="weight_grams" label="Weight (grams)" min="0" placeholder="0" :value="$productData['weight_grams'] ?? ''" />
                    <x-tenant::select2 name="category_ids" multiple label="Category" placeholder="Select categories" :options="$categoryOptions" :value="$categoryIds" />
                </div>

                <div class="op-countries" data-op-countries>
                    <label class="op-search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="8.75"/><path d="M17.5 17.5l4.25 4.25"/></svg>
                        <input type="search" placeholder="Search" aria-label="Search countries" data-op-country-search>
                    </label>
                    <ul class="op-country-list" role="list">
                        <li class="op-country-all-item">
                            <label class="ds-check op-country op-country-all">
                                <input type="checkbox" data-op-country-all @checked(count($countryOptions) && count(array_diff(array_keys($countryOptions), $selectedCountries)) === 0)>
                                <span>Select all countries</span>
                            </label>
                        </li>
                        @foreach($countryOptions as $code => $country)
                            <li data-op-country="{{ strtolower($country) }}">
                                <label class="ds-check op-country">
                                    <input type="checkbox" name="countries[]" value="{{ $code }}" @checked(in_array($code, $selectedCountries, true))>
                                    <x-tenant::flag :code="$code" class="op-flag" />
                                    <span>{{ $country }}</span>
                                </label>
                            </li>
                        @endforeach
                        <li class="op-country-empty" data-op-country-empty hidden>No countries match your search.</li>
                    </ul>
                </div>

                @if($languages->count())
                    <x-tenant::locale-tabs :languages="$languages" :active="$defaultLocale">
                        @foreach($languages as $language)
                            <x-tenant::locale-pane :code="$language->code" :active="$language->code === $defaultLocale">
                                <div class="form-grid op-grid-3">
                                    <x-tenant::input
                                        name="translations.{{ $language->code }}.name"
                                        label="Product name"
                                        :required="(bool) $language->is_default"
                                        :value="$translations[$language->code]['name'] ?? ''"
                                        placeholder="Localized product name"
                                    />
                                    <x-tenant::input name="translations.{{ $language->code }}.label" label="Label" placeholder="Short display label (used in UI, breadcrumbs, listings)" :value="$translations[$language->code]['label'] ?? ''" />
                                    <x-tenant::input name="translations.{{ $language->code }}.summary" label="Summary" placeholder="Short merchandising summary" :value="$translations[$language->code]['summary'] ?? ''" />
                                    <div class="op-span-all">
                                        <x-tenant::editor name="translations.{{ $language->code }}.description" label="Description" height="220" toolbar="basic" placeholder="Describe your product" :value="$translations[$language->code]['description'] ?? ''" />
                                    </div>
                                    <div class="op-span-all form-grid form-grid-2">
                                        <x-tenant::input name="translations.{{ $language->code }}.meta_keywords" label="Meta Keywords" placeholder="keyword1, keyword2, keyword3" :value="$translations[$language->code]['meta_keywords'] ?? ''" />
                                        <x-tenant::input name="translations.{{ $language->code }}.meta_description" label="Meta Description" placeholder="SEO-friendly page description (≤ 160 chars)" :value="$translations[$language->code]['meta_description'] ?? ''" />
                                    </div>
                                </div>
                            </x-tenant::locale-pane>
                        @endforeach
                    </x-tenant::locale-tabs>
                @else
                    <div class="notice-muted">Create at least one active language before managing translated product content.</div>
                @endif

                <div class="op-flags">
                    <x-tenant::checkbox name="active" label="Product is Active" :checked="$productData['active'] ?? true" wrapper-class="ds-check" />
                    <x-tenant::checkbox name="featured" label="Featured" :checked="$productData['featured'] ?? false" wrapper-class="ds-check" />
                    <x-tenant::checkbox name="manage_stock" label="Manage Stock" :checked="$productData['manage_stock'] ?? true" wrapper-class="ds-check" data-manage-stock-toggle />
                    <x-tenant::checkbox name="is_taxable" label="Taxable" :checked="$productData['is_taxable'] ?? true" wrapper-class="ds-check" />
                </div>
            </div>
        </x-tenant::card>

        {{-- ── Pricing ────────────────────────────────────────────────────── --}}
        <x-tenant::card title="Pricing" subtitle="Base pricing, promotional pricing, and stock thresholds." class="op-card">
            <div class="form-grid op-grid-3 op-numbers">
                <x-tenant::input type="number" name="base_price" label="Base Price" required min="0" step="0.01" placeholder="0" :value="$productData['base_price'] ?? '0.00'" />
                <x-tenant::input type="number" name="sale_price" label="Sale price" min="0" step="0.01" placeholder="0" :value="$productData['sale_price'] ?? ''" />
                <x-tenant::input type="number" name="cost_price" label="Cost price" min="0" step="0.01" placeholder="0" :value="$productData['cost_price'] ?? ''" />
                <div data-stock-fields>
                    <x-tenant::input type="number" name="stock" label="Stock" min="0" :value="$productData['stock'] ?? 0" />
                </div>
                <div data-stock-fields>
                    <x-tenant::input type="number" name="min_stock" label="Minimum Stock" min="0" :value="$productData['min_stock'] ?? 0" />
                </div>
            </div>
        </x-tenant::card>

        {{-- ── Primary Image + Media Gallery ──────────────────────────────── --}}
        <div class="card ds-card op-card op-media">
            <section class="op-media-col">
                <div class="op-media-head">
                    <h3 class="panel-title">Primary Image</h3>
                    <p class="panel-copy">Main product image shown on listings and the product page.</p>
                </div>

                {{-- Same fields as x-tenant::image-upload (primary_image + remove_primary_image); preview via [data-image-upload]. --}}
                <div class="t-field op-primary" data-field="primary_image">
                    <div class="t-image-upload" data-image-upload data-op-primary
                        data-expect-w="{{ config('image_dimensions.product.width') }}" data-expect-h="{{ config('image_dimensions.product.height') }}">
                        <label class="t-dropzone-drop op-drop">
                            <span class="op-drop-icon">{!! $uploadIcon !!}</span>
                            <span class="op-drop-title">Click to upload or drag and drop</span>
                            <span class="op-drop-sub">PNG, JPG or WEBP (max. 4MB)</span>
                            <input type="file" name="primary_image" id="f-primary_image" accept="image/*" class="t-dropzone-input">
                        </label>
                        <div class="op-thumbs">
                            <div class="op-thumb">
                                <img src="{{ $existingImage }}" alt="" class="t-image-upload-preview" @unless($existingImage) hidden @endunless>
                                <label class="op-thumb-remove" aria-label="Remove image">
                                    <input type="checkbox" name="remove_primary_image" value="1" data-op-primary-remove>
                                </label>
                            </div>
                        </div>
                        <p class="dimension-warning" hidden></p>
                    </div>
                    <p class="field-error" data-error-for="primary_image" role="alert" hidden></p>
                </div>
            </section>

            <section class="op-media-col">
                <div class="op-media-head">
                    <h3 class="panel-title">Media Gallery</h3>
                    <p class="panel-copy">Additional images and videos shown in the product slider. JPG, PNG, GIF, WEBP, MP4, WEBM, MOV.</p>
                </div>

                <x-tenant::dropzone
                    name="gallery_files"
                    multiple
                    sortable
                    remove-name="remove_gallery_ids"
                    order-name="gallery_order"
                    accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/quicktime"
                    label="Click to upload or drag and drop"
                    sublabel="JPG, PNG, GIF, WEBP, MP4, WEBM or MOV (max. 50MB)"
                    wrapper-class="op-gallery"
                    :expected-width="config('image_dimensions.product.width')"
                    :expected-height="config('image_dimensions.product.height')"
                    :existing="$existingGallery->map(fn ($file) => [
                        'id' => $file->id,
                        'url' => $file->full_path,
                        'name' => strtoupper($file->extension),
                        'type' => $file->file_type->value,
                    ])->all()"
                />
            </section>
        </div>

        <x-tenant::card-collapse title="Badges" subtitle="Assign badges such as Featured or Recommended — used to highlight this product on the storefront.">
            @if($badges->isEmpty())
                <div class="notice-muted">No badges available yet.</div>
            @else
                <x-tenant::select2
                    name="badge_ids"
                    multiple
                    label="Badges"
                    placeholder="Search and select badges"
                    :options="$badges->map(fn ($badge) => ['value' => $badge->id, 'label' => ucfirst(str_replace('-', ' ', $badge->text))])->all()"
                    :value="$selectedBadgeIds"
                />
            @endif
        </x-tenant::card-collapse>

        <x-tenant::card-collapse title="Product Variants" subtitle="Optional: build product variants. Each variant is a combination of variation options (e.g. Size: Small + Color: Red) with its own price, stock and image.">
            <div class="notice-muted" style="margin-bottom:16px">
                <strong>How it works:</strong> Click <em>Add Variant</em>, pick one option per variation group
                (Size, Color, …), and set this variant's own price, stock and image.
                Use <em>+ Add another group</em> to combine multiple groups per variant.
                When variants exist, per-variant stock replaces the product-level stock field.
            </div>

            <script id="variations-data" type="application/json">@json($variationsJson)</script>

            <div class="t-repeater vrow-list" data-tenant-repeater data-name="variants" data-min="0" data-sortable="true">
                <template data-repeater-template>
                    @include('tenant.pages.catalog.own-products._variant-row-fields', ['vIdx' => '__INDEX__', 'variant' => null, 'variations' => $variations])
                </template>

                <div class="t-repeater-rows" data-repeater-rows>
                    @foreach($variants as $vIdx => $variant)
                        <div class="t-repeater-row" data-repeater-index="{{ $vIdx }}">
                            @include('tenant.pages.catalog.own-products._variant-row-fields', ['vIdx' => $vIdx, 'variant' => $variant, 'variations' => $variations])
                        </div>
                    @endforeach
                </div>

                <button type="button" class="btn btn-secondary" data-repeater-add>
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add Variant
                </button>
            </div>
        </x-tenant::card-collapse>

        <x-tenant::card-collapse title="Return Policy Override" subtitle="Override the store-wide return policy for this product." :open="(bool) ($productData['return_policy_override'] ?? false)">
            <x-tenant::switch name="return_policy_override" label="Override return policy for this product" :checked="$productData['return_policy_override'] ?? false" data-return-policy-toggle />

            <div class="form-grid form-grid-2" data-return-policy-fields @unless($productData['return_policy_override'] ?? false) hidden @endunless>
                <x-tenant::switch name="is_returnable" label="This product is returnable" :checked="$productData['is_returnable'] ?? true" />
                <x-tenant::input type="number" name="return_window_days" label="Return Window (days)" min="1" max="365" :value="$productData['return_window_days'] ?? ''" />
                <x-tenant::input type="number" name="return_fee" label="Return Fee" min="0" step="0.01" :value="$productData['return_fee'] ?? ''" />
                <x-tenant::switch name="return_video_required" label="Require unboxing video for returns" :checked="$productData['return_video_required'] ?? false" />
                <div class="span-2">
                    <x-tenant::textarea name="return_conditions" label="Return Conditions" rows="4" :value="$productData['return_conditions'] ?? ''" />
                </div>
            </div>
        </x-tenant::card-collapse>

        <div class="page-actions compact-actions justify-end">
            <a href="{{ route('tenant.own-products.index') }}" class="btn btn-secondary">Back to products</a>
            <x-tenant::submit>Save product</x-tenant::submit>
        </div>
    </x-tenant::form>
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/own-product-form.js')
@endpush
