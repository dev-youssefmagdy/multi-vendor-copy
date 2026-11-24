<?php

namespace App\Repositories\Tenant\Catalog;

use App\Enums\Tenant\CouponType;
use App\Models\Tenant;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Order;
use App\Support\Catalog\CatalogRef;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CentralTable;
use App\Support\CacheVersion;
use App\Support\OrderPricing;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Coupons, dedup verdict DROP (plan §4, phase 3). The ONE place storefront, checkout, mobile API and panel look coupons
 * up. Sync mode (default): the exact Eloquent queries the readers used before, on the synced `coupons` table (returns
 * `Tenant\Coupon`). Central mode (`catalog_source.coupons = central` and the phase-3 tenant schema present): returns
 * CatalogCoupon objects merged from
 *
 *  - central `central_coupons` read live through the tenant connection (CentralTable), applicable to this tenant by the
 *    rule `TenantCatalogSyncService::syncCoupons()` used: not soft-deleted, targets all countries or at least one of the
 *    tenant's active `tenant_countries` (country set trimmed to that intersection), plus `active = 1` (the sync never
 *    copied `active`, so a switched-off central coupon used to stay redeemable; deliberate fix, see plan §11);
 *  - tenant own coupons (`coupons.central_coupon_id IS NULL`). Legacy central copies are ignored (deleted in phase 7).
 *
 * Code collision (D6): own coupons win at lookup (legacy collisions); new own coupons may not take a live central code
 * (centralCodeExists(), used by SaveCouponRequest).
 * Usage (D4): no counter table; usageCount() counts the tenant's orders by typed coupon ref (orders.coupon_type /
 * catalog_coupon_id, written by OrderComposer, back-filled by `catalog:dedup-migrate coupons`).
 */
final class CouponCatalog
{
    /** Global CacheVersion tag bumped on central coupon writes (invariant 8). */
    public const CACHE_TAG = 'CentralCoupon';

    /** Phase-3 order columns (snapshot + typed ref). */
    public const ORDER_COLUMNS = ['coupon_code', 'coupon_type', 'catalog_coupon_id'];

    private static bool $warned = false;

    public static function enabled(?Tenant $tenant = null): bool
    {
        $tenant ??= tenant();
        if (! $tenant instanceof Tenant || ! CatalogSource::isCentral($tenant, 'coupons')) {
            return false;
        }

        if (self::schemaReady()) {
            return true;
        }

        if (! self::$warned) {
            self::$warned = true;
            Log::warning('catalog_source.coupons is central but the tenant DB lacks the phase-3 order coupon columns; reading synced coupons.', [
                'tenant' => $tenant->getTenantKey(),
            ]);
        }

        return false;
    }

    public static function schemaReady(?ConnectionInterface $db = null): bool
    {
        return self::orderColumnsReady($db ?? DB::connection('tenant'));
    }

    public static function orderColumnsReady(ConnectionInterface $db): bool
    {
        foreach (self::ORDER_COLUMNS as $column) {
            if (! CatalogRefColumns::has($db, 'orders', $column)) {
                return false;
            }
        }

        return true;
    }

    private function db(): ConnectionInterface
    {
        return DB::connection('tenant');
    }

    // ── Storefront / checkout lookups (both modes) ──────────────────────────────────────────────────────────────

    /** Coupon by code, any date window (checkout re-read of the session code). */
    public function findByCode(?string $code): Coupon|CatalogCoupon|null
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        if (! self::enabled()) {
            return Coupon::query()->where('code', $code)->first();
        }

