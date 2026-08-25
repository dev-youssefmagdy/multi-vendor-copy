<?php

namespace App\Http\Controllers;

use App\Services\ImageSearchService;
use Illuminate\Http\Request;
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
     * Admin panel: search the full central catalog, return ranked IDs as JSON.
     */
    public function adminPanel(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate($this->rules());

        try {
            $embedding = $this->imageSearchService->embedFromUploadedFile($request->file('image'))['embedding'];
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $candidateIds = \App\Models\Product::query()
            ->whereNull('deleted_at')
            ->pluck('id')
            ->all();

        $rankedIds = $this->imageSearchService->search($embedding, $candidateIds, 60);

        return response()->json(['ids' => $rankedIds]);
    }

    /**
     * Tenant panel: search only this tenant's catalog (own + assigned products),
     * return ranked central_product_ids as JSON.
     */
    public function tenantPanel(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate($this->rules());

        try {
            $embedding = $this->imageSearchService->embedFromUploadedFile($request->file('image'))['embedding'];
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $candidateIds = \App\Models\Tenant\Product::query()
            ->whereNotNull('central_product_id')
            ->pluck('central_product_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $rankedIds = $this->imageSearchService->search($embedding, $candidateIds, 60);

        return response()->json(['ids' => $rankedIds]);
    }
}
