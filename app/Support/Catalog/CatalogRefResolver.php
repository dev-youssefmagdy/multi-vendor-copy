<?php

namespace App\Support\Catalog;

use App\Models\Tenant;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * Tenant ids -> CatalogRef (dedup plan phase 1, §5). Batch-capable; the one place that decides whether a tenant row
 * stands for a CENTRAL catalog item or a TENANT-owned one:
 *
 *  - product:  `central_product_id` NOT NULL and not `is_own_product` -> central(central_product_id), else tenant(id)
 *              (`is_tenant_owned` = a central product assigned to one tenant -> central, like shipping does).
 *  - variant:  `central_product_variant_id` NOT NULL and its product is not own -> central(central_product_variant_id),
 *              else tenant(id).
 *  - category / badge / label / flash_sale: link column (`central_<x>_id`) NOT NULL -> central(link), else tenant(id).
 *
 * Ids that do not exist (row deleted) are absent from the result: callers leave the typed pair NULL and report them.
 *
 * Two modes:
 *  - `on($connection)`: the tenant connection (tenancy initialised), plain table names. Used by the dual-write.
 *  - `forTenant($tenant)`: the CENTRAL connection with schema-qualified tenant tables (same server, invariant 2), so
 *    central code (return requests, backfill, sync-check) can resolve without initialising tenancy.
 */
final class CatalogRefResolver
{
    private const CHUNK = 1000;

    public function __construct(public readonly ConnectionInterface $db, public readonly ?string $schema = null)
    {
    }

    /** Resolver on a tenant connection (default: the current default connection = tenant while tenancy is initialised). */
    public static function on(ConnectionInterface|string|null $connection = null): self
    {
        return new self($connection instanceof ConnectionInterface ? $connection : DB::connection($connection));
    }

    /**
     * Resolver for a given tenant from any context. Uses the tenant connection when that tenant is the current one,
     * otherwise the central connection + the tenant's schema. Null when the tenant or its database does not exist.
     */
    public static function forTenant(Tenant|string|null $tenant): ?self
    {
        if ($tenant === null || $tenant === '') {
            return null;
        }

        $id = $tenant instanceof Tenant ? (string) $tenant->getTenantKey() : $tenant;
        if (tenancy()->initialized && (string) tenancy()->tenant?->getTenantKey() === $id) {
            return self::on('tenant');
        }

        $model = $tenant instanceof Tenant ? $tenant : Tenant::query()->find($id);
        if ($model === null) {
            return null;
        }

        $central = DB::connection(config('tenancy.database.central_connection'));
        $schema = $model->database()->getName();

        if ($central->getDriverName() === 'mysql'
            && $central->selectOne('SELECT 1 AS ok FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$schema]) === null) {
            return null;
        }

        return new self($central, $schema);
    }

    /** Quoted table name for raw SQL (schema-qualified in cross-schema mode; tenant DB names contain '-'). */
    public function table(string $table): string
    {
        $quoted = '`' . str_replace('`', '``', $table) . '`';

        return $this->schema === null ? $quoted : '`' . str_replace('`', '``', $this->schema) . '`.' . $quoted;
    }

    public function hasColumn(string $table, string $column): bool
    {
        return CatalogRefColumns::has($this->db, $table, $column, $this->schema);
    }

    public function product(int|string|null $id): ?CatalogRef
    {
        return $this->one('product', $id);
    }

    public function variant(int|string|null $id): ?CatalogRef
    {
        return $this->one('variant', $id);
    }

    /** @return array<int, CatalogRef> tenant product id => ref */
    public function products(iterable $ids): array
    {
        return $this->resolve('product', $ids);
    }

    /** @return array<int, CatalogRef> tenant variant id => ref */
    public function variants(iterable $ids): array
    {
        return $this->resolve('variant', $ids);
    }

    /** @return array<int, CatalogRef> */
    public function categories(iterable $ids): array
    {
        return $this->resolve('category', $ids);
    }

    /** @return array<int, CatalogRef> */
    public function badges(iterable $ids): array
    {
        return $this->resolve('badge', $ids);
    }

    /** @return array<int, CatalogRef> */
    public function labels(iterable $ids): array
    {
        return $this->resolve('label', $ids);
    }

    /** @return array<int, CatalogRef> */
    public function flashSales(iterable $ids): array
    {
        return $this->resolve('flash_sale', $ids);
    }

    public function one(string $entity, int|string|null $id): ?CatalogRef
    {
        if ($id === null || (int) $id < 1) {
            return null;
        }

        return $this->resolve($entity, [(int) $id])[(int) $id] ?? null;
    }

    /**
     * @param  iterable<int|string|null>  $ids
     * @return array<int, CatalogRef> tenant id => ref (unknown ids omitted)
     */
    public function resolve(string $entity, iterable $ids): array
    {
        if (! isset(CatalogRefColumns::ENTITIES[$entity])) {
            throw new \InvalidArgumentException("Unknown catalog entity [{$entity}].");
        }

        $clean = [];
        foreach ($ids as $id) {
            if ($id !== null && is_numeric($id) && (int) $id > 0) {
                $clean[(int) $id] = true;
            }
        }
        if ($clean === []) {
            return [];
        }

        [$table, $link] = CatalogRefColumns::ENTITIES[$entity];
        if (! $this->hasColumn($table, 'id')) {
            return [];
        }
        // A missing link column (migration not run) means "cannot classify": leave the pair NULL rather than guess.
        if (! $this->hasColumn($table, $link)) {
            return [];
        }

        $refs = [];
        foreach (array_chunk(array_keys($clean), self::CHUNK) as $chunk) {
            $in = implode(',', array_fill(0, count($chunk), '?'));
            foreach ($this->db->select($this->sql($entity, $in), $chunk) as $row) {
                $refs[(int) $row->id] = $row->central_id !== null && ! (bool) $row->is_own
                    ? CatalogRef::central((int) $row->central_id)
                    : CatalogRef::tenant((int) $row->id);
            }
        }

        return $refs;
    }

    /** SELECT id, central_id, is_own for the ids in `$in` placeholders. */
    private function sql(string $entity, string $in): string
    {
        $ownProduct = fn (string $alias) => $this->hasColumn('products', 'is_own_product')
            ? "COALESCE({$alias}.`is_own_product`, 0)"
            : '0';

        return match ($entity) {
            'product' => "SELECT p.`id` AS id, p.`central_product_id` AS central_id, {$ownProduct('p')} AS is_own
                          FROM {$this->table('products')} p WHERE p.`id` IN ({$in})",
            'variant' => "SELECT v.`id` AS id, v.`central_product_variant_id` AS central_id, {$ownProduct('p')} AS is_own
                          FROM {$this->table('product_variants')} v
                          LEFT JOIN {$this->table('products')} p ON p.`id` = v.`product_id`
                          WHERE v.`id` IN ({$in})",
            default => (function () use ($entity, $in) {
                [$table, $link] = CatalogRefColumns::ENTITIES[$entity];

                return "SELECT t.`id` AS id, t.`{$link}` AS central_id, 0 AS is_own FROM {$this->table($table)} t WHERE t.`id` IN ({$in})";
            })(),
        };
    }
}
