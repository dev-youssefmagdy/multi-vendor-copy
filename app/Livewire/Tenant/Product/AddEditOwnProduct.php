<?php

namespace App\Livewire\Tenant\Product;

use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddEditOwnProduct extends Component
{
    use InteractsWithTenantUi;
    use WithFileUploads;

    public ?int $productId = null;
    public string $slug = '';
    public string $price = '0.00';
    public ?int $stock = null;
    public bool $stockUnlimited = true;
    public bool $active = true;
    public bool $featured = false;
    public bool $returnPolicyOverride = false;
    public bool $isReturnable = true;
    public ?int $returnWindowDays = null;
    public ?float $returnFee = null;
    public bool $returnVideoRequired = false;
    public string $returnConditions = '';
    public array $categoryIds = [];
    public array $translations = [];
    public string $activeLocale = 'en';
    public mixed $imageFile = null;

    /**
     * Tenant own-product variants.
     * Each row: {id?, pairs:[{variation_id,option_id}], price, stock, image?, thumbnail_url?, remove_image?, active}
     */
    public array $variants = [];

    public function mount(?Product $product = null): void
    {
        $languages = app(TenantPanelRepository::class)->activeLanguages();
        $this->activeLocale = $languages->first()?->code ?? 'en';
        $this->translations = $languages->mapWithKeys(fn($language) => [
            $language->code => ['name' => '', 'description' => '', 'meta_keywords' => ''],
        ])->all();

        if (!$product || !$product->is_own_product) {
            return;
        }

        $product->load(['translations.language', 'categories', 'variants']);
        $this->productId = $product->id;
        $this->slug = $product->slug ?? '';
        $this->price = number_format((float) ($product->default_price ?? 0), 2, '.', '');
        $this->stock = $product->stock;
        $this->stockUnlimited = $product->stock === null;
        $this->active = $product->active;
        $this->featured = $product->featured;
        $this->returnPolicyOverride = (bool) $product->return_policy_override;
        $this->isReturnable = (bool) ($product->is_returnable ?? true);
        $this->returnWindowDays = $product->return_window_days;
        $this->returnFee = $product->return_fee !== null ? (float) $product->return_fee : null;
        $this->returnVideoRequired = (bool) $product->return_video_required;
        $this->returnConditions = $product->return_conditions ?? '';
        $this->categoryIds = $product->categories->pluck('id')->all();
        $this->translations = array_replace_recursive(
            $this->translations,
            $product->translationsByLocale(['name', 'description', 'meta_keywords'])
        );

        $allOptionIds = $product->variants
            ->flatMap(fn($v) => (array) $v->option_ids)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $optionMap = $allOptionIds === []
            ? collect()
            : \App\Models\VariationOption::query()->whereIn('id', $allOptionIds)->get(['id', 'variation_id'])->keyBy('id');

        $this->variants = $product->variants->map(function ($variant) use ($optionMap) {
            $pairs = collect((array) $variant->option_ids)
                ->map(function ($optId) use ($optionMap) {
                    $opt = $optionMap->get((int) $optId);
                    return $opt ? [
                        'variation_id' => (int) $opt->variation_id,
                        'option_id' => (int) $opt->id,
                    ] : null;
                })
                ->filter()
                ->values()
                ->all();

            return [
                'id' => $variant->id,
                'pairs' => $pairs ?: [['variation_id' => '', 'option_id' => '']],
                'price' => number_format((float) ($variant->default_sell_price ?? 0), 2, '.', ''),
                'stock' => (int) ($variant->stock ?? 0),
                'thumbnail_url' => $variant->thumbnail_url,
                'image' => null,
                'remove_image' => false,
                'active' => (bool) $variant->active,
            ];
        })->all();
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            'id' => null,
            'pairs' => [['variation_id' => '', 'option_id' => '']],
            'price' => $this->price,
            'stock' => 0,
            'thumbnail_url' => null,
            'image' => null,
            'remove_image' => false,
            'active' => true,
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
        if (preg_match('/^(\d+)\.pairs\.(\d+)\.variation_id$/', $key, $m)) {
            $i = (int) $m[1];
            $j = (int) $m[2];
            if (isset($this->variants[$i]['pairs'][$j])) {
                $this->variants[$i]['pairs'][$j]['option_id'] = '';
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

    public function save(TenantPanelService $service)
    {
        if (!$this->productId) {
            $limitService = app(\App\Services\Tenant\PlanLimitService::class);
            if (!$limitService->canPerform(tenant(), \App\Services\Tenant\PlanLimitService::FEATURE_PRODUCTS)) {
                $message = $limitService->errorMessage(\App\Services\Tenant\PlanLimitService::FEATURE_PRODUCTS);
                $this->dispatch('admin-toast', message: $message, type: 'error');
                $this->addError('name', $message);

                return null;
            }
        }

        $validated = $this->validate($this->rules());

        $hasVariants = collect($this->variants)
            ->filter(fn($v) => collect($v['pairs'] ?? [])->contains(fn($p) => filled($p['option_id'] ?? null)))
            ->isNotEmpty();

        $stockValue = $hasVariants ? null : ($this->stockUnlimited ? null : (int) $this->stock);

        $variantsPayload = collect($this->variants)
            ->map(function (array $v): array {
                $optionIds = collect($v['pairs'] ?? [])
                    ->map(fn($p) => $p['option_id'] ?? null)
                    ->filter(fn($id) => filled($id))
                    ->map(fn($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'id' => filled($v['id'] ?? null) ? (int) $v['id'] : null,
                    'option_ids' => $optionIds,
                    'real_price' => $v['price'] ?? 0,
                    'sell_price' => $v['price'] ?? 0,
                    'stock' => (int) ($v['stock'] ?? 0),
                    'active' => (bool) ($v['active'] ?? true),
                    'image' => $v['image'] ?? null,
                    'remove_image' => (bool) ($v['remove_image'] ?? false),
                ];
            })
            ->filter(fn(array $v) => $v['option_ids'] !== [])
            ->values()
            ->all();

        $seen = [];
        foreach ($variantsPayload as $i => $v) {
            $sig = implode(':', collect($v['option_ids'])->sort()->all());
            if (in_array($sig, $seen, true)) {
                $this->addError("variants.{$i}", 'Duplicate variant combination — each variant must be unique.');
                return null;
            }
            $seen[] = $sig;
        }

        $product = $service->saveProduct([
            'central_product_id' => null,
            'slug' => $validated['slug'] ?: null,
            'price' => $validated['price'],
            'active' => $validated['active'],
            'featured' => $validated['featured'],
            'return_policy_override' => $validated['returnPolicyOverride'],
            'is_returnable' => $validated['returnPolicyOverride'] ? $validated['isReturnable'] : true,
            'return_window_days' => $validated['returnPolicyOverride'] && $validated['returnWindowDays'] !== null
                ? (int) $validated['returnWindowDays'] : null,
            'return_fee' => $validated['returnPolicyOverride'] && $validated['returnFee'] !== null
                ? (float) $validated['returnFee'] : null,
            'return_video_required' => $validated['returnPolicyOverride'] && $validated['returnVideoRequired'],
            'return_conditions' => $validated['returnPolicyOverride'] ? $validated['returnConditions'] : null,
            'category_ids' => $validated['categoryIds'] ?? [],
            'translations' => $validated['translations'],
            'default_locale' => $this->activeLocale,
            'is_own_product' => true,
            'requires_shipping' => false,
            'stock' => $stockValue,
            'variants' => $variantsPayload,
        ], $this->productId
            ? Product::where('id', $this->productId)->where('is_own_product', true)->firstOrFail()
            : null);

        if ($this->imageFile) {
            $path = $this->imageFile->store('product-images', 'public');
            $product->images()->create(['path' => $path, 'order_number' => 0]);
        }

        session()->flash('status', $this->productId ? 'Product updated successfully.' : 'Product created successfully.');

        return redirect()->route('tenant.own-products.edit', $product);
    }

    protected function rules(): array
    {
        $rules = [
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('products', 'slug')->ignore($this->productId)],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
            'featured' => ['boolean'],
            'returnPolicyOverride' => ['boolean'],
            'isReturnable' => ['boolean'],
            'returnWindowDays' => ['nullable', 'integer', 'min:1', 'max:365'],
            'returnFee' => ['nullable', 'numeric', 'min:0'],
            'returnVideoRequired' => ['boolean'],
            'returnConditions' => ['nullable', 'string', 'max:2000'],
            'categoryIds' => ['array'],
            'categoryIds.*' => ['integer', 'exists:categories,id'],
            'imageFile' => ['nullable', 'image', 'max:5120'],
            'variants' => ['array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.pairs' => ['array', 'min:1'],
            'variants.*.pairs.*.variation_id' => ['nullable', 'integer'],
            'variants.*.pairs.*.option_id' => ['nullable', 'integer'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'variants.*.image' => ['nullable', 'image', 'max:5120'],
            'variants.*.remove_image' => ['boolean'],
            'variants.*.active' => ['boolean'],
        ];

        $languages = app(TenantPanelRepository::class)->activeLanguages();
        $defaultLocale = $languages->firstWhere('is_default', true)?->code ?? $this->activeLocale;

        foreach ($languages as $language) {
            $rules["translations.{$language->code}.name"] = [
                $language->code === $defaultLocale ? 'required' : 'nullable',
                'string',
                'max:255',
            ];
            $rules["translations.{$language->code}.description"] = ['nullable', 'string'];
            $rules["translations.{$language->code}.meta_keywords"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    #[Layout('layouts.tenant')]
    public function render()
    {
        $repository = app(TenantPanelRepository::class);

        return view('livewire.tenant.product.add-edit-own-product', [
            'pageTitle' => $this->productId ? 'Edit Own Product' : 'Add Own Product',
            'pageDescription' => $this->productId
                ? 'Update this product — it will not be shipped from central inventory.'
                : 'Add a product directly to your catalog. These products are not shipped from our side.',
            'languages' => $repository->activeLanguages(),
            'categories' => $repository->categoryOptions(),
            'variations' => $repository->variationGroups(),
        ]);
    }
}
