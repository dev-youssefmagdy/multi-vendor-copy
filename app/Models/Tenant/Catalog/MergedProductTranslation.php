<?php

namespace App\Models\Tenant\Catalog;

use App\Models\Tenant\Translation;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/** Merged product translation rows (ProductCatalog::translationsSql()); `language_id` is the TENANT language id. Read-only. */
class MergedProductTranslation extends Translation
{
    protected $table = 'merged_product_translations';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    public function newModelQuery(): Builder
    {
        $builder = parent::newModelQuery();
        $query = $builder->getQuery();
        $query->fromRaw('(' . $this->derivedSql() . ') as ' . $query->getGrammar()->wrapTable($this->getTable()));

        return $builder;
    }

    protected function derivedSql(): string
    {
        return ProductCatalog::translationsSql();
    }

    protected function casts(): array
    {
        return ['translatable_id' => 'integer', 'language_id' => 'integer'];
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
