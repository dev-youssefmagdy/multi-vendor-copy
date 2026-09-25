<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\TenantPanelRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class OwnProductsListController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
    ) {
    }

    public function index(Request $request): View
    {
        // Card grid (Own products tab): 12 own products per page, newest first.
        $products = $this->repo->queryOwnProducts([])->paginate(12)->withQueryString();

        return view('tenant.pages.catalog.own-products.index', [
            'products' => $products,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status', 'stock']);

        return DataTables::eloquent($this->repo->queryOwnProducts($filters))
            ->addIndexColumn()
            ->editColumn('product', fn (Product $product) => view('tenant.pages.catalog.own-products._cols.product', ['product' => $product])->render())
            ->editColumn('price', fn (Product $product) => '$'.number_format((float) $product->default_price, 2))
            ->editColumn('stock', fn (Product $product) => $product->stock !== null ? (string) $product->stock : 'Unlimited')
            ->editColumn('status', fn (Product $product) => view('tenant.pages.catalog.own-products._cols.status', ['product' => $product])->render())
            ->editColumn('added', fn (Product $product) => $product->created_at?->format('M d, Y'))
            ->addColumn('actions', fn (Product $product) => view('tenant.pages.catalog.own-products._cols.actions', ['product' => $product])->render())
            ->rawColumns(['product', 'status', 'actions'])
            ->toJson();
    }

    public function destroy(Product $product): JsonResponse
    {
        abort_unless($product->is_own_product, 404);

        $product->delete();

        return $this->success('Product deleted.');
    }
}
