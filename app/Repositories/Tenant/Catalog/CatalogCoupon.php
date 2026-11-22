<?php

namespace App\Repositories\Tenant\Catalog;

use App\Enums\Tenant\CouponType;
use App\Support\Catalog\CatalogRef;
use App\Support\OrderPricing;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * One merged coupon (dedup plan phase 3, CouponCatalog): a live central coupon applicable to the tenant or a tenant own
 * coupon. Exposes the properties / methods the storefront, checkout and panel read on `Tenant\Coupon` today (`code`,
 * `name`, `type` (CouponType), `value`, `minimum_spend`, `start_date`, `end_date`, `central_coupon_id`,
 * `appliesToCountry()`, `countryIds()`), so readers work with either.
 *
 *  - `ref` / `source`: who owns it (central → coupon_source `central`, tenant → `vendor`).
 *  - `id`: the TENANT coupon id for own coupons (orders.discount_id, panel routes); NULL for central coupons
 *    (they have no tenant row in central mode).
 *  - `countryIds()`: for a central coupon the central set trimmed to the tenant's active countries (empty = all), the
 *    same set the sync used to write into `coupon_country`.
 */
final class CatalogCoupon
{
    public readonly string $source;

    public readonly ?int $id;

    public readonly ?int $central_coupon_id;

    /** @param list<int> $countryIds */
    public function __construct(
        public readonly CatalogRef $ref,
        public readonly string $code,
        public readonly ?string $name,
        public readonly CouponType $type,
        public readonly string $value,
        public readonly string $minimum_spend,
        public readonly ?CarbonInterface $start_date,
        public readonly ?CarbonInterface $end_date,
        public readonly bool $active,
        private readonly array $countryIds,
    ) {
        $this->source = $ref->source;
        $this->id = $ref->isTenant() ? $ref->id : null;
        $this->central_coupon_id = $ref->isCentral() ? $ref->id : null;
    }

    public function isCentral(): bool
    {
        return $this->ref->isCentral();
    }

    /** orders.coupon_source of an order using this coupon (OrderPricing::SOURCE_*). */
    public function orderSource(): string
    {
        return $this->isCentral() ? OrderPricing::SOURCE_CENTRAL : OrderPricing::SOURCE_VENDOR;
    }

    /** @return list<int> sorted; empty = all countries */
    public function countryIds(): array
    {
        return $this->countryIds;
    }

    public function isAllCountries(): bool
    {
        return $this->countryIds === [];
    }

    public function appliesToCountry(?int $countryId): bool
    {
        return $this->countryIds === [] || ($countryId !== null && in_array((int) $countryId, $this->countryIds, true));
    }

    /** Within its date window (the storefront check; `active` is already applied by CouponCatalog). */
    public function isWithinWindow(?CarbonInterface $moment = null): bool
    {
        $moment ??= Carbon::now();

        return ($this->start_date === null || $this->start_date->lte($moment))
            && ($this->end_date === null || $this->end_date->gte($moment));
    }
}
