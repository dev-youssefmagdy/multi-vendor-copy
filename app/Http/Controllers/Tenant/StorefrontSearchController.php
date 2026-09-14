<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontSearchController extends Controller
{
    /** GET /search/autocomplete */
    public function autocomplete(Request $request, StorefrontRepository $repo): JsonResponse
    {
        $keyword = trim((string) $request->query('q', ''));
        if (strlen($keyword) < 2) {
            return response()->json(['products' => []]);
        }

        $products = $repo->autocompleteProducts($keyword);
        $currentCurrency = $request->attributes->get('storefrontCurrentCurrency') ?: $repo->currentCurrency();
        $symbol = data_get($currentCurrency, 'symbol', '$');
        $rate = (float) data_get($currentCurrency, 'conversion_rate', 1.0);

        return response()->json([
            'products' => $products->map(function ($p) use ($symbol, $rate) {
                $pricing = $p->storefrontPricing();

                return [
                    'name' => $p->translationValue('name') ?? $p->slug,
                    'slug' => $p->slug,
                    'url' => route('tenant.storefront.product', $p->slug),
                    'image' => $p->primary_image_url,
                    'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
                    'original_price' => $pricing['original_price'] !== null
                        ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2)
                        : null,
                    'has_discount' => $pricing['has_discount'],
                    'discount_percentage' => $pricing['discount_percentage'],
                ];
            })->values(),
        ]);
    }

    /** GET /api/products */
    public function products(Request $request, StorefrontRepository $repo): JsonResponse
    {
        $currentCurrency = $request->attributes->get('storefrontCurrentCurrency')
            ?: $repo->currentCurrency();
        $paginator = $repo->paginatedProducts([], 20);
        $cardView = $this->resolveCardView($request);
        $cards = $this->renderCards($paginator, $cardView, $currentCurrency);

        return response()->json([
            'has_more' => $paginator->hasMorePages(),
            'next_page' => $paginator->currentPage() + 1,
            'cards' => $cards,
        ]);
    }

    /** GET /categories-products/{slug?} */
    public function categoryProducts(Request $request, StorefrontRepository $repo, ?string $slug = null): JsonResponse
    {
        $paginator = $this->paginatedCategoryProducts($request, $repo, $slug);
        $currentCurrency = $repo->currentCurrency();
        $cards = $this->renderCards($paginator, 'themes.elora.pages._product-card', $currentCurrency);

        return response()->json([
            'has_more' => $paginator->hasMorePages(),
            'cards' => $cards,
        ]);
    }

    /** GET /vendor/{tenant}/search/autocomplete */
    public function autocompletePath(Request $request, StorefrontRepository $repo): JsonResponse
    {
        $keyword = trim((string) $request->query('q', ''));
        if (strlen($keyword) < 2) {
            return response()->json(['products' => []]);
        }

        $products = $repo->autocompleteProducts($keyword);
        $currentCurrency = $request->attributes->get('storefrontCurrentCurrency') ?: $repo->currentCurrency();
        $symbol = data_get($currentCurrency, 'symbol', '$');
        $rate = (float) data_get($currentCurrency, 'conversion_rate', 1.0);
        $tenantSlug = $request->route('tenant');

        return response()->json([
            'products' => $products->map(function ($p) use ($symbol, $rate, $tenantSlug) {
                $pricing = $p->storefrontPricing();

                return [
                    'name' => $p->name ?? $p->slug,
                    'slug' => $p->slug,
                    'url' => route('tenant.path.storefront.product', [$tenantSlug, $p->slug]),
                    'image' => $p->primary_image_url,
                    'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
                    'original_price' => $pricing['original_price'] !== null
                        ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2)
                        : null,
                    'has_discount' => $pricing['has_discount'],
                    'discount_percentage' => $pricing['discount_percentage'],
                ];
            })->values(),
        ]);
    }

    /** GET /vendor/{tenant}/api/products */
    public function productsPath(Request $request, StorefrontRepository $repo): JsonResponse
    {
        $currentCurrency = $request->attributes->get('storefrontCurrentCurrency')
            ?: $repo->currentCurrency();
        $paginator = $repo->paginatedProducts([], 20);
        $cardView = $this->resolveCardView($request);
        $cards = $this->renderCards($paginator, $cardView, $currentCurrency);

        return response()->json([
            'has_more' => $paginator->hasMorePages(),
            'next_page' => $paginator->currentPage() + 1,
            'cards' => $cards,
        ]);
    }

    /** GET /vendor/{tenant}/categories-products/{slug?} */
    public function categoryProductsPath(Request $request, StorefrontRepository $repo, ?string $slug = null): JsonResponse
    {
        $paginator = $this->paginatedCategoryProducts($request, $repo, $slug);
        $currentCurrency = $repo->currentCurrency();
        $cards = $this->renderCards($paginator, 'themes.elora.pages._product-card', $currentCurrency);

        return response()->json([
            'has_more' => $paginator->hasMorePages(),
            'cards' => $cards,
        ]);
    }

    private function paginatedCategoryProducts(Request $request, StorefrontRepository $repo, ?string $slug)
    {
        $category = $repo->categoryBySlug($slug);
        $filters = [
            'keyword' => trim((string) $request->query('keyword', '')),
            'sort' => $request->query('sort', 'latest'),
            'availability' => $request->query('availability', ''),
            'product_flag' => $request->query('product_flag', ''),
            'on_sale' => $request->query('on_sale', ''),
            'ratings' => $request->query('ratings', ''),
            'min' => $request->query('min', ''),
            'max' => $request->query('max', ''),
        ];

        return $repo->paginatedProductsByCategory($slug ? $category : null, $filters, 15);
    }

    private function resolveCardView(Request $request): string
    {
        $theme = $request->attributes->get('storefrontCurrentTheme');
        $themeSlug = $theme?->slug ?? 'elora';
        $cardView = "themes.{$themeSlug}.pages._product-card";

        return view()->exists($cardView) ? $cardView : 'themes.elora.pages._product-card';
    }

    private function renderCards($paginator, string $cardView, $currentCurrency): array
    {
        return collect($paginator->items())->map(fn($product) => view($cardView, [
            'product' => $product,
            'badge' => null,
            'currentCurrency' => $currentCurrency,
        ])->render())->values()->all();
    }
}
