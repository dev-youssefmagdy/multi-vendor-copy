<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Store;

use App\Enums\Tenant\CouponType;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Store\SaveCouponRequest;
use App\Models\Country;
use App\Models\Tenant\Coupon;
use App\Models\TenantCountry;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class CouponController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function index(): View
    {
        $counts = Coupon::query()
            ->selectRaw('country_id, count(*) as aggregate')
            ->groupBy('country_id')
            ->pluck('aggregate', 'country_id');

        $countryIds = TenantCountry::query()
            ->where('tenant_id', tenant()->id)
            ->where('is_active', true)
            ->pluck('country_id');

        $countries = Country::query()->whereIn('id', $countryIds)->orderBy('name')->get();

        return view('tenant.pages.store.coupons.index', [
            'countries' => $countries,
            'defaultCount' => (int) ($counts[null] ?? 0),
            'countryCounts' => $counts,
        ]);
    }

    public function list(?int $countryId = null): View
    {
        $country = $countryId ? Country::query()->find($countryId) : null;
        $stats = $this->repo->couponStats($countryId);

        return view('tenant.pages.store.coupons.list', [
            'countryId' => $countryId,
            'country' => $country,
            'stats' => Metric::cards([
                ['label' => 'Coupons', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Coupons stored for this tenant', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => $stats['active'], 'format' => 'number', 'caption' => 'Coupons currently usable', 'dot' => 'dot-green'],
                ['label' => 'Scheduled', 'value' => $stats['scheduled'], 'format' => 'number', 'caption' => 'Coupons with future start dates', 'dot' => 'dot-amber'],
            ]),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('coupon', 'Coupon')->orderable(false),
                TableColumn::make('type', 'Type')->orderable(false),
                TableColumn::make('value', 'Value')->orderable(false),
                TableColumn::make('window', 'Window')->orderable(false),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request, ?int $countryId = null): JsonResponse
    {
        $query = $this->repo->queryCoupons($countryId);

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('coupon', fn (Coupon $coupon) => e($coupon->name ?? $coupon->code))
            ->editColumn('type', fn (Coupon $coupon) => e($coupon->type->label()))
            ->editColumn('value', fn (Coupon $coupon) => $coupon->type === CouponType::Percentage
                ? e(number_format((float) $coupon->value, 2)).'%'
                : '$'.e(number_format((float) $coupon->value, 2)))
            ->editColumn('window', fn (Coupon $coupon) => e(optional($coupon->start_date)->format('M d, Y') ?: '-').' - '.e(optional($coupon->end_date)->format('M d, Y') ?: '-'))
            ->addColumn('actions', fn (Coupon $coupon) => view('tenant.pages.store.coupons._cols.actions', ['coupon' => $coupon])->render())
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function show(Coupon $coupon): JsonResponse
    {
        $defaultLocale = $this->repo->activeLanguages()->firstWhere('is_default', true)?->code ?? 'en';
        $translations = $coupon->translationsByLocale(['name']);

        return response()->json(['data' => [
            'code' => $coupon->code,
            'name_text' => data_get($translations, $defaultLocale.'.name', ''),
            'type' => $coupon->type->value,
            'value' => number_format((float) $coupon->value, 2, '.', ''),
            'minimum_spend' => number_format((float) $coupon->minimum_spend, 2, '.', ''),
            'start_date' => optional($coupon->start_date)->format('Y-m-d\TH:i'),
            'end_date' => optional($coupon->end_date)->format('Y-m-d\TH:i'),
            'country_id' => $coupon->country_id,
        ]]);
    }

    public function store(SaveCouponRequest $request): JsonResponse
    {
        return $this->save($request, null);
    }

    public function update(SaveCouponRequest $request, Coupon $coupon): JsonResponse
    {
        return $this->save($request, $coupon);
    }

    private function save(SaveCouponRequest $request, ?Coupon $coupon): JsonResponse
    {
        $validated = $request->validated();

        if ($validated['type'] === CouponType::Percentage->value) {
            $maxPercentage = (float) (tenant('profit_percentage') ?? 0);

            if ((float) $validated['value'] >= $maxPercentage) {
                $message = "Discount percentage must be less than the store's profit percentage ({$maxPercentage}%).";

                return $this->failure($message, 422, ['value' => [$message]]);
            }
        }

        $defaultLocale = $this->repo->activeLanguages()->firstWhere('is_default', true)?->code ?? 'en';

        $this->service->saveCoupon([
            'code' => $validated['code'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'minimum_spend' => filled($validated['minimum_spend'] ?? null) ? $validated['minimum_spend'] : 0,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'translations' => [$defaultLocale => ['name' => $validated['name_text']]],
            'country_id' => $validated['country_id'] ?? null,
        ], $coupon);

        return $this->success($coupon ? 'Coupon updated successfully.' : 'Coupon created successfully.');
    }

    public function validateStore(SaveCouponRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function validateUpdate(SaveCouponRequest $request, Coupon $coupon): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $this->service->deleteModel($coupon);

        return $this->success('Coupon deleted successfully.');
    }
}
