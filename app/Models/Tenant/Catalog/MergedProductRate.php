<?php

namespace App\Models\Tenant\Catalog;

use App\Models\Tenant\ProductRate;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/** Ratings keyed by the merged product key (`product_key`), read-only (ProductCatalog::ratesSql()). */
class MergedProductRate extends ProductRate
{
    protected $table = 'product_rates';

    public function newModelQuery(): Builder
    {
        $builder = parent::newModelQuery();
        $query = $builder->getQuery();
        $query->fromRaw('(' . ProductCatalog::ratesSql() . ') as ' . $query->getGrammar()->wrapTable($this->getTable()));
        $query->select($this->getTable() . '.*');

        return $builder;
    }

    public function save(array $options = []): bool
    {
        throw new LogicException(static::class . ' is a read-only merged catalog row.');
    }

    public function delete(): ?bool
    {
        throw new LogicException(static::class . ' is a read-only merged catalog row.');
    }
}
