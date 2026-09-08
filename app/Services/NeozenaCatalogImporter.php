<?php

namespace App\Services;

use App\Enums\CategoryStatus;
use App\Enums\DeliveryScope;
use App\Enums\FileStorageType;
use App\Enums\FileType;
use App\Enums\ProductStatus;
use App\Enums\VariationStatus;
use App\Models\Category;
use App\Models\File;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Variation;
use App\Models\VariationOption;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as InterventionDriver;
use Intervention\Image\ImageManager as InterventionImageManager;

class NeozenaCatalogImporter
{
    public function __construct(protected CentralCatalogTenantSyncService $tenantSync)
    {
    }

    /** Runtime cache: API variant-group external_id => local Variation ID. */
    protected array $variationCache = [];

    /** Runtime cache: API option external_id => option data array (value, translations). */
    protected array $apiOptionDataCache = [];

    public const BASE_URL = 'https://neozena.com/api/catalog';

    /**
     * Normalize a meta_keywords value that may arrive as:
     *  - a JSON string like '[{"value":"foo"},{"value":"bar"}]'
     *  - a plain array of {value: ...} entries
     *  - an already-formatted comma-separated string
     *  - null / empty
     * Returns a comma-separated string ("foo,bar") or null when empty.
     */
    protected function normalizeMetaKeywords(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $decoded = $raw;
        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '') {
                return null;
            }
            if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                $attempt = json_decode($trimmed, true);
                if (is_array($attempt)) {
                    $decoded = $attempt;
                }
            }
        }

        if (is_array($decoded)) {
            $values = collect($decoded)
                ->map(function ($entry) {
                    if (is_array($entry)) {
                        return $entry['value'] ?? ($entry['name'] ?? null);
                    }
                    return is_string($entry) ? $entry : null;
                })
                ->filter(fn($v) => is_string($v) && trim($v) !== '')
                ->map(fn($v) => trim($v))
                ->unique()
                ->values()
                ->all();

            return $values === [] ? null : implode(',', $values);
        }

        // Plain string fallback (already comma-separated or single keyword)
        return (string) $raw;
    }

    /**
     * Fetch a single page of products list.
     */
    public function fetchPage(int $page): ?array
    {
        $response = Http::timeout(60)->retry(3, 1000)->acceptJson()->get(self::BASE_URL . '/products', [
            'page' => $page,
        ]);

        if (!$response->successful()) {
            Log::warning('Neozena page fetch failed', [
                'page' => $page,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Fetch a single page of categories from the dedicated categories endpoint.
     */
    public function fetchCategoriesPage(int $page): ?array
    {
        $response = Http::timeout(60)->retry(3, 1000)->acceptJson()->get(self::BASE_URL . '/categories', [
            'page' => $page,
        ]);

        if (!$response->successful()) {
            Log::warning('Neozena categories page fetch failed', [
                'page' => $page,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Fetch a single page of variant groups from the dedicated variants endpoint.
     */
    public function fetchVariantsPage(int $page): ?array
    {
        $response = Http::timeout(60)->retry(3, 1000)->acceptJson()->get(self::BASE_URL . '/variants', [
            'page' => $page,
        ]);

        if (!$response->successful()) {
            Log::warning('Neozena variants page fetch failed', [
                'page' => $page,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Fetch full product details by external id.
     */
    public function fetchProduct(int $externalId): ?array
    {
        $response = Http::timeout(60)->retry(3, 1000)->acceptJson()->get(self::BASE_URL . '/products/' . $externalId);

        if (!$response->successful()) {
            Log::warning('Neozena product fetch failed', [
                'external_id' => $externalId,
                'status' => $response->status(),
            ]);

            return null;
        }

        $payload = $response->json();

        return $payload['data'] ?? null;
    }

    /**
     * Persist a product (and its categories, translations, variants, files) from the API payload.
     */
    public function importProduct(array $data): ?Product
    {
        if (!isset($data['id'])) {
            return null;
        }

        $product = DB::transaction(function () use ($data) {
            $categoryIds = [];
            foreach ($data['categories'] ?? [] as $categoryPayload) {
                if (!isset($categoryPayload['id'])) {
                    continue;
                }
                // Prefer already-imported category matched by external_id (from pre-import step).
                $localCategoryId = Category::query()
                    ->where('external_id', $categoryPayload['id'])
                    ->value('id');
                if ($localCategoryId) {
                    $categoryIds[] = $localCategoryId;
                } else {
                    $category = $this->importCategory($categoryPayload);
                    if ($category) {
                        $categoryIds[] = $category->id;
                    }
                }
            }

            $product = Product::query()
                ->where('external_id', $data['id'])
                // ->orWhere('sku', $data['sku'])
                ->first()
                ?? new Product();

            info('Importing Neozena product', ['external_id' => $data['id'], 'sku' => $data['sku'] ?? null]);

            $productHaveSameSku = isset($data['sku']) && Product::query()
                ->where('sku', $data['sku'])
                ->where('id', '!=', $product->id)
                ->exists();
            if ($productHaveSameSku) {
                $data['sku'] = $data['sku'] . '-' . $data['id'];
            }

            $defaultPrice = $this->resolveDefaultPrice($data);
            $totalStock = collect($data['variant_combinations'] ?? [])->sum('stock');
            $totalWeight = $data['weight'] ?? collect($data['variant_combinations'] ?? [])->avg('weight');
            $product->fill([
                'external_id' => $data['id'],
                'sku' => $data['sku'] ?? ('NEO-' . $data['id']),
                'slug' => $this->uniqueProductSlug($this->resolveSlugSource($data), $product->getKey()),
                'status' => ($data['status'] ?? false)
                    ? ProductStatus::Published->value
                    : ProductStatus::Draft->value,
                'delivery_scope' => DeliveryScope::AllZones->value,
                'base_price' => $defaultPrice,
                'sale_price' => $defaultPrice,
                'cost_price' => $defaultPrice,
                'stock' => (int) ($data['stock'] ?? $totalStock),
                'min_stock' => 0,
                'manage_stock' => !($data['stock_unlimited'] ?? false),
                'is_taxable' => (bool) ($data['taxable'] ?? false),
                'requires_shipping' => (bool) ($data['shippable'] ?? true),
                'factory' => $data['factory'] ?? null,
                'weight_grams' => $totalWeight ? (int) round($totalWeight) : null,
                'published_at' => ($data['status'] ?? false) ? now() : null,
            ])->save();

            $this->syncProductTranslations($product, $data['translations'] ?? []);

            if ($categoryIds !== []) {
                $product->categories()->sync($categoryIds);
            }

            info('Syncing Neozena product variants', ['external_id' => $data['id'], 'variant_count' => count($data['variant_combinations'] ?? [])]);

            $this->syncVariantCombinations($product, $data['variants'] ?? [], $data['variant_combinations'] ?? []);

            info('Syncing Neozena product files', ['external_id' => $data['id'], 'file_count' => count($data['images'] ?? [])]);

            $this->syncProductFiles($product, $data['images'] ?? []);

            return $product->refresh();
        }, 5); // retry up to 5 times on deadlock / lock-wait timeout

        return $product;
    }

    /**
     * Fetch a single category from the Neozena API by its external id.
     */
    public function fetchCategory(int $externalId): ?array
    {
        $response = Http::timeout(60)->retry(3, 1000)->acceptJson()->get(self::BASE_URL . '/categories/' . $externalId);

        if (!$response->successful()) {
            Log::warning('Neozena category fetch failed', [
                'external_id' => $externalId,
                'status' => $response->status(),
            ]);

            return null;
        }

        $payload = $response->json();

        return $payload['data'] ?? null;
    }

    public function importCategory(array $data, int $depth = 0): ?Category
    {
        if (!isset($data['id'])) {
            return null;
        }

        $parentId = null;
        if (!empty($data['parent_id'])) {
            // Try to find an already-imported parent.
            $parentId = Category::query()
                ->where('external_id', $data['parent_id'])
                ->value('id');

            // Parent not imported yet — fetch and import it first (max 5 levels deep).
            if ($parentId === null && $depth < 5) {
                if (empty($data['parent'])) {
                    $parentId = null;
                } else {
                    $parentData = $this->fetchCategory((int) $data['parent_id']);
                    if ($parentData) {
                        $parentCategory = $this->importCategory($parentData, $depth + 1);
                        $parentId = $parentCategory?->id;
                    }
                }
            }
        }

        return $this->retryOnDeadlock(function () use ($data, $parentId) {
            $category = Category::query()->where('external_id', $data['id'])->first() ?? new Category();

            $category->fill([
                'external_id' => $data['id'],
                'parent_id' => $parentId,
                'status' => ($data['status'] ?? false)
                    ? CategoryStatus::Published->value
                    : CategoryStatus::Draft->value,
                'is_featured' => (bool) ($data['is_featured'] ?? false),
            ])->save();

            $translations = [];
            foreach ($data['translations'] ?? [] as $translation) {
                $locale = $translation['locale'] ?? null;
                if (!$locale) {
                    continue;
                }
                $translations[$locale] = [
                    'name' => $translation['name'] ?? ($data['name'] ?? null),
                    'slug' => $translation['slug'] ?? ($data['slug'] ?? null),
                    'description' => $translation['description'] ?? null,
                    'meta_keywords' => $this->normalizeMetaKeywords($translation['meta_keywords'] ?? null),
                    'meta_description' => $translation['meta_description'] ?? null,
                ];
            }
            if ($translations === [] && !empty($data['name'])) {
                $translations['en'] = [
                    'name' => $data['name'],
                    'slug' => $data['slug'] ?? Str::slug((string) $data['name']),
                ];
            }

            $category->syncTranslations($translations);

            $this->syncCategoryFiles($category, $data);

            return $category;
        });
    }

    /**
     * Import a full variant group (with all its options) from the variants API.
     * Persists the Variation and all VariationOptions with their external_id values,
     * so subsequent product imports can look them up by external_id.
     */
    public function importVariantGroup(array $variantData): Variation
    {
        return $this->retryOnDeadlock(function () use ($variantData) {
            $variation = $this->ensureVariantGroupFromApiData((int) $variantData['id'], $variantData);

            foreach ($variantData['options'] ?? [] as $optionData) {
                $optExtId = (int) ($optionData['id'] ?? 0);
                if ($optExtId) {
                    $this->apiOptionDataCache[$optExtId] = $optionData;
                    $this->ensureVariationOption($optExtId, $optionData);
                }
            }

            return $variation;
        });
    }

    protected function syncProductTranslations(Product $product, array $apiTranslations): void
    {
        $payload = [];
        foreach ($apiTranslations as $translation) {
            $locale = $translation['locale'] ?? null;
            if (!$locale) {
                continue;
            }
            $payload[$locale] = [
                'name' => $translation['title'] ?? null,
                'label' => $translation['label'] ?? null,
                'summary' => $translation['summary'] ?? null,
                'description' => $translation['description'] ?? null,
                'meta_keywords' => $this->normalizeMetaKeywords($translation['meta_keywords'] ?? null),
                'meta_description' => $translation['meta_description'] ?? null,
            ];
        }

        if ($payload !== []) {
            $product->syncTranslations($payload);
        }
    }

    protected function syncVariantCombinations(Product $product, array $variants, array $combinations): void
    {
        if ($combinations === []) {
            return;
        }

        // Pre-load variation groups and their options from the full API variants[] data.
        foreach ($variants as $variantGroup) {
            $groupExtId = (int) ($variantGroup['id'] ?? 0);
            if ($groupExtId) {
                $this->ensureVariantGroupFromApiData($groupExtId, $variantGroup);
            }
            foreach ($variantGroup['options'] ?? [] as $optionData) {
                $optExtId = (int) ($optionData['id'] ?? 0);
                if ($optExtId) {
                    $this->apiOptionDataCache[$optExtId] = $optionData;
                }
            }
        }

        // Build variant group info from options embedded in each combination entry.
        // Handles the common case where $variants is empty but variant metadata
        // (variant_id, variant_name, variant_translations) is embedded in combination options.
        foreach ($combinations as $combination) {
            foreach ($combination['options'] ?? [] as $optData) {
                $variantId = (int) ($optData['variant_id'] ?? 0);
                $optId = (int) ($optData['id'] ?? 0);
                if ($variantId && !isset($this->variationCache[$variantId])) {
                    $this->ensureVariantGroupFromApiData($variantId, [
                        'id' => $variantId,
                        'name' => $optData['variant_name'] ?? null,
                        'translations' => $optData['variant_translations'] ?? [],
                    ]);
                }
                if ($optId && !isset($this->apiOptionDataCache[$optId])) {
                    $this->apiOptionDataCache[$optId] = $optData;
                }
            }
        }

        // Match existing variants by their option signature (no external_id column on product_variants).
        $existing = $product->variants()->with('options')->get();
        $usedIds = [];
        $usedSkus = [];

        foreach ($combinations as $position => $combination) {
            $optionIds = collect($combination['option_ids'] ?? [])
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $options = collect($optionIds)
                ->map(fn(int $extId) => $this->ensureVariationOption($extId, $this->apiOptionDataCache[$extId] ?? null))
                ->filter();

            $signature = $this->variantSignature($options->pluck('id')->all());

            $sku = $combination['sku'] ?? $product->sku . '-' . ($position + 1);

            $variant = $existing->first(fn(ProductVariant $v) => !in_array($v->id, $usedIds, true)
                && $this->variantSignature($v->options->pluck('id')->all()) === $signature)
                ?? $existing->first(fn(ProductVariant $v) => !in_array($v->id, $usedIds, true) && $v->sku === $sku);

            // Disambiguate within this import run (upstream may emit duplicate SKUs for the same product).
            if ($sku !== null && in_array($sku, $usedSkus, true)) {
                $sku = $sku . '-' . ($position + 1);
            }

            // Ensure global DB uniqueness — exclude the variant we are updating (if it already exists).
            $sku = $this->uniqueVariantSku((string) $sku, $variant?->exists ? $variant->getKey() : null);

            $usedSkus[] = $sku;

            if (!$variant) {
                $variant = $product->variants()->make();
            }

            $weightInCombination = $combination['weight'] ?? 0;

            $variant->fill([
                'sku' => $sku,
                'weight_grams' => $weightInCombination,
                'title' => $options->map(fn($o) => $o->translationValue('name') ?? ('opt-' . $o->external_id))->implode(' / ') ?: null,
                'price' => $combination['price'] ?? null,
                'stock' => (int) ($combination['stock'] ?? 0),
                'position' => (int) $position,
                'status' => ($combination['is_active'] ?? true)
                    ? VariationStatus::Active->value
                    : VariationStatus::Inactive->value,
            ]);
            $variant->product()->associate($product);
            $variant->save();

            $variant->options()->sync($options->pluck('id')->all());

            // Download variant combination thumbnail.
            if (!empty($combination['thumbnail_path'])) {
                $thumbExists = $variant->files()->where('key', 'variant_thumb')->exists();
                if (!$thumbExists) {
                    $this->downloadFileFromUrl(
                        model: $variant,
                        url: $combination['thumbnail_path'],
                        key: 'variant_thumb',
                        externalId: $combination['id'] ?? null,
                        fileType: FileType::Image,
                    );
                }
            }

            $usedIds[] = $variant->id;
        }

        // Remove stale variants not present in this sync.
        $product->variants()->whereNotIn('id', $usedIds)->get()->each->delete();

        // Sync variation pivot for product to mirror options groups.
        $variationIds = collect($combinations)
            ->flatMap(fn($c) => $c['option_ids'] ?? [])
            ->map(fn($id) => VariationOption::query()->where('external_id', (int) $id)->value('variation_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($variationIds !== []) {
            $product->variations()->sync($variationIds);
        }
    }

    protected ?int $importedVariationId = null;

    /**
     * Ensure a Variation (group) exists for the given API variant group data.
     * Matches by cached ID, then by English name translation.
     */
    protected function ensureVariantGroupFromApiData(int $externalGroupId, array $variantData): Variation
    {
        if (isset($this->variationCache[$externalGroupId])) {
            $cached = Variation::query()->find($this->variationCache[$externalGroupId]);
            if ($cached) {
                return $cached;
            }
        }

        // Build translations payload.
        $translationsPayload = [];
        foreach ($variantData['translations'] ?? [] as $t) {
            $locale = $t['locale'] ?? null;
            if ($locale && !empty($t['name'])) {
                $translationsPayload[$locale] = ['name' => $t['name']];
            }
        }
        // Fallback to top-level name.
        if (empty($translationsPayload) && !empty($variantData['name'])) {
            $translationsPayload['en'] = ['name' => $variantData['name']];
        }

        $enName = $translationsPayload['en']['name'] ?? ($variantData['name'] ?? null);

        $variation = null;
        if ($enName) {
            $variation = Variation::query()
                ->whereHas('translations', fn($q) => $q->where('field', 'name')->where('value', $enName))
                ->first();
        }

        if (!$variation) {
            $variation = new Variation();
            $variation->fill([
                'status' => VariationStatus::Active->value,
            ])->save();
        }

        if ($translationsPayload !== []) {
            $variation->syncTranslations($translationsPayload);
        }

        $this->variationCache[$externalGroupId] = $variation->id;

        return $variation;
    }

    protected function ensureImportedVariation(): Variation
    {
        if ($this->importedVariationId) {
            $variation = Variation::query()->find($this->importedVariationId);
            if ($variation) {
                return $variation;
            }
        }

        $variation = Variation::query()
            ->whereHas('translations', fn($q) => $q->where('field', 'name')->where('value', 'Imported Options'))
            ->first();

        if (!$variation) {
            $variation = new Variation();
            $variation->fill([
                'status' => VariationStatus::Active->value,
            ])->save();
            $variation->syncTranslations(['en' => ['name' => 'Imported Options']]);
        }

        $this->importedVariationId = $variation->id;

        return $variation;
    }

    protected function ensureVariationOption(int $externalOptionId, ?array $apiOptionData = null): ?VariationOption
    {
        $option = VariationOption::query()->where('external_id', $externalOptionId)->first();

        // Resolve target variation from API data if available.
        $variantId = (int) ($apiOptionData['variant_id'] ?? 0);
        $variation = $variantId && isset($this->variationCache[$variantId])
            ? Variation::query()->find($this->variationCache[$variantId])
            : null;

        if ($option) {
            // Update translations and variation assignment when we have richer API data.
            if ($apiOptionData !== null) {
                $translationsPayload = [];
                foreach ($apiOptionData['translations'] ?? [] as $t) {
                    $locale = $t['locale'] ?? null;
                    if ($locale && !empty($t['name'])) {
                        $translationsPayload[$locale] = ['name' => $t['name']];
                    }
                }
                if (empty($translationsPayload) && !empty($apiOptionData['value'])) {
                    $translationsPayload['en'] = ['name' => (string) $apiOptionData['value']];
                }
                if ($translationsPayload !== []) {
                    $option->syncTranslations($translationsPayload);
                }
                if ($variation && $option->variation_id !== $variation->id) {
                    $option->variation_id = $variation->id;
                    $option->save();
                }
            }

            return $option;
        }

        $variation ??= $this->ensureImportedVariation();

        $option = new VariationOption();
        $option->fill([
            'external_id' => $externalOptionId,
            'variation_id' => $variation->id,
        ])->save();

        // Build translation payload from API data.
        $translationsPayload = [];
        foreach ($apiOptionData['translations'] ?? [] as $t) {
            $locale = $t['locale'] ?? null;
            if ($locale && !empty($t['name'])) {
                $translationsPayload[$locale] = ['name' => $t['name']];
            }
        }
        // Fallback: use value field or a generic name.
        if (empty($translationsPayload)) {
            $fallback = $apiOptionData['value'] ?? ('Option ' . $externalOptionId);
            $translationsPayload['en'] = ['name' => (string) $fallback];
        }
        $option->syncTranslations($translationsPayload);

        return $option;
    }

    protected function syncCategoryFiles(Category $category, array $categoryData): void
    {
        $thumbnailUrl = $categoryData['thumbnail_path'] ?? null;
        $bannerUrl = $categoryData['banner_path'] ?? null;

        if ($thumbnailUrl && !$category->files()->where('key', 'thumb')->exists()) {
            $this->downloadFileFromUrl(
                model: $category,
                url: $thumbnailUrl,
                key: 'thumb',
                externalId: null,
                fileType: FileType::Image,
            );
        }

        if ($bannerUrl && !$category->files()->where('key', 'banner')->exists()) {
            $this->downloadFileFromUrl(
                model: $category,
                url: $bannerUrl,
                key: 'banner',
                externalId: null,
                fileType: FileType::Image,
            );
        }
    }

    protected function syncProductFiles(Product $product, array $images): void
    {
        foreach ($images as $image) {
            $url = $image['image_path'] ?? null;
            $externalId = $image['id'] ?? null;
            if (!$url || !$externalId) {
                continue;
            }

            $exists = $product->files()->where('external_id', $externalId)->exists();
            if ($exists) {
                continue;
            }

            $type = ($image['type'] ?? 'image') === 'video' ? FileType::Video : FileType::Image;
            $isPrimary = $type !== FileType::Video && (bool) ($image['is_primary'] ?? false);
            $primaryKey = $isPrimary ? 'primary_original' : 'gallery';

            $originalFile = $this->downloadFileFromUrl(
                model: $product,
                url: $url,
                key: $type === FileType::Video ? 'gallery' : $primaryKey,
                externalId: $externalId,
                fileType: $type,
            );

            // Mirror ProductMediaService key set: when an image is stored we
            // also generate the resized derivatives the storefronts read.
            if ($originalFile && $type === FileType::Image) {
                $derivatives = $isPrimary
                    ? [
                        'primary_large' => 1600,
                        'primary_medium' => 960,
                        'primary_thumb' => 360,
                    ]
                    : [
                        'gallery_thumb' => 480,
                    ];

                $this->generateImageDerivatives($product, $originalFile, $derivatives);
            }
        }
    }

    /**
     * Generate resized JPEG derivatives next to the original file so all
     * `primary_*` / `gallery_*` keys match the admin upload pipeline.
     */
    protected function generateImageDerivatives($model, File $originalFile, array $derivatives): void
    {
        $disk = $originalFile->storage_type ?: FileStorageType::Public ->value;
        $storage = Storage::disk($disk);

        if (!$storage->exists($originalFile->path)) {
            return;
        }

        try {
            $manager = new InterventionImageManager(new InterventionDriver());
            $sourceBinary = $storage->get($originalFile->path);
            $directory = pathinfo($originalFile->path, PATHINFO_DIRNAME);
            $basename = pathinfo($originalFile->path, PATHINFO_FILENAME);

            foreach ($derivatives as $key => $maxDimension) {
                if ($model->files()->where('key', $key)->where('external_id', $originalFile->external_id)->exists()) {
                    continue;
                }

                $image = $manager->read($sourceBinary)->scaleDown(width: $maxDimension, height: $maxDimension);
                $encoded = $image->toJpeg(82);
                $suffix = Str::contains($key, '_') ? Str::after($key, '_') : $key;
                $path = sprintf('%s/%s-%s.jpg', $directory, $basename, $suffix);

                $storage->put($path, (string) $encoded);

                $model->files()->create([
                    'external_id' => $originalFile->external_id,
                    'key' => $key,
                    'path' => $path,
                    'storage_type' => $disk,
                    'file_type' => FileType::Image->value,
                    'mime_type' => 'image/jpeg',
                    'extension' => 'jpg',
                    'size' => $storage->size($path),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Neozena image derivative generation failed', [
                'file_id' => $originalFile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function downloadFileFromUrl($model, string $url, string $key, ?int $externalId = null, ?FileType $fileType = null): ?File
    {
        try {
            $response = Http::timeout(120)->retry(2, 1000)->get($url);
            if (!$response->successful()) {
                return null;
            }

            $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'bin');
            $contents = $response->body();
            $mime = $response->header('Content-Type') ?: null;
            $directory = sprintf('%s/%s', Str::plural(class_basename($model)), $model->getKey());
            $filename = sprintf('%s.%s', Str::uuid(), $extension);
            $path = sprintf('%s/%s', strtolower($directory), $filename);


            Storage::disk(FileStorageType::Public ->value)->put($path, $contents);

            return $model->files()->create([
                'external_id' => $externalId,
                'key' => $key,
                'path' => $path,
                'storage_type' => FileStorageType::Public ->value,
                'file_type' => ($fileType ?? FileType::Image)->value,
                'mime_type' => $mime,
                'extension' => $extension,
                'size' => strlen($contents),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Neozena file download failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function variantSignature(array $optionIds): string
    {
        sort($optionIds);

        return implode(':', $optionIds);
    }

    protected function resolveDefaultPrice(array $data): float
    {
        $current = (float) ($data['current_price'] ?? 0);
        if ($current > 0) {
            return $current;
        }

        $first = collect($data['variant_combinations'] ?? [])->first();

        return (float) ($first['price'] ?? 0);
    }

    protected function resolveSlugSource(array $data): string
    {
        $en = collect($data['translations'] ?? [])->firstWhere('locale', 'en');
        if ($en && !empty($en['slug'])) {
            return $en['slug'];
        }
        if ($en && !empty($en['title'])) {
            return $en['title'];
        }

        return $data['sku'] ?? ('product-' . ($data['id'] ?? Str::random(6)));
    }

    /**
     * Execute a callback and automatically retry it when MySQL signals a deadlock
     * (SQLSTATE 40001) or a lock-wait timeout (SQLSTATE HY000 / errno 1205).
     * Useful for operations that are NOT already inside a DB::transaction() with $attempts.
     */
    protected function retryOnDeadlock(callable $callback, int $maxAttempts = 5): mixed
    {
        $attempt = 0;
        while (true) {
            try {
                return $callback();
            } catch (\Illuminate\Database\QueryException $e) {
                $code = (string) $e->getCode();
                $isDeadlock = $code === '40001' || str_contains($e->getMessage(), '1213')
                    || str_contains($e->getMessage(), '1205'); // lock wait timeout
                if (!$isDeadlock || ++$attempt >= $maxAttempts) {
                    throw $e;
                }
                // Exponential back-off with jitter: 100 ms … 800 ms
                usleep(random_int(100_000, min(800_000, 100_000 * (2 ** $attempt))));
            }
        }
    }

    protected function uniqueVariantSku(string $value, ?int $ignoreId = null): string
    {
        $base = $value !== '' ? $value : Str::random(8);
        $sku = $base;
        $i = 1;
        while (
            ProductVariant::query()
                ->when($ignoreId, fn($q) => $q->whereKeyNot($ignoreId))
                ->where('sku', $sku)
                ->exists()
        ) {
            $i++;
            $sku = $base . '-' . $i;
        }

        return $sku;
    }

    protected function uniqueProductSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $i = 1;
        while (
            Product::query()
                ->when($ignoreId, fn($q) => $q->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $i++;
            $slug = $base . '-' . $i;
        }

        return $slug;
    }

    protected function uniqueCategorySlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $i = 1;
        while (
            Category::query()
                ->when($ignoreId, fn($q) => $q->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $i++;
            $slug = $base . '-' . $i;
        }

        return $slug;
    }
}
