@extends('tenant.layouts.app')

@section('title', $pageTitle)

@section('content')
    <x-tenant::page-header :title="$pageTitle" badge="Own Catalog" :description="$pageDescription">
        <x-slot:actions>
            <a href="{{ route('tenant.own-products.index') }}" class="btn btn-secondary">Back to Products</a>
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
        <x-tenant::card-collapse title="Basics" subtitle="Identifiers, publication state, and catalog flags.">
            <div class="form-grid form-grid-3">
                <x-tenant::input name="sku" label="SKU" placeholder="PRD-001" :value="$productData['sku'] ?? ''" />
                <x-tenant::input name="slug" label="Slug" placeholder="auto-generated-if-empty" :value="$productData['slug'] ?? ''" />
                <x-tenant::input type="number" name="weight_grams" label="Weight (grams)" min="0" placeholder="0" :value="$productData['weight_grams'] ?? ''" />

                <div class="field-flag-grid span-3">
                    <x-tenant::switch name="active" label="Product is Active" :checked="$productData['active'] ?? true" />
                    <x-tenant::switch name="featured" label="Featured" :checked="$productData['featured'] ?? false" />
                    <x-tenant::switch name="manage_stock" label="Manage Stock" :checked="$productData['manage_stock'] ?? true" data-manage-stock-toggle />
                    <x-tenant::switch name="is_taxable" label="Taxable" :checked="$productData['is_taxable'] ?? true" />
                </div>
            </div>
        </x-tenant::card-collapse>

        <x-tenant::card-collapse title="Primary Image" subtitle="Main product image shown on listings and the product page.">
            <x-tenant::image-upload
                name="primary_image"
                :current="$existingImage"
                :removable="(bool) $existingImage"
                :expected-width="config('image_dimensions.product.width')"
                :expected-height="config('image_dimensions.product.height')"
                help="PNG, JPG, WEBP up to 5MB."
            />
        </x-tenant::card-collapse>

        <x-tenant::card-collapse title="Media Gallery" subtitle="Additional images and videos shown in the product slider. JPG, PNG, GIF, WEBP, MP4, WEBM, MOV.">
            <x-tenant::dropzone
                name="gallery_files"
                multiple
                sortable
                remove-name="remove_gallery_ids"
                order-name="gallery_order"
                accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/quicktime"
                label="Upload images or videos"
                sublabel="JPG, PNG, GIF, WEBP, MP4, WEBM, MOV — multiple allowed"
                :expected-width="config('image_dimensions.product.width')"
                :expected-height="config('image_dimensions.product.height')"
                :existing="$existingGallery->map(fn ($file) => [
                    'id' => $file->id,
                    'url' => $file->full_path,
                    'name' => strtoupper($file->extension),
                    'type' => $file->file_type->value,
                ])->all()"
            />
        </x-tenant::card-collapse>

        <x-tenant::card-collapse title="Translations" subtitle="Multilingual product name, summary, description, and SEO meta fields.">
            @if($languages->count())
                <x-tenant::locale-tabs :languages="$languages" :active="$defaultLocale">
                    @foreach($languages as $language)
                        <x-tenant::locale-pane :code="$language->code" :active="$language->code === $defaultLocale">
                            <div class="form-grid form-grid-2">
                                <div class="span-2">
                                    <x-tenant::input
                                        name="translations.{{ $language->code }}.name"
                                        :label="'Name (' . strtoupper($language->code) . ')' . ($language->is_default ? ' *' : '')"
                                        :value="$translations[$language->code]['name'] ?? ''"
                                        placeholder="Localized product name"
                                    />
                                </div>
                                <x-tenant::input name="translations.{{ $language->code }}.label" label="Label" placeholder="Short display label (used in UI, breadcrumbs, listings)" :value="$translations[$language->code]['label'] ?? ''" />
                                <x-tenant::input name="translations.{{ $language->code }}.summary" label="Summary" placeholder="Short merchandising summary" :value="$translations[$language->code]['summary'] ?? ''" />
                                <div class="span-2">
                                    <x-tenant::editor name="translations.{{ $language->code }}.description" label="Description" height="320" :value="$translations[$language->code]['description'] ?? ''" />
                                </div>
                                <x-tenant::input name="translations.{{ $language->code }}.meta_keywords" label="Meta Keywords" placeholder="keyword1, keyword2, keyword3" :value="$translations[$language->code]['meta_keywords'] ?? ''" />
                                <x-tenant::textarea name="translations.{{ $language->code }}.meta_description" label="Meta Description" rows="3" placeholder="SEO-friendly page description (≤ 160 chars)" :value="$translations[$language->code]['meta_description'] ?? ''" />
                            </div>
                        </x-tenant::locale-pane>
                    @endforeach
                </x-tenant::locale-tabs>
            @else
                <div class="notice-muted">Create at least one active language before managing translated product content.</div>
            @endif
        </x-tenant::card-collapse>

        <x-tenant::card-collapse title="Pricing &amp; Inventory" subtitle="Base pricing, promotional pricing, and stock thresholds.">
            <div class="form-grid form-grid-2">
                <x-tenant::input type="number" name="base_price" label="Base Price" required min="0" step="0.01" :value="$productData['base_price'] ?? '0.00'" />
                <x-tenant::input type="number" name="sale_price" label="Sale Price" min="0" step="0.01" :value="$productData['sale_price'] ?? ''" />
                <x-tenant::input type="number" name="cost_price" label="Cost Price" min="0" step="0.01" :value="$productData['cost_price'] ?? ''" />
                <div data-stock-fields>
                    <x-tenant::input type="number" name="stock" label="Stock" min="0" :value="$productData['stock'] ?? 0" />
                </div>
                <div data-stock-fields>
                    <x-tenant::input type="number" name="min_stock" label="Minimum Stock" min="0" :value="$productData['min_stock'] ?? 0" />
                </div>
            </div>
        </x-tenant::card-collapse>

        <x-tenant::card-collapse title="Categories" subtitle="Assign one or more storefront categories.">
            <x-tenant::select2 name="category_ids" multiple label="Categories" placeholder="Select categories" :options="$categoryOptions" :value="$categoryIds" />
        </x-tenant::card-collapse>

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
            <a href="{{ route('tenant.own-products.index') }}" class="btn btn-secondary">Back to Products</a>
            <x-tenant::submit>Save Product</x-tenant::submit>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/own-product-form.js')
@endpush
