<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Store;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Store\SaveBannerRequest;
use App\Models\Tenant\Banner;
use App\Repositories\Tenant\StorefrontRepository;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\PlanLimitService;
use App\Services\Tenant\TenantPanelService;
use App\Support\CacheVersion;
use App\Support\CountryScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BannerController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $storefrontRepo = app(StorefrontRepository::class);
        $activeTheme = $storefrontRepo->currentTheme();
        $bannerDimensions = config('image_dimensions.themes.'.($activeTheme->slug ?? ''));

        $countries = CountryScope::tenantCountries();
        $countryFilter = $this->countryFilter($request);

        $banners = Banner::query()
            ->with(['translations.language', 'countryRows'])
            ->filterByCountry($countryFilter)
            ->orderBy('serial_number')
            ->orderBy('id')
            ->get();

        return view('tenant.pages.store.banners.index', [
            'banners' => $banners,
            'countries' => $countries,
            'countriesById' => $countries->keyBy('id'),
            'countryOptions' => CountryScope::options($countries),
            'countryFilter' => $countryFilter,
            'languages' => $this->repo->activeLanguages(),
            'activeThemeLabel' => $bannerDimensions['label'] ?? ($activeTheme->name ?? 'your theme'),
            'bannerWidth' => $bannerDimensions['width'] ?? null,
            'bannerHeight' => $bannerDimensions['height'] ?? null,
        ]);
    }

    /** Old per-country URLs (/banners/list/{countryId}) now map to the filtered list. */
    public function list(?int $countryId = null): RedirectResponse
    {
        return redirect()->route('tenant.store.banners.index', $countryId ? ['country' => $countryId] : []);
    }

    private function countryFilter(Request $request): int|string|null
    {
        $value = $request->query('country');

        if ($value === CountryScope::ALL) {
            return CountryScope::ALL;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    public function show(Banner $banner): JsonResponse
    {
        $banner->loadMissing('translations.language');

        return response()->json(['data' => [
            'url' => (string) ($banner->url ?? ''),
            'serial_number' => (int) $banner->serial_number,
            'banner_image_current' => $banner->image_path,
            'translations' => $banner->translationsByLocale(['title', 'subtitle', 'button_text']),
            'country_ids' => CountryScope::formValue($banner->countryIds()),
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
            'country_ids' => $request->countryIds(),
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

    public function updateOrder(Request $request): JsonResponse
    {
        $ids = array_map('intval', (array) $request->input('ids', []));

        $banners = Banner::query()->whereIn('id', $ids)->pluck('serial_number', 'id');
        $ids = array_values(array_filter($ids, fn (int $id) => $banners->has($id)));

        // A filtered list only shows some banners: hand the new order the same set of
        // serial numbers those banners already held so the hidden ones keep their place.
        $slots = $banners->values()->sort()->values();
        $unfiltered = count($ids) === Banner::query()->count();

        foreach ($ids as $index => $id) {
            $serial = $unfiltered ? $index : (int) $slots[$index];
            Banner::query()->where('id', $id)->update(['serial_number' => $serial]);
        }

        CacheVersion::bump('Banner');

        return $this->success('Banner order saved.');
    }
}
