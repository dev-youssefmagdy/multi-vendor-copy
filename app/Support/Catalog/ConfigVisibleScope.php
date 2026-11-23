<?php

namespace App\Support\Catalog;

use App\Repositories\Tenant\Catalog\ConfigCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Dedup plan phase 6, only while the tenant reads the config section from central (ConfigCatalog::enabled); `sync`
 * tenants never see a difference:
 *
 *  1. hides tenant identity rows whose central counterpart is gone / not eligible;
 *  2. rewrites the caller's WHERE / ORDER BY on CENTRAL-owned columns (`is_active` of a language or gateway, a theme's
 *     `slug` / `is_universal`, a page's `active`, `sort_order`, ...) to central's live value
 *     (ConfigCatalog::rewriteCentralColumns), so filters agree with what the overlay returns. Global scopes are applied
 *     when the query runs (get / count / exists / paginate / update), so the caller's clauses are already present.
 */
final class ConfigVisibleScope implements Scope
{
    public function __construct(private readonly string $section)
    {
    }

    public function apply(Builder $builder, Model $model): void
    {
        if (! ConfigCatalog::enabled($this->section === 'theme-countries' ? 'themes' : $this->section)) {
            return;
        }

        $ref = self::reference($builder, $model);

        if ($this->section === 'theme-countries') {
            $builder->whereRaw(ConfigCatalog::themeCountryVisibleSql($ref));

            return;
        }

        ConfigCatalog::rewriteCentralColumns($builder->getQuery(), $this->section, $model->getTable(), $ref);

        [$sql, $bindings] = ConfigCatalog::visibleSql($this->section, $ref);
        $builder->whereRaw($sql, $bindings);
    }

    /** The name the query uses for the model's table (alias of `from ... as x`, else the table). */
    private static function reference(Builder $builder, Model $model): string
    {
        $from = $builder->getQuery()->from;
        if (is_string($from) && preg_match('/\s+as\s+`?([\w]+)`?\s*$/i', $from, $m)) {
            return $m[1];
        }

        return $model->getTable();
    }
}
