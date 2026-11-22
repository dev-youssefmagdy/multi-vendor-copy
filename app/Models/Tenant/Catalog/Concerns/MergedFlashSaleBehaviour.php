<?php

namespace App\Models\Tenant\Catalog\Concerns;

use App\Repositories\Tenant\Catalog\FlashSaleCatalog;
use App\Support\Catalog\CatalogRef;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Shared surface of the merged flash-sale read models (dedup plan phase 3): the attributes, scopes and helpers readers
 * use on `Tenant\FlashSale` today (`discount_percentage`, `start_date`, `end_date`, `active`, `central_flash_sale_id`,
 * `banner_url`, scopeActiveWindow(), scopeForCountry(), scopeForAllCountries(), isCurrentlyActive(), countryIds(),
 * appliesToCountry()) plus `source` / `source_id` / `ref`.
 */
trait MergedFlashSaleBehaviour
{
    /** @var list<int>|null */
    protected ?array $mergedCountryIds = null;

    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'central_flash_sale_id' => 'integer',
            'product_id' => 'integer',
            'discount_percentage' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getRefAttribute(): CatalogRef
    {
        return CatalogRef::make((string) $this->getAttribute('source'), (int) $this->getAttribute('source_id'));
    }

    public function isCentral(): bool
    {
        return $this->getAttribute('source') === 'central';
    }

    public function scopeActiveWindow(Builder $query, ?CarbonInterface $moment = null): Builder
    {
        $moment ??= Carbon::now();
        $t = $this->getTable();

        return $query
            ->where("{$t}.active", true)
            ->where(fn ($q) => $q->whereNull("{$t}.start_date")->orWhere("{$t}.start_date", '<=', $moment))
            ->where(fn ($q) => $q->whereNull("{$t}.end_date")->orWhere("{$t}.end_date", '>=', $moment));
    }

    /** Applies to the country: targets all countries or that country (a null country sees all-countries rows only). */
    public function scopeForCountry(Builder $query, ?int $countryId): Builder
    {
        return $query->whereRaw(FlashSaleCatalog::countrySql($query->getQuery()->getGrammar()->wrapTable($this->getTable()), $countryId));
    }

    public function scopeForAllCountries(Builder $query): Builder
    {
        return $query->whereRaw(FlashSaleCatalog::countrySql($query->getQuery()->getGrammar()->wrapTable($this->getTable()), null));
    }

    public function isCurrentlyActive(?CarbonInterface $moment = null): bool
    {
        $moment ??= Carbon::now();

        return (bool) $this->active
            && ! ($this->start_date && $this->start_date->gt($moment))
            && ! ($this->end_date && $this->end_date->lt($moment));
    }

    /** @return list<int> sorted country ids; empty = all countries */
    public function countryIds(): array
    {
        if ($this->mergedCountryIds !== null) {
            return $this->mergedCountryIds;
        }

        $central = $this->isCentral();
        $ids = DB::connection($this->getConnectionName())
            ->table($central ? \App\Support\Catalog\CentralTable::qualified('central_flash_sale_country') : 'flash_sale_country')
            ->where($central ? 'central_flash_sale_id' : 'flash_sale_id', (int) $this->source_id)
            ->pluck('country_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        return $this->mergedCountryIds = $ids;
    }

    public function isAllCountries(): bool
    {
        return $this->countryIds() === [];
    }

    public function appliesToCountry(?int $countryId): bool
    {
        $ids = $this->countryIds();

        return $ids === [] || ($countryId !== null && in_array((int) $countryId, $ids, true));
    }

    /**
     * Same URL the storefront showed before: central sale → CentralFlashSale's URL (what the sync stored in the copy);
     * own sale → its tenant `banner` file, else an absolute `banner_image` (Tenant\FlashSale::getBannerUrlAttribute()).
     */
    public function getBannerUrlAttribute(): ?string
    {
        if ($this->isCentral()) {
            return FlashSaleCatalog::centralBannerUrl((int) $this->source_id, $this->getAttribute('banner_image'));
        }

        $path = DB::connection($this->getConnectionName())->table('files')
            ->where('model_type', (new \App\Models\Tenant\FlashSale())->getMorphClass())
            ->where('model_id', (int) $this->source_id)
            ->where('key', 'banner')
            ->value('path');
        if ($path) {
            return tenant_asset($path);
        }

        $image = (string) $this->getAttribute('banner_image');

        return str_starts_with($image, 'http') ? $image : null;
    }
}
