<?php

namespace App\Livewire\Tenant\Product;

use App\Models\Tenant\Product;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

class AddEditProduct extends Component
{
    public ?int $productId = null;
    public ?int $centralProductId = null;
    public string $slug = '';
    public string $price = '0.00';
    public bool $active = true;
    public bool $featured = false;
    public array $categoryIds = [];
    public array $translations = [];
    public array $variants = [];
    public string $activeLocale = 'en';

    public function mount(?Product $product = null): void
    {
        $languages = app(TenantPanelRepository::class)->activeLanguages();
        $this->activeLocale = $languages->first()?->code ?? 'en';
        $this->translations = $languages->mapWithKeys(fn($language) => [$language->code => ['name' => '', 'description' => '', 'meta_keywords' => '', 'meta_description' => '']])->all();

        if (!$product) {
            return;
        }

        $product->load(['translations.language', 'categories', 'variants']);
        $this->productId = $product->id;
        $this->centralProductId = $product->central_product_id;
        $this->slug = $product->slug ?? '';
        $this->price = number_format((float) ($product->default_price ?? 0), 2, '.', '');
        $this->active = $product->active;
        $this->featured = $product->featured;
        $this->categoryIds = $product->categories->pluck('id')->all();
        $this->translations = array_replace_recursive($this->translations, $product->translationsByLocale(['name', 'description', 'meta_keywords', 'meta_description']));
        $this->syncVariantsFromCentral($this->centralProductId, $product);
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function updatedCentralProductId($value): void
    {
        $centralProductId = filled($value) ? (int) $value : null;

        $this->syncVariantsFromCentral($centralProductId);

        if (!$this->productId && $centralProductId) {
            $snapshot = app(TenantPanelRepository::class)->centralProductSnapshot($centralProductId);

            if ($snapshot) {
                if (blank($this->slug)) {
                    $this->slug = (string) ($snapshot['slug'] ?? '');
                }

                if ((float) $this->price <= 0) {
                    $this->price = number_format((float) $snapshot['current_price'], 2, '.', '');
                }
            }
        }
    }

    public function save(TenantPanelService $service)
    {
        $validated = $this->validate($this->rules());

        if ($validated['centralProductId'] ?? null) {
            $snapshot = app(TenantPanelRepository::class)->centralProductSnapshot((int) $validated['centralProductId']);

            if (!$snapshot) {
                $this->addError('centralProductId', 'The selected central product could not be found.');

                return null;
            }
        }

        // When editing an existing product, intercept name/description changes
        // and route them through the admin approval flow instead of saving directly.
        $editRequestSubmitted = false;
        if ($this->productId) {
            $existingProduct = Product::query()->findOrFail($this->productId);
            $currentTranslations = $existingProduct->translationsByLocale(['name', 'description']);

            $changedTranslations = [];
            foreach ($validated['translations'] as $locale => $fields) {
                $currentName = data_get($currentTranslations, "$locale.name", '');
                $currentDescription = data_get($currentTranslations, "$locale.description", '');
                $newName = $fields['name'] ?? '';
                $newDescription = $fields['description'] ?? '';

                if ($newName !== $currentName || $newDescription !== $currentDescription) {
                    $changedTranslations[$locale] = [
                        'name' => $newName,
                        'description' => $newDescription,
                    ];
                }
            }

            if ($changedTranslations) {
                $service->submitProductEditRequest($existingProduct, $changedTranslations, $currentTranslations);

                // Strip name/description from translations so they are NOT updated now;
                // only meta_keywords and meta_description save immediately.
                foreach (array_keys($changedTranslations) as $locale) {
                    $validated['translations'][$locale]['name'] = data_get($currentTranslations, "$locale.name", '');
                    $validated['translations'][$locale]['description'] = data_get($currentTranslations, "$locale.description", '');
                }

                $editRequestSubmitted = true;
            }
        }

        $product = $service->saveProduct([
            'central_product_id' => $this->centralProductId,
            'slug' => $validated['slug'] ?? null,
            'price' => $validated['price'],
            'active' => $validated['active'],
            'featured' => $validated['featured'],
            'category_ids' => $validated['categoryIds'] ?? [],
            'translations' => $validated['translations'],
            'variants' => collect($validated['variants'] ?? [])->map(function (array $variant) {
                return [
                    'id' => $variant['id'] ?? null,
                    'central_product_variant_id' => $variant['central_product_variant_id'] ?? null,
                    'real_price' => $variant['real_price'] ?? 0,
                    'sell_price' => $variant['sell_price'] ?? 0,
                    'active' => $variant['active'] ?? true,
                ];
            })->all(),
            'default_locale' => $this->activeLocale,
        ], $this->productId ? ($existingProduct ?? Product::query()->findOrFail($this->productId)) : null);

        if ($editRequestSubmitted) {
            session()->flash('status', 'Product updated. Your changes to the product name/description have been submitted for admin review and will be applied once approved.');
            session()->flash('status_type', 'info');
        } else {
            session()->flash('status', $this->productId ? 'Product updated successfully.' : 'Product created successfully.');
        }

        return redirect()->route('tenant.products.edit', $product);
    }

    protected function rules(): array
    {
        $rules = [
            // 'centralProductId' => ['nullable', 'integer'],
            'slug' => ['nullable', 'string', 'max:300', Rule::unique('products', 'slug')->ignore($this->productId)],
            'price' => ['required', 'numeric', 'min:0'],
            'active' => ['boolean'],
            'featured' => ['boolean'],
            'categoryIds' => ['array'],
            'categoryIds.*' => ['integer', 'exists:categories,id'],
            'variants' => ['array'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.central_product_variant_id' => ['nullable', 'integer'],
            'variants.*.real_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.sell_price' => ['required', 'numeric', 'min:0'],
            'variants.*.active' => ['boolean'],
        ];

        $languages = app(TenantPanelRepository::class)->activeLanguages();
        $defaultLocale = $languages->firstWhere('is_default', true)?->code ?? $this->activeLocale;

        foreach ($languages as $language) {
            $rules["translations.{$language->code}.name"] = [$language->code === $defaultLocale ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.description"] = ['nullable', 'string'];
            $rules["translations.{$language->code}.meta_keywords"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.meta_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    #[Layout('layouts.tenant')]
    public function render()
    {
        $repository = app(TenantPanelRepository::class);
        $centralProduct = $repository->centralProductSnapshot($this->centralProductId);

        // Load fixed shipping costs for this product's weight so the vendor can
        // see per-country rates directly on the product edit page.
        $shippingCosts = collect();
        if ($this->centralProductId) {
            $centralModel = \App\Models\Product::query()->find($this->centralProductId);
            if ($centralModel) {
                $shippingCosts = $centralModel->applicableFixedShippingCosts();
            }
        }

        return view('livewire.tenant.product.add-edit-product', [
            'pageTitle' => $this->productId ? 'Edit Product' : 'Add Product',
            'pageDescription' => $this->productId ? 'Update vendor sale pricing, visibility, synced variants, and translated storefront content.' : 'Create a tenant product mapped to central catalog resources and pricing.',
            'languages' => $repository->activeLanguages(),
            'categories' => $repository->categoryOptions(),
            'categoryTree' => $repository->categoryTreeOptions(),
            'centralProduct' => $centralProduct,
            'shippingCosts' => $shippingCosts,
            'weightGrams' => $centralProduct?->weight_grams ?? 0,
        ]);
    }

    protected function syncVariantsFromCentral(?int $centralProductId, ?Product $product = null): void
    {
        if (!$centralProductId) {
            $this->variants = [];

            return;
        }

        $snapshot = app(TenantPanelRepository::class)->centralProductSnapshot($centralProductId);

        if (!$snapshot) {
            $this->variants = [];

            return;
        }

        $existingVariants = $product?->variants
            ? $product->variants->keyBy('central_product_variant_id')
            : collect($this->variants)->filter(fn($variant) => filled($variant['central_product_variant_id'] ?? null))->keyBy('central_product_variant_id');

        $this->variants = collect($snapshot['variants'] ?? [])->map(function (array $centralVariant) use ($existingVariants) {
            $existing = $existingVariants->get($centralVariant['id']);

            return [
                'id' => is_array($existing) ? ($existing['id'] ?? null) : $existing?->id,
                'central_product_variant_id' => $centralVariant['id'],
                'title' => $centralVariant['title'],
                'sku' => $centralVariant['sku'] ?? null,
                'options_label' => implode(' / ', $centralVariant['options'] ?? []),
                'status' => $centralVariant['status'] ?? 'Active',
                'image_url' => $centralVariant['image_url'] ?? null,
                'thumbnail_path' => is_array($existing) ? ($existing['thumbnail_path'] ?? null) : $existing?->thumbnail_path,
                'weight_grams' => $centralVariant['weight_grams'] ?? null,
                'real_price' => number_format((float) $centralVariant['price'], 2, '.', ''),
                'sell_price' => number_format((float) (is_array($existing) ? ($existing['default_sell_price'] ?? $centralVariant['price']) : ($existing?->default_sell_price ?? $centralVariant['price'])), 2, '.', ''),
                'active' => (bool) (is_array($existing) ? ($existing['active'] ?? true) : ($existing?->active ?? true)),
            ];
        })->all();
    }
}
