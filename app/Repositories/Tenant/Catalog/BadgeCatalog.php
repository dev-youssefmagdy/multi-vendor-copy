<?php

namespace App\Repositories\Tenant\Catalog;

use App\Models\Tenant;
use App\Support\Catalog\CatalogRef;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Merged badge read layer (dedup plan §3.3, phase 2): central badges read LIVE from central through the tenant
 * connection (CentralTable, invariant 2) UNION the tenant's own badges, plus the merged product↔badge membership.
 *
 * Badges:      central `product_badges` (all rows; central has no badge delete path) + tenant `product_badges` with
 *              `central_product_badge_id IS NULL` (own). Tenant rows linked to central are sync copies: ignored.
 *              An unlinked tenant row whose text equals a central badge (the 5 seeded featured / new-in / … rows of a
 *              tenant whose badges were never synced) is a not-yet-linked copy, not an own badge: shadowed by the
 *              central one everywhere (`catalog:dedup-migrate badges` links it and converts its rows).
 * Membership:  (a) central pivot pairs (`product_badge_product`, per `country_id`) for central products the tenant
 *              carries (tenant `products.central_product_id`, not own), minus pairs the vendor hid, sort = tenant
 *              override ?? central sort (`tenant_badge_product_overrides`);
 *              (b) tenant attaches to a central badge (tenant pivot row, `product_badge_id` NULL,
 *              `badge_type = central`, `catalog_badge_id` = central id) unless (a) already has that pair;
 *              (c) tenant pivot rows of own badges.
 *              Legacy rows pointing at central-linked tenant copies are ignored (converted by
 *              `catalog:dedup-migrate badges`).
 *
 * Only used when the tenant's `catalog_source.badges` is `central` (enabled()); otherwise every reader keeps the
 * synced tables. Read-only: writes go through BadgeAssignments.
 */
final class BadgeCatalog
{
    public const OVERRIDES = 'tenant_badge_product_overrides';

    /** Global CacheVersion tag bumped on central badge / badge-assignment writes (invariant 8). */
    public const CACHE_TAG = 'CentralProductBadge';

    /** Columns of membershipSql(), in order. */
    public const MEMBERSHIP_COLUMNS = [
        'id', 'badge_type', 'badge_id', 'text', 'active', 'product_id', 'product_type', 'catalog_product_id',
        'country_id', 'sort_order', 'pair_source',
    ];

    private static bool $warned = false;

    /** Merged reads are on for the current (or given) tenant: section flipped AND the phase 1/2 tenant schema exists. */
    public static function enabled(?Tenant $tenant = null): bool
    {
        $tenant ??= tenant();
        if (! $tenant instanceof Tenant || ! CatalogSource::isCentral($tenant, 'badges')) {
            return false;
        }

        if (self::schemaReady()) {
            return true;
        }

        if (! self::$warned) {
            self::$warned = true;
            Log::warning('catalog_source.badges is central but the tenant DB lacks the phase-2 badge schema; reading synced badges.', [
                'tenant' => $tenant->getTenantKey(),
            ]);
        }

        return false;
    }

    public static function schemaReady(?ConnectionInterface $db = null): bool
    {
        $db ??= DB::connection('tenant');

        return CatalogRefColumns::hasTable($db, self::OVERRIDES)
            && CatalogRefColumns::ready($db, 'product_badge_product')
            && CatalogRefColumns::has($db, 'product_badges', 'central_product_badge_id');
    }

    private function db(): ConnectionInterface
    {
        return DB::connection('tenant');
    }

    // ── Badges ──────────────────────────────────────────────────────────────

