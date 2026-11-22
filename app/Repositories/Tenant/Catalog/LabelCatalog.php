<?php

namespace App\Repositories\Tenant\Catalog;

use App\Models\Tenant;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Labels, dedup verdict DROP (§4): the tenant owns nothing about central labels, so once its `catalog_source.labels`
 * is `central` labels are read from central directly (through the tenant connection, CentralTable) and the tenant
 * copies (`product_labels`, `product_label_product`) are neither read nor written any more (deleted in phase 7).
 *
 *  - live = central row not soft-deleted (SoftDeletesCatalogRow; before the phase-0 migration every row is live);
 *    `active` is returned as a column, readers filter it exactly as they did on the copy (Product::cardLabels()).
 *  - product membership = central `product_label_product` pairs of the central products the tenant carries
 *    (tenant `products.central_product_id`, not own). Own products have no labels (as today).
 */
final class LabelCatalog
{
    /** Global CacheVersion tag bumped on central label / label-assignment writes (invariant 8). */
    public const CACHE_TAG = 'CentralProductLabel';

    public static function enabled(?Tenant $tenant = null): bool
    {
        $tenant ??= tenant();

        return $tenant instanceof Tenant && CatalogSource::isCentral($tenant, 'labels');
    }

    /** @return Collection<int, object> live central labels (id, text, translations, icon, active, sort_order), ordered */
    public function all(bool $activeOnly = false): Collection
    {
        $db = DB::connection('tenant');

        return $db->table(CentralTable::qualified('product_labels'))
            ->when(self::hasSoftDeletes($db), fn ($q) => $q->whereNull('deleted_at'))
            ->when($activeOnly, fn ($q) => $q->where('active', true))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'text', 'translations', 'icon', 'active', 'sort_order']);
    }

    /**
     * SQL of the merged product↔label rows (no bindings). Columns: id (central label id), product_id (TENANT product
     * id, products are still synced until phase 5), catalog_product_id (central product id), text, translations,
     * icon, active, sort_order.
     */
    public static function membershipSql(?ConnectionInterface $db = null): string
    {
        $db ??= DB::connection('tenant');
        $cl = CentralTable::name('product_labels');
        $cpl = CentralTable::name('product_label_product');
        $live = self::hasSoftDeletes($db) ? 'cl.`deleted_at` IS NULL' : '1 = 1';
        $notOwn = CatalogRefColumns::has($db, 'products', 'is_own_product') ? 'COALESCE(p.`is_own_product`, 0) = 0' : '1 = 1';

        return "SELECT cl.`id` AS `id`, p.`id` AS `product_id`, cp.`product_id` AS `catalog_product_id`, cl.`text` AS `text`,
                    cl.`translations` AS `translations`, cl.`icon` AS `icon`, cl.`active` AS `active`, cl.`sort_order` AS `sort_order`
                FROM {$cpl} cp
                JOIN {$cl} cl ON cl.`id` = cp.`product_label_id` AND {$live}
                JOIN `products` p ON p.`central_product_id` = cp.`product_id` AND {$notOwn}";
    }

    private static function hasSoftDeletes(ConnectionInterface $db): bool
    {
        return CatalogRefColumns::has($db, 'product_labels', 'deleted_at', CentralTable::database());
    }
}
