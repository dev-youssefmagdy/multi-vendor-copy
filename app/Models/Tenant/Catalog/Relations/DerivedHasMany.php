<?php

namespace App\Models\Tenant\Catalog\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * HasMany between two rows of the same DERIVED table (merged categories → children): the stock self-relation
 * existence query (`withCount('children')`, `has('children')`) re-selects `FROM <table> AS <hash>`, i.e. the real
 * tenant table; this one re-selects `FROM (<derivedSql>) AS <hash>`.
 */
class DerivedHasMany extends HasMany
{
    public function __construct(Builder $query, Model $parent, string $foreignKey, string $localKey, private readonly string $derivedSql)
    {
        parent::__construct($query, $parent, $foreignKey, $localKey);
    }

    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $hash = $this->getRelationCountHash();
        $query->getQuery()->fromRaw('(' . $this->derivedSql . ') as ' . $query->getQuery()->getGrammar()->wrapTable($hash));
        $query->getModel()->setTable($hash);

        return $query->select($columns)->whereColumn(
            $this->getQualifiedParentKeyName(), '=', $hash . '.' . $this->getForeignKeyName()
        );
    }
}
