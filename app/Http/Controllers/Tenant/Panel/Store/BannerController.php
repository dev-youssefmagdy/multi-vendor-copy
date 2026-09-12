<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Store;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Store\SaveBannerRequest;
use App\Models\Country;
use App\Models\Tenant\Banner;
use App\Models\TenantCountry;
use App\Repositories\Tenant\StorefrontRepository;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\PlanLimitService;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BannerController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function index(): View
    {
        $counts = Banner::query()
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

        return view('tenant.pages.store.banners.index', [
            'countries' => $countries,
            'defaultCount' => (int) ($counts[null] ?? 0),
            'countryCounts' => $counts,
            'storefrontBase' => $storefrontBase,
        ]);
    }

    public function list(?int $countryId = null): View
    {
        $storefrontRepo = app(StorefrontRepository::class);
        $activeTheme = $storefrontRepo->currentTheme();
        $bannerDimensions = config('image_dimensions.themes.'.($activeTheme->slug ?? ''));

        $country = $countryId ? Country::query()->find($countryId) : null;

        $banners = Banner::query()
            ->with('translations.language')
            ->where('country_id', $countryId)
            ->orderBy('serial_number')
            ->get();

        return view('tenant.pages.store.banners.list', [
            'countryId' => $countryId,
            'country' => $country,
            'banners' => $banners,
            'languages' => $this->repo->activeLanguages(),
            'activeThemeLabel' => $bannerDimensions['label'] ?? ($activeTheme->name ?? 'your theme'),
            'bannerWidth' => $bannerDimensions['width'] ?? null,
            'bannerHeight' => $bannerDimensions['height'] ?? null,
        ]);
    }

    public function show(Banner $banner): JsonResponse
    {
        $banner->loadMissing('translations.language');

        return response()->json(['data' => [
            'url' => (string) ($banner->url ?? ''),
            'serial_number' => (int) $banner->serial_number,
            'banner_image_current' => $banner->image_path,
            'translations' => $banner->translationsByLocale(['title', 'subtitle', 'button_text']),
            'country_id' => $banner->country_id,
        ]]);
    }

    public function store(SaveBannerRequest $request): JsonResponse
    {
        $limitService = app(PlanLimitService::class);
        if (! $limitService->canPerform(tenant(), PlanLimitService::FEATURE_BANNERS)) {
            $message = $limitService->errorMessage(PlanLimitService::FEATURE_BANNERS);

            return $this->failure($message, 422, ['url' => [$message]]);
        }

        return $this->save($request, null);
    }

    public function update(SaveBannerRequest $request, Banner $banner): JsonResponse
    {
        return $this->save($request, $banner);
    }

    private function save(SaveBannerRequest $request, ?Banner $banner): JsonResponse
    {
        $validated = $request->validated();

        $imagePath = $banner?->image_path;
        if ($request->hasFile('banner_image')) {
            $imagePath = $request->file('banner_image')->store('appearances/banners', 'public');
            $imagePath = tenant_asset($imagePath);
        }

        $this->service->saveBanner([
            'url' => $validated['url'] ?: null,
            'image_path' => $imagePath,
            'serial_number' => $validated['serial_number'],
            'country_id' => $validated['country_id'] ?? null,
            'translations' => $validated['translations'] ?? [],
        ], $banner);

        return $this->success('Banner saved successfully.');
    }

    public function validateStore(SaveBannerRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function validateUpdate(SaveBannerRequest $request, Banner $banner): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();

        return $this->success('Banner deleted.');
    }

    public function updateOrder(Request $request, int $countryId): JsonResponse
    {
        // The route parameter is a required int, so "0" is used as the URL
        // sentinel for the default (no country) banner list.
        $filterCountryId = $countryId === 0 ? null : $countryId;
        $ids = (array) $request->input('ids', []);

        foreach ($ids as $index => $id) {
            Banner::query()->where('id', (int) $id)->where('country_id', $filterCountryId)->update(['serial_number' => $index]);
        }

        return $this->success('Banner order saved.');
    }
}
