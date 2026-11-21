<?php

namespace App\Support\Catalog;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Schema;

/**
 * SoftDeletingScope for SoftDeletesCatalogRow models: inactive (plain hard deletes, no `deleted_at` filter) until
 * the table has the column, and a builder-level `->delete()` replays the model's FK side effects like a model
 * soft delete does.
 */
class CatalogSoftDeletingScope extends SoftDeletingScope
{
    /** @var array<string, array{0: array<string, true>, 1: int}> connection|schema => [tables with deleted_at, checked at] */
    private static array $tables = [];

    /**
     * Whether $table has the soft-delete column. One information_schema query per connection + schema lists every
     * table that has it (MySQL); the list is cached for the process and re-read after 60 s while a table is still
     * missing the column, so long-running queue workers pick up the migration on their own.
     */
    public static function hasDeletedAt(Connection $connection, string $table, string $column = 'deleted_at'): bool
    {
        if ($connection->getDriverName() !== 'mysql') {
            return Schema::connection($connection->getName())->hasColumn($table, $column);
        }

        $key = $connection->getName() . '|' . $connection->getDatabaseName() . '|' . $column;
        [$tables, $checkedAt] = self::$tables[$key] ?? [null, 0];

        if ($tables === null || (! isset($tables[$table]) && time() - $checkedAt >= 60)) {
            $tables = array_fill_keys(array_map(fn ($row) => $row->t, $connection->select(
                'SELECT TABLE_NAME AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = ?',
                [$connection->getDatabaseName(), $column],
            )), true);
            self::$tables[$key] = [$tables, time()];
        }

        return isset($tables[$table]);
    }

    /** Tests only: forget the cached column lists. */
    public static function flushColumnCache(): void
    {
        self::$tables = [];
    }

    public function apply(Builder $builder, Model $model)
    {
        if ($model::softDeletesReady()) {
            parent::apply($builder, $model);
        }
    }

    public function extend(Builder $builder)
    {
        parent::extend($builder);

        $builder->onDelete(function (Builder $builder) {
            $model = $builder->getModel();

            if (! $model::softDeletesReady()) {
                return $builder->toBase()->delete();
            }

            $ids = (clone $builder)->pluck($model->getQualifiedKeyName())->all();
            $affected = $builder->update([$this->getDeletedAtColumn($builder) => $model->freshTimestampString()]);
            $model::replayDeleteActions($ids);

            return $affected;
        });
    }
}
