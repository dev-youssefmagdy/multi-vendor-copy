<?php

namespace App\Services\Catalog\Dedup;

use App\Models\Tenant;
use App\Models\Tenant\FlashSale;
use App\Repositories\Tenant\Catalog\CouponCatalog;
use App\Repositories\Tenant\Catalog\FlashSaleCatalog;
use App\Support\Catalog\CatalogRefResolver;
use App\Support\Catalog\CatalogRefWriter;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * `catalog:dedup-migrate flash_sales` (dedup plan phase 3). Verdict DROP: the tenant owns nothing about central sales.
 *
 *  - migrate (additive, idempotent): fills NULL typed pairs of `flash_sale_product` / `flash_sales` (phase-1 refs;
 *    pivot rows typed `flash_sale_type = central` + central id for copies, `tenant` for own sales). Sale rows are never
 *    touched. Report: copies, own, orphan copies (central gone / soft-deleted), stale copies (fields differ), central
 *    pairs of carried products missing in the copy pivot / copy pairs central does not have.
 *  - verify (read-only): (1) sale × product multiset diff between the synced tables and
 *    FlashSaleCatalog::membershipSql() (owner, central/tenant id, discount, window, active, countries); (2) sale-level
 *    banner URL; (3) the effective discount per product per country (tenant countries + every targeted country +
 *    "no country"): the synced-table expression vs FlashSaleCatalog::discountExpression(). All must be 0.
 */
final class FlashSalesDedup implements DedupSection
{
    private const EXAMPLES = 200;

    public function name(): string
    {
        return 'flash_sales';
    }

    private function db(): ConnectionInterface
    {
        return DB::connection('tenant');
    }

    public function notReadyReason(): ?string
    {
        return FlashSaleCatalog::schemaReady($this->db())
            ? null
            : 'tenant DB lacks the phase-1 flash_sale_product typed columns: run `php artisan tenants:migrate` first';
    }

    public function migrate(Tenant $tenant, bool $dryRun, array $options = []): array
    {
        $db = $this->db();
        $cfs = CentralTable::name('central_flash_sales');
        $cfp = CentralTable::name('central_flash_sale_product');
        $live = FlashSaleCatalog::centralLive($db, 'c');

        $counts = [
            'copies' => (int) $db->scalar('SELECT COUNT(*) FROM `flash_sales` WHERE `central_flash_sale_id` IS NOT NULL'),
            'own' => (int) $db->scalar('SELECT COUNT(*) FROM `flash_sales` WHERE `central_flash_sale_id` IS NULL'),
            'orphan_copies' => (int) $db->scalar(
                "SELECT COUNT(*) FROM `flash_sales` t LEFT JOIN {$cfs} c ON c.`id` = t.`central_flash_sale_id`
                  WHERE t.`central_flash_sale_id` IS NOT NULL AND (c.`id` IS NULL OR NOT ({$live}))"
            ),
            'stale_copies' => (int) $db->scalar(
                "SELECT COUNT(*) FROM `flash_sales` t JOIN {$cfs} c ON c.`id` = t.`central_flash_sale_id` AND {$live}
                  WHERE NOT (t.`discount_percentage` <=> c.`discount_percentage` AND t.`start_date` <=> c.`start_date`
                             AND t.`end_date` <=> c.`end_date` AND t.`active` <=> c.`active`)"
            ),
            'pairs_missing_in_copy' => (int) $db->scalar(
                "SELECT COUNT(*) FROM (SELECT DISTINCT cp.`central_flash_sale_id`, p.`id` FROM {$cfp} cp
                   JOIN {$cfs} c ON c.`id` = cp.`central_flash_sale_id` AND {$live}
                   JOIN `products` p ON p.`central_product_id` = cp.`product_id`
                  WHERE NOT EXISTS (SELECT 1 FROM `flash_sale_product` t JOIN `flash_sales` s ON s.`id` = t.`flash_sale_id`
                                     WHERE s.`central_flash_sale_id` = cp.`central_flash_sale_id` AND t.`product_id` = p.`id`)) d"
            ),
            'pairs_extra_in_copy' => (int) $db->scalar(
                "SELECT COUNT(*) FROM `flash_sale_product` t JOIN `flash_sales` s ON s.`id` = t.`flash_sale_id` AND s.`central_flash_sale_id` IS NOT NULL
                   LEFT JOIN `products` p ON p.`id` = t.`product_id`
                  WHERE NOT EXISTS (SELECT 1 FROM {$cfp} cp WHERE cp.`central_flash_sale_id` = s.`central_flash_sale_id` AND cp.`product_id` = p.`central_product_id`)"
            ),
        ];

        $writer = new CatalogRefWriter(CatalogRefResolver::on($db), $dryRun);
        $pivot = $writer->fillTable('flash_sale_product');
        $sales = $writer->fillTable('flash_sales');

