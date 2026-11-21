<?php

namespace App\Support\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Exists;

/**
 * `exists` validation for central catalog rows that use SoftDeletesCatalogRow (dedup plan §0.3-Q3).
 *
 * A plain `exists:table,id` rule queries the table directly and would accept a soft-deleted id (e.g. a parent
 * category deleted while the form was open). This rule ignores soft-deleted rows once the table has `deleted_at`,
 * and is a plain `exists` rule before the central migration has run.
 */
final class LiveCatalogRule
{
    /** @param  class-string<Model>  $model  a central model using SoftDeletesCatalogRow */
    public static function exists(string $model, string $column = 'id'): Exists
    {
        /** @var Model $instance */
        $instance = new $model();
        $rule = new Exists($instance->getConnectionName() ? $instance->getConnectionName() . '.' . $instance->getTable() : $instance->getTable(), $column);

        return $model::softDeletesReady()
            ? $rule->whereNull($instance->getDeletedAtColumn())
            : $rule;
    }
}
