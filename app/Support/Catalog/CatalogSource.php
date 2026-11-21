<?php

namespace App\Support\Catalog;

use App\Models\Tenant;
use InvalidArgumentException;

/**
 * Per-tenant, per-section cut-over flag of the dedup plan (§6): where a catalog section is read from.
 *
 *  - `sync`    (default): today's behaviour, the tenant reads its synced copies.
 *  - `central`: the section is read live from central through the merged layer (and its sync is skipped).
 *
 * Storage (decision, phase 0): the tenant's `catalog_source` attribute, i.e. a key inside the central
 * `tenants.data` JSON column (stancl VirtualColumn: Tenant has no custom physical columns), e.g.
 * `{"badges": "central"}`. No migration needed; unknown / missing sections fall back to `sync`.
 *
 * Phase 0: TenantCatalogSyncService / CentralCatalogTenantSyncService skip `central` sections and
 * `catalog:sync-check` reports the mode. Nothing sets a section to `central` outside tests yet (no read path
 * exists before phase 2), so all tenants stay on `sync`.
 */
final class CatalogSource
{
    public const SYNC = 'sync';
    public const CENTRAL = 'central';

    public const ATTRIBUTE = 'catalog_source';

    /** Same section names as TenantCatalogSyncService::SECTIONS. */
    public const SECTIONS = [
        'languages', 'currencies', 'settings', 'payment-gateways', 'themes', 'email-templates', 'pages',
        'categories', 'products', 'badges', 'labels', 'flash-sales', 'coupons',
    ];

    /** @return 'sync'|'central' */
    public static function for(?Tenant $tenant, string $section): string
    {
        self::assertSection($section);

        if ($tenant === null) {
            return self::SYNC;
        }

        return self::map($tenant)[$section] ?? self::SYNC;
    }

    public static function isCentral(?Tenant $tenant, string $section): bool
    {
        return self::for($tenant, $section) === self::CENTRAL;
    }

    /**
     * Valid entries of the tenant's flag map (invalid sections/values are ignored, never trusted).
     *
     * @return array<string, 'sync'|'central'>
     */
    public static function map(Tenant $tenant): array
    {
        $raw = $tenant->getAttribute(self::ATTRIBUTE);

        // Same defensive unwrap as Tenant::getCategoryIdsAttribute(): VirtualColumn values may be JSON strings.
        while (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
            $raw = $decoded;
        }

        if (! is_array($raw)) {
            return [];
        }

        $map = [];
        foreach ($raw as $section => $value) {
            if (in_array($section, self::SECTIONS, true) && in_array($value, [self::SYNC, self::CENTRAL], true)) {
                $map[$section] = $value;
            }
        }

        return $map;
    }

    /**
     * Persists one section's mode (tests and ops commands only; no request path flips a mode). Row-locked
     * read-modify-write on the central `tenants` row, so concurrent `saveData()` writers keep their keys; the given
     * model is updated in memory too.
     */
    public static function set(Tenant $tenant, string $section, string $source): void
    {
        $tenant->getConnection()->transaction(function () use ($tenant, $section, $source) {
            $fresh = $tenant->newQuery()->whereKey($tenant->getTenantKey())->lockForUpdate()->firstOrFail();
            $map = self::withSection($fresh, $section, $source);

            $fresh->setAttribute(self::ATTRIBUTE, $map);
            $fresh->save();

            $tenant->setAttribute(self::ATTRIBUTE, $map);
            $tenant->syncOriginalAttribute(self::ATTRIBUTE);
        });
    }

    /**
     * Returns the full map with one section changed (does not save; see set()).
     *
     * @return array<string, 'sync'|'central'>
     */
    public static function withSection(Tenant $tenant, string $section, string $source): array
    {
        self::assertSection($section);
        if (! in_array($source, [self::SYNC, self::CENTRAL], true)) {
            throw new InvalidArgumentException("Invalid catalog source [{$source}]; expected sync or central.");
        }

        return array_merge(self::map($tenant), [$section => $source]);
    }

    private static function assertSection(string $section): void
    {
        if (! in_array($section, self::SECTIONS, true)) {
            throw new InvalidArgumentException("Unknown catalog section [{$section}].");
        }
    }
}
