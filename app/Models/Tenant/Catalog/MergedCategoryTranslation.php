<?php

namespace App\Models\Tenant\Catalog;

use App\Models\Tenant\Translation;
use App\Repositories\Tenant\Catalog\CategoryCatalog;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/**
 * One merged category translation (CategoryCatalog::translationsSql(), dedup plan phase 4 / §4.2): central values by
 * language CODE, tenant-only-language rows of central categories, own categories' rows. `language_id` is the TENANT
 * language id, so the inherited `language` relation and HasTranslations (`t()`, `translationValue()`,
 * `translationsByLocale()`) work unchanged. Read-only.
 */
class MergedCategoryTranslation extends Translation
{
    protected $table = 'merged_category_translations';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    public function newModelQuery(): Builder
    {
        $builder = parent::newModelQuery();
        $query = $builder->getQuery();
        $query->fromRaw('(' . CategoryCatalog::translationsSql() . ') as ' . $query->getGrammar()->wrapTable($this->getTable()));

        return $builder;
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