    /** @return Collection<int, CatalogBadge> central + own badges, sorted by text (central first on equal text). */
    public function all(bool $activeOnly = false): Collection
    {
        $central = $this->db()->table(CentralTable::qualified('product_badges'))
            ->when($activeOnly, fn (Builder $q) => $q->where('active', true))
            ->get(['id', 'text', 'active'])
            ->map(fn (object $row) => new CatalogBadge(CatalogRef::central($row->id), (string) $row->text, (bool) $row->active));

        $own = $this->ownBadges()
            ->when($activeOnly, fn (Builder $q) => $q->where('active', true))
            ->get(['id', 'text', 'active'])
            ->map(fn (object $row) => new CatalogBadge(CatalogRef::tenant($row->id), (string) $row->text, (bool) $row->active));

        return $central->concat($own)
            ->sortBy(fn (CatalogBadge $badge) => [$badge->text, $badge->isCentral() ? 0 : 1])
            ->values();
    }

    public function find(CatalogRef|string|null $ref): ?CatalogBadge
    {
        $ref = $ref instanceof CatalogRef ? $ref : CatalogRef::tryParse($ref);
        if ($ref === null) {
            return null;
        }

        $row = $ref->isCentral()
            ? $this->db()->table(CentralTable::qualified('product_badges'))->where('id', $ref->id)->first(['id', 'text', 'active'])
            : $this->ownBadges()->where('id', $ref->id)->first(['id', 'text', 'active']);

        return $row === null ? null : new CatalogBadge($ref, (string) $row->text, (bool) $row->active);
    }

    /** Route value (text, like ProductBadge::getRouteKeyName(), or a ref) → badge; central wins on equal text. */
    public function resolve(string $value): ?CatalogBadge
    {
        if (CatalogRef::tryParse($value) !== null) {
            return $this->find($value);
        }

        $central = $this->db()->table(CentralTable::qualified('product_badges'))->where('text', $value)->first(['id', 'text', 'active']);
        if ($central !== null) {
            return new CatalogBadge(CatalogRef::central($central->id), (string) $central->text, (bool) $central->active);
        }

        $own = $this->ownBadges()->where('text', $value)->orderBy('id')->first(['id', 'text', 'active']);

        return $own === null ? null : new CatalogBadge(CatalogRef::tenant($own->id), (string) $own->text, (bool) $own->active);
    }

    /** Own tenant badges: unlinked and not shadowed by a central badge with the same text. */
    private function ownBadges(): Builder
    {
        return $this->db()->table('product_badges')
            ->whereNull('central_product_badge_id')
            ->whereNotIn('text', fn (Builder $q) => $q->from(CentralTable::qualified('product_badges'))->select('text'));
    }

    /**
     * Badges valid for a product form: [ref string => CatalogBadge] for the given refs (unknown refs dropped).
     *
     * @param  iterable<mixed>  $refs
     * @return array<string, CatalogBadge>
     */
    public function findMany(iterable $refs): array
    {
        $found = [];
        foreach ($refs as $value) {
            $badge = is_string($value) ? $this->find($value) : null;
            if ($badge !== null) {
                $found[$badge->id] = $badge;
            }
        }

        return $found;
    }

    // ── Membership ──────────────────────────────────────────────────────────

    /** Merged membership as a query builder (alias $alias) on the tenant connection. */
    public function membershipQuery(string $alias = 'merged_badge_product'): Builder
    {
        return $this->db()->query()->fromRaw('(' . self::membershipSql() . ') as ' . $this->db()->getQueryGrammar()->wrapTable($alias));
    }

