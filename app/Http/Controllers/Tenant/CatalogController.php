<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CatalogController extends Controller
{
    /** GET /categories */
    public function categories(): JsonResponse
    {
        $repo = app(StorefrontRepository::class);

        return response()->json([
            'data' => $repo->rootCategoriesWithChildren()->map(fn(Category $c) => $this->transformCategory($c))->values(),
        ]);
    }

    /** GET /categories/{slug} */
    public function category(Request $request, string $slug): JsonResponse
    {
        $repo = app(StorefrontRepository::class);
        $category = $repo->categoryBySlug($slug);

        if (!$category) {
            return response()->json(['success' => false, 'message' => __('Category not found.')], 404);
        }

        $products = $repo->paginatedProductsByCategory($category, $this->filters($request), $this->perPage($request));

        return response()->json([
            'category' => $this->transformCategory($category),
            'products' => $this->transformPaginator($products),
        ]);
    }

    /** GET /products */
    public function products(Request $request): JsonResponse
    {
        $repo = app(StorefrontRepository::class);
        $products = $repo->paginatedProducts($this->filters($request), $this->perPage($request));

        return response()->json(['products' => $this->transformPaginator($products)]);
    }

    /** GET /products/{slug} */
    public function product(string $slug): JsonResponse
    {
        $repo = app(StorefrontRepository::class);
        $product = $repo->productBySlug($slug);

        if (!$product) {
            return response()->json(['success' => false, 'message' => __('Product not found.')], 404);
        }

        return response()->json(['product' => $this->transformProduct($product, detailed: true)]);
    }

    private function filters(Request $request): array
    {
        return [
            'keyword' => (string) $request->query('keyword', ''),
            'sort' => (string) $request->query('sort', 'latest'),
            'availability' => (string) $request->query('availability', ''),
            'product_flag' => (string) $request->query('product_flag', ''),
            'on_sale' => (string) $request->query('sale', ''),
            'ratings' => $request->query('ratings'),
            'section' => (string) $request->query('section', ''),
            'min' => $request->query('min'),
            'max' => $request->query('max'),
        ];
    }

    private function perPage(Request $request): int
    {
        return max(1, min(60, (int) $request->query('per_page', 15)));
    }

    private function transformPaginator(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => collect($paginator->items())->map(fn(Product $p) => $this->transformProduct($p))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    private function transformCategory(Category $category): array
    {
        return [
            'id' => $category->id,
            'slug' => $category->translationValue('slug'),
            'name' => $category->translationValue('name'),
            'image' => $category->files->first()?->full_path,
            'children' => $category->children->map(fn(Category $c) => $this->transformCategory($c))->values(),
        ];
    }

    private function transformProduct(Product $product, bool $detailed = false): array
    {
        $pricing = $product->storefrontPricing();

        $data = [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->translationValue('name') ?? $product->slug,
            'image' => $product->primary_image_url,
            'price' => $pricing['current_price'] ?? null,
            'base_price' => $pricing['base_price'] ?? null,
            'is_flash_sale' => (bool) ($pricing['is_flash_sale'] ?? false),
            'url' => route('tenant.storefront.product', $product->slug),
        ];

        if ($detailed) {
            $data['description'] = $product->translationValue('description');
            $data['variants'] = $product->variants->map(fn($v) => [
                'id' => $v->id,
                'title' => $v->title,
                'sku' => $v->sku,
                'active' => (bool) $v->active,
                'stock' => $v->stock,
            ])->values();
            $data['reviews_summary'] = [
                'average' => round((float) \App\Models\Tenant\ProductRate::where('product_id', $product->id)->avg('stars'), 2),
                'count' => \App\Models\Tenant\ProductRate::where('product_id', $product->id)->count(),
            ];
        }

        return $data;
    }
}
