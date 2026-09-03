<?php

namespace App\View\Composers;

use App\Enums\ShippingZoneStatus;
use App\Models\ShippingZone;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Tenant\CustomerCountryResolver;
use Illuminate\View\View;

/**
 * Shares storefront layout data with every theme view.
 *
 * Registered by TenancyServiceProvider after TenancyBootstrapped fires,
 * so the tenant DB is always ready when this runs.
 */
class StorefrontComposer
{
    public function __construct(protected StorefrontRepository $repo)
    {
    }

    public function compose(View $view): void
    {
        // Compute layout data once per request — StorefrontComposer fires on every
        // themes.* sub-view (header, footer, nav, sections…), so without this guard
        // every DB query repeats N times per page load.
        if (!request()->attributes->has('_sf_layout')) {
            $currentLanguage = request()->attributes->get('storefrontCurrentLanguage')
                ?: $this->repo->currentLanguage();
            $currentCurrency = request()->attributes->get('storefrontCurrentCurrency')
                ?: $this->repo->currentCurrency();

            if ($currentLanguage) {
                app()->setLocale($currentLanguage->code);
            }

            request()->attributes->set('_sf_layout', [
                'storeName' => $this->repo->storeName(),
                'logoPath' => $this->repo->logoPath(),
                'cartCount' => $this->repo->cartCount(),
                'rootCategories' => $this->repo->rootCategoriesWithChildren(),
                'categories' => $this->repo->activeCategories(),
                'socialLinks' => $this->repo->socialLinks(),
                'footerText' => $this->repo->footerText(),
                'footerCopyright' => $this->repo->footerCopyright(),
                'languages' => $this->repo->activeLanguages(),
                'currencies' => $this->repo->activeCurrencies(),
                'currentLanguage' => $currentLanguage,
                'currentCurrency' => $currentCurrency,
                'hasFreeShipping' => $this->hasFreeShippingForCurrentCountry(),
            ]);
        }

        $view->with(request()->attributes->get('_sf_layout'));
    }

    /**
     * Whether the visitor's current country has an active free-shipping rate,
     * used to gate the static "Free shipping" banner text in theme headers.
     */
    private function hasFreeShippingForCurrentCountry(): bool
    {
        $countryId = app(CustomerCountryResolver::class)->resolveId();

        if (!$countryId) {
            return false;
        }

        $zone = ShippingZone::query()
            ->where('status', ShippingZoneStatus::Active)
            ->where('country_id', $countryId)
            ->with(['rates' => fn($q) => $q->where('is_active', true)])
            ->first();

        if (!$zone) {
            return false;
        }

        return $zone->rates->contains(fn($r) => (float) $r->price === 0.0);
    }
}
