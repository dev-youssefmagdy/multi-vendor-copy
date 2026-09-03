<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryScope;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductTenantAssignment;
use App\Models\Tenant;
use App\Models\Variation;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use App\Services\TenantNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function create()
    {
        return view('admin.product.add-edit-product', $this->viewData());
    }

    public function edit(Product $product)
    {
        return view('admin.product.add-edit-product', $this->viewData($product));
    }

    public function store(
        Request $request,
        ProductService $service,
        CentralCatalogTenantSyncService $tenantSyncService,
        TenantNotificationService $notifier
    ) {
        return $this->processForm($request, null, $service, $tenantSyncService, $notifier);
    }

    public function update(
        Request $request,
        Product $product,
        ProductService $service,
        CentralCatalogTenantSyncService $tenantSyncService,
        TenantNotificationService $notifier
    ) {
        return $this->processForm($request, $product, $service, $tenantSyncService, $notifier);
    }

    // ──────────────────────────────────────────────────────────────────────────

    protected function processForm(
        Request $request,
        ?Product $product,
        ProductService $service,
        CentralCatalogTenantSyncService $tenantSyncService,
        TenantNotificationService $notifier
    ) {
        $request->validate($this->rules($product));

        // Only leaf categories (no children) may be submitted.
        $submittedCatIds = array_filter((array) $request->input('category_ids', []), fn($id) => filled($id));
        if (!empty($submittedCatIds) && Category::whereIn('id', $submittedCatIds)->whereHas('children')->exists()) {
            return back()
                ->withErrors(['category_ids' => 'Only the deepest (leaf) category may be selected.'])
                ->withInput();
        }

        $normalizedVariants = $this->normalizeVariants($request);

        if (!$this->validateVariantUniqueness($normalizedVariants)) {
            return back()
                ->withErrors(['variants' => 'Duplicate variant combination — each variant must be unique.'])
                ->withInput();
        }

        $savedProduct = $service->save([
            'sku' => $request->input('sku'),
            'slug' => $request->input('slug') ?: null,
            'status' => $request->input('status'),
            'delivery_scope' => $request->input('delivery_scope', 'all_zones'),
            'base_price' => $request->input('base_price'),
            'sale_price' => $request->input('sale_price') ?: null,
            'cost_price' => $request->input('cost_price') ?: null,
            'stock' => (int) $request->input('stock', 0),
            'min_stock' => (int) $request->input('min_stock', 0),
            'manage_stock' => $request->boolean('manage_stock'),
            'is_taxable' => $request->boolean('is_taxable'),
            'factory' => $request->input('factory') ?: null,
            'weight_grams' => $request->input('weight_grams') ?: null,
            'category_ids' => $request->input('category_ids', []),
            'variants' => $normalizedVariants,
            'shipping_zone_ids' => $request->input('shipping_zone_ids', []),
            'translations' => $request->input('translations', []),
            'remove_primary_image' => $request->boolean('remove_primary_image'),
            'primary_image' => $request->file('primary_image'),
            'gallery_files' => $request->file('gallery_files', []),
            'remove_gallery_ids' => $request->input('remove_gallery_ids', []),
        ], $product);


        $tenantIds = [];
        foreach (Tenant::all() as $tenant) {
            tenancy()->initialize($tenant);
            $exists = \App\Models\Tenant\Product::query()->where('central_product_id', $savedProduct->id)->exists();
            if ($exists) {
                $tenantIds[] = $tenant->id;
            }
            tenancy()->end();
        }

        if (count($tenantIds) > 0) {
            $tenantSyncService->syncProductToAssignedTenants($savedProduct, $tenantIds);
        }

        // ── Tenant Assignments ──────────────────────────────────────────────
        $previousAssignedIds = ProductTenantAssignment::where('product_id', $savedProduct->id)
            ->pluck('tenant_id')
            ->all();

        $newTenantIds = $request->boolean('assign_to_all_tenants')
            ? Tenant::query()->pluck('id')->all()
            : array_filter((array) $request->input('assigned_tenant_ids', []), filled(...));

        ProductTenantAssignment::where('product_id', $savedProduct->id)
            ->whereNotIn('tenant_id', $newTenantIds)
            ->delete();

        foreach ($newTenantIds as $tenantId) {
            ProductTenantAssignment::firstOrCreate(
                ['product_id' => $savedProduct->id, 'tenant_id' => $tenantId],
                ['synced_at' => null]
            );

            if (!in_array($tenantId, $previousAssignedIds)) {
                $notifier->notifyById(
                    $tenantId,
                    'product_assigned',
                    'New Product Added for You',
                    'A new product has been added to your catalog.',
                    ['product_id' => $savedProduct->id]
                );
            }
        }

        $affectedTenantIds = array_unique(array_merge($previousAssignedIds, array_values($newTenantIds)));
        $tenantSyncService->syncProductToAssignedTenants($savedProduct, $affectedTenantIds);

        session()->flash('status', $product ? 'Product updated successfully.' : 'Product created successfully.');

        return redirect()->route('admin.products.edit', $savedProduct);
    }

    public function validateForm(Request $request, ?Product $product = null)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $this->rules($product));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $submittedCatIds = array_filter((array) $request->input('category_ids', []), fn($id) => filled($id));
        if (!empty($submittedCatIds) && Category::whereIn('id', $submittedCatIds)->whereHas('children')->exists()) {
            return response()->json([
                'success' => false,
                'errors' => ['category_ids' => ['Only the deepest (leaf) category may be selected.']],
            ], 422);
        }

        if (!$this->validateVariantUniqueness($this->normalizeVariants($request))) {
            return response()->json([
                'success' => false,
                'errors' => ['variants' => ['Duplicate variant combination — each variant must be unique.']],
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────────

    protected function normalizeVariants(Request $request): array
    {
        $variantsData = array_values($request->input('variants', []));
        $variantFiles = array_values($request->file('variants', []));

        return collect($variantsData)
            ->map(function (array $variant, int $idx) use ($variantFiles): array {
                $optionIds = collect($variant['pairs'] ?? [])
                    ->map(fn($pair) => $pair['option_id'] ?? null)
                    ->filter(fn($id) => filled($id))
                    ->map(fn($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'id' => filled($variant['id'] ?? null) ? (int) $variant['id'] : null,
                    'option_ids' => $optionIds,
                    'title' => trim((string) ($variant['title'] ?? '')) ?: null,
                    'sku' => trim((string) ($variant['sku'] ?? '')) ?: null,
                    'weight_grams' => ($variant['weight_grams'] ?? '') !== '' ? (int) $variant['weight_grams'] : null,
                    'price' => ($variant['price'] ?? '') !== '' ? $variant['price'] : null,
                    'stock' => (int) ($variant['stock'] ?? 0),
                    'status' => $variant['status'] ?? 'active',
                    'image' => $variantFiles[$idx]['image'] ?? null,
                    'remove_image' => (bool) ($variant['remove_image'] ?? false),
                ];
            })
            ->filter(fn(array $v) => $v['option_ids'] !== [])
            ->values()
            ->all();
    }

    protected function validateVariantUniqueness(array $variants): bool
    {
        $signatures = [];
        foreach ($variants as $variant) {
            $sig = implode(':', collect($variant['option_ids'])->sort()->values()->all());
            if (in_array($sig, $signatures, true)) {
                return false;
            }
            $signatures[] = $sig;
        }

        return true;
    }

    protected function rules(?Product $product = null): array
    {
        $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';
        $productId = $product?->getKey();

        $rules = [
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'slug' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'base_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'factory' => ['nullable', 'string', 'max:255'],
            'weight_grams' => ['nullable', 'integer', 'min:0'],
            'primary_image' => ['nullable', 'image', 'max:4096'],
        ];

        foreach (Language::query()->where('is_active', true)->get() as $language) {
            $nameRule = $language->code === $defaultLocale ? 'required' : 'nullable';

            $rules["translations.{$language->code}.name"] = [$nameRule, 'string', 'max:255'];
            $rules["translations.{$language->code}.label"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.summary"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$language->code}.description"] = ['nullable', 'string'];
            $rules["translations.{$language->code}.meta_keywords"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$language->code}.meta_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    // ──────────────────────────────────────────────────────────────────────────

    protected function viewData(?Product $product = null): array
    {
        $languages = Language::query()->where('is_active', true)->orderByDesc('is_default')->get();

        $blankTranslations = $languages->mapWithKeys(fn(Language $lang) => [
            $lang->code => [
                'name' => '',
                'label' => '',
                'summary' => '',
                'description' => '',
                'meta_keywords' => '',
                'meta_description' => '',
            ],
        ])->all();

        $translations = $blankTranslations;
        $variants = [];
        $existingImage = null;
        $existingGallery = collect();
        $assignedTenantIds = [];
        $productData = null;

        if ($product) {
            $loaded = app(ProductRepository::class)->findForEditor($product);

            $translations = array_replace_recursive(
                $blankTranslations,
                $loaded->translationsByLocale(['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description'])
            );

            $variants = $loaded->variants
                ->sortBy('position')
                ->values()
                ->map(function ($variant) {
                    $pairs = $variant->options->map(fn($opt) => [
                        'variation_id' => (int) $opt->variation_id,
                        'option_id' => (int) $opt->id,
                    ])->values()->all();

                    $thumbFile = $variant->files->firstWhere('key', 'variant_thumb');

                    return [
                        'id' => $variant->id,
                        'pairs' => $pairs ?: [['variation_id' => '', 'option_id' => '']],
                        'title' => (string) ($variant->title ?? ''),
                        'sku' => (string) ($variant->sku ?? ''),
                        'weight_grams' => $variant->weight_grams !== null ? (string) $variant->weight_grams : '',
                        'price' => $variant->price !== null
                            ? number_format((float) $variant->price, 2, '.', '')
                            : '',
                        'stock' => (int) ($variant->stock ?? 0),
                        'thumbnail_url' => $thumbFile?->full_path,
                    ];
                })
                ->all();

            $existingImage = $loaded->primary_image_url;
            $existingGallery = Product::query()->with('files')->find($product->id)
                ?->files->where('key', 'gallery')->values()
                ?? collect();

            $assignedTenantIds = ProductTenantAssignment::where('product_id', $product->id)
                ->pluck('tenant_id')
                ->all();

            $totalTenants = Tenant::query()->count();
            $assignToAllTenants = $totalTenants > 0 && \count($assignedTenantIds) === $totalTenants;

            $productData = [
                'sku' => $loaded->sku,
                'slug' => $loaded->slug,
                'status' => $loaded->status->value,
                'base_price' => number_format((float) $loaded->base_price, 2, '.', ''),
                'sale_price' => $loaded->sale_price !== null
                    ? number_format((float) $loaded->sale_price, 2, '.', '')
                    : '',
                'cost_price' => $loaded->cost_price !== null
                    ? number_format((float) $loaded->cost_price, 2, '.', '')
                    : '',
                'stock' => $loaded->stock,
                'min_stock' => $loaded->min_stock,
                'manage_stock' => $loaded->manage_stock,
                'is_taxable' => $loaded->is_taxable,
                'factory' => $loaded->factory ?? '',
                'weight_grams' => $loaded->weight_grams,
                'category_ids' => $loaded->categories->pluck('id')->all(),
            ];
        }

        return [
            'product' => $product,
            'productData' => $productData,
            'pageTitle' => $product ? 'Edit Product' : 'Add Product',
            'pageDescription' => $product
                ? 'Update translated content, media, and delivery coverage.'
                : 'Create a new central product with multilingual content and delivery logic.',
            'languages' => $languages,
            'defaultLocale' => $languages->first(fn($l) => $l->is_default)?->code
                ?? $languages->first()?->code
                ?? 'en',
            'categoryTree' => Category::query()
                ->with([
                    'translations.language',
                    'children.translations.language',
                    'children.children.translations.language',
                    'children.children.children.translations.language',
                ])
                ->whereNull('parent_id')
                ->get(),
            'variations' => Variation::query()
                ->with(['translations.language', 'options.translations.language'])
                ->get(),
            'statusOptions' => ProductStatus::cases(),
            'existingImage' => $existingImage,
            'existingGallery' => $existingGallery,
            'tenants' => Tenant::query()->orderBy('name')->get(),
            'translations' => $translations,
            'variants' => $variants,
            'assignedTenantIds' => $assignedTenantIds,
            'assignToAllTenants' => $assignToAllTenants ?? false,
        ];
    }
}
