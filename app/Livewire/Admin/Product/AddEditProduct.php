<?php

namespace App\Livewire\Admin\Product;

use App\Enums\DeliveryScope;
use App\Enums\ProductStatus;
use App\Jobs\NotifyTenantsForProductCategories;
use App\Models\Category;
use App\Models\Country;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductBadge;
use App\Models\ProductTenantAssignment;
use App\Models\ShippingZone;
use App\Models\Tenant;
use App\Models\Variation;
use App\Repositories\ProductRepository;
use App\Services\ProductMediaService;
use App\Services\ProductService;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use App\Services\TenantNotificationService;
use Artisan;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddEditProduct extends Component
{
    use WithFileUploads;

    public ?int $productId = null;

    public string $sku = '';
    public string $slug = '';
    public string $status = 'draft';
    public string $deliveryScope = 'all_zones';
    public string $basePrice = '0.00';
    public ?string $salePrice = null;
    public ?string $costPrice = null;
    public int $stock = 0;
    public int $minStock = 0;
    public bool $manageStock = true;
    public bool $isTaxable = true;
    public string $factory = '';
    public ?int $weightGrams = null;

    /** Country targeting — independent of $deliveryScope, which governs shipping zones. */
    public array $assignedCountryIds = [];
    public bool $allCountries = true;

    public array $categoryIds = [];
    public array $shippingZoneIds = [];
    public array $badgeIds = [];
    public array $translations = [];
    public string $activeLocale = 'en';

    /**
     * Explicit variant rows. Each entry:
     *   - id: ?int
     *   - pairs: list<array{variation_id: int|string, option_id: int|string}>
     *   - sku: string
     *   - price: string|numeric
     *   - stock: int
     *   - status: string
     *   - thumbnail_url: ?string (existing variant image URL, display only)
     *   - image: TemporaryUploadedFile|null (pending upload)
     *   - remove_image: bool
     *
     * @var array<int, array<string, mixed>>
     */
    public array $variants = [];

    public bool $removePrimaryImage = false;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $primaryImage = null;

    /** @var list<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $galleryFiles = [];

    public array $removeGalleryIds = [];

    public bool $assignToAllTenants = false;

    /** @var list<string> */
    public array $assignedTenantIds = [];

    public function mount(?Product $product = null): void
    {
        $languages = Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get();
        $this->activeLocale = $languages->first()?->code ?? 'en';

        $this->translations = $languages->mapWithKeys(fn(Language $language) => [
            $language->code => [
                'name' => '',
                'label' => '',
                'summary' => '',
                'description' => '',
                'meta_keywords' => '',
                'meta_description' => '',
            ],
        ])->all();

        $this->variants = [];

        if (!$product) {
            return;
        }

        $loaded = app(ProductRepository::class)->findForEditor($product);

        $this->productId = $loaded->getKey();
        $this->sku = $loaded->sku;
        $this->slug = $loaded->slug;
        $this->status = $loaded->status->value;
        $this->deliveryScope = $loaded->delivery_scope->value;
        $this->basePrice = number_format((float) $loaded->base_price, 2, '.', '');
        $this->salePrice = $loaded->sale_price !== null ? number_format((float) $loaded->sale_price, 2, '.', '') : null;
        $this->costPrice = $loaded->cost_price !== null ? number_format((float) $loaded->cost_price, 2, '.', '') : null;
        $this->stock = $loaded->stock;
        $this->minStock = $loaded->min_stock;
        $this->manageStock = $loaded->manage_stock;
        $this->isTaxable = $loaded->is_taxable;
        $this->factory = $loaded->factory ?? '';
        $this->weightGrams = $loaded->weight_grams;
        $this->categoryIds = $loaded->categories->pluck('id')->all();
        $this->shippingZoneIds = $loaded->shippingZones->pluck('id')->all();
        $this->assignedCountryIds = $loaded->countries()->pluck('countries.id')->map(fn ($id) => (int) $id)->all();
        $this->allCountries = $this->assignedCountryIds === [];
        $this->badgeIds = $loaded->badges->pluck('id')->all();

        $this->translations = array_replace_recursive(
            $this->translations,
            $loaded->translationsByLocale(['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description'])
        );

        $this->variants = $loaded->variants
            ->sortBy('position')
            ->values()
            ->map(function ($variant) {
                $pairs = $variant->options
                    ->map(fn($option) => [
                        'variation_id' => (int) $option->variation_id,
                        'option_id' => (int) $option->id,
                    ])
                    ->values()
                    ->all();

                $thumbFile = $variant->files->firstWhere('key', 'variant_thumb');

                return [
                    'id' => $variant->id,
                    'pairs' => $pairs ?: [['variation_id' => '', 'option_id' => '']],
                    'title' => (string) ($variant->title ?? ''),
                    'sku' => (string) ($variant->sku ?? ''),
                    'weight_grams' => $variant->weight_grams !== null ? (string) $variant->weight_grams : '',
                    'price' => $variant->price !== null ? number_format((float) $variant->price, 2, '.', '') : '',
                    'stock' => (int) ($variant->stock ?? 0),
                    'status' => $variant->status?->value ?? 'active',
                    'thumbnail_url' => $thumbFile?->full_path,
                    'image' => null,
                    'remove_image' => false,
                ];
            })
            ->all();

        $this->assignedTenantIds = ProductTenantAssignment::where('product_id', $product->id)
            ->pluck('tenant_id')
            ->all();

        $totalTenants = Tenant::query()->count();
        $this->assignToAllTenants = $totalTenants > 0 && \count($this->assignedTenantIds) === $totalTenants;
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function updatedDeliveryScope(string $scope): void
    {
        if ($scope !== DeliveryScope::SelectedZones->value) {
            $this->shippingZoneIds = [];
        }
    }

    public function removeImage(?string $model = null): void
    {
        if ($model === null || $model === 'primaryImage') {
            $this->removePrimaryImage = true;
            $this->primaryImage = null;
            return;
        }

        if (property_exists($this, $model)) {
            $this->{$model} = null;
        }
    }

    public function markGalleryForRemoval(int $fileId): void
    {
        $this->removeGalleryIds[] = $fileId;
    }

    /**
     * @param list<int|string> $orderedIds gallery file IDs in the desired display order
     */
    public function updateGalleryOrder(array $orderedIds, ProductMediaService $mediaService): void
    {
        if (!$this->productId) {
            return;
        }

        $product = Product::query()->findOrFail($this->productId);
        $mediaService->reorderGalleryFiles($product, array_map('intval', $orderedIds));
    }

    public function removeCategory(int $categoryId): void
    {
        $this->categoryIds = array_values(array_filter($this->categoryIds, fn($id) => (int) $id !== $categoryId));
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            'id' => null,
            'pairs' => [['variation_id' => '', 'option_id' => '']],
            'title' => '',
            'sku' => '',
            'weight_grams' => '',
            'price' => $this->basePrice,
            'stock' => 0,
            'status' => 'active',
            'thumbnail_url' => null,
            'image' => null,
            'remove_image' => false,
        ];
    }

    public function removeVariant(int $index): void
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function addOptionPair(int $variantIndex): void
    {
        if (!isset($this->variants[$variantIndex])) {
            return;
        }
        $this->variants[$variantIndex]['pairs'][] = ['variation_id' => '', 'option_id' => ''];
    }

    public function removeOptionPair(int $variantIndex, int $pairIndex): void
    {
        if (!isset($this->variants[$variantIndex]['pairs'][$pairIndex])) {
            return;
        }
        unset($this->variants[$variantIndex]['pairs'][$pairIndex]);
        $this->variants[$variantIndex]['pairs'] = array_values($this->variants[$variantIndex]['pairs']);

        if ($this->variants[$variantIndex]['pairs'] === []) {
            $this->variants[$variantIndex]['pairs'] = [['variation_id' => '', 'option_id' => '']];
        }
    }

    public function updatedVariants($value, $key): void
    {
        // When the variation group changes for a pair, reset the chosen option.
        if (preg_match('/^(\d+)\.pairs\.(\d+)\.variation_id$/', $key, $m)) {
            $variantIndex = (int) $m[1];
            $pairIndex = (int) $m[2];
            if (isset($this->variants[$variantIndex]['pairs'][$pairIndex])) {
                $this->variants[$variantIndex]['pairs'][$pairIndex]['option_id'] = '';
            }
        }
    }

    public function removeVariantImage(int $variantIndex): void
    {
        if (!isset($this->variants[$variantIndex])) {
            return;
        }
        $this->variants[$variantIndex]['image'] = null;
        $this->variants[$variantIndex]['remove_image'] = true;
        $this->variants[$variantIndex]['thumbnail_url'] = null;
    }

    public function save(ProductService $service, CentralCatalogTenantSyncService $tenantSyncService, TenantNotificationService $notifier): mixed
    {
        $validated = $this->validate($this->rules(), [], $this->validationAttributes());
        $normalizedVariants = $this->normalizeVariants();

        if (!$this->validateNormalizedVariants($normalizedVariants)) {
            return null;
        }

        if ($this->deliveryScope === DeliveryScope::SelectedZones->value && $this->shippingZoneIds === []) {
            $this->addError('shippingZoneIds', 'Choose at least one delivery zone for selected-zone products.');
            return null;
        }

        $existingProduct = $this->productId ? Product::query()->findOrFail($this->productId) : null;

        $isNew = $existingProduct === null;
        $previousCatIds = $existingProduct
            ? $existingProduct->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all()
            : [];
        $prevVariantCount = $existingProduct ? $existingProduct->variants()->count() : 0;

        $product = $service->save([
            'sku' => $validated['sku'],
            'slug' => $validated['slug'] ?? null,
            'status' => $validated['status'],
            'delivery_scope' => $validated['deliveryScope'],
            'base_price' => $validated['basePrice'],
            'sale_price' => $validated['salePrice'] ?? null,
            'cost_price' => $validated['costPrice'] ?? null,
            'stock' => $validated['stock'],
            'min_stock' => $validated['minStock'],
            'manage_stock' => $validated['manageStock'],
            'is_taxable' => $validated['isTaxable'],
            'factory' => $validated['factory'] ?? null,
            'weight_grams' => $validated['weightGrams'] ?? null,
            'category_ids' => $validated['categoryIds'] ?? [],
            'variants' => $normalizedVariants,
            'shipping_zone_ids' => $validated['shippingZoneIds'] ?? [],
            'translations' => $validated['translations'],
            'remove_primary_image' => $validated['removePrimaryImage'],
            'primary_image' => $this->primaryImage,
            'gallery_files' => $this->galleryFiles,
            'remove_gallery_ids' => $this->removeGalleryIds,
        ], $existingProduct);

        $product->badges()->sync(array_filter((array) $this->badgeIds, filled(...)));

        // Country targeting — empty pivot means "no preference / available everywhere".
        // Synced before the tenant push so syncProductToTenant() denormalizes the new list.
        $product->countries()->sync(
            $this->allCountries
                ? []
                : array_values(array_filter(array_map('intval', (array) $this->assignedCountryIds)))
        );
        $product->load('countries');

        $tenantSyncService->syncProduct($product);
        $tenantSyncService->syncAllTenants(['badges']);

        // ── Tenant Assignments ──────────────────────────────────────────────
        $previousAssignedTenantIds = ProductTenantAssignment::where('product_id', $product->id)
            ->pluck('tenant_id')
            ->all();

        $newTenantIds = $this->assignToAllTenants
            ? Tenant::query()->pluck('id')->all()
            : array_filter((array) $this->assignedTenantIds, filled(...));

        // Remove unselected assignments
        ProductTenantAssignment::where('product_id', $product->id)
            ->whereNotIn('tenant_id', $newTenantIds)
            ->delete();

        // Add new assignments and notify new tenants
        foreach ($newTenantIds as $tenantId) {
            ProductTenantAssignment::firstOrCreate(
                ['product_id' => $product->id, 'tenant_id' => $tenantId],
                ['synced_at' => null]
            );

            if (!in_array($tenantId, $previousAssignedTenantIds)) {
                $productName = data_get($this->translations, $this->activeLocale . '.name', 'A new product');
                $notifier->notifyById(
                    $tenantId,
                    'product_assigned',
                    'New Product Added for You',
                    'A new product "' . $productName . '" has been added to your catalog.',
                    ['product_id' => $product->id, 'product_name' => $productName]
                );
            }
        }

        // Push product to all affected tenants (added, updated, or removed).
        // Added/updated tenants: syncProducts() will upsert the record with is_tenant_owned=true.
        // Removed tenants: syncProducts() will delete the record since the assignment was removed.
        $affectedTenantIds = array_unique(array_merge($previousAssignedTenantIds, array_values($newTenantIds)));
        $tenantSyncService->syncProductToAssignedTenants($product, $affectedTenantIds);

        // Notify tenants whose category tree covers this product, even without
        // an explicit assignment (skip tenants already notified above).
        NotifyTenantsForProductCategories::dispatch(
            productId: $product->id,
            isNew: $isNew,
            previousCatIds: $previousCatIds,
            prevVariantCount: $prevVariantCount,
            alreadyNotifiedIds: array_values($newTenantIds),
        );

        session()->flash('status', $this->productId ? 'Product updated successfully.' : 'Product created successfully.');

        return redirect()->route('admin.products.edit', $product);
    }

    protected function rules(): array
    {
        $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';

        $rules = [
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($this->productId)],
            'slug' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'deliveryScope' => ['required', Rule::enum(DeliveryScope::class)],
            'basePrice' => ['required', 'numeric', 'min:0'],
            'salePrice' => ['nullable', 'numeric', 'min:0'],
            'costPrice' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'minStock' => ['required', 'integer', 'min:0'],
            'manageStock' => ['boolean'],
            'isTaxable' => ['boolean'],
            'factory' => ['nullable', 'string', 'max:255'],
            'weightGrams' => ['nullable', 'integer', 'min:0'],
            'categoryIds' => ['array'],
            'categoryIds.*' => ['integer', 'exists:categories,id'],
            'shippingZoneIds' => ['array'],
            'shippingZoneIds.*' => ['integer', 'exists:shipping_zones,id'],
            'allCountries' => ['boolean'],
            'assignedCountryIds' => ['array'],
            'assignedCountryIds.*' => ['integer', 'exists:countries,id'],
            'badgeIds' => ['array'],
            'badgeIds.*' => ['integer', 'exists:product_badges,id'],
            'assignedTenantIds' => ['array'],
            'assignedTenantIds.*' => ['string', 'exists:tenants,id'],
            'removePrimaryImage' => ['boolean'],
            'primaryImage' => ['nullable', 'image', 'max:4096'],
            'variants' => ['array'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.pairs' => ['array', 'min:1'],
            'variants.*.pairs.*.variation_id' => ['nullable', 'integer', 'exists:variations,id'],
            'variants.*.pairs.*.option_id' => ['nullable', 'integer', 'exists:variation_options,id'],
            'variants.*.sku' => ['nullable', 'string', 'max:120'],
            'variants.*.weight_grams' => ['nullable', 'integer', 'min:0'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'variants.*.status' => ['nullable', 'string'],
            'variants.*.image' => ['nullable', 'image', 'max:4096'],
            'variants.*.remove_image' => ['boolean'],
        ];

        foreach (Language::query()->where('is_active', true)->get() as $language) {
            $nameRule = $language->code === $defaultLocale ? 'required' : 'nullable';

            $rules["translations.{$language->code}.name"] = [$nameRule, 'string', 'max:255'];
            $rules["translations.{$language->code}.label"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.summary"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.description"] = ['nullable', 'string'];
            $rules["translations.{$language->code}.meta_keywords"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$language->code}.meta_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        return [
            'basePrice' => 'base price',
            'salePrice' => 'sale price',
            'costPrice' => 'cost price',
            'minStock' => 'minimum stock',
            'weightGrams' => 'weight (grams)',
            'categoryIds' => 'categories',
            'shippingZoneIds' => 'shipping zones',
            'badgeIds' => 'badges',
            'primaryImage' => 'primary image',
        ];
    }

    /**
     * Build the payload expected by ProductService::syncVariants.
     */
    protected function normalizeVariants(): array
    {
        return collect($this->variants)
            ->map(function (array $variant): array {
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
                    'title' => trim((string) ($variant['title'] ?? '')),
                    'sku' => trim((string) ($variant['sku'] ?? '')) ?: null,
                    'weight_grams' => ($variant['weight_grams'] ?? '') !== '' ? (int) $variant['weight_grams'] : null,
                    'price' => $variant['price'] !== '' ? $variant['price'] : null,
                    'stock' => (int) ($variant['stock'] ?? 0),
                    'status' => $variant['status'] ?? 'active',
                    'image' => $variant['image'] ?? null,
                    'remove_image' => (bool) ($variant['remove_image'] ?? false),
                ];
            })
            ->filter(fn(array $v) => $v['option_ids'] !== [])
            ->values()
            ->all();
    }

    protected function validateNormalizedVariants(array $variants): bool
    {
        if ($variants === []) {
            return true;
        }

        $allOptionIds = collect($variants)->flatMap(fn(array $v) => $v['option_ids'])->unique()->all();
        $options = \App\Models\VariationOption::query()
            ->whereIn('id', $allOptionIds)
            ->get(['id', 'variation_id'])
            ->keyBy('id');

        $signatures = [];

        foreach ($variants as $i => $variant) {
            // Each variant must have unique variation groups (no two pairs share variation_id).
            $variationIds = collect($variant['option_ids'])
                ->map(fn(int $id) => $options->get($id)?->variation_id)
                ->filter()
                ->all();

            if (count($variationIds) !== count(array_unique($variationIds))) {
                $this->addError("variants.{$i}.pairs", 'Each variation group can only be chosen once per variant.');

                return false;
            }

            $signature = implode(':', collect($variant['option_ids'])->sort()->values()->all());
            if (in_array($signature, $signatures, true)) {
                $this->addError("variants.{$i}", 'Duplicate variant combination — each variant must be unique.');

                return false;
            }
            $signatures[] = $signature;
        }

        return true;
    }

    public function render()
    {
        $languages = Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get();

        $existingGallery = $this->productId
            ? Product::query()->with('files')->find($this->productId)?->files->where('key', 'gallery')->values()
            : collect();

        return view('livewire.admin.product.add-edit-product', [
            'pageTitle' => $this->productId ? 'Edit Product' : 'Add Product',
            'pageDescription' => $this->productId
                ? 'Update translated content, media, and delivery coverage.'
                : 'Create a new central product with multilingual content and delivery logic.',
            'languages' => $languages,
            'categories' => Category::query()->with('translations.language')->get(),
            'categoryTree' => Category::query()
                ->with(['translations.language', 'children.translations.language', 'children.children.translations.language', 'children.children.children.translations.language'])
                ->whereNull('parent_id')

                ->get(),
            'shippingZones' => ShippingZone::query()->orderBy('name')->get(),
            'countries' => Country::query()
                ->where('is_active_for_tenants', true)
                ->orderBy('name')
                ->get(['id', 'name', 'iso2', 'flag_emoji']),
            'variations' => Variation::query()->with(['translations.language', 'options.translations.language'])->get(),
            'statusOptions' => ProductStatus::cases(),
            'deliveryScopes' => DeliveryScope::cases(),
            'existingImage' => $this->productId ? Product::query()->with('files')->find($this->productId)?->primary_image_url : null,
            'existingGallery' => $existingGallery,
            'tenants' => Tenant::query()->orderBy('data->name')->get(),
            'badges' => ProductBadge::query()->where('active', true)->orderBy('text')->get(),
        ]);
    }
}
