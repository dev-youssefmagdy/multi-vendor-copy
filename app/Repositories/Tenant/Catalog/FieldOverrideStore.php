<?php

namespace App\Repositories\Tenant\Catalog;

use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CentralTable;
use App\Support\Catalog\EditableCentralFields;
use DateTimeInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tenant `tenant_field_overrides` (dedup plan §4.2a): the approved (field, locale) values of a central entity that
 * win over central. Current tenant connection only. Every read returns "nothing" while the table does not exist yet
 * (code deployed before `tenants:migrate`), so callers keep today's behaviour.
 *
 * Also the two lookups the approve / sync / migration need around it: central values by language CODE (ids differ
 * between DBs) and the tenant translation pairs.
 */
final class FieldOverrideStore
{
    public const TABLE = 'tenant_field_overrides';

    /**
     * Connection NAME (not an injected instance: the container would resolve ConnectionInterface to whatever the
     * default connection is at construction time, e.g. central before tenancy is initialised). Default: `tenant`,
     * resolved on every call.
     */
    public function __construct(private readonly string $connection = 'tenant')
    {
    }

    public function db(): ConnectionInterface
    {
        return DB::connection($this->connection);
    }

    public function ready(): bool
    {
        return CatalogRefColumns::hasTable($this->db(), self::TABLE);
    }

    private function table(): Builder
    {
        return $this->db()->table(self::TABLE);
    }

    /**
     * Overridden values per entity: [entity id => [locale ('' = not translatable) => [field => value]]].
     *
     * @param  iterable<int>  $ids
     * @return array<int, array<string, array<string, string>>>
     */
    public function valuesFor(string $entityType, iterable $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', is_array($ids) ? $ids : iterator_to_array($ids))));
        if ($ids === [] || ! $this->ready()) {
            return [];
        }

        $out = [];
        foreach (array_chunk($ids, 1000) as $chunk) {
            foreach ($this->table()->where('entity_type', $entityType)->whereIn('entity_id', $chunk)->get(['entity_id', 'field', 'locale', 'value']) as $row) {
                $out[(int) $row->entity_id][(string) $row->locale][$row->field] = (string) $row->value;
            }
        }

        return $out;
    }

    /** @return Collection<int, object> override rows of one entity (field, locale, value, central_value_at_approval, edit_request_id, approved_at, approved_by) */
    public function rowsFor(string $entityType, int $id): Collection
    {
        if (! $this->ready()) {
            return collect();
        }

        return $this->table()->where('entity_type', $entityType)->where('entity_id', $id)->orderBy('field')->orderBy('locale')->get();
    }

    public function has(string $entityType, int $id): bool
    {
        return $this->ready() && $this->table()->where('entity_type', $entityType)->where('entity_id', $id)->exists();
    }

    /**
     * Insert or replace ONE (field, locale) override. Non-whitelisted fields are refused (EditableCentralFields).
     *
     * @param  bool  $onlyIfMissing  true = never replace an existing row (additive data migration)
     */
    public function put(
        string $entityType,
        int $id,
        string $field,
        ?string $locale,
        ?string $value,
        ?string $centralValue,
        ?int $editRequestId,
        DateTimeInterface $approvedAt,
        ?int $approvedBy,
        bool $onlyIfMissing = false,
    ): bool {
        if (! EditableCentralFields::allows($entityType, $field)) {
            throw new \InvalidArgumentException("Field [{$field}] of [{$entityType}] is not vendor-editable (EditableCentralFields).");
        }

        $match = ['entity_type' => $entityType, 'entity_id' => $id, 'field' => $field];
        $existing = $this->table()->where($match)->where(fn (Builder $q) => $locale === null ? $q->whereNull('locale') : $q->where('locale', $locale));
        $values = [
            'value' => $value,
            'central_value_at_approval' => $centralValue,
            'edit_request_id' => $editRequestId,
            'approved_at' => $approvedAt,
            'approved_by' => $approvedBy,
            'updated_at' => now(),
        ];

        if ((clone $existing)->exists()) {
            return $onlyIfMissing ? false : (bool) $existing->update($values);
        }

        return $this->table()->insert($match + ['locale' => $locale] + $values + ['created_at' => now()]);
    }

