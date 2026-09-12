<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Enums\FileStorageType;
use App\Enums\FileType;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Catalog\SaveOwnProductRequest;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class OwnProductController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function create(): View
    {
        return view('tenant.pages.catalog.own-products.form', $this->viewData());
    }

    public function edit(Product $product): View
    {
        abort_unless($product->is_own_product, 404);

        return view('tenant.pages.catalog.own-products.form', $this->viewData($product));
    }

    public function store(SaveOwnProductRequest $request): JsonResponse
    {
        return $this->save($request, null);
    }

    public function update(SaveOwnProductRequest $request, Product $product): JsonResponse
    {
        abort_unless($product->is_own_product, 404);

        return $this->save($request, $product);
    }

    public function validateStore(SaveOwnProductRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function validateUpdate(SaveOwnProductRequest $request, Product $product): JsonResponse
    {
        abort_unless($product->is_own_product, 404);

        return $this->validFormResponse();
    }

    private function save(SaveOwnProductRequest $request, ?Product $product): JsonResponse
    {
        $validated = $request->validated();

        $normalizedVariants = $this->normalizeVariants($request);

        $hasVariants = collect($normalizedVariants)->filter(fn (array $v) => $v['option_ids'] !== [])->isNotEmpty();
        $stockValue = $hasVariants ? null : ($request->boolean('manage_stock') ? (int) $request->input('stock', 0) : null);

        $languages = $this->repo->activeLanguages();
        $defaultLocale = $languages->firstWhere('is_default', true)?->code ?? $languages->first()?->code ?? 'en';

        $returnPolicyOverride = $request->boolean('return_policy_override');

        $savedProduct = $this->service->saveProduct([
            'central_product_id' => null,
            'sku' => $request->input('sku') ?: null,
            'slug' => $request->input('slug') ?: null,
            'price' => $request->input('base_price', 0),
            'sale_price' => $request->input('sale_price') ?: null,
            'cost_price' => $request->input('cost_price') ?: null,
            'stock' => $stockValue,
            'min_stock' => (int) $request->input('min_stock', 0),
            'manage_stock' => $request->boolean('manage_stock'),
            'is_taxable' => $request->boolean('is_taxable'),
            'weight_grams' => $request->input('weight_grams') ?: null,
            'active' => $request->boolean('active'),
            'featured' => $request->boolean('featured'),
            'category_ids' => array_values(array_filter((array) $request->input('category_ids', []), fn ($id) => filled($id))),
            'translations' => $request->input('translations', []),
            'default_locale' => $defaultLocale,
            'is_own_product' => true,
            'requires_shipping' => false,
            'variants' => $normalizedVariants,
            'return_policy_override' => $returnPolicyOverride,
            'is_returnable' => $returnPolicyOverride ? $request->boolean('is_returnable') : true,
            'return_window_days' => $returnPolicyOverride && $request->filled('return_window_days') ? (int) $request->input('return_window_days') : null,
            'return_fee' => $returnPolicyOverride && $request->filled('return_fee') ? (float) $request->input('return_fee') : null,
            'return_video_required' => $returnPolicyOverride && $request->boolean('return_video_required'),
            'return_conditions' => $returnPolicyOverride ? ($request->input('return_conditions') ?: null) : null,
        ], $product);

        $savedProduct->badges()->sync(array_values(array_filter((array) $request->input('badge_ids', []), fn ($id) => filled($id))));

        // Primary image — a new upload always wins; explicit removal without a
        // new file simply deletes the existing one.
        if ($request->hasFile('primary_image')) {
            $file = $request->file('primary_image');
            $path = $file->store('product-images', 'public');
            $savedProduct->files()->where('key', 'like', 'primary_%')->delete();
            $savedProduct->files()->create([
                'key' => 'primary_original',
                'path' => $path,
                'storage_type' => FileStorageType::Public->value,
                'file_type' => FileType::Image->value,
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
            ]);
        } elseif ($request->boolean('remove_primary_image')) {
            $savedProduct->files()->where('key', 'like', 'primary_%')->delete();
        }

        // Gallery: remove flagged
        $removeGalleryIds = array_filter((array) $request->input('remove_gallery_ids', []), fn ($id) => filled($id));
        if (! empty($removeGalleryIds)) {
            $savedProduct->files()->whereIn('id', $removeGalleryIds)->where('key', 'gallery')->each(fn ($f) => $f->delete());
        }

        // Gallery: add new uploads
        foreach ($request->file('gallery_files', []) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $mime = $file->getMimeType();
            $fileType = Str::startsWith($mime, 'video/') ? FileType::Video->value : FileType::Image->value;
            $path = $file->store(sprintf('product-images/%s/gallery', $savedProduct->getKey()), 'public');
            $savedProduct->files()->create([
                'key' => 'gallery',
                'path' => $path,
                'storage_type' => FileStorageType::Public->value,
                'file_type' => $fileType,
                'mime_type' => $mime,
                'extension' => strtolower($file->getClientOriginalExtension() ?: 'jpg'),
                'size' => $file->getSize(),
            ]);
        }

        // Gallery: persist drag-and-drop order
        if ($request->filled('gallery_order')) {
            $orderedIds = array_filter(explode(',', (string) $request->input('gallery_order')));
            if (! empty($orderedIds)) {
                $galleryFiles = $savedProduct->files()->where('key', 'gallery')->whereIn('id', $orderedIds)->get();
                foreach ($orderedIds as $index => $fileId) {
                    $file = $galleryFiles->firstWhere('id', (int) $fileId);
                    $file?->update(['sort_order' => $index + 1]);
                }
            }
        }

        return $this->success(
            $product ? 'Product updated successfully.' : 'Product created successfully.',
            redirect: route('tenant.own-products.edit', $savedProduct),
        );
    }

    /**
     * @return list<array{id:?int,option_ids:list<int>,title:?string,sku:?string,weight_grams:?int,real_price:mixed,sell_price:mixed,stock:int,active:bool,image:?UploadedFile,remove_image:bool}>
     */
    private function normalizeVariants(SaveOwnProductRequest $request): array
    {
        $variants = $request->input('variants', []);
        $variantFiles = $request->file('variants', []);

        return array_values(
            collect($variants)
                ->map(function (array $variant, int $idx) use ($variantFiles): array {
                    $optionIds = collect($variant['pairs'] ?? [])
                        ->map(fn ($p) => $p['option_id'] ?? null)
                        ->filter(fn ($id) => filled($id))
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->all();

                    $imageFile = $variantFiles[$idx]['image'] ?? null;

                    return [
                        'id' => filled($variant['id'] ?? null) ? (int) $variant['id'] : null,
                        'option_ids' => $optionIds,
                        'title' => trim((string) ($variant['title'] ?? '')) ?: null,
                        'sku' => trim((string) ($variant['sku'] ?? '')) ?: null,
                        'weight_grams' => ($variant['weight_grams'] ?? '') !== '' ? (int) $variant['weight_grams'] : null,
                        'real_price' => $variant['price'] ?? 0,
                        'sell_price' => $variant['price'] ?? 0,
                        'stock' => (int) ($variant['stock'] ?? 0),
                        'active' => (bool) ($variant['active'] ?? true),
                        'image' => $imageFile instanceof UploadedFile ? $imageFile : null,
                        'remove_image' => (bool) ($variant['remove_image'] ?? false),
                    ];
                })
                ->filter(fn (array $v) => $v['option_ids'] !== [])
                ->all()
        );
    }

    private function viewData(?Product $product = null): array
    {
        $languages = $this->repo->activeLanguages();
        $defaultLocale = $languages->firstWhere('is_default', true)?->code ?? $languages->first()?->code ?? 'en';

        $blankTranslations = $languages->mapWithKeys(fn ($lang) => [
            $lang->code => ['name' => '', 'label' => '', 'summary' => '', 'description' => '', 'meta_keywords' => '', 'meta_description' => ''],
        ])->all();

        $translations = $blankTranslations;
        $variants = [];
        $existingImage = null;
        $existingGallery = collect();
        $categoryIds = [];
        $selectedBadgeIds = [];
        $productData = null;

        if ($product) {
            $product->load(['translations.language', 'categories', 'variants', 'files', 'badges']);

            $translations = array_replace_recursive(
                $blankTranslations,
                $product->translationsByLocale(['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description'])
            );

            $categoryIds = $product->categories->pluck('id')->all();
            $selectedBadgeIds = $product->badges->pluck('id')->all();
            $existingImage = $product->primary_image_url;
            $existingGallery = $product->files->where('key', 'gallery')->values();

            $allOptionIds = $product->variants->flatMap(fn ($v) => (array) $v->option_ids)->filter()->unique()->values()->all();
            $optionMap = $allOptionIds === []
                ? collect()
                : \App\Models\VariationOption::query()->whereIn('id', $allOptionIds)->get(['id', 'variation_id'])->keyBy('id');

            $variants = $product->variants->map(function ($variant) use ($optionMap) {
                $pairs = collect((array) $variant->option_ids)
                    ->map(fn ($optId) => ($opt = $optionMap->get((int) $optId)) ? ['variation_id' => (int) $opt->variation_id, 'option_id' => (int) $opt->id] : null)
                    ->filter()->values()->all();

                return [
                    'id' => $variant->id,
                    'pairs' => $pairs ?: [['variation_id' => '', 'option_id' => '']],
                    'title' => (string) ($variant->title ?? ''),
                    'sku' => (string) ($variant->sku ?? ''),
                    'weight_grams' => $variant->weight_grams !== null ? (string) $variant->weight_grams : '',
                    'price' => number_format((float) ($variant->default_sell_price ?? 0), 2, '.', ''),
                    'stock' => (int) ($variant->stock ?? 0),
                    'thumbnail_url' => $variant->thumbnail_url,
                    'active' => (bool) $variant->active,
                    'remove_image' => false,
                ];
            })->all();

            $productData = [
                'sku' => $product->sku ?? '',
                'slug' => $product->slug ?? '',
                'base_price' => number_format((float) ($product->default_price ?? 0), 2, '.', ''),
                'sale_price' => $product->sale_price !== null ? number_format((float) $product->sale_price, 2, '.', '') : '',
                'cost_price' => $product->cost_price !== null ? number_format((float) $product->cost_price, 2, '.', '') : '',
                'stock' => $product->stock,
                'min_stock' => $product->min_stock ?? 0,
                'manage_stock' => (bool) ($product->manage_stock ?? true),
                'is_taxable' => (bool) ($product->is_taxable ?? true),
                'weight_grams' => $product->weight_grams,
                'active' => $product->active,
                'featured' => $product->featured,
                'return_policy_override' => (bool) $product->return_policy_override,
                'is_returnable' => (bool) ($product->is_returnable ?? true),
                'return_window_days' => $product->return_window_days,
                'return_fee' => $product->return_fee !== null ? (float) $product->return_fee : null,
                'return_video_required' => (bool) $product->return_video_required,
                'return_conditions' => $product->return_conditions ?? '',
            ];
        }

        $variations = $this->repo->variationGroups();

        $variationsJson = $variations->keyBy('id')->map(fn ($v) => [
            'name' => $v->translationValue('name') ?? $v->slug,
            'options' => $v->options->map(fn ($o) => ['id' => $o->id, 'name' => $o->translationValue('name') ?? $o->slug])->values()->all(),
        ]);

        $categoryOptions = collect($this->repo->categoryTreeOptions())
            ->map(fn (array $row) => ['value' => $row['id'], 'label' => $row['name'], 'level' => $row['depth']])
            ->values()
            ->all();

        return [
            'product' => $product,
            'productData' => $productData,
            'pageTitle' => $product ? 'Edit Own Product' : 'Add Own Product',
            'pageDescription' => $product
                ? "Update this own-catalog product's details, images, and variants."
                : 'Create a new product managed entirely by your store.',
            'languages' => $languages,
            'defaultLocale' => $defaultLocale,
            'activeLocale' => $defaultLocale,
            'translations' => $translations,
            'variants' => $variants,
            'categoryIds' => $categoryIds,
            'categoryOptions' => $categoryOptions,
            'existingImage' => $existingImage,
            'existingGallery' => $existingGallery,
            'variations' => $variations,
            'variationsJson' => $variationsJson,
            'badges' => ProductBadge::query()->where('active', true)->orderBy('text')->get(),
            'selectedBadgeIds' => $selectedBadgeIds,
        ];
    }
}
