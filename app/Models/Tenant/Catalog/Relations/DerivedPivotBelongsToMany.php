<?php

namespace App\Models\Tenant\Catalog\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Expression;
use LogicException;

/**
 * Read-only BelongsToMany whose "pivot table" is a DERIVED table (a merged central ∪ tenant SELECT, dedup plan §3.3):
 * the join is `JOIN (<pivotSql>) AS <alias>`, everything else (eager loading, lazy access, whereHas / withCount,
 * withPivot / orderByPivot) is the stock relation working on the alias. Pivot writes (attach / detach / sync /
 * updateExistingPivot) throw: writes go through the section's writer (CategoryAssignments).
 */
class DerivedPivotBelongsToMany extends BelongsToMany
{
    public function __construct(
        Builder $query,
        Model $parent,
        private readonly string $pivotSql,
        string $alias,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $parentKey,
        string $relatedKey,
        ?string $relationName = null,
    ) {
        parent::__construct($query, $parent, $alias, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }

    protected function performJoin($query = null)
    {
        $query = $query ?: $this->query;
        $grammar = $query->getQuery()->getGrammar();

        $query->join(
            new Expression('(' . $this->pivotSql . ') as ' . $grammar->wrapTable($this->table)),
            $this->getQualifiedRelatedKeyName(),
            '=',
            $this->getQualifiedRelatedPivotKeyName(),
        );

        return $this;
    }

    public function newPivotStatement()
    {
        throw new LogicException('Merged catalog pivot [' . $this->table . '] is read-only.');
    }
}
