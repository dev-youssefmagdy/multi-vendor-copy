<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomePageController extends Controller
{
    public function __invoke()
    {
        $repo = app(\App\Repositories\Tenant\StorefrontRepository::class);
        $currentCurrency = request()->attributes->get('storefrontCurrentCurrency') ?: $repo->currentCurrency();
        $tab = request('tab', 'flash');
        $page = max(1, (int) request('page', 1));
        $perPage = 10;

        switch ($tab) {
            case 'flash':
                $flashSales = $repo->activeFlashSales();
                $allFlash = $flashSales->flatMap(fn($fs) => $fs->products)->unique('id')
                    ->sortBy(fn($p) => (float) $p->storefrontPricing()['current_price'])
                    ->values();
                $offset = ($page - 1) * $perPage;
                $items = $allFlash->slice($offset, $perPage)->values();
                $hasMore = $allFlash->count() > $offset + $perPage;
                $badge = __('Flash Sale');
                break;
            case 'bestselling':
                $paginator = $repo->paginatedBestSellingProducts(30, [], $perPage);
                $items = collect($paginator->items());
                $hasMore = $paginator->hasMorePages();
                $badge = __('Best-Selling');
                break;
            case 'newin':
                $paginator = $repo->newInProducts($perPage, $page);
                $items = collect($paginator->items());
                $hasMore = $paginator->hasMorePages();
                $badge = __('New In');
                break;
            case 'toprated':
                $paginator = $repo->paginatedTopRatedProducts([], $perPage, 1);
                $items = collect($paginator->items());
                $hasMore = $paginator->hasMorePages();
                $badge = null;
                break;
            default:
                return response()->json(['cards' => [], 'has_more' => false, 'next_page' => 1]);
        }

        $cards = $items->map(fn($p) => view('themes.elora.pages._product-card', [
            'product' => $p,
            'badge' => $badge,
            'currentCurrency' => $currentCurrency,
        ])->render())->values()->all();

        return response()->json([
            'cards' => $cards,
            'has_more' => $hasMore,
            'next_page' => $page + 1,
        ]);
    }
}
