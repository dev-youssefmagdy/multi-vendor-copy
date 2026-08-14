<?php


namespace App\Http\Controllers\Tenant;

use App\Enums\FileStorageType;
use App\Enums\FileType;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OwnProductController extends Controller
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {}

    public function create()
    {
        return view('tenant.product.add-edit-own-product', $this->viewData());
    }

    public function edit(Product $product)
    {
        abort_unless($product->is_own_product, 404);
        return view('tenant.product.add-edit-own-product', $this->viewData($product));
    }

    public function store(Request $request)
    {
        return $this->processForm($request, null);
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($product->is_own_product, 404);
        return $this->processForm($request, $product);
    }

    public function validateForm(Request $request, ?Product $product = null)
    {
        if ($product) {
            abort_unless($product->is_own_product, 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $this->rules($product));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $seen = [];
        foreach ($this->normalizeVariants($request) as $variant) {
            $sig = implode(':', collect($variant['option_ids'])->sort()->all());
            if ($sig !== '' && in_array($sig, $seen, true)) {
                return response()->json([
                    'success' => false,
                    'errors'  => ['variants' => ['Duplicate variant combination — each variant must be unique.']],
                ], 422);
            }
            if ($sig !== '') {
                $seen[] = $sig;
            }
        }

        return response()->json(['success' => true]);
    }

    protected function processForm(Request $request, ?Product $product)
    {
        $request->validate($this->rules($product));

        $normalizedVariants = $this->normalizeVariants($request);

        $seen = [];
        foreach ($normalizedVariants as $variant) {
            $sig = implode(':', collect($variant['option_ids'])->sort()->all());
            if ($sig !== '' && in_array($sig, $seen, true)) {
                return back()
                    ->withErrors(['variants' => 'Duplicate variant combination — each variant must be unique.'])
                    ->withInput();
            }
            if ($sig !== '') {
                $seen[] = $sig;
            }
        }

        $hasVariants = collect($normalizedVariants)->filter(fn($v) => $v['option_ids'] !== [])->isNotEmpty();
        $stockValue  = $hasVariants ? null : ($request->boolean('manage_stock') ? (int) $request->input('stock', 0) : null);

        $languages     = $this->repo->activeLanguages();
        $defaultLocale = $languages->firstWhere('is_default', true)?->code ?? $languages->first()?->code ?? 'en';

        $savedProduct = $this->service->saveProduct([
            'central_product_id' => null,
            'sku'                => $request->input('sku') ?: null,
            'slug'               => $request->input('slug') ?: null,
            'price'              => $request->input('base_price', 0),
            'sale_price'         => $request->input('sale_price') ?: null,
            'cost_price'         => $request->input('cost_price') ?: null,
            'stock'              => $stockValue,
            'min_stock'          => (int) $request->input('min_stock', 0),
            'manage_stock'       => $request->boolean('manage_stock'),
            'is_taxable'         => $request->boolean('is_taxable'),
            'weight_grams'       => $request->input('weight_grams') ?: null,
            'active'             => $request->boolean('active'),
            'featured'           => $request->boolean('featured'),
            'category_ids'       => array_values(array_filter((array) $request->input('category_ids', []), fn($id) => filled($id))),
            'translations'       => $request->input('translations', []),
            'default_locale'     => $defaultLocale,
            'is_own_product'     => true,
            'requires_shipping'  => false,
            'variants'           => $normalizedVariants,
        ], $product);

        $savedProduct->badges()->sync(array_values(array_filter((array) $request->input('badge_ids', []), fn($id) => filled($id))));

        // Primary image — a new upload always wins; explicit removal without a
        // new file simply deletes the existing one.
        if ($request->hasFile('primary_image')) {
            $file = $request->file('primary_image');
            $path = $file->store('product-images', 'public');
            $savedProduct->files()->where('key', 'like', 'primary_%')->delete();
            $savedProduct->files()->create([
                'key'          => 'primary_original',
                'path'         => $path,
                'storage_type' => FileStorageType::Public->value,
                'file_type'    => FileType::Image->value,
                'mime_type'    => $file->getMimeType(),
                'extension'    => $file->getClientOriginalExtension(),
                'size'         => $file->getSize(),
            ]);
        } elseif ($request->boolean('remove_primary_image')) {
            $savedProduct->files()->where('key', 'like', 'primary_%')->delete();
        }

        // Gallery: remove flagged
        $removeGalleryIds = array_filter((array) $request->input('remove_gallery_ids', []), fn($id) => filled($id));
        if (!empty($removeGalleryIds)) {
            $savedProduct->files()->whereIn('id', $removeGalleryIds)->where('key', 'gallery')->each(fn($f) => $f->delete());
        }

        // Gallery: add new uploads
        foreach ($request->file('gallery_files', []) as $file) {
            if (!$file instanceof UploadedFile) continue;
            $mime     = $file->getMimeType();
            $fileType = Str::startsWith($mime, 'video/') ? FileType::Video->value : FileType::Image->value;
            $path     = $file->store(sprintf('product-images/%s/gallery', $savedProduct->getKey()), 'public');
            $savedProduct->files()->create([
                'key'          => 'gallery',
                'path'         => $path,
                'storage_type' => FileStorageType::Public->value,
                'file_type'    => $fileType,
                'mime_type'    => $mime,
                'extension'    => strtolower($file->getClientOriginalExtension() ?: 'jpg'),
                'size'         => $file->getSize(),
            ]);
        }

        session()->flash('status', $product ? 'Product updated successfully.' : 'Product created successfully.');
        return redirect()->route('tenant.own-products.edit', $savedProduct);
    }

    protected function normalizeVariants(Request $request): array
    {
        $variants     = $request->input('variants', []);
        $variantFiles = $request->file('variants', []);

        return array_values(
            collect($variants)
                ->map(function (array $variant, int $idx) use ($variantFiles): array {
                    $optionIds = collect($variant['pairs'] ?? [])
                        ->map(fn($p) => $p['option_id'] ?? null)
                        ->filter(fn($id) => filled($id))
                        ->map(fn($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->all();

                    $imageFile = $variantFiles[$idx]['image'] ?? null;

                    return [
                        'id'           => filled($variant['id'] ?? null) ? (int) $variant['id'] : null,
                        'option_ids'   => $optionIds,
                        'title'        => trim((string) ($variant['title'] ?? '')) ?: null,
                        'sku'          => trim((string) ($variant['sku'] ?? '')) ?: null,
                        'weight_grams' => ($variant['weight_grams'] ?? '') !== '' ? (int) $variant['weight_grams'] : null,
                        'real_price'   => $variant['price'] ?? 0,
                        'sell_price'   => $variant['price'] ?? 0,
                        'stock'        => (int) ($variant['stock'] ?? 0),
                        'active'       => (bool) ($variant['active'] ?? true),
                        'image'        => $imageFile instanceof UploadedFile ? $imageFile : null,
                        'remove_image' => (bool) ($variant['remove_image'] ?? false),
                    ];
                })
                ->filter(fn(array $v) => $v['option_ids'] !== [])
                ->all()
        );
    }

    protected function rules(?Product $product = null): array
    {
        $languages     = $this->repo->activeLanguages();
        $defaultLocale = $languages->firstWhere('is_default', true)?->code ?? $languages->first()?->code ?? 'en';

        $rules = [
            'sku'                  => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->id)],
            'slug'                 => ['nullable', 'string', 'max:150'],
            'base_price'           => ['required', 'numeric', 'min:0'],
            'sale_price'           => ['nullable', 'numeric', 'min:0'],
            'cost_price'           => ['nullable', 'numeric', 'min:0'],
            'stock'                => ['required', 'integer', 'min:0'],
            'min_stock'            => ['required', 'integer', 'min:0'],
            'manage_stock'         => ['boolean'],
            'is_taxable'           => ['boolean'],
            'weight_grams'         => ['nullable', 'integer', 'min:0'],
            'active'               => ['boolean'],
            'featured'             => ['boolean'],
            'category_ids'         => ['array'],
            'category_ids.*'       => ['integer', 'exists:categories,id'],
            'badge_ids'            => ['array'],
            'badge_ids.*'          => ['integer', 'exists:product_badges,id'],
            'primary_image'        => ['nullable', 'image', 'max:4096'],
            'gallery_files'        => ['array'],
            'gallery_files.*'      => ['nullable', 'mimes:jpg,jpeg,png,gif,webp,mp4,webm,mov', 'max:51200'],
            'remove_gallery_ids'   => ['array'],
            'remove_gallery_ids.*' => ['nullable', 'integer'],
            'variants'                          => ['array'],
            'variants.*.id'                     => ['nullable', 'integer'],
            'variants.*.pairs'                  => ['array', 'min:1'],
            'variants.*.pairs.*.variation_id'   => ['nullable', 'integer'],
            'variants.*.pairs.*.option_id'      => ['nullable', 'integer'],
            'variants.*.price'                  => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock'                  => ['nullable', 'integer', 'min:0'],
            'variants.*.sku'                    => ['nullable', 'string', 'max:100'],
            'variants.*.weight_grams'           => ['nullable', 'integer', 'min:0'],
            'variants.*.image'                  => ['nullable', 'image', 'max:5120'],
            'variants.*.remove_image'           => ['boolean'],
            'variants.*.active'                 => ['boolean'],
        ];

        foreach ($languages as $language) {
            $rules["translations.{$language->code}.name"]             = [$language->code === $defaultLocale ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.label"]            = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.summary"]          = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.description"]      = ['nullable', 'string'];
            $rules["translations.{$language->code}.meta_keywords"]    = ['nullable', 'string', 'max:500'];
            $rules["translations.{$language->code}.meta_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    protected function viewData(?Product $product = null): array
    {
        $languages     = $this->repo->activeLanguages();
        $defaultLocale = $languages->firstWhere('is_default', true)?->code ?? $languages->first()?->code ?? 'en';

        $blankTranslations = $languages->mapWithKeys(fn($lang) => [
            $lang->code => ['name' => '', 'label' => '', 'summary' => '', 'description' => '', 'meta_keywords' => '', 'meta_description' => ''],
        ])->all();

        $translations    = $blankTranslations;
        $variants        = [];
        $existingImage   = null;
        $existingGallery = collect();
        $categoryIds     = [];
        $selectedBadgeIds = [];
        $productData     = null;

        if ($product) {
            $product->load(['translations.language', 'categories', 'variants', 'files', 'badges']);

            $translations = array_replace_recursive(
                $blankTranslations,
                $product->translationsByLocale(['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description'])
            );

            $categoryIds     = $product->categories->pluck('id')->all();
            $selectedBadgeIds = $product->badges->pluck('id')->all();
            $existingImage   = $product->primary_image_url;
            $existingGallery = $product->files->where('key', 'gallery')->values();

            $allOptionIds = $product->variants->flatMap(fn($v) => (array) $v->option_ids)->filter()->unique()->values()->all();
            $optionMap    = $allOptionIds === []
                ? collect()
                : \App\Models\VariationOption::query()->whereIn('id', $allOptionIds)->get(['id', 'variation_id'])->keyBy('id');

            $variants = $product->variants->map(function ($variant) use ($optionMap) {
                $pairs = collect((array) $variant->option_ids)
                    ->map(fn($optId) => ($opt = $optionMap->get((int) $optId)) ? ['variation_id' => (int) $opt->variation_id, 'option_id' => (int) $opt->id] : null)
                    ->filter()->values()->all();

                return [
                    'id'            => $variant->id,
                    'pairs'         => $pairs ?: [['variation_id' => '', 'option_id' => '']],
                    'title'         => (string) ($variant->title ?? ''),
                    'sku'           => (string) ($variant->sku ?? ''),
                    'weight_grams'  => $variant->weight_grams !== null ? (string) $variant->weight_grams : '',
                    'price'         => number_format((float) ($variant->default_sell_price ?? 0), 2, '.', ''),
                    'stock'         => (int) ($variant->stock ?? 0),
                    'thumbnail_url' => $variant->thumbnail_url,
                    'active'        => (bool) $variant->active,
                    'remove_image'  => false,
                ];
            })->all();

            $productData = [
                'sku'          => $product->sku ?? '',
                'slug'         => $product->slug ?? '',
                'base_price'   => number_format((float) ($product->default_price ?? 0), 2, '.', ''),
                'sale_price'   => $product->sale_price !== null ? number_format((float) $product->sale_price, 2, '.', '') : '',
                'cost_price'   => $product->cost_price !== null ? number_format((float) $product->cost_price, 2, '.', '') : '',
                'stock'        => $product->stock,
                'min_stock'    => $product->min_stock ?? 0,
                'manage_stock' => (bool) ($product->manage_stock ?? true),
                'is_taxable'   => (bool) ($product->is_taxable ?? true),
                'weight_grams' => $product->weight_grams,
                'active'       => $product->active,
                'featured'     => $product->featured,
            ];
        }

        $variations = $this->repo->variationGroups();

        $variationsJson = $variations->keyBy('id')->map(fn($v) => [
            'name'    => $v->translationValue('name') ?? $v->slug,
            'options' => $v->options->map(fn($o) => ['id' => $o->id, 'name' => $o->translationValue('name') ?? $o->slug])->values()->all(),
        ]);

        $flatTree = $this->repo->categoryTreeOptions();
        $catMap   = [];
        foreach ($flatTree as $row) {
            $catMap[$row['id']] = ['id' => $row['id'], 'name' => $row['name'], 'parent_id' => null, 'has_children' => false];
        }
        $prevByDepth = [];
        foreach ($flatTree as $row) {
            $d = $row['depth'];
            $prevByDepth[$d] = $row['id'];
            if ($d > 0 && isset($prevByDepth[$d - 1])) {
                $catMap[$row['id']]['parent_id'] = $prevByDepth[$d - 1];
                $catMap[$prevByDepth[$d - 1]]['has_children'] = true;
            }
        }

        return [
            'product'         => $product,
            'productData'     => $productData,
            'pageTitle'       => $product ? 'Edit Own Product' : 'Add Own Product',
            'pageDescription' => $product
                ? "Update this own-catalog product's details, images, and variants."
                : 'Create a new product managed entirely by your store.',
            'languages'       => $languages,
            'defaultLocale'   => $defaultLocale,
            'translations'    => $translations,
            'variants'        => $variants,
            'categoryIds'     => $categoryIds,
            'existingImage'   => $existingImage,
            'existingGallery' => $existingGallery,
            'catMap'          => $catMap,
            'variations'      => $variations,
            'variationsJson'  => $variationsJson,
            'badges'          => ProductBadge::query()->where('active', true)->orderBy('text')->get(),
            'selectedBadgeIds' => $selectedBadgeIds,
        ];
    }
}
