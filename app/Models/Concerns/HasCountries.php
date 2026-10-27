<?php

namespace App\Models\Concerns;

use App\Models\Pivots\CountryPivotRow;
use App\Support\CacheVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Many-to-many country targeting backed by a plain pivot table
 * (`<model>_country`: `<model>_id`, `country_id`).
 *
 * "No rows" means ALL countries — there is no separate flag, so a record that
 * was never scoped (or whose countries were all cleared) is available
 * everywhere. Countries live in the central DB, so tenant pivots carry the
 * country id without a foreign key.
 *
 * The using model declares `protected string $countryPivotTable` and may set
 * `protected ?string $countryCacheTag` to bump the storefront cache version
 * whenever the country set changes.
 */
trait HasCountries
{
    protected static function bootHasCountries(): void
    {
        static::deleted(function (self $model) {
            $model->pivotQuery()->delete();
        });
    }

    protected function countryPivotKey(): string
    {
        return $this->getForeignKey();
    }

    protected function pivotQuery()
    {
        return $this->getConnection()->table($this->countryPivotTable)
            ->where($this->countryPivotKey(), $this->getKey());
    }

    /** Raw pivot rows; eager-load with `->with('countryRows')` in lists to avoid N+1. */
    public function countryRows(): HasMany
    {
        $related = (new CountryPivotRow())
            ->setTable($this->countryPivotTable)
            ->setConnection($this->getConnectionName());

        return $this->newHasMany(
            $related->newQuery(),
            $this,
            $this->countryPivotTable.'.'.$this->countryPivotKey(),
            $this->getKeyName()
        );
    }

    /**
     * Country ids this record is limited to. Empty = all countries.
     *
     * @return int[]
     */
    public function countryIds(): array
    {
        if (! $this->exists) {
            return [];
        }

        return $this->loadMissing('countryRows')->countryRows
            ->pluck('country_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    public function isAllCountries(): bool
    {
        return $this->countryIds() === [];
    }

    public function appliesToCountry(?int $countryId): bool
    {
        $ids = $this->countryIds();

        return $ids === [] || ($countryId !== null && in_array((int) $countryId, $ids, true));
    }

    /**
     * Replace the country set. An empty array means "all countries".
     *
     * @param  iterable<int|string>  $countryIds
     */
    public function syncCountries(iterable $countryIds): void
    {
        $ids = collect($countryIds)
            ->filter(fn ($id) => filled($id) && is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        $this->pivotQuery()->delete();

        if ($ids->isNotEmpty()) {
            $key = $this->countryPivotKey();
            $this->getConnection()->table($this->countryPivotTable)->insert(
                $ids->map(fn (int $id) => [$key => $this->getKey(), 'country_id' => $id])->all()
            );
        }

        $this->unsetRelation('countryRows');

        if (property_exists($this, 'countryCacheTag') && $this->countryCacheTag) {
            CacheVersion::bump($this->countryCacheTag);
        }
    }

    /**
     * Resolve the country ids of many models with ONE query.
     *
     * @param  iterable<static>  $models
     */
    public static function hydrateCountryIds(iterable $models): void
    {
        (new EloquentCollection(collect($models)->filter(fn ($model) => $model->exists)->values()->all()))
            ->loadMissing('countryRows');
    }

    /** Records with no country rows (available everywhere). */
    public function scopeForAllCountries(Builder $query): Builder
    {
        return $query->whereNotExists(fn ($sub) => $this->pivotExistsSubquery($sub));
    }

    /**
     * Records a visitor from $countryId may see: available everywhere OR
     * explicitly targeting that country. A null country sees only the
     * available-everywhere records.
     */
    public function scopeForCountry(Builder $query, ?int $countryId): Builder
    {
        return $query->where(function (Builder $where) use ($countryId) {
            $where->whereNotExists(fn ($sub) => $this->pivotExistsSubquery($sub));

            if ($countryId) {
                $where->orWhereExists(fn ($sub) => $this->pivotExistsSubquery($sub)->where($this->countryPivotTable.'.country_id', $countryId));
            }
        });
    }

    /**
     * List filter: 'all' → only records available everywhere; a country id →
     * records that apply to that country (explicitly or via "all countries").
     */
    public function scopeFilterByCountry(Builder $query, int|string|null $value): Builder
    {
        if ($value === null || $value === '') {
            return $query;
        }

        return $value === 'all'
            ? $query->forAllCountries()
            : $query->forCountry((int) $value);
    }

    /**
     * Raw-SQL predicate (for hand-written subqueries) matching rows that apply
     * to the country. $table is the model's table alias in the outer query.
     */
    public static function countryScopeSql(string $table, ?int $countryId): string
    {
        $model = new static();
        $pivot = $model->countryPivotTable;
        $key = $model->countryPivotKey();

        $none = "NOT EXISTS (SELECT 1 FROM {$pivot} WHERE {$pivot}.{$key} = {$table}.id)";

        if (! $countryId) {
            return $none;
        }

        return "({$none} OR EXISTS (SELECT 1 FROM {$pivot} WHERE {$pivot}.{$key} = {$table}.id AND {$pivot}.country_id = ".(int) $countryId.'))';
    }

    protected function pivotExistsSubquery($sub)
    {
        return $sub->select(DB::raw(1))
            ->from($this->countryPivotTable)
            ->whereColumn($this->countryPivotTable.'.'.$this->countryPivotKey(), $this->getTable().'.'.$this->getKeyName());
    }
}
