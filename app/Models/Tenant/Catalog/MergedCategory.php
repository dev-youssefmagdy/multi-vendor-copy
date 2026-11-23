<?php

namespace App\Models\Tenant\Catalog;

use App\Models\Tenant\Catalog\Relations\DerivedBelongsTo;
use App\Models\Tenant\Catalog\Relations\DerivedHasMany;
use App\Models\Tenant\Catalog\Relations\DerivedPivotBelongsToMany;
use App\Models\Tenant\Category;
use App\Models\Tenant\File;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\Catalog\CategoryCatalog;
use App\Support\Catalog\CatalogRef;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

/**
 * One merged category (CategoryCatalog::categoriesSql(), dedup plan phase 4): a central category (+ the tenant's
 * overrides) or an own tenant category, read from a derived table aliased `categories` on the tenant connection.
 *
 * Extends Tenant\Category so every reader (themes, panel, mobile, `instanceof` / closure type-hints) gets the same
 * properties: `id` (= CategoryKey: central id | own id + 1e9), `parent_id` (key), `active`, `featured`,
 * `order_number`, `central_category_id`, translated `name` / `slug` / … (HasTranslations over
 * MergedCategoryTranslation), `thumb_url` (vendor thumb → central thumb → default), `files`, `parent`, `children`,
 * `products` (merged membership, pivot `sort_order`), `centralCategory`. Plus `ref` ("c:12" / "t:3"), `source`,
 * `source_id`. Read-only: saving / deleting throws (writes: CategoryAssignments).
 *
 * @property string $ref
 * @property string $source
 * @property int $source_id
 * @property int|null $thumb_file_id
 */
class MergedCategory extends Category
{
    protected $table = 'categories';

    public $incrementing = false;

    protected $guarded = [];

    public function newModelQuery(): Builder
    {
        $builder = parent::newModelQuery();
        $query = $builder->getQuery();
        $query->fromRaw('(' . CategoryCatalog::categoriesSql() . ') as ' . $query->getGrammar()->wrapTable($this->getTable()));
        // `from` is an Expression: give the query explicit columns so withCount() / withAggregate() (which would
        // otherwise do `$from . '.*'`) work.
        $query->select($this->getTable() . '.*');

        return $builder;
    }

    protected function casts(): array
    {
        return parent::casts() + [
            'id' => 'integer',
            'parent_id' => 'integer',
            'source_id' => 'integer',
            'central_category_id' => 'integer',
            'thumb_file_id' => 'integer',
        ];
    }

    public function ref(): CatalogRef
    {
        return CatalogRef::parse((string) $this->getAttribute('ref'));
    }

    public function isCentral(): bool
    {
        return $this->getAttribute('source') === CatalogRef::CENTRAL;
    }

    /** Merged translations (derived rows carry `translatable_type` = this class, `translatable_id` = the key). */
    public function translations(): MorphMany
    {
        return $this->morphMany(MergedCategoryTranslation::class, 'translatable');
    }

    public function parent(): BelongsTo
    {
        $instance = $this->newRelatedInstance(self::class);

        return new DerivedBelongsTo($instance->newQuery(), $this, 'parent_id', 'id', 'parent', CategoryCatalog::categoriesSql());
    }

    public function children(): HasMany
    {
        $instance = $this->newRelatedInstance(self::class);

        return new DerivedHasMany($instance->newQuery(), $this, $instance->qualifyColumn('parent_id'), 'id', CategoryCatalog::categoriesSql());
    }

    public function products(): BelongsToMany
    {
        return (new DerivedPivotBelongsToMany(
            \App\Repositories\Tenant\Catalog\ProductCatalog::query(), // merged products (keys) once products = central (Phase 8 E2E finding)
            $this,
            CategoryCatalog::membershipSql(),
            'merged_category_product',
            'category_key',
            'product_id',
            'id',
            'id',
            'products',
        ))->withPivot(['sort_order', 'pair_source'])->withTimestamps()->orderByPivot('sort_order');
    }

    /** At most one file: the thumb (vendor override for a central category, the own category's thumb row). */
    public function files(): MorphMany|HasMany
    {
        return $this->hasMany(File::class, 'id', 'thumb_file_id');
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
