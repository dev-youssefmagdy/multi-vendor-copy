<?php

namespace App\Models\Tenant\Catalog;

use App\Models\Tenant\File;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use App\Support\Catalog\CatalogRef;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

/**
 * One merged product (ProductCatalog::productsSql(), dedup plan phase 5): a central product + the tenant's overrides, or an
 * own tenant product, read from a derived table aliased `products` on the tenant connection. A drop-in for Tenant\Product:
 * every attribute / pricing method (storefrontPricing(), priceBreakdownForCountry(), toEloraV4Card(), ...) works on the same
 * columns (`profit`, `price`, `default_price`, `fixed_shipping_costs`, `weight_grams`, `central_product_id`, ...), so prices
 * come ONLY from the existing formulas (TenantPricingService / OrderPricing); nothing is computed here.
 *
 * `id` = ProductKey (central id | own id + 1e9), plus `ref`, `source`, `source_id`. Relations that key on the tenant
 * product id are re-pointed at the key: variants (MergedProductVariant), rates (MergedProductRate), translations
 * (MergedProductTranslation), files (own rows only, via `files_key`; central rows fall back to the central product's media
 * exactly like a synced copy without own uploads). badges / labels / flash sales / categories already key on `id` (phase 2-4
 * membership SQL switches to the product key while `products` = central). Read-only: writes go through ProductCatalog.
 *
 * @property string $ref
 * @property string $source
 * @property int $source_id
 */
class MergedProduct extends Product
{
    protected $table = 'products';

    public $incrementing = false;

    protected $guarded = [];

    protected $appends = ['primary_image_url', 'average_rating'];

    public function newModelQuery(): Builder
    {
        $builder = parent::newModelQuery();
        $query = $builder->getQuery();
        $tenant = tenant();
        $query->fromRaw('(' . ProductCatalog::productsSql($tenant ? (float) ($tenant->profit_percentage ?? 0) : 0.0) . ') as ' . $query->getGrammar()->wrapTable($this->getTable()));
        $query->select($this->getTable() . '.*');

        return $builder;
    }

    protected function casts(): array
    {
        return parent::casts() + ['id' => 'integer', 'source_id' => 'integer', 'central_product_id' => 'integer', 'orders_count' => 'integer'];
    }

    /** Morph class of stored polymorphic rows (translations / files of own products are typed Tenant\Product). */
    public function getMorphClass()
    {
        return Product::class;
    }

    protected function translationModelClass(): string
    {
        return MergedProductTranslation::class;
    }

    public function ref(): CatalogRef
    {
        return CatalogRef::parse((string) $this->getAttribute('ref'));
    }

    public function isCentral(): bool
    {
        return $this->getAttribute('source') === CatalogRef::CENTRAL;
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MergedProductVariant::class, 'product_id', 'id')->orderBy('id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(MergedProductRate::class, 'product_key', 'id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model', 'model_type', 'model_id', 'files_key')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Panel writes on a merged row (dedup plan phase 5): a CENTRAL row accepts only tenant-owned fields
     * (ProductCatalog::PRODUCT_OVERRIDE_FIELDS) and stores them as an override row; central fields are read-only and throw.
     * An OWN row updates its real tenant row (`source_id`). Returns true when something was written.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        $catalog = app(ProductCatalog::class);
        if ($this->isCentral()) {
            // Derived columns (per-country prices) are recomputed from the stored profit rows at read time: ignored on write.
            $attributes = array_diff_key($attributes, array_flip(['price', 'default_price']));
            $foreign = array_diff(array_keys($attributes), ProductCatalog::PRODUCT_OVERRIDE_FIELDS);
            if ($foreign !== []) {
                throw new \App\Support\Catalog\ReadOnlyCatalogRowException('Central product fields are read-only (not overridable here): ' . implode(', ', $foreign));
            }
            $catalog->setOverride((int) $this->source_id, $attributes);
        } else {
            Product::query()->whereKey((int) $this->source_id)->update($attributes);
            \App\Support\CacheVersion::bumpMany(['Product', 'ProductVariant']);
        }
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        $this->syncOriginal();

        return true;
    }

    public function save(array $options = []): bool
    {
        throw new \App\Support\Catalog\ReadOnlyCatalogRowException(static::class . ' is a read-only merged catalog row (write through ProductCatalog).');
    }

    public function delete(): ?bool
    {
        throw new \App\Support\Catalog\ReadOnlyCatalogRowException(static::class . ' is a read-only merged catalog row (write through ProductCatalog).');
    }
}