        return $this->merged(code: $code)->first();
    }

    /** Coupon by code that is within its date window now (apply / validate endpoints). */
    public function findValidByCode(?string $code, ?CarbonInterface $moment = null): Coupon|CatalogCoupon|null
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        if (! self::enabled()) {
            return Coupon::query()
                ->where('code', $code)
                ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $moment ?? now()))
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $moment ?? now()))
                ->first();
        }

        $coupon = $this->merged(code: $code)->first();

        return $coupon !== null && $coupon->isWithinWindow($moment) ? $coupon : null;
    }

    /** Newest currently valid coupon code usable in the country (flash-sale strip CTA, web + mobile). */
    public function latestValidCodeForCountry(?int $countryId): ?string
    {
        if (! self::enabled()) {
            return Coupon::query()
                ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
                ->forCountry($countryId)
                ->latest()
                ->value('code');
        }

        return $this->merged()
            ->filter(fn (CatalogCoupon $c) => $c->isWithinWindow() && $c->appliesToCountry($countryId))
            ->sortByDesc(fn (CatalogCoupon $c) => $this->createdAt[(string) $c->ref] ?? '')
            ->first()?->code;
    }

    /** orders.coupon_source for a coupon of either kind (OrderComposer, mobile quote). */
    public static function orderSource(Coupon|CatalogCoupon $coupon): string
    {
        if ($coupon instanceof CatalogCoupon) {
            return $coupon->orderSource();
        }

        return $coupon->central_coupon_id !== null ? OrderPricing::SOURCE_CENTRAL : OrderPricing::SOURCE_VENDOR;
    }

    /** Typed ref of a coupon of either kind (a sync copy stands for its central coupon). */
    public static function refOf(Coupon|CatalogCoupon $coupon): CatalogRef
    {
        if ($coupon instanceof CatalogCoupon) {
            return $coupon->ref;
        }

        return $coupon->central_coupon_id !== null
            ? CatalogRef::central((int) $coupon->central_coupon_id)
            : CatalogRef::tenant((int) $coupon->id);
    }

    // ── Panel / tooling ─────────────────────────────────────────────────────────────────────────────────────────

    /**
     * Merged coupons (central applicable + own), newest first like the panel list.
     *
     * @param  int|string|null  $country  'all' (all-countries only), a country id (applies to it) or null
     * @return Collection<int, CatalogCoupon>
     */
    public function all(int|string|null $country = null): Collection
    {
        return $this->merged()
            ->filter(fn (CatalogCoupon $c) => match (true) {
                $country === null || $country === '' => true,
                $country === 'all' => $c->isAllCountries(),
                default => $c->appliesToCountry((int) $country),
            })
            ->sortByDesc(fn (CatalogCoupon $c) => ($this->createdAt[(string) $c->ref] ?? '') . sprintf('%012d', $c->ref->id))
            ->values();
    }

    /** D6: a live central coupon (any tenant, any status) already uses this code. */
    public function centralCodeExists(string $code): bool
    {
        return $this->db()->table(CentralTable::qualified('central_coupons'))
            ->when($this->centralSoftDeletes(), fn (Builder $q) => $q->whereNull('deleted_at'))
            ->where('code', trim($code))
            ->exists();
    }

    /**
     * D4: how many of this tenant's checkouts used the coupon (orders by typed ref; the two orders of a mixed cart
     * count once). Cancelled orders are included (the coupon was redeemed).
     */
    public function usageCount(CatalogRef $ref): int
    {
        if (! self::orderColumnsReady($this->db())) {
            return 0;
        }

        return (int) Order::query()
            ->where('coupon_type', $ref->source)
            ->where('catalog_coupon_id', $ref->id)
            ->selectRaw('COUNT(DISTINCT COALESCE(order_group_uuid, uuid)) AS uses')
            ->value('uses');
    }

    /** @var array<string, string> ref => created_at (sort key for "latest") */
    private array $createdAt = [];

    /**
     * Merged rows (optionally one code). Own coupons first so a legacy own coupon wins a code collision.
     *
     * @return Collection<int, CatalogCoupon>
     */
    public function merged(?string $code = null): Collection
    {
        $own = Coupon::query()
            ->whereNull('central_coupon_id')
            ->when($code !== null, fn ($q) => $q->where('code', $code))
            // Panel lists render every row's countries + name, so eager load there; a code lookup touches at most one row
            // and loads only what it reads (D-P3-3).
            ->when($code === null, fn ($q) => $q->with(['countryRows', 'translations.language']))
            ->orderBy('id')
            ->get()
            ->map(function (Coupon $coupon) {
                $ref = CatalogRef::tenant($coupon->id);
                $this->createdAt[(string) $ref] = (string) $coupon->created_at;

                return new CatalogCoupon(
                    $ref,
                    (string) $coupon->code,
                    $coupon->name,
                    $coupon->type,
                    (string) $coupon->value,
                    (string) $coupon->minimum_spend,
                    $coupon->start_date,
                    $coupon->end_date,
                    true,
                    $coupon->countryIds(),
                );
            });

        $ownCodes = $own->map(fn (CatalogCoupon $c) => mb_strtolower($c->code))->flip();
        $central = $this->applicableCentral($code)
            ->reject(fn (CatalogCoupon $c) => $ownCodes->has(mb_strtolower($c->code)));

        return $own->concat($central)->values();
    }

    /**
     * Live, active central coupons applicable to the current tenant (syncCoupons() rule), country set trimmed.
     *
     * @return Collection<int, CatalogCoupon>
     */
    public function applicableCentral(?string $code = null, bool $includeInactive = false): Collection
    {
        $db = $this->db();
        [$rows, $countries] = $this->memoised('central:' . ($code === null ? '*' : 'c:' . $code) . ':' . (int) $includeInactive, function () use ($db, $code, $includeInactive) {
            $rows = $db->table(CentralTable::qualified('central_coupons'))
                ->when($this->centralSoftDeletes(), fn (Builder $q) => $q->whereNull('deleted_at'))
                ->when(! $includeInactive, fn (Builder $q) => $q->where('active', true))
                ->when($code !== null, fn (Builder $q) => $q->where('code', $code))
                ->orderBy('id')
                ->get();
            if ($rows->isEmpty()) {
                return [$rows, collect()];
            }

            return [$rows, $db->table(CentralTable::qualified('central_coupon_country'))
                ->whereIn('central_coupon_id', $rows->pluck('id'))
                ->get(['central_coupon_id', 'country_id'])
                ->groupBy('central_coupon_id')
                ->map(fn ($group) => $group->pluck('country_id')->map(fn ($id) => (int) $id)->all())];
        });
        if ($rows->isEmpty()) {
            return collect();
        }

        $tenantCountries = $this->tenantCountryIds();
        // E2E fixtures are visible only to the dedicated E2E tenants (rollout QA step 4).
        $hidden = array_flip(\App\Support\Catalog\E2eFixtures::hiddenCouponIds());

        return $rows->map(function (object $row) use ($countries, $tenantCountries, $hidden) {
            if (isset($hidden[(int) $row->id])) {
                return null;
            }
            $set = $countries->get($row->id, []);
            if ($set !== []) {
                $set = array_values(array_intersect($set, $tenantCountries));
                if ($set === []) {
                    return null; // targets only countries this tenant does not serve
                }
                sort($set);
            }

            $ref = CatalogRef::central((int) $row->id);
            $this->createdAt[(string) $ref] = (string) $row->created_at;

            return new CatalogCoupon(
                $ref,
                (string) $row->code,
                $row->name,
                CouponType::tryFrom((string) $row->type) ?? CouponType::Fixed,
                number_format((float) $row->value, 2, '.', ''),
                number_format((float) $row->minimum_spend, 2, '.', ''),
                $row->start_date !== null ? Carbon::parse($row->start_date) : null,
                $row->end_date !== null ? Carbon::parse($row->end_date) : null,
                (bool) $row->active,
                $set,
            );
        })->filter()->values();
    }

    /** @return list<int> the tenant's active countries (central `tenant_countries`) */
    public function tenantCountryIds(): array
    {
        $tenant = tenant();
        if (! $tenant instanceof Tenant) {
            return [];
        }

        return $this->memoised('tenant_countries', fn () => $this->db()->table(CentralTable::qualified('tenant_countries'))
            ->where('tenant_id', $tenant->getTenantKey())
            ->where('is_active', true)
            ->pluck('country_id')
            ->map(fn ($id) => (int) $id)
            ->all());
    }

    /** @var array<string, mixed> per-request memo of central reads (D-P3-3), scoped by app instance + tenant + CentralCoupon tag version */
    private static array $memo = [];

    /** Drops the per-request memo (tests / long-running callers). */
    public static function flushMemo(): void
    {
        self::$memo = [];
    }

    private function memoised(string $key, \Closure $load): mixed
    {
        $scope = spl_object_id(app()) . '|' . (tenant()?->getTenantKey() ?? '-') . '|' . CacheVersion::get(self::CACHE_TAG);
        if (! isset(self::$memo[$scope])) {
            self::$memo = [$scope => []]; // one live scope at a time: a bump / other tenant / new app drops the rest
        }

        return self::$memo[$scope][$key] ??= $load();
    }

    private function centralSoftDeletes(): bool
    {
        return CatalogRefColumns::has($this->db(), 'central_coupons', 'deleted_at', CentralTable::database());
    }
}
