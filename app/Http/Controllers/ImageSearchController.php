<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ImageSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

// Image search v1 — expandable to vector DB (pgvector, Pinecone, etc.).
// Thin controller: validate the query image, embed it, rank candidate
// products by cosine similarity via ImageSearchService, then hand the
// ranked IDs to the surface-appropriate results view.
class ImageSearchController extends Controller
{
    private const RESULTS_TTL_MINUTES = 30;

    public function __construct(private readonly ImageSearchService $imageSearchService)
    {
    }

    private function rules(): array
    {
        return ['image' => ['required', 'image', 'max:8192']];
    }

    /**
     * @param array<int> $rankedCentralIds
     */
    private function stashResults(array $rankedCentralIds): string
    {
        $uuid = (string) Str::uuid();

        // Cache::store(...) is a real method (not proxied through Stancl
        // Tenancy's CacheManager::__call), so it bypasses the per-tenant
        // ->tags() wrapping that CacheTenancyBootstrapper forces onto the
        // bare Cache facade — tags aren't supported by the file cache driver.
        Cache::store(config('cache.default'))->put(
            "image_search_results:{$uuid}",
            $rankedCentralIds,
            now()->addMinutes(self::RESULTS_TTL_MINUTES),
        );

        return $uuid;
    }

    /**
     * @return array<int>
     */
    public static function resultsFor(string $uuid): array
    {
        return (array) Cache::store(config('cache.default'))->get("image_search_results:{$uuid}", []);
    }

    /**
     * Storefront: search the current tenant's published catalog, then
     * redirect into the existing search results page (BestSellingPage) in
     * "image" mode so results render through the normal product grid.
     */
    public function storefront(Request $request): RedirectResponse
    {
        $request->validate($this->rules());

        $routeName = $request->routeIs('tenant.path.*') ? 'tenant.path.storefront.search' : 'tenant.storefront.search';

        try {
            $embedding = $this->imageSearchService->embedFromUploadedFile($request->file('image'))['embedding'];
        } catch (\RuntimeException $e) {
            return redirect()->route($routeName, ['mode' => 'image'])->withErrors(['image' => $e->getMessage()]);
        }

        $centralIds = \App\Models\Tenant\Product::query()
            ->where('active', true)
            ->pluck('central_product_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $rankedCentralIds = $this->imageSearchService->search($embedding, $centralIds, 60);

        $uuid = $this->stashResults($rankedCentralIds);

        return redirect()->route($routeName, ['mode' => 'image', 'iid' => $uuid]);
    }

    /**
     * Admin: search the full central catalog.
     */
    public function admin(Request $request): JsonResponse
    {
        $request->validate($this->rules());

        try {
            $embedding = $this->imageSearchService->embedFromUploadedFile($request->file('image'))['embedding'];
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $candidateIds = Product::query()->pluck('id')->all();
        $rankedIds = $this->imageSearchService->search($embedding, $candidateIds, 40);

        return response()->json(['products' => $this->serializeProducts($rankedIds)]);
    }

    /**
     * Tenant panel: search the central catalog available to tenants
     * (published products), used to find products to add to the store.
     */
    public function tenant(Request $request): JsonResponse
    {
        $request->validate($this->rules());

        try {
            $embedding = $this->imageSearchService->embedFromUploadedFile($request->file('image'))['embedding'];
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $candidateIds = Product::query()
            ->where('status', \App\Enums\ProductStatus::Published->value)
            ->pluck('id')
            ->all();
        $rankedIds = $this->imageSearchService->search($embedding, $candidateIds, 40);

        return response()->json(['products' => $this->serializeProducts($rankedIds)]);
    }

    /**
     * @param array<int> $rankedIds
     */
    private function serializeProducts(array $rankedIds): array
    {
        if (empty($rankedIds)) {
            return [];
        }

        $products = Product::query()
            ->with(['files', 'variants.files', 'translations.language'])
            ->whereIn('id', $rankedIds)
            ->get()
            ->keyBy('id');

        return collect($rankedIds)
            ->map(fn(int $id) => $products->get($id))
            ->filter()
            ->map(fn(Product $product) => [
                'id' => $product->id,
                'name' => $product->translationValue('name') ?? $product->slug,
                'names' => collect($product->translationsByLocale(['name']))
                    ->map(fn(array $fields) => $fields['name'] ?? ''),
                'sku' => $product->sku,
                'slug' => $product->slug,
                'image' => $product->primary_image_url,
                'images' => collect([$product->primary_image_url])
                    ->merge($product->files->where('key', 'gallery')->pluck('full_path'))
                    ->merge($product->variants->pluck('files')->flatten()->where('key', 'variant_thumb')->pluck('full_path'))
                    ->filter()
                    ->unique()
                    ->values(),
            ])
            ->values()
            ->all();
    }
}
