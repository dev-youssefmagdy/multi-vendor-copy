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
 *   1. ?country=XX query string override (persisted to session as an explicit override)
 *   2. Session override from an earlier ?country=XX
 *   3. CF-IPCountry    (Cloudflare) - read on EVERY request
 *   4. X-Country-Code  (custom load-balancer header) - read on every request
 *   5. Session-cached value from a previous (non-IP) detection
 *   6. Accept-Language region hint (e.g. en-EG → EG)
 *   7. Returns null if nothing can be determined
 *
 * The IP headers are checked before the session cache so a visitor who changes network
 * (travel, VPN) is re-detected immediately. Before 2026-11-15 the first detection stuck for
 * the whole session, which made "Sold in countries" look broken when testing with a VPN.
 *
 * The ISO 2-letter code is matched against the central `countries` table.
 */
class CountryDetectorService
{
    /** Request attribute holding the per-request country lookup memo (by ISO2). */
    public const MEMO_ATTRIBUTE = '_country_detector_memo';

    public function detect(Request $request): ?Country
    {
        // Always re-run detection (it may persist the code to the session); only
        // the central `countries` lookup is memoized, per request and per ISO2.
        $iso2 = $this->extractIso2($request);

        if ($iso2 === null) {
            return null;
        }

        $iso2 = strtoupper($iso2);
        $memo = $request->attributes->get(self::MEMO_ATTRIBUTE, []);

        if (!array_key_exists($iso2, $memo)) {
            $memo[$iso2] = Country::query()
                ->where('iso2', $iso2)
                ->select(['id', 'iso2', 'iso3', 'name', 'currency_code', 'language_code', 'language_direction'])
                ->first();
            $request->attributes->set(self::MEMO_ATTRIBUTE, $memo);
        }

        return $memo[$iso2];
    }

    protected function extractIso2(Request $request): ?string
    {
        // 1. Explicit override via query string (?country=EG) — useful for QA
        //    and operators testing country-specific themes. Persisted in the
        //    session so subsequent page loads keep the override.
        $override = $request->query('country');
        if (is_string($override) && strlen($override) === 2) {
            $request->session()?->put('storefront_country_override', strtoupper($override));
            $request->session()?->put('storefront_detected_country', strtoupper($override));
            return strtoupper($override);
        }

        // 2. Explicit override from an earlier ?country=XX in this session.
        $sessionOverride = $request->session()?->get('storefront_country_override');
        if (is_string($sessionOverride) && strlen($sessionOverride) === 2) {
            return $sessionOverride;
        }

        // 3. Cloudflare's IP-country header — authoritative, re-read on every request.
        //    XX = unknown, T1 = Tor exit node: not a country.
        $cf = strtoupper((string) $request->server('HTTP_CF_IPCOUNTRY'));
        if (strlen($cf) === 2 && !in_array($cf, ['XX', 'T1'], true)) {
            $request->session()?->put('storefront_detected_country', $cf);
            return $cf;
        }

        // 4. Custom header some load balancers / CDNs inject.
        $custom = strtoupper((string) $request->header('X-Country-Code'));
        if (strlen($custom) === 2) {
            $request->session()?->put('storefront_detected_country', $custom);
            return $custom;
        }

        // 5. Session-cached detection (no IP header on this request).
        $cached = $request->session()?->get('storefront_detected_country');
        if (is_string($cached) && strlen($cached) === 2) {
            return $cached;
        }

        // 6. Best-effort fallback: the region suffix of Accept-Language
        //    (e.g. "en-EG,en;q=0.9" → EG). Not authoritative but better than
        //    nothing for visitors not behind a country-aware proxy.
        $acceptLang = (string) $request->header('Accept-Language', '');
        if ($acceptLang !== '' && preg_match('/[a-z]{2,3}-([A-Z]{2})/', $acceptLang, $m)) {
            $iso2 = strtoupper($m[1]);
            $request->session()?->put('storefront_detected_country', $iso2);
            return $iso2;
        }

        return null;
    }
}
