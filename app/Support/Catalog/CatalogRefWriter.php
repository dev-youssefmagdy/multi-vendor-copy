<?php

namespace App\Support\Catalog;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fills the typed reference pairs (dedup plan phase 1) of rows whose pair is still NULL, from the tenant rows'
 * central links (CatalogRefResolver). Set-based: one SELECT of distinct ids + one `UPDATE … CASE` per chunk.
 *
 * Used for
 *  - the dual-write of pivot rows (written by `sync()` / `attach()` / raw inserts in many places): call
 *    `fillTable($pivot, ['product_id' => [$id]])` after the write;
 *  - the backfill command (`catalog:backfill-refs`, unrestricted, chunked, dry-run).
 *
 * Only NULL pairs are ever written, so it is idempotent and resumable, and it never touches the existing id columns.
 * No-op while the columns do not exist yet (code shipped before `tenants:migrate`).
 */
final class CatalogRefWriter
{
    public function __construct(public readonly CatalogRefResolver $resolver, public readonly bool $dryRun = false)
    {
    }

    /** Writer on the current tenant connection. */
    public static function current(): self
    {
        return new self(CatalogRefResolver::on());
    }

    /**
     * Dual-write helper: fill the pairs of $table rows matching $only, never throwing (a failure here must not break
     * a panel save or a sync; the backfill / sync-check pick the rows up).
     *
     * @param  array<string, iterable<int|string>>  $only  column => ids restriction
     */
    public static function fillQuietly(string $table, array $only = []): void
    {
        try {
            $writer = self::current();
            $only !== [] && $writer->resolver->db->getDriverName() === 'mysql'
                ? $writer->fillJoined($table, $only)
                : $writer->fillTable($table, $only);
        } catch (Throwable $e) {
            Log::warning('Catalog typed refs not filled (picked up by catalog:backfill-refs).', [
                'table' => $table,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fill NULL pairs of one table (tenant table, or central table restricted by `tenant_id` via $only).
     *
     * @param  array<string, iterable<int|string>>  $only  column => ids (e.g. ['product_id' => [5]]); [] = whole table
     * @return array{skipped?: string, filled: int, pending: int, unmapped: array<string, array<int, int>>}
     *         filled = rows written (dry-run: rows that would be), pending = NULL pairs left, unmapped = column => [id => rows]
     */
    public function fillTable(string $table, array $only = [], int $chunk = 1000, ?string $tenantId = null): array
    {
        $result = ['filled' => 0, 'pending' => 0, 'unmapped' => []];
        // Central tables (`tenant_id` given) live on the central connection; ids are resolved in that tenant's DB.
        $central = $tenantId !== null;
        $db = $central ? DB::connection(config('tenancy.database.central_connection')) : $this->resolver->db;
        $schema = $central ? null : $this->resolver->schema;

        if (! CatalogRefColumns::hasTable($db, $table, $schema)) {
            return ['skipped' => 'table missing'] + $result;
        }
        if (! CatalogRefColumns::ready($db, $table, $schema)) {
            return ['skipped' => 'typed columns missing (migration not run)'] + $result;
        }

        $tableSql = $central ? '`' . $table . '`' : $this->resolver->table($table);
        [$restrictSql, $restrictBindings] = $this->restriction($only, $tenantId);
        if ($restrictSql === null) {
            return $result; // an explicit empty id list: nothing to do
        }

        foreach (CatalogRefColumns::specFor($table) as $column => [$entity, $typeColumn, $idColumn]) {
            $last = 0;
            while (true) {
                $rows = $db->select(
                    "SELECT `{$column}` AS src, COUNT(*) AS n FROM {$tableSql}
                     WHERE `{$typeColumn}` IS NULL AND `{$column}` IS NOT NULL AND `{$column}` > ? {$restrictSql}
                     GROUP BY `{$column}` ORDER BY `{$column}` LIMIT " . max(1, $chunk),
                    array_merge([$last], $restrictBindings),
                );
                if ($rows === []) {
                    break;
                }

                $counts = [];
                foreach ($rows as $row) {
                    $counts[(int) $row->src] = (int) $row->n;
                }
                $last = array_key_last($counts);

                $refs = $this->resolver->resolve($entity, array_keys($counts));
                foreach (array_diff_key($counts, $refs) as $id => $n) {
                    $result['unmapped'][$column][$id] = $n;
                }

                if ($refs === []) {
                    continue;
                }

                if ($this->dryRun) {
                    $result['filled'] += array_sum(array_intersect_key($counts, $refs));

                    continue;
                }

                $typeCase = $idCase = '';
                $bindings = [];
                $idBindings = [];
                foreach ($refs as $id => $ref) {
                    $typeCase .= ' WHEN ? THEN ?';
                    $bindings[] = $id;
                    $bindings[] = $ref->source;
                    $idCase .= ' WHEN ? THEN ?';
                    $idBindings[] = $id;
                    $idBindings[] = $ref->id;
                }
                $in = implode(',', array_fill(0, count($refs), '?'));

                $result['filled'] += $db->update(
                    "UPDATE {$tableSql}
                     SET `{$typeColumn}` = CASE `{$column}`{$typeCase} END, `{$idColumn}` = CASE `{$column}`{$idCase} END
                     WHERE `{$typeColumn}` IS NULL AND `{$column}` IN ({$in}) {$restrictSql}",
                    array_merge($bindings, $idBindings, array_keys($refs), $restrictBindings),
                );
            }

            $result['pending'] += (int) ($db->selectOne(
                "SELECT COUNT(*) AS n FROM {$tableSql} WHERE `{$typeColumn}` IS NULL AND `{$column}` IS NOT NULL {$restrictSql}",
                $restrictBindings,
            )->n ?? 0);
        }

        return $result;
    }

    /**
     * Save-path variant of fillTable() (QA D-P1-1): ONE `UPDATE … LEFT JOIN` per table (all its pairs), restricted to the rows just
     * written, no SELECT / resolve / pending COUNT round-trips. Same classification as CatalogRefResolver (kept in
     * sync by CatalogPhase1RefsTest, which compares both paths). Rows whose target no longer exists keep a NULL pair.
     *
     * @param  array<string, iterable<int|string>>  $only  non-empty column => ids restriction
     * @return int rows changed
     */
    public function fillJoined(string $table, array $only): int
    {
        $db = $this->resolver->db;
        $schema = $this->resolver->schema;
        if (! CatalogRefColumns::ready($db, $table, $schema)) {
            return 0;
        }

        [$restrictSql, $restrictBindings] = $this->restriction($only, null);
        if ($restrictSql === null) {
            return 0;
        }
        $restrictSql = str_replace(' AND `', ' AND t.`', $restrictSql);

        // One UPDATE for every pair of the table (pivots carry two): LEFT JOIN each target as x0, x1, …; a pair is
        // only set when it is still NULL and its target row exists.
        $joins = $sets = $pending = [];
        foreach (array_values(array_keys(CatalogRefColumns::specFor($table))) as $i => $column) {
            [$entity, $typeColumn, $idColumn] = CatalogRefColumns::specFor($table)[$column];
            [$join, $isCentral, $centralId] = $this->joinFor($entity, "x{$i}", $column);
            if ($join === null) {
                continue; // link column missing on the target table: cannot classify, leave NULL
            }
            $joins[] = $join;
            // Each assignment reads only its own column: multi-table UPDATE has no guaranteed SET order in MySQL.
            $sets[] = "t.`{$idColumn}` = IF(t.`{$idColumn}` IS NULL AND x{$i}.`id` IS NOT NULL, IF({$isCentral}, {$centralId}, x{$i}.`id`), t.`{$idColumn}`)";
            $sets[] = "t.`{$typeColumn}` = IF(t.`{$typeColumn}` IS NULL AND x{$i}.`id` IS NOT NULL, IF({$isCentral}, 'central', 'tenant'), t.`{$typeColumn}`)";
            $pending[] = "(t.`{$typeColumn}` IS NULL AND t.`{$column}` IS NOT NULL)";
        }
        if ($sets === []) {
            return 0;
        }

        return $db->update(
            "UPDATE {$this->resolver->table($table)} t " . implode(' ', $joins) . '
             SET ' . implode(', ', $sets) . '
             WHERE (' . implode(' OR ', $pending) . ") {$restrictSql}",
            $restrictBindings,
        );
    }

    /**
     * JOIN clause (alias x = the target row, joined on t.<source column>) + SQL "is central" condition + central id
     * expression, mirroring CatalogRefResolver::resolve(). [null, '', ''] when the link column does not exist yet.
     *
     * @return array{0: ?string, 1: string, 2: string}
     */
    private function joinFor(string $entity, string $x, string $column): array
    {
        [$table, $link] = CatalogRefColumns::ENTITIES[$entity];
        if (! $this->resolver->hasColumn($table, $link)) {
            return [null, '', ''];
        }

        $notOwn = fn (string $alias) => $this->resolver->hasColumn('products', 'is_own_product')
            ? "COALESCE({$alias}.`is_own_product`, 0) = 0"
            : '1 = 1';

        return match ($entity) {
            'product' => [
                "LEFT JOIN {$this->resolver->table('products')} {$x} ON {$x}.`id` = t.`{$column}`",
                "({$x}.`central_product_id` IS NOT NULL AND {$notOwn($x)})",
                "{$x}.`central_product_id`",
            ],
            'variant' => [
                "LEFT JOIN {$this->resolver->table('product_variants')} {$x} ON {$x}.`id` = t.`{$column}`
                 LEFT JOIN {$this->resolver->table('products')} {$x}p ON {$x}p.`id` = {$x}.`product_id`",
                "({$x}.`central_product_variant_id` IS NOT NULL AND {$notOwn($x . 'p')})",
                "{$x}.`central_product_variant_id`",
            ],
            default => [
                "LEFT JOIN {$this->resolver->table($table)} {$x} ON {$x}.`id` = t.`{$column}`",
                "({$x}.`{$link}` IS NOT NULL)",
                "{$x}.`{$link}`",
            ],
        };
    }

    /**
     * Adds product_type / catalog_product_id (+ variant_type / catalog_variant_id when the item has a variant) to
     * every cart item that has no `product_type` yet. Items whose product no longer exists are left untouched and
     * returned in $unmapped (cart key => item).
     *
     * @param  array<string|int, mixed>  $items  session-cart shape: key => [product_id, variant_id, qty, …]
     * @param  array<string|int, array<string, mixed>>|null  $unmapped
     * @return array<string|int, mixed>
     */
    public function enrichCartItems(array $items, ?array &$unmapped = null): array
    {
        $unmapped = [];
        $productIds = $variantIds = [];
        foreach ($items as $item) {
            if (! is_array($item) || isset($item['product_type'])) {
                continue;
            }
            $productIds[] = $item['product_id'] ?? null;
            $variantIds[] = $item['variant_id'] ?? null;
        }
        if (array_filter($productIds) === [] && array_filter($variantIds) === []) {
            return $items;
        }

        $products = $this->resolver->products($productIds);
        $variants = $this->resolver->variants($variantIds);

        foreach ($items as $key => $item) {
            if (! is_array($item) || isset($item['product_type'])) {
                continue;
            }

            $product = $products[(int) ($item['product_id'] ?? 0)] ?? null;
            $variant = $variants[(int) ($item['variant_id'] ?? 0)] ?? null;
            if ($product === null) {
                $unmapped[$key] = $item;

                continue;
            }

            $item['product_type'] = $product->source;
            $item['catalog_product_id'] = $product->id;
            if ($variant !== null) {
                $item['variant_type'] = $variant->source;
                $item['catalog_variant_id'] = $variant->id;
            }
            $items[$key] = $item;
        }

        return $items;
    }

    /** @return array{0: ?string, 1: array<int, mixed>} SQL ("AND …") + bindings; [null, []] = empty id list */
    private function restriction(array $only, ?string $tenantId): array
    {
        $sql = '';
        $bindings = [];

        if ($tenantId !== null) {
            $sql .= ' AND `tenant_id` = ?';
            $bindings[] = $tenantId;
        }

        foreach ($only as $column => $ids) {
            if (! preg_match('/^[a-z_]+$/', (string) $column)) {
                throw new \InvalidArgumentException("Invalid column [{$column}].");
            }
            $ids = array_values(array_unique(array_map('intval', array_filter(
                is_array($ids) ? $ids : iterator_to_array($ids),
                fn ($id) => $id !== null && $id !== '',
            ))));
            if ($ids === []) {
                return [null, []];
            }
            $sql .= " AND `{$column}` IN (" . implode(',', array_fill(0, count($ids), '?')) . ')';
            array_push($bindings, ...$ids);
        }

        return [$sql, $bindings];
    }
}
