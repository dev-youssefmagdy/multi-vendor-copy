<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Tenant\CatalogController as BaseCatalogController;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\FileType;
use App\Models\Tenant\Product;

/** Catalog endpoints (reuse /api/v1 transformers) plus search and list screens. */
class CatalogController extends BaseCatalogController
{
    public function search(Request $request): JsonResponse
    {
        $repo = app(StorefrontRepository::class);
        $paginator = $repo->paginatedSearchProducts($this->filters($request), $this->perPage($request));

        return response()->json(['products' => $this->transformPaginator($paginator)]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('q', $request->query('keyword', '')));
        if (mb_strlen($keyword) < 2) {
            return response()->json(['data' => []]);
        }
        $items = app(StorefrontRepository::class)->autocompleteProducts($keyword);

        return response()->json(['data' => $items->map(fn ($p) => $this->transformProduct($p))->values()]);
    }

    /** best-selling | new-in | top-rated */
    public function lists(Request $request, string $list): JsonResponse
    {
        $repo = app(StorefrontRepository::class);
        $f = $this->filters($request);
        $n = $this->perPage($request);

        $paginator = match ($list) {
            'best-selling' => $repo->paginatedBestSellingProducts(30, $f, $n),
            'new-in' => $repo->paginatedNewInProducts($f, $n),
            'top-rated' => $repo->paginatedTopRatedProducts($f, $n),
            default => null,
        };

        if (!$paginator) {
            return response()->json(['success' => false, 'message' => 'Unknown list.'], 404);
        }

        return response()->json(['products' => $this->transformPaginator($paginator)]);
    }

    /** GET /products/{slug}: detail + gallery + related. */
    public function product(string $slug): JsonResponse
    {
        $repo = app(StorefrontRepository::class);
        $product = $repo->productBySlug($slug);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found.', 'code' => 'not_found'], 404);
        }
        $data = $this->transformProduct($product, detailed: true);
        $data['images'] = $this->gallery($product);
        $data['related'] = $this->relatedFor($product, $repo);

        return response()->json(['product' => $data]);
    }

    /** GET /products/{slug}/related */
    public function related(string $slug): JsonResponse
    {
        $repo = app(StorefrontRepository::class);
        $product = $repo->productBySlug($slug);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found.', 'code' => 'not_found'], 404);
        }

        return response()->json(['data' => $this->relatedFor($product, $repo)]);
    }

    protected function transformProduct(Product $product, bool $detailed = false): array
    {
        $data = parent::transformProduct($product, $detailed);
        // The base `url` points at the central/API host, useless in the app: dropped (use slug).
        unset($data['url']);
        $data['rating'] = round((float) $product->average_rating, 2);
        $data['reviews_summary'] = [
            'average' => $data['rating'],
            'count' => $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count(),
        ];

        return $data;
    }

    protected function transformCategory(\App\Models\Tenant\Category $category): array
    {
        return parent::transformCategory($category); // no `url` field is emitted
    }

    private function relatedFor(Product $product, StorefrontRepository $repo): array
    {
        $category = $product->categories->first();
        if (!$category) {
            return [];
        }

        return $repo->productsByCategory($category, 12)->reject(fn ($p) => $p->id === $product->id)
            ->take(10)->map(fn ($p) => $this->transformProduct($p))->values()->all();
    }

    /** @return string[] absolute image URLs (product, central fallback, variant thumbnails), deduped. */
    private function gallery(Product $product): array
    {
        $keys = ['primary_medium', 'primary_original', 'gallery'];
        $pick = fn ($files) => $files->whereIn('key', $keys)->where('file_type', FileType::Image)
            ->map(fn ($f) => $f->full_path)->filter()->values();

        $urls = $pick($product->files);
        if ($urls->isEmpty() && $product->centralProduct) {
            $urls = $pick($product->centralProduct->files);
        }
        foreach ($product->variants as $v) {
            if ($v->thumbnail_url) {
                $urls->push($v->thumbnail_url);
            }
        }
        if ($urls->isEmpty() && $product->primary_image_url) {
            $urls->push($product->primary_image_url);
        }

        return $urls->map(fn ($u) => str_starts_with($u, 'http') ? $u : url($u))->unique()->values()->all();
    }
}
