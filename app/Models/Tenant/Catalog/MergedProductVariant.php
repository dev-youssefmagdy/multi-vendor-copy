<?php

namespace App\Models\Tenant\Catalog;

use App\Models\Tenant\ProductVariant;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Read-only merged variant (ProductCatalog::variantsSql()), drop-in for Tenant\ProductVariant: `id` = variant key,
 * `product_id` = merged product key, overrides (stock / price / margin / profit) already applied in the columns the pricing
 * methods read. Prices only through the existing ProductVariant formulas.
 */
class MergedProductVariant extends ProductVariant
{
    protected $table = 'product_variants';

    public $incrementing = false;

    protected $guarded = [];

    public function newModelQuery(): Builder
    {
        $builder = parent::newModelQuery();
        $query = $builder->getQuery();
        $tenant = tenant();
        $query->fromRaw('(' . ProductCatalog::variantsSql($tenant ? (float) ($tenant->profit_percentage ?? 0) : 0.0) . ') as ' . $query->getGrammar()->wrapTable($this->getTable()));
        $query->select($this->getTable() . '.*');

        return $builder;
    }

    protected function casts(): array
    {
        return parent::casts() + ['id' => 'integer', 'product_id' => 'integer', 'central_product_variant_id' => 'integer', 'stock_override' => 'integer'];
    }

    public function getMorphClass()
    {
        return ProductVariant::class;
    }

    protected function translationModelClass(): string
    {
        return MergedProductVariantTranslation::class;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MergedProduct::class, 'product_id');
    }

    /** Variant overrides of a CENTRAL variant (ProductCatalog::VARIANT_OVERRIDE_FIELDS); an own variant updates its real row. */
    public function update(array $attributes = [], array $options = []): bool
    {
        if ($this->source === 'central') {
            $attributes = array_diff_key($attributes, array_flip(['sell_price', 'default_sell_price', 'real_price']));
            $foreign = array_diff(array_keys($attributes), ProductCatalog::VARIANT_OVERRIDE_FIELDS);
            if ($foreign !== []) {
                throw new \App\Support\Catalog\ReadOnlyCatalogRowException('Central variant fields are read-only (not overridable here): ' . implode(', ', $foreign));
            }
            $productId = \App\Models\ProductVariant::query()->whereKey((int) $this->source_id)->value('product_id');
            app(ProductCatalog::class)->setVariantOverride((int) $this->source_id, (int) $productId, $attributes);
        } else {
            ProductVariant::query()->whereKey((int) $this->source_id)->update($attributes);
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
