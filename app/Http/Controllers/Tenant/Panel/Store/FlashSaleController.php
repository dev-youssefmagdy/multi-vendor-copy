<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Store;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Store\SaveFlashSaleRequest;
use App\Models\Country;
use App\Models\Tenant\FlashSale;
use App\Models\TenantCountry;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\FlashSaleMediaService;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class FlashSaleController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function index(): View
    {
        $counts = FlashSale::query()
            ->selectRaw('country_id, count(*) as aggregate')
            ->groupBy('country_id')
            ->pluck('aggregate', 'country_id');

        $countryIds = TenantCountry::query()
            ->where('tenant_id', tenant()->id)
            ->where('is_active', true)
            ->pluck('country_id');

        $countries = Country::query()->whereIn('id', $countryIds)->orderBy('name')->get();

        $domain = tenant()?->domains()->first()?->domain;
        $storefrontBase = $domain
            ? ((str_starts_with($domain, 'http') ? '' : 'https://').$domain)
            : null;

        return view('tenant.pages.store.flash-sales.index', [
            'countries' => $countries,
            'defaultCount' => (int) ($counts[null] ?? 0),
            'countryCounts' => $counts,
            'storefrontBase' => $storefrontBase,
        ]);
    }

    public function list(?int $countryId = null): View
    {
        $country = $countryId ? Country::query()->find($countryId) : null;
        $stats = $this->repo->flashSaleStats($countryId);

        return view('tenant.pages.store.flash-sales.list', [
            'countryId' => $countryId,
            'country' => $country,
            'defaultBanner' => asset('elora/assets/images/flash-sales-banner.png'),
            'stats' => Metric::cards([
                ['label' => 'Flash Sales', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Discount campaigns configured', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => $stats['active'], 'format' => 'number', 'caption' => 'Currently running campaigns', 'dot' => 'dot-green'],
                ['label' => 'Avg Discount', 'value' => $stats['avg_discount'], 'format' => 'percent', 'caption' => 'Average configured discount', 'dot' => 'dot-amber'],
                ['label' => 'Products', 'value' => $stats['products'], 'format' => 'number', 'caption' => 'Unique products enrolled in flash sales', 'dot' => 'dot-violet'],
            ]),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('product', 'Product')->orderable(false),
                TableColumn::make('banner', 'Banner')->orderable(false),
                TableColumn::make('discount', 'Discount')->orderable(false),
                TableColumn::make('window', 'Window')->orderable(false),
                TableColumn::make('source', 'Source')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request, ?int $countryId = null): JsonResponse
    {
        $query = $this->repo->queryFlashSales($countryId);

        // Batch productNamesForIds()/productImagesForIds() ONCE per draw
        // (mirroring ProductsListController::data()'s centralProductSnapshots
        // memoization): re-derive the current page window from a clone of the
        // filtered query, collect every product id appearing on that page,
        // and resolve names/images with exactly one call each, reused by
        // every row's "product" column closure below.
        $names = null;
        $images = null;
        $resolveProductLookups = function () use (&$names, &$images, $query, $request): array {
            if ($names === null) {
                $start = max(0, (int) $request->input('start', 0));
                $length = (int) $request->input('length', 10);
                $pageQuery = (clone $query);
                if ($length > 0) {
                    $pageQuery->skip($start)->take($length);
                }
                $flashSaleIds = $pageQuery->pluck('id')->all();

                $pivotIds = DB::table('flash_sale_product')
                    ->whereIn('flash_sale_id', $flashSaleIds)
                    ->pluck('product_id');
                $directIds = FlashSale::query()->whereIn('id', $flashSaleIds)->pluck('product_id')->filter();

                $ids = $pivotIds->merge($directIds)->unique()->map(fn ($id) => (int) $id)->values()->all();

                $names = $this->repo->productNamesForIds($ids);
                $images = $this->repo->productImagesForIds($ids);
            }

            return [$names, $images];
        };

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('product', function (FlashSale $flashSale) use ($resolveProductLookups) {
                [$names, $images] = $resolveProductLookups();

                return view('tenant.pages.store.flash-sales._cols.product', [
                    'flashSale' => $flashSale,
                    'names' => $names,
                    'images' => $images,
                ])->render();
            })
            ->editColumn('banner', fn (FlashSale $flashSale) => view('tenant.pages.store.flash-sales._cols.banner', ['flashSale' => $flashSale])->render())
            ->editColumn('discount', fn (FlashSale $flashSale) => e(number_format((float) $flashSale->discount_percentage, 2)).'%')
            ->editColumn('window', fn (FlashSale $flashSale) => e(optional($flashSale->start_date)->format('M d, Y') ?: '-').' - '.e(optional($flashSale->end_date)->format('M d, Y') ?: '-'))
            ->editColumn('source', fn (FlashSale $flashSale) => $flashSale->central_flash_sale_id
                ? '<span class="badge badge-cyan">Platform</span>'
                : '<span class="badge badge-indigo">Store</span>')
            ->editColumn('status', fn (FlashSale $flashSale) => '<span class="badge '.($flashSale->active ? 'badge-green' : 'badge-amber').'">'.e($flashSale->active ? 'Active' : 'Inactive').'</span>')
            ->addColumn('actions', fn (FlashSale $flashSale) => $flashSale->central_flash_sale_id
                ? '<span style="font-size:11.5px;color:var(--t3);">Managed by platform</span>'
                : view('tenant.pages.store.flash-sales._cols.actions', ['flashSale' => $flashSale])->render())
            ->rawColumns(['product', 'banner', 'source', 'status', 'actions'])
            ->toJson();
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $result = $this->repo->searchProducts(
            (string) $request->input('q', ''),
            (int) $request->input('page', 1),
        );

        return response()->json([
            'results' => collect($result['items'])->map(fn ($name, $id) => ['id' => $id, 'text' => $name])->values()->all(),
            'pagination' => ['more' => $result['has_more']],
        ]);
    }

    public function show(FlashSale $flashSale): JsonResponse
    {
        $flashSale->loadMissing(['product', 'products']);

        $productIds = $flashSale->products->pluck('id')
            ->whenEmpty(fn ($collection) => $flashSale->product ? $collection->push($flashSale->product->id) : $collection)
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return response()->json(['data' => [
            // "_options" is read by TenantForm.fill() first (declared before
            // "product_ids" below, and JS object key order follows insertion
            // order) to inject the currently-selected <option> elements into
            // the ajax select2 before it marks them selected — that select2
            // never ran its own search for these ids, so without this they
            // would not render on edit.
            'product_ids_options' => $this->repo->productNamesForIds($productIds),
            'product_ids' => $productIds,
            'discount_percentage' => number_format((float) $flashSale->discount_percentage, 2, '.', ''),
            'start_date' => optional($flashSale->start_date)->format('Y-m-d\TH:i'),
            'end_date' => optional($flashSale->end_date)->format('Y-m-d\TH:i'),
            'active' => $flashSale->active,
            'banner_image_current' => $flashSale->banner_url,
            'country_id' => $flashSale->country_id,
        ]]);
    }

    public function store(SaveFlashSaleRequest $request, FlashSaleMediaService $mediaService): JsonResponse
    {
        return $this->save($request, null, $mediaService);
    }

    public function update(SaveFlashSaleRequest $request, FlashSale $flashSale, FlashSaleMediaService $mediaService): JsonResponse
    {
        return $this->save($request, $flashSale, $mediaService);
    }

    private function save(SaveFlashSaleRequest $request, ?FlashSale $flashSale, FlashSaleMediaService $mediaService): JsonResponse
    {
        $validated = $request->validated();

        $maxPercentage = (float) (tenant('profit_percentage') ?? 0);
        if ((float) $validated['discount_percentage'] >= $maxPercentage) {
            $message = "Discount percentage must be less than the store's profit percentage ({$maxPercentage}%).";

            return $this->failure($message, 422, ['discount_percentage' => [$message]]);
        }

        $productIds = array_map('intval', $validated['product_ids']);
        $labels = $this->repo->productNamesForIds($productIds);
        $profitErrors = $this->service->validateFlashSaleProfitMargins($productIds, (float) $validated['discount_percentage'], $labels);

        if ($profitErrors !== []) {
            return $this->failure($profitErrors[0], 422, ['discount_percentage' => $profitErrors]);
        }

        $savedFlashSale = $this->service->saveFlashSale([
            'product_ids' => $productIds,
            'discount_percentage' => $validated['discount_percentage'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'active' => $validated['active'] ?? true,
            'banner_image' => $flashSale?->banner_url,
            'country_id' => $validated['country_id'] ?? null,
        ], $flashSale);

        if ($request->hasFile('banner_image')) {
            $mediaService->syncBanner($savedFlashSale, $request->file('banner_image'));
        } elseif (!$savedFlashSale->banner_url) {
            $savedFlashSale->forceFill(['banner_image' => asset('elora/assets/images/flash-sales-banner.png')])->save();
        }

        return $this->success($flashSale ? 'Flash sale updated successfully.' : 'Flash sale created successfully.');
    }

    public function validateStore(SaveFlashSaleRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function validateUpdate(SaveFlashSaleRequest $request, FlashSale $flashSale): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function destroy(FlashSale $flashSale): JsonResponse
    {
        $this->service->deleteModel($flashSale);

        return $this->success('Flash sale deleted successfully.');
    }
}