    /**
     * Ordered tenant product ids of a badge for one country (NULL = default rows), like
     * ProductBadge::productsForCountry()->pluck('products.id').
     *
     * @return list<int>
     */
    public function productIds(CatalogBadge $badge, ?int $countryId, bool $activeProductsOnly = false): array
    {
        return $this->entries($badge, $countryId)
            ->when($activeProductsOnly && ProductCatalog::enabled(), fn (Builder $q) => $q
                ->joinSub(\App\Models\Tenant\Catalog\MergedProduct::query()->getQuery()->select(['id', 'active']), 'products', 'products.id', '=', 'm.product_id')
                ->where('products.active', true))
            ->when($activeProductsOnly && ! ProductCatalog::enabled(), fn (Builder $q) => $q->join('products', 'products.id', '=', 'm.product_id')
                ->where('products.active', true)
                ->when(CatalogRefColumns::has($this->db(), 'products', 'central_visible'), fn (Builder $q) => $q->where('products.central_visible', true)))
            ->pluck('m.product_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Membership rows (alias m) of one badge + country, ordered like the storefront (sort_order, product id). */
    public function entries(CatalogBadge $badge, ?int $countryId): Builder
    {
        return $this->membershipQuery('m')
            ->where('m.badge_type', $badge->ref->source)
            ->where('m.badge_id', $badge->badgeId)
            ->when($countryId === null, fn (Builder $q) => $q->whereNull('m.country_id'), fn (Builder $q) => $q->where('m.country_id', $countryId))
            ->orderBy('m.sort_order')
            ->orderBy('m.product_id');
    }

    /**
     * SQL of the merged membership (no bindings: identifiers only, so it can be embedded in raw ORDER BY / EXISTS).
     * Columns: MEMBERSHIP_COLUMNS. `id` = badge ref string, `product_id` = TENANT product id (products are still
     * synced until phase 5), `product_type`/`catalog_product_id` = the typed product ref.
     */
    public static function membershipSql(?ConnectionInterface $db = null): string
    {
        $db ??= DB::connection('tenant');
        if (ProductCatalog::enabled()) {
            return self::membershipSqlMerged();
        }

        $cb = CentralTable::name('product_badges');
        $cpb = CentralTable::name('product_badge_product');
        $o = '`' . self::OVERRIDES . '`';
        $hasOwnFlag = CatalogRefColumns::has($db, 'products', 'is_own_product');
        $notOwn = fn (string $alias) => $hasOwnFlag ? "COALESCE({$alias}.`is_own_product`, 0) = 0" : '1 = 1';
        $isCentralProduct = "({$notOwn('p')} AND p.`central_product_id` IS NOT NULL)";
        $productType = "CASE WHEN {$isCentralProduct} THEN 'central' ELSE 'tenant' END";
        $catalogProductId = "CASE WHEN {$isCentralProduct} THEN p.`central_product_id` ELSE p.`id` END";

        return "SELECT CONCAT('c:', cb.`id`) AS `id`, 'central' AS `badge_type`, cb.`id` AS `badge_id`, cb.`text` AS `text`,
                    cb.`active` AS `active`, p.`id` AS `product_id`, 'central' AS `product_type`, cp.`product_id` AS `catalog_product_id`,
                    cp.`country_id` AS `country_id`, COALESCE(o.`sort_order`, cp.`sort_order`) AS `sort_order`, 'central' AS `pair_source`
                FROM {$cpb} cp
                JOIN {$cb} cb ON cb.`id` = cp.`product_badge_id`
                JOIN `products` p ON p.`central_product_id` = cp.`product_id` AND {$notOwn('p')}
                LEFT JOIN {$o} o ON o.`badge_type` = 'central' AND o.`badge_id` = cp.`product_badge_id`
                    AND o.`product_type` = 'central' AND o.`product_id` = cp.`product_id` AND o.`country_key` = IFNULL(cp.`country_id`, 0)
                WHERE COALESCE(o.`hidden`, 0) = 0
            UNION ALL
            SELECT CONCAT('c:', cb.`id`), 'central', cb.`id`, cb.`text`, cb.`active`, t.`product_id`, {$productType}, {$catalogProductId},
                    t.`country_id`, t.`sort_order`, 'tenant'
                FROM `product_badge_product` t
                JOIN {$cb} cb ON cb.`id` = t.`catalog_badge_id`
                JOIN `products` p ON p.`id` = t.`product_id`
                WHERE t.`product_badge_id` IS NULL AND t.`badge_type` = 'central'
                  AND NOT EXISTS (SELECT 1 FROM {$cpb} x WHERE x.`product_badge_id` = cb.`id` AND {$isCentralProduct}
                        AND x.`product_id` = p.`central_product_id` AND x.`country_id` <=> t.`country_id`)
            UNION ALL
            SELECT CONCAT('t:', b.`id`), 'tenant', b.`id`, b.`text`, b.`active`, t.`product_id`, {$productType}, {$catalogProductId},
                    t.`country_id`, t.`sort_order`, 'tenant'
                FROM `product_badge_product` t
                JOIN `product_badges` b ON b.`id` = t.`product_badge_id`
                JOIN `products` p ON p.`id` = t.`product_id`
                WHERE b.`central_product_badge_id` IS NULL
                  AND NOT EXISTS (SELECT 1 FROM {$cb} sx WHERE sx.`text` = b.`text`)";
    }

    /**
     * Phase 5 (`products` = central): same rows, `product_id` = the merged product KEY (ProductKey: central id | own
     * id + 1e9) and no join to the tenant `products` copies: central pairs need an adoption row, tenant attaches use the
     * phase-1 typed pair of the pivot.
     */
    private static function membershipSqlMerged(): string
    {
        $cb = CentralTable::name('product_badges');
        $cpb = CentralTable::name('product_badge_product');
        $o = '`' . self::OVERRIDES . '`';
        $key = ProductCatalog::pairKeySql('t.`product_type`', 't.`catalog_product_id`');

        return "SELECT CONCAT('c:', cb.`id`) AS `id`, 'central' AS `badge_type`, cb.`id` AS `badge_id`, cb.`text` AS `text`,
                    cb.`active` AS `active`, cp.`product_id` AS `product_id`, 'central' AS `product_type`, cp.`product_id` AS `catalog_product_id`,
                    cp.`country_id` AS `country_id`, COALESCE(o.`sort_order`, cp.`sort_order`) AS `sort_order`, 'central' AS `pair_source`
                FROM {$cpb} cp
                JOIN {$cb} cb ON cb.`id` = cp.`product_badge_id`
                LEFT JOIN {$o} o ON o.`badge_type` = 'central' AND o.`badge_id` = cp.`product_badge_id`
                    AND o.`product_type` = 'central' AND o.`product_id` = cp.`product_id` AND o.`country_key` = IFNULL(cp.`country_id`, 0)
                WHERE COALESCE(o.`hidden`, 0) = 0 AND " . ProductCatalog::adoptedSql('cp.`product_id`') . "
            UNION ALL
            SELECT CONCAT('c:', cb.`id`), 'central', cb.`id`, cb.`text`, cb.`active`, {$key}, t.`product_type`, t.`catalog_product_id`,
                    t.`country_id`, t.`sort_order`, 'tenant'
                FROM `product_badge_product` t
                JOIN {$cb} cb ON cb.`id` = t.`catalog_badge_id`
                WHERE t.`product_badge_id` IS NULL AND t.`badge_type` = 'central' AND t.`catalog_product_id` IS NOT NULL
                  AND (t.`product_type` = 'tenant' OR " . ProductCatalog::adoptedSql('t.`catalog_product_id`') . ")
                  AND NOT EXISTS (SELECT 1 FROM {$cpb} x WHERE x.`product_badge_id` = cb.`id` AND t.`product_type` = 'central'
                        AND x.`product_id` = t.`catalog_product_id` AND x.`country_id` <=> t.`country_id`)
            UNION ALL
            SELECT CONCAT('t:', b.`id`), 'tenant', b.`id`, b.`text`, b.`active`, {$key}, t.`product_type`, t.`catalog_product_id`,
                    t.`country_id`, t.`sort_order`, 'tenant'
                FROM `product_badge_product` t
                JOIN `product_badges` b ON b.`id` = t.`product_badge_id`
                WHERE b.`central_product_badge_id` IS NULL AND t.`catalog_product_id` IS NOT NULL
                  AND (t.`product_type` = 'tenant' OR " . ProductCatalog::adoptedSql('t.`catalog_product_id`') . ")
                  AND NOT EXISTS (SELECT 1 FROM {$cb} sx WHERE sx.`text` = b.`text`)";
    }
}
