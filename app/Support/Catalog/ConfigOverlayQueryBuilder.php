<?php

namespace App\Support\Catalog;

use App\Repositories\Tenant\Catalog\ConfigCatalog;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\LazyCollection;

/**
 * Dedup plan phase 6: the base query builder of a tenant config model. While the model's section is read from central
 * (ConfigCatalog::enabled) every SELECT returns the CENTRAL-owned columns live (ConfigCatalog::overlayValues), whatever
 * the caller does with the result: Eloquent hydration, `value('slug')`, `pluck('value', 'name')`, `toBase()->get()`,
 * `cursor()`. Columns the overlay needs to decide (link, marker, tenant state) are selected on the side and removed again.
 * WHERE / ORDER BY on central-owned columns are rewritten by ConfigVisibleScope (Eloquent queries).
 * Left alone: `sync` tenants, aggregates, expressions / aliased columns, and GROUP BY / DISTINCT / UNION queries that do
 * not already select what the overlay needs (adding columns would change their result).
 */
final class ConfigOverlayQueryBuilder extends Builder
{
    private string $section = '';

    public function forSection(string $section): static
    {
        $this->section = $section;

        return $this;
    }

    /** Columns overlayValues() reads from the tenant row, per section. */
    private const NEEDS = [
        'languages' => ['central_language_id', 'is_active'],
        'currencies' => ['central_currency_id'],
        'themes' => ['central_theme_id'],
        'payment-gateways' => ['central_payment_gateway_id'],
        'email-templates' => ['central_email_template_id', 'central_overridden'],
        'settings' => ['name', 'central_overridden'],
        'pages' => ['slug', 'source', 'active'],
    ];

    /**
     * null = leave the query alone; else [requested columns, side columns to add].
     *
     * @return array{0: array<int, mixed>, 1: array<int, string>}|null
     */
    private function plan(): ?array
    {
        if ($this->section === '' || $this->aggregate !== null || ! ConfigCatalog::enabled($this->section)) {
            return null;
        }

        $requested = $this->columns ?? ['*'];
        $all = false;
        $names = [];
        foreach ($requested as $column) {
            if (! is_string($column) || str_contains($column, '(') || stripos($column, ' as ') !== false) {
                return null; // expressions / aliases: leave alone
            }
            $bare = str_contains($column, '.') ? substr($column, strrpos($column, '.') + 1) : $column;
            if ($bare === '*') {
                $all = true;
            }
            $names[] = str_replace('`', '', $bare);
        }

        $added = $all ? [] : array_values(array_diff(self::NEEDS[$this->section], $names));
        if ($added !== [] && ($this->groups || $this->distinct || $this->unions)) {
            return null;
        }

        return [$requested, $added];
    }

    /** `from` reference for side columns: the alias of `table as x`, else the table. */
    private function reference(): ?string
    {
        if (! is_string($this->from)) {
            return null;
        }

        return preg_match('/\s+as\s+`?([\w]+)`?\s*$/i', $this->from, $m) ? $m[1] : trim($this->from);
    }

    /** @param  array<int, string>  $added */
    private function withSideColumns(array $requested, array $added): array
    {
        $ref = $this->reference();

        return array_merge($requested, array_map(fn (string $c) => $ref !== null ? "{$ref}.{$c}" : $c, $added));
    }

    /** get() / pluck() / value() / first() all end in runSelect() with $this->columns already set. */
    protected function runSelect()
    {
        if (($plan = $this->plan()) === null) {
            return parent::runSelect();
        }

        [$requested, $added] = $plan;
        $this->columns = $this->withSideColumns($requested, $added);
        try {
            $rows = parent::runSelect();
        } finally {
            $this->columns = $requested;
        }

        return array_map(fn ($row) => $this->overlayRow($row, $added), $rows);
    }

    public function cursor()
    {
        if (($plan = $this->plan()) === null) {
            return parent::cursor();
        }

        [$requested, $added] = $plan;
        $this->columns = $this->withSideColumns($requested, $added);
        try {
            $sql = $this->toSql();
            $bindings = $this->getBindings();
        } finally {
            $this->columns = $requested;
        }

        return new LazyCollection(function () use ($sql, $bindings, $added) {
            foreach ($this->connection->cursor($sql, $bindings, ! $this->useWritePdo) as $row) {
                yield $this->overlayRow($row, $added);
            }
        });
    }

    private function overlayRow(object $row, array $added): object
    {
        $array = (array) $row;
        foreach (ConfigCatalog::overlayValues($this->section, $array) ?? [] as $column => $value) {
            if (array_key_exists($column, $array)) {
                $row->{$column} = $value;
            }
        }
        foreach ($added as $column) {
            unset($row->{$column});
        }

        return $row;
    }
}