    /**
     * Delete overrides of one entity: all, one field (all locales or one), or those of one edit request.
     *
     * @return list<array{field: string, locale: ?string}> the deleted (field, locale) pairs
     */
    public function forget(string $entityType, int $id, ?string $field = null, ?string $locale = null, ?int $editRequestId = null): array
    {
        if (! $this->ready()) {
            return [];
        }

        $query = $this->table()->where('entity_type', $entityType)->where('entity_id', $id)
            ->when($field !== null, fn (Builder $q) => $q->where('field', $field))
            ->when($field !== null && $locale !== null, fn (Builder $q) => $q->where('locale', $locale))
            ->when($editRequestId !== null, fn (Builder $q) => $q->where('edit_request_id', $editRequestId));

        $pairs = (clone $query)->get(['field', 'locale'])->map(fn ($r) => ['field' => (string) $r->field, 'locale' => $r->locale])->all();
        $query->delete();

        return $pairs;
    }

    // ── central values / tenant translations ───────────────────────────────

    /**
     * Central translations of central products by language CODE: [central id => [code => [field => value]]], read
     * live through the tenant connection (CentralTable). Every central language code is present for each product
     * (missing values = '').
     *
     * @param  list<int>  $centralIds
     * @param  list<string>  $fields
     * @return array<int, array<string, array<string, string>>>
     */
    public function centralProductValues(array $centralIds, array $fields): array
    {
        if ($centralIds === []) {
            return [];
        }

        $codes = $this->db()->table(CentralTable::qualified('languages'))->pluck('code')->map(fn ($c) => (string) $c)->all();
        $out = [];
        foreach ($centralIds as $id) {
            foreach ($codes as $code) {
                $out[(int) $id][$code] = array_fill_keys($fields, '');
            }
        }

        foreach (array_chunk($centralIds, 1000) as $chunk) {
            $rows = $this->db()->table(CentralTable::qualified('translations') . ' as t')
                ->join(CentralTable::qualified('languages') . ' as l', 'l.id', '=', 't.language_id')
                ->where('t.translatable_type', \App\Models\Product::class)
                ->whereIn('t.translatable_id', $chunk)
                ->whereIn('t.field', $fields)
                ->get(['t.translatable_id', 'l.code', 't.field', 't.value']);

            foreach ($rows as $row) {
                $out[(int) $row->translatable_id][(string) $row->code][$row->field] = trim((string) $row->value);
            }
        }

        return $out;
    }

    /**
     * Tenant translation values of tenant models by language CODE (all languages, active or not):
     * [tenant id => [code => [field => value]]].
     *
     * @param  list<int>  $ids
     * @param  list<string>  $fields
     * @return array<int, array<string, array<string, string>>>
     */
    public function tenantValues(string $morphType, array $ids, array $fields): array
    {
        if ($ids === []) {
            return [];
        }

        $out = [];
        foreach (array_chunk($ids, 1000) as $chunk) {
            $rows = $this->db()->table('translations as t')
                ->join('languages as l', 'l.id', '=', 't.language_id')
                ->where('t.translatable_type', $morphType)
                ->whereIn('t.translatable_id', $chunk)
                ->whereIn('t.field', $fields)
                ->get(['t.translatable_id', 'l.code', 't.field', 't.value']);

            foreach ($rows as $row) {
                $out[(int) $row->translatable_id][(string) $row->code][$row->field] = trim((string) $row->value);
            }
        }

        return $out;
    }

    /** Write ONE tenant translation pair (empty value = remove the row); other fields / locales are not touched. */
    public function putTenantTranslation(string $morphType, int $id, string $locale, string $field, ?string $value): void
    {
        $languageId = $this->db()->table('languages')->where('code', $locale)->value('id');
        if ($languageId === null) {
            return;
        }

        $key = ['language_id' => $languageId, 'translatable_type' => $morphType, 'translatable_id' => $id, 'field' => $field];
        $value = trim((string) $value);

        if ($value === '') {
            $this->db()->table('translations')->where($key)->delete();

            return;
        }

        $this->db()->table('translations')->upsert(
            [$key + ['value' => $value, 'created_at' => now(), 'updated_at' => now()]],
            array_keys($key),
            ['value', 'updated_at'],
        );
    }
}
