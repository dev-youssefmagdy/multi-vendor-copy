<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class ProductsListController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
    ) {
    }

    public function index(Request $request): View
    {
        $stats = $this->repo->productStats();

        return view('tenant.pages.catalog.products.index', [
            'stats' => Metric::cards([
                ['label' => 'Products', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Products in this tenant catalog', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => $stats['active'], 'format' => 'number', 'caption' => 'Currently saleable products', 'dot' => 'dot-green'],
                ['label' => 'Featured', 'value' => $stats['featured'], 'format' => 'number', 'caption' => 'Homepage promoted products', 'dot' => 'dot-amber'],
            ]),
            'categoryOptions' => $this->categoryOptions(),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('product', 'Product')->orderable(false),
                TableColumn::make('central_price', 'Central Price')->orderable(false),
                TableColumn::make('vendor_price', 'Vendor Price')->orderable(false),
                TableColumn::make('ai_price', 'AI Price')->orderable(false),
                TableColumn::make('stock', 'Stock')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::make('categories', 'Categories')->orderable(false),
                TableColumn::make('updated_at', 'Updated At'),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status', 'stock', 'category']);
        $imageIds = $request->input('filters.image_ids', []);
        if (is_string($imageIds)) {
            $imageIds = array_filter(explode(',', $imageIds), fn ($id) => $id !== '');
        }
        $filters['image_ids'] = array_values(array_map('intval', (array) $imageIds));

        $query = $this->repo->queryProducts($filters);

        // Batch the central-product snapshot lookup once per draw: memoize the
        // map in a closure-captured variable, computed lazily the first time a
        // column needs it (using the full set of central_product_ids present
        // in the current page's already-resolved model collection). Yajra
        // resolves the paginated Eloquent collection once per draw and every
        // editColumn/addColumn closure below runs against that same in-memory
        // collection, so the first closure invocation seeds the map and every
        // subsequent row/column reuses it — one repository call per draw.
        $centralSnapshots = null;
        $resolveSnapshots = function () use (&$centralSnapshots, $query, $request): array {
            if ($centralSnapshots === null) {
                // Yajra's EloquentDataTable doesn't expose its resolved page
                // collection to editColumn/addColumn closures, so we re-derive the
                // exact same page window (DataTables' own `start`/`length` request
                // params) from a clone of the same filtered query, fetch only the
                // central_product_ids appearing on that page, and batch ONE
                // centralProductSnapshots() call for the whole draw. The first
                // closure invocation for this draw seeds the map; every other
                // row/column below reuses it.
                $start = max(0, (int) $request->input('start', 0));
                $length = (int) $request->input('length', 10);
                $pageQuery = (clone $query);
                if ($length > 0) {
                    $pageQuery->skip($start)->take($length);
                }
                $ids = $pageQuery->pluck('central_product_id')->filter()->unique()->values()->all();
                $centralSnapshots = $this->repo->centralProductSnapshots($ids);
            }

            return $centralSnapshots;
        };

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('product', function (Product $product) use ($resolveSnapshots) {
                $central = $resolveSnapshots()[$product->central_product_id] ?? null;

                return view('tenant.pages.catalog.products._cols.product', ['product' => $product, 'central' => $central])->render();
            })
            ->editColumn('central_price', function (Product $product) use ($resolveSnapshots) {
                $central = $resolveSnapshots()[$product->central_product_id] ?? null;

                return view('tenant.pages.catalog.products._cols.central-price', ['central' => $central])->render();
            })
            ->editColumn('vendor_price', fn (Product $product) => view('tenant.pages.catalog.products._cols.vendor-price', ['product' => $product])->render())
            ->editColumn('ai_price', fn (Product $product) => view('tenant.pages.catalog.products._cols.ai-price', ['product' => $product])->render())
            ->editColumn('stock', fn (Product $product) => view('tenant.pages.catalog.products._cols.stock', ['product' => $product])->render())
            ->editColumn('status', fn (Product $product) => view('tenant.pages.catalog.products._cols.status', ['product' => $product])->render())
            ->editColumn('categories', fn (Product $product) => e($product->categories->pluck(fn ($q) => $q->translationValue('name'))->filter()->implode(', ') ?: 'Unassigned'))
            ->editColumn('updated_at', fn (Product $product) => $product->updated_at?->format('M d, Y'))
            ->addColumn('actions', fn (Product $product) => view('tenant.pages.catalog.products._cols.actions', ['product' => $product])->render())
            ->rawColumns(['product', 'central_price', 'vendor_price', 'ai_price', 'stock', 'status', 'actions'])
            ->toJson();
    }

    public function toggleActive(Product $product): JsonResponse
    {
        $product->update(['active' => !$product->active]);

        return $this->success('Product status updated successfully.');
    }

    public function toggleFeatured(Product $product): JsonResponse
    {
        $product->update(['featured' => !$product->featured]);

        return $this->success('Product featured state updated successfully.');
    }

    /**
     * Flat, hierarchical option list for the category filter: root categories
     * followed by their descendants indented, so picking any node (parent or
     * child) filters by that category plus its own sub-tree.
     *
     * @return array<string, string>
     */
    private function categoryOptions(): array
    {
        $options = [];

        $roots = Category::query()
            ->with(['translations.language', 'children.translations.language', 'children.children.translations.language', 'children.children.children.translations.language'])
            ->whereNull('parent_id')
            ->orderBy('order_number')
            ->get();

        $flatten = function ($categories, int $depth) use (&$flatten, &$options): void {
            foreach ($categories as $category) {
                $options[(string) $category->id] = str_repeat('— ', $depth) . ($category->translationValue('name') ?: ('#' . $category->id));
                if ($category->children->isNotEmpty()) {
                    $flatten($category->children, $depth + 1);
                }
            }
        };

        $flatten($roots, 0);

        return $options;
    }
}
