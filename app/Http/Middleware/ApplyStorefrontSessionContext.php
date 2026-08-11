<?php

namespace App\Http\Middleware;

use App\Repositories\Tenant\StorefrontRepository;
use App\Services\CountryDetectorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyStorefrontSessionContext
{
    public function __construct(
        protected StorefrontRepository $storefrontRepository,
        protected CountryDetectorService $countryDetector,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Auto-detect the visitor's country. The service caches its result in
        // the session so subsequent requests skip header inspection. A
        // ?country=XX query parameter lets operators / QA override detection.
        $country = $this->countryDetector->detect($request);

        if ($country) {
            $previousIso = session('storefront_country');
            $request->attributes->set('storefrontCurrentCountry', $country);

            // Backfill the ID if it is missing (e.g. sessions created before this key existed).
            if (!session('storefront_country_id')) {
                session(['storefront_country_id' => $country->id]);
            }

            // When the detected country CHANGES, drop any auto-assigned currency
            // and language so they re-derive from the new country's defaults.
            // Manual selections made via the locale switcher are preserved via
            // the `*_manual` flags so the visitor's explicit choice always wins.
            if ($previousIso !== $country->iso2) {
                session([
                    'storefront_country' => $country->iso2,
                    'storefront_country_id' => $country->id,
                ]);
                if (!session('storefront_language_manual')) {
                    session()->forget('storefront_language');
                }
                if (!session('storefront_currency_manual')) {
                    session()->forget('storefront_currency');
                }
            }
        }

        $currentLanguage = $this->storefrontRepository->currentLanguage();
        $currentCurrency = $this->storefrontRepository->currentCurrency();
        $currentTheme = $this->storefrontRepository->currentTheme();

        if ($currentLanguage) {
            app()->setLocale($currentLanguage->code);
            $request->attributes->set('storefrontCurrentLanguage', $currentLanguage);

            if (session('storefront_language') !== $currentLanguage->code) {
                session(['storefront_language' => $currentLanguage->code]);
            }
        }

        if ($currentCurrency) {
            $request->attributes->set('storefrontCurrentCurrency', $currentCurrency);

            if (session('storefront_currency') !== $currentCurrency->code) {
                session(['storefront_currency' => $currentCurrency->code]);
            }
        }

        if ($currentTheme) {
            $request->attributes->set('storefrontCurrentTheme', $currentTheme);
            // Share globally so Blade views can read `$storefrontTheme` without
            // passing it through every controller.
            view()->share('storefrontTheme', $currentTheme);
        }

        return $next($request);
    }
}
