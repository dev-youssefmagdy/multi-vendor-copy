<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Http\Request;

/**
 * CountryDetectorService
 *
 * Detects the visitor's country from request headers set by Cloudflare or
 * common proxy/reverse-proxy stacks. Does not depend on any external API or
 * additional PHP extensions.
 *
 * Detection order:
 *   1. ?country=XX query string override (persisted to session)
 *   2. Session-cached value from a previous request
 *   3. CF-IPCountry    (Cloudflare)
 *   4. X-Country-Code  (custom load-balancer header)
 *   5. Accept-Language region hint (e.g. en-EG → EG)
 *   6. Returns null if nothing can be determined
 *
 * The ISO 2-letter code is matched against the central `countries` table.
 */
class CountryDetectorService
{
    public function detect(Request $request): ?Country
    {
        $iso2 = $this->extractIso2($request);

        if ($iso2 === null) {
            return null;
        }

        return Country::query()
            ->where('iso2', strtoupper($iso2))
            ->select(['id', 'iso2', 'iso3', 'name', 'currency_code', 'language_code', 'language_direction'])
            ->first();
    }

    protected function extractIso2(Request $request): ?string
    {
        // 1. Explicit override via query string (?country=EG) — useful for QA
        //    and operators testing country-specific themes. Persisted in the
        //    session so subsequent page loads keep the override.
        $override = $request->query('country');
        if (is_string($override) && strlen($override) === 2) {
            $request->session()?->put('storefront_detected_country', strtoupper($override));
            return strtoupper($override);
        }

        // 2. Session-cached detection (set by this service on an earlier request).
        $cached = $request->session()?->get('storefront_detected_country');
        if (is_string($cached) && strlen($cached) === 2) {
            return $cached;
        }

        // 3. Cloudflare's IP-country header — most reliable when behind CF.
        $cf = $request->server('HTTP_CF_IPCOUNTRY');
        if ($cf && $cf !== 'XX' && strlen($cf) === 2) {
            $request->session()?->put('storefront_detected_country', strtoupper($cf));
            return $cf;
        }

        // 4. Custom header some load balancers / CDNs inject.
        $custom = $request->header('X-Country-Code');
        if ($custom && strlen($custom) === 2) {
            $request->session()?->put('storefront_detected_country', strtoupper($custom));
            return $custom;
        }

        // 5. Best-effort fallback: the region suffix of Accept-Language
        //    (e.g. "en-EG,en;q=0.9" → EG). Not authoritative but better than
        //    nothing for visitors not behind a country-aware proxy.
        $acceptLang = (string) $request->header('Accept-Language', '');
        if ($acceptLang !== '' && preg_match('/[a-z]{2,3}-([A-Z]{2})/', $acceptLang, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }
}
