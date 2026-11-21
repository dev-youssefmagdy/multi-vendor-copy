<?php

namespace App\Models\Concerns;

use App\Support\Catalog\CatalogSoftDeletingScope;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Soft deletes for CENTRAL catalog rows that tenants reference (dedup plan §0.3-Q3, invariant 6).
 *
 * Behaviour-neutral by construction:
 *  - Until the central migration `2026_11_22_000001_add_soft_deletes_to_central_catalog_tables` has added
 *    `deleted_at`, the model hard-deletes exactly as before (no scope, no `deleted_at` in SQL). Code can therefore
 *    ship before the migration runs.
 *  - Once soft-deleted, the row is invisible to every Eloquent query (global scope), like a deleted row.
 *  - Dependents behave as the old hard delete made them behave: the model lists the ON DELETE actions of the
 *    foreign keys that point at it (`catalogDeleteActions()`), and they are replayed on soft delete (pivot rows
 *    removed, nullable references set to NULL). Only the row itself survives, so ids stay resolvable.
 *  - `forceDelete()` still hard-deletes (the real FKs then do the same work).
 *
 * Unique columns are made "unique among live rows" by the migration, so a deleted SKU / code can be reused.
 */
trait SoftDeletesCatalogRow
{
    use SoftDeletes;

    /**
     * ON DELETE actions of the FKs that reference this table: list of [table, column, 'cascade'|'null'].
     *
     * @return list<array{0: string, 1: string, 2: 'cascade'|'null'}>
     */
    abstract protected static function catalogDeleteActions(): array;

    /** Replaces SoftDeletes' scope with one that is a no-op until the column exists. */
    public static function bootSoftDeletes(): void
    {
        static::addGlobalScope(new CatalogSoftDeletingScope());
    }

    /** true once the table has `deleted_at` (see CatalogSoftDeletingScope::hasDeletedAt() for the caching). */
    public static function softDeletesReady(): bool
    {
        $model = new static();

        return CatalogSoftDeletingScope::hasDeletedAt($model->getConnection(), $model->getTable(), $model->getDeletedAtColumn());
    }

    /**
     * Applies the old FK side effects to the dependents of soft-deleted rows.
     *
     * @param  array<int, int|string>  $ids
     */
    public static function replayDeleteActions(array $ids): void
    {
        $ids = array_values(array_filter($ids, fn ($id) => $id !== null));
        if ($ids === []) {
            return;
        }

        $connection = (new static())->getConnection();

        foreach (static::catalogDeleteActions() as [$table, $column, $action]) {
            foreach (array_chunk($ids, 1000) as $chunk) {
                $query = $connection->table($table)->whereIn($column, $chunk);
                $action === 'null' ? $query->update([$column => null]) : $query->delete();
            }
        }
    }

    protected function performDeleteOnModel()
    {
        if ($this->forceDeleting || ! static::softDeletesReady()) {
            return tap($this->setKeysForSaveQuery($this->newModelQuery())->forceDelete(), function () {
                $this->exists = false;
            });
        }

        $this->runSoftDelete();

        // Not an event listener on purpose: deleteQuietly() / withoutEvents() must keep the FK side effects too.
        static::replayDeleteActions([$this->getKey()]);
    }
}
