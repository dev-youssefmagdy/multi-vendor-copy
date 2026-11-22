<?php

namespace App\Models\Tenant\Catalog\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BelongsTo between two rows of the same DERIVED table (merged category → parent): the stock self-relation existence
 * query (`has('parent')`, `whereDoesntHave('parent')`) re-selects `FROM <table> AS <hash>`, i.e. the real tenant table;
 * this one re-selects `FROM (<derivedSql>) AS <hash>`.
 */
class DerivedBelongsTo extends BelongsTo
{
    public function __construct(Builder $query, Model $child, string $foreignKey, string $ownerKey, string $relationName, private readonly string $derivedSql)
    {
        parent::__construct($query, $child, $foreignKey, $ownerKey, $relationName);
    }

    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $hash = $this->getRelationCountHash();
        $query->select($columns);
        $query->getQuery()->fromRaw('(' . $this->derivedSql . ') as ' . $query->getQuery()->getGrammar()->wrapTable($hash));
        $query->getModel()->setTable($hash);

        return $query->whereColumn($hash . '.' . $this->ownerKey, '=', $this->getQualifiedForeignKeyName());
    }
}
