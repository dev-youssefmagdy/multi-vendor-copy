<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Catalog\SaveProductRequest;
use App\Models\Product as CentralProduct;
use App\Models\ProductEditRequest;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\PlanLimitService;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProductController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function create(): View
    {
        return $this->form();
    }

    public function edit(Product $product): View
    {
        return $this->form($product);
    }

    private function form(?Product $product = null): View
    {
        $languages = $this->repo->activeLanguages();
        $activeLocale = $languages->first()?->code ?? 'en';

        $translations = $languages->mapWithKeys(fn ($language) => [$language->code => ['name' => '', 'description' => '', 'meta_keywords' => '', 'meta_description' => '']])->all();
        $variants = [];
        $centralProductId = null;
        $pendingEditRequest = null;

        if ($product) {
            $product->load(['translations.language', 'categories', 'variants', 'badges']);
            $centralProductId = $product->central_product_id;
            $translations = array_replace_recursive($translations, $product->translationsByLocale(['name', 'description', 'meta_keywords', 'meta_description']));
            $variants = $this->syncVariantsFromCentral($centralProductId, $product);

            $tenantId = tenant()->getTenantKey();
            $pendingEditRequest = tenancy()->central(fn () => ProductEditRequest::where('tenant_id', $tenantId)
                ->where('product_id', $product->id)
                ->where('status', 'pending')
                ->first(['id', 'requested_translations', 'created_at'])
                ?->toArray());
        }

        $centralProduct = $this->repo->centralProductSnapshot($centralProductId);

        $shippingCosts = collect();
        if ($centralProductId) {
            $centralModel = CentralProduct::query()->find($centralProductId);
            if ($centralModel) {
                $shippingCosts = $centralModel->applicableFixedShippingCosts();
            }
        }

        return view('tenant.pages.catalog.products.form', [
            'product' => $product,
            'pageTitle' => $product ? 'Edit Product' : 'Add Product',
            'pageDescription' => $product ? 'Update vendor sale pricing, visibility, synced variants, and translated storefront content.' : 'Create a tenant product mapped to central catalog resources and pricing.',
            'languages' => $languages,
            'activeLocale' => $activeLocale,
            'translations' => $translations,
            'categoryTree' => $this->repo->categoryTreeOptions(),
            'badges' => ProductBadge::query()->where('active', true)->orderBy('text')->get(),
            'centralProduct' => $centralProduct,
            'centralProductId' => $centralProductId,
            'shippingCosts' => $shippingCosts,
            'weightGrams' => $centralProduct['weight_grams'] ?? 0,
            'variants' => $variants,
            'pendingEditRequest' => $pendingEditRequest,
        ]);
    }

    public function centralSearch(Request $request): JsonResponse
    {
        $result = $this->repo->searchCentralProducts(
            (string) $request->input('q', ''),
            (int) $request->input('page', 1),
        );

        return response()->json([
            'results' => collect($result['items'])->map(fn ($name, $id) => ['id' => $id, 'text' => $name])->values()->all(),
            'pagination' => ['more' => $result['has_more']],
        ]);
    }

    public function centralSnapshot(Request $request, int $centralProduct): JsonResponse
    {
        $snapshot = $this->repo->centralProductSnapshot($centralProduct);

        if (!$snapshot) {
            return $this->failure('The selected central product could not be found.', 404);
        }

        $product = $request->integer('product_id')
            ? Product::query()->with('variants')->find($request->integer('product_id'))
            : null;

        $variants = $this->syncVariantsFromCentral($centralProduct, $product);

        return response()->json([
            'data' => $snapshot,
            'slug' => $snapshot['slug'] ?? '',
            'price' => number_format((float) $snapshot['current_price'], 2, '.', ''),
            'snapshot_html' => view('tenant.pages.catalog.products._central-snapshot', ['centralProduct' => $snapshot])->render(),
            'variants_html' => view('tenant.pages.catalog.products._variants-table', ['variants' => $variants])->render(),
        ]);
    }

    public function store(SaveProductRequest $request): JsonResponse
    {
        return $this->save($request, null);
    }

    public function update(SaveProductRequest $request, Product $product): JsonResponse
    {
        return $this->save($request, $product);
    }

    private function save(SaveProductRequest $request, ?Product $product): JsonResponse
    {
        if (!$product) {
            $limitService = app(PlanLimitService::class);
            if (!$limitService->canPerform(tenant(), PlanLimitService::FEATURE_PRODUCTS)) {
                return $this->failure($limitService->errorMessage(PlanLimitService::FEATURE_PRODUCTS));
            }
        }

        $validated = $request->validated();

        if ($validated['central_product_id'] ?? null) {
            $snapshot = $this->repo->centralProductSnapshot((int) $validated['central_product_id']);

            if (!$snapshot) {
                return $this->failure('The selected central product could not be found.', 422, ['central_product_id' => ['The selected central product could not be found.']]);
            }
        }

        $editRequestSubmitted = false;

        if ($product) {
            $currentTranslations = $product->translationsByLocale(['name', 'description']);
            $changedTranslations = [];

            foreach ($validated['translations'] as $locale => $fields) {
                $currentName = data_get($currentTranslations, "$locale.name", '');
                $currentDescription = data_get($currentTranslations, "$locale.description", '');
                $newName = $fields['name'] ?? '';
                $newDescription = $fields['description'] ?? '';

                if ($newName !== $currentName || $newDescription !== $currentDescription) {
                    $changedTranslations[$locale] = ['name' => $newName, 'description' => $newDescription];
                }
            }

            if ($changedTranslations) {
                $this->service->submitProductEditRequest($product, $changedTranslations, $currentTranslations);

                foreach (array_keys($changedTranslations) as $locale) {
                    $validated['translations'][$locale]['name'] = data_get($currentTranslations, "$locale.name", '');
                    $validated['translations'][$locale]['description'] = data_get($currentTranslations, "$locale.description", '');
                }

                $editRequestSubmitted = true;
            }
        }

        $saved = $this->service->saveProduct([
            'central_product_id' => $validated['central_product_id'] ?? null,
            'slug' => $validated['slug'] ?? null,
            'price' => $validated['price'],
            'active' => $validated['active'] ?? false,
            'featured' => $validated['featured'] ?? false,
            'category_ids' => $validated['category_ids'] ?? [],
            'translations' => $validated['translations'],
            'variants' => collect($validated['variants'] ?? [])->map(fn (array $variant) => [
                'id' => $variant['id'] ?? null,
                'central_product_variant_id' => $variant['central_product_variant_id'] ?? null,
                'real_price' => $variant['real_price'] ?? 0,
                'sell_price' => $variant['sell_price'] ?? 0,
                'active' => $variant['active'] ?? true,
            ])->all(),
            'default_locale' => $validated['active_locale'],
        ], $product);

        $saved->badges()->sync(array_filter((array) ($validated['badge_ids'] ?? []), filled(...)));

        $message = $editRequestSubmitted
            ? 'Product updated. Your changes to the product name/description have been submitted for admin review and will be applied once approved.'
            : ($product ? 'Product updated successfully.' : 'Product created successfully.');

        return $this->success($message, redirect: route('tenant.products.edit', $saved));
    }

    public function validateStore(SaveProductRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function validateUpdate(SaveProductRequest $request, Product $product): JsonResponse
    {
        return $this->validFormResponse();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function syncVariantsFromCentral(?int $centralProductId, ?Product $product = null): array
    {
        if (!$centralProductId) {
            return [];
        }

        $snapshot = $this->repo->centralProductSnapshot($centralProductId);

        if (!$snapshot) {
            return [];
        }

        $existingVariants = $product?->variants?->keyBy('central_product_variant_id') ?? collect();

        return collect($snapshot['variants'] ?? [])->map(function (array $centralVariant) use ($existingVariants) {
            $existing = $existingVariants->get($centralVariant['id']);

            return [
                'id' => $existing?->id,
                'central_product_variant_id' => $centralVariant['id'],
                'title' => $centralVariant['title'],
                'sku' => $centralVariant['sku'] ?? null,
                'options_label' => implode(' / ', $centralVariant['options'] ?? []),
                'status' => $centralVariant['status'] ?? 'Active',
                'image_url' => $centralVariant['image_url'] ?? null,
                'thumbnail_path' => $existing?->thumbnail_path,
                'weight_grams' => $centralVariant['weight_grams'] ?? null,
                'real_price' => number_format((float) $centralVariant['price'], 2, '.', ''),
                'sell_price' => number_format((float) ($existing?->default_sell_price ?? $centralVariant['price']), 2, '.', ''),
                'active' => (bool) ($existing?->active ?? true),
            ];
        })->all();
    }
}
