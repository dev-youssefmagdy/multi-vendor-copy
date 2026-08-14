<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ImageSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

// Image search v1 — expandable to vector DB (pgvector, Pinecone, etc.).
// Thin controller: validate the query image, embed it, rank candidate
// products by cosine similarity via ImageSearchService, then hand the
// ranked IDs to the surface-appropriate results view.
class ImageSearchController extends Controller
{
    public function __construct(private readonly ImageSearchService $imageSearchService)
    {
    }

    private function rules(): array
    {
        return ['image' => ['required', 'image', 'max:8192']];
    }

    /**
     * Storefront: search the current tenant's published catalog, then
     * redirect into the existing search results page (BestSellingPage) in
     * "image" mode so results render through the normal product grid.
     */
    public function storefront(Request $request): RedirectResponse
    {
        $request->validate($this->rules());

        $embedding = $this->imageSearchService->embedFromUploadedFile($request->file('image'))['embedding'];

        $centralIds = \App\Models\Tenant\Product::query()
            ->where('active', true)
            ->pluck('central_product_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $rankedCentralIds = $this->imageSearchService->search($embedding, $centralIds, 60);

        session(['image_search_product_ids' => $rankedCentralIds]);

        $routeName = $request->routeIs('tenant.path.*') ? 'tenant.path.storefront.search' : 'tenant.storefront.search';

        return redirect()->route($routeName, ['mode' => 'image']);
    }

    /**
     * Admin: search the full central catalog.
     */
    public function admin(Request $request): JsonResponse
    {
        $request->validate($this->rules());

        $embedding = $this->imageSearchService->embedFromUploadedFile($request->file('image'))['embedding'];

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

        $embedding = $this->imageSearchService->embedFromUploadedFile($request->file('image'))['embedding'];

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

        $products = Product::query()->whereIn('id', $rankedIds)->get()->keyBy('id');

        return collect($rankedIds)
            ->map(fn(int $id) => $products->get($id))
            ->filter()
            ->map(fn(Product $product) => [
                'id' => $product->id,
                'name' => $product->translationValue('name') ?? $product->slug,
                'sku' => $product->sku,
                'slug' => $product->slug,
                'image' => $product->primary_image_url,
            ])
            ->values()
            ->all();
    }
}