        return [
            'counts' => $counts + [
                'pivot_refs_filled' => (int) ($pivot['filled'] ?? 0),
                'pivot_refs_pending' => (int) ($pivot['pending'] ?? 0),
                'sale_refs_filled' => (int) ($sales['filled'] ?? 0),
            ],
            'unmapped' => ['flash_sale_product' => $pivot['unmapped'] ?? [], 'flash_sales' => $sales['unmapped'] ?? []],
            'note' => 'flash sales = DROP: only typed pivot refs are written; copies are deleted in phase 7.',
        ];
    }

    public function verify(Tenant $tenant): array
    {
        $db = $this->db();
        $catalog = app(FlashSaleCatalog::class);

        // (1) membership, old read (synced tables) vs new read (merged).
        $oldCountries = $this->countries('flash_sale_country', 'flash_sale_id');
        $old = [];
        foreach ($db->select(
            'SELECT t.`product_id`, s.`id` AS sid, s.`central_flash_sale_id`, s.`discount_percentage`, s.`start_date`, s.`end_date`, s.`active`
               FROM `flash_sale_product` t JOIN `flash_sales` s ON s.`id` = t.`flash_sale_id`'
        ) as $r) {
            $source = $r->central_flash_sale_id !== null ? 'central' : 'tenant';
            $id = (int) ($r->central_flash_sale_id ?? $r->sid);
            $key = $this->tuple((int) $r->product_id, $source, $id, $r, $oldCountries[(int) $r->sid] ?? []);
            $old[$key] = ($old[$key] ?? 0) + 1;
        }

        $centralCountries = $this->countries(CentralTable::qualified('central_flash_sale_country'), 'central_flash_sale_id');
        $new = [];
        foreach ($db->select('SELECT * FROM (' . FlashSaleCatalog::membershipSql($db) . ') m') as $r) {
            $countries = $r->source === 'central' ? ($centralCountries[(int) $r->source_id] ?? []) : ($oldCountries[(int) $r->source_id] ?? []);
            $key = $this->tuple((int) $r->product_id, (string) $r->source, (int) $r->source_id, $r, $countries);
            $new[$key] = ($new[$key] ?? 0) + 1;
        }

        $onlyOld = $onlyNew = [];
        foreach ($old as $k => $n) {
            if (($d = $n - ($new[$k] ?? 0)) > 0) {
                $onlyOld[$k] = $d;
            }
        }
        foreach ($new as $k => $n) {
            if (($d = $n - ($old[$k] ?? 0)) > 0) {
                $onlyNew[$k] = $d;
            }
        }

        // (2) banners of central sales: copy's stored URL (as the storefront showed it) vs the merged URL.
        $bannerDiffs = [];
        foreach (FlashSale::query()->whereNotNull('central_flash_sale_id')->get() as $copy) {
            $merged = $catalog->find('c:' . $copy->central_flash_sale_id);
            if ($merged !== null && $copy->banner_url !== $merged->banner_url) {
                $bannerDiffs[] = ['central' => (int) $copy->central_flash_sale_id, 'old' => $copy->banner_url, 'new' => $merged->banner_url];
            }
        }

        // (3) effective discount per product per country.
        $countryIds = array_values(array_unique(array_merge(
            [0],
            app(CouponCatalog::class)->tenantCountryIds(),
            ...array_values($oldCountries),
            ...array_values($centralCountries),
        )));
        $discountDiffs = [];
        $checked = 0;
        foreach ($countryIds as $countryId) {
            $cid = $countryId ?: null;
            foreach ($db->select(
                'SELECT products.`id` AS id, (' . $this->oldDiscountExpression($cid) . ') AS o, (' . FlashSaleCatalog::discountExpression($cid, $db) . ') AS n FROM `products`'
            ) as $r) {
                $checked++;
                if (round((float) $r->o, 2) !== round((float) $r->n, 2)) {
                    $discountDiffs[] = ['product' => (int) $r->id, 'country' => $cid, 'old' => (float) $r->o, 'new' => (float) $r->n];
                }
            }
        }

        return [
            'diffs' => array_sum($onlyOld) + array_sum($onlyNew) + count($bannerDiffs) + count($discountDiffs),
            'counts' => [
                'old_rows' => array_sum($old), 'new_rows' => array_sum($new), 'only_old' => array_sum($onlyOld), 'only_new' => array_sum($onlyNew),
                'banner_diffs' => count($bannerDiffs), 'discount_checks' => $checked, 'discount_diffs' => count($discountDiffs),
            ],
            'only_old' => array_slice(array_keys($onlyOld), 0, self::EXAMPLES),
            'only_new' => array_slice(array_keys($onlyNew), 0, self::EXAMPLES),
            'banner_diffs' => array_slice($bannerDiffs, 0, self::EXAMPLES),
            'discount_diffs' => array_slice($discountDiffs, 0, self::EXAMPLES),
        ];
    }

    /**
     * The synced-table expression StorefrontRepository::activeFlashDiscountExpression() uses in sync mode (outer
     * alias `products`), reproduced here for the shadow-read comparison only.
     */
    private function oldDiscountExpression(?int $countryId): string
    {
        $window = 'flash_sales.active = 1 AND (flash_sales.start_date IS NULL OR flash_sales.start_date <= CURRENT_TIMESTAMP) AND (flash_sales.end_date IS NULL OR flash_sales.end_date >= CURRENT_TIMESTAMP)';
        $country = FlashSale::countryScopeSql('flash_sales', $countryId);

        return "COALESCE((SELECT MAX(flash_sales.discount_percentage) FROM flash_sale_product INNER JOIN flash_sales ON flash_sales.id = flash_sale_product.flash_sale_id
                 WHERE flash_sale_product.product_id = products.id AND {$window} AND {$country}), 0)";
    }

    /** @return array<int, list<int>> sale id => sorted country ids */
    private function countries(string $table, string $key): array
    {
        $out = [];
        foreach ($this->db()->table($table)->get([$key, 'country_id']) as $row) {
            $out[(int) $row->{$key}][] = (int) $row->country_id;
        }
        foreach ($out as &$ids) {
            sort($ids);
        }

        return $out;
    }

    private function tuple(int $productId, string $source, int $id, object $r, array $countries): string
    {
        $date = fn (mixed $d) => $d === null ? '' : substr((string) $d, 0, 19);

        return implode('|', [$productId, $source, $id, number_format((float) $r->discount_percentage, 2, '.', ''),
            $date($r->start_date), $date($r->end_date), (int) $r->active, implode(',', $countries)]);
    }
}
