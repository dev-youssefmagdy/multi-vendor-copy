<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Tenant\CatalogController as BaseCatalogController;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
