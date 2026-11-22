<?php

namespace App\Models\Tenant\Catalog;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Read-only Eloquent model over a DERIVED table (a merged central ∪ tenant SELECT, dedup plan §3.3) on the tenant
 * connection. Every query starts `FROM (<derivedSql>) AS <table>`, so it works as the related side of a normal
 * relation: eager loading (`with()`), lazy access and `whereHas()` all run against the merged rows.
 * Saving / deleting throws: writes go through the section's writer (e.g. BadgeAssignments).
 */
abstract class MergedReadModel extends Model
{
    protected $connection = 'tenant';

    protected $guarded = [];

    public $timestamps = false;

    public $incrementing = false;

    /** SELECT producing the rows (identifiers only, no bindings). */
    abstract protected static function derivedSql(): string;

    public function newModelQuery(): Builder
    {
        $builder = parent::newModelQuery();
        $query = $builder->getQuery();
        $query->fromRaw('(' . static::derivedSql() . ') as ' . $query->getGrammar()->wrapTable($this->getTable()));

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
