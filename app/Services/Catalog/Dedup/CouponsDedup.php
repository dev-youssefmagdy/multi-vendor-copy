<?php

namespace App\Services\Catalog\Dedup;

use App\Models\Tenant;
use App\Repositories\Tenant\Catalog\CatalogCoupon;
use App\Repositories\Tenant\Catalog\CouponCatalog;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * `catalog:dedup-migrate coupons` (dedup plan phase 3). Verdict DROP: the tenant owns nothing about central coupons.
 *
 *  - migrate (additive, idempotent): orders that used a coupon get the phase-3 identity columns where still NULL —
 *    `coupon_type` / `catalog_coupon_id` from `discount_id` (a sync copy → central id, own → tenant id) and the
 *    `coupon_code` snapshot. This is the D4 usage history (CouponCatalog::usageCount()). Coupon rows are never touched.
 *    Report classes: copies, own, orphan copies (central gone / soft-deleted), inactive_central copies, not-applicable
 *    copies, own codes colliding with a live central code (D6, tenant wins), orders filled / unmapped (coupon row gone).
 *  - verify (read-only): old read (synced `coupons` + `coupon_country`) vs new read (CouponCatalog::merged()) per code:
 *    source, central / tenant id, type, value, minimum spend, window, country set; plus orders whose coupon ref is
 *    still pending. Copies of switched-off central coupons are reported as `inactive_central` (intended behaviour change,
 *    plan §11), not as diffs.
 */
final class CouponsDedup implements DedupSection
{
    private const EXAMPLES = 200;

    public function name(): string
    {
        return 'coupons';
    }

    private function db(): ConnectionInterface
    {
        return DB::connection('tenant');
    }

    public function notReadyReason(): ?string
    {
        return CouponCatalog::orderColumnsReady($this->db())
            ? null
            : 'tenant DB lacks orders.coupon_code / coupon_type / catalog_coupon_id: run `php artisan tenants:migrate` first';
    }

    public function migrate(Tenant $tenant, bool $dryRun, array $options = []): array
    {
        $db = $this->db();
        $catalog = app(CouponCatalog::class);
        $centralAll = $this->centralRows();
        $applicable = $catalog->applicableCentral(includeInactive: true)->keyBy(fn (CatalogCoupon $c) => $c->ref->id);
        $liveCodes = $centralAll->filter(fn (object $c) => ($c->deleted_at ?? null) === null)
            ->map(fn (object $c) => mb_strtolower((string) $c->code))->flip();

        $counts = ['copies' => 0, 'own' => 0, 'orphan_copies' => 0, 'inactive_central' => 0, 'not_applicable_copies' => 0, 'code_collisions' => 0];
        $examples = [];
        foreach ($db->table('coupons')->get(['id', 'central_coupon_id', 'code']) as $row) {
            if ($row->central_coupon_id === null) {
                $counts['own']++;
                if ($liveCodes->has(mb_strtolower((string) $row->code))) {
                    $counts['code_collisions']++;
                    $examples[] = ['class' => 'code_collision', 'coupon' => (int) $row->id, 'code' => $row->code, 'resolution' => 'tenant wins'];
                }

                continue;
            }

            $counts['copies']++;
            $central = $centralAll->get((int) $row->central_coupon_id);
            if ($central === null || ($central->deleted_at ?? null) !== null) {
                $counts['orphan_copies']++;
                $examples[] = ['class' => 'orphan', 'coupon' => (int) $row->id, 'central' => (int) $row->central_coupon_id];
            } elseif (! $applicable->has((int) $central->id)) {
                $counts['not_applicable_copies']++;
            } elseif (! (bool) $central->active) {
                $counts['inactive_central']++;
            }
        }

        $orders = $this->fillOrders($dryRun);

        return [
            'counts' => $counts + $orders,
            'examples' => array_slice($examples, 0, self::EXAMPLES),
            'note' => 'coupons = DROP: only order coupon refs are written; copies are deleted in phase 7.',
        ];
    }

    /** @return array<string, int> */
    private function fillOrders(bool $dryRun): array
    {
        $db = $this->db();
        $base = "FROM `orders` o JOIN `coupons` c ON c.`id` = o.`discount_id`";
        $pendingRef = "o.`coupon_type` IS NULL";
        $pendingCode = "o.`coupon_code` IS NULL";

        $counts = [
            'orders_with_coupon' => (int) $db->scalar('SELECT COUNT(*) FROM `orders` WHERE `discount_id` IS NOT NULL'),
            'orders_ref_filled' => (int) $db->scalar("SELECT COUNT(*) {$base} WHERE {$pendingRef}"),
            'orders_code_filled' => (int) $db->scalar("SELECT COUNT(*) {$base} WHERE {$pendingCode}"),
            'orders_unmapped' => (int) $db->scalar(
                'SELECT COUNT(*) FROM `orders` o WHERE o.`discount_id` IS NOT NULL AND o.`coupon_type` IS NULL
                   AND NOT EXISTS (SELECT 1 FROM `coupons` c WHERE c.`id` = o.`discount_id`)'
            ),
        ];

        if (! $dryRun) {
            // Each SET reads only its own source columns (multi-table UPDATE has no guaranteed SET order).
            $db->update("UPDATE `orders` o JOIN `coupons` c ON c.`id` = o.`discount_id`
                            SET o.`coupon_type` = IF(c.`central_coupon_id` IS NULL, 'tenant', 'central'),
                                o.`catalog_coupon_id` = COALESCE(c.`central_coupon_id`, c.`id`)
                          WHERE o.`coupon_type` IS NULL");
            $db->update("UPDATE `orders` o JOIN `coupons` c ON c.`id` = o.`discount_id`
                            SET o.`coupon_code` = c.`code`
                          WHERE o.`coupon_code` IS NULL");
        }

        return $counts;
    }

    public function verify(Tenant $tenant): array
    {
        $db = $this->db();
        $catalog = app(CouponCatalog::class);
        $centralAll = $this->centralRows();

        // Old read: every synced coupon row (copies + own), keyed by code.
        $countries = $db->table('coupon_country')->get()->groupBy('coupon_id')
            ->map(fn ($g) => $g->pluck('country_id')->map(fn ($id) => (int) $id)->sort()->values()->all());
        $old = [];
        $inactive = 0;
        foreach ($db->table('coupons')->orderBy('id')->get() as $row) {
            $central = $row->central_coupon_id !== null ? $centralAll->get((int) $row->central_coupon_id) : null;
            if ($central !== null && ($central->deleted_at ?? null) === null && ! (bool) $central->active) {
                $inactive++; // intended: hidden in central mode (plan §11), not a diff

                continue;
            }
            $key = mb_strtolower((string) $row->code);
            if (isset($old[$key]) && $row->central_coupon_id !== null) {
                continue; // own row wins a collision (cannot happen with the unique code index; defensive)
            }
            $old[$key] = $this->fingerprint(
                $row->central_coupon_id !== null ? 'central' : 'tenant',
                (int) ($row->central_coupon_id ?? $row->id),
                (string) $row->type, (float) $row->value, (float) $row->minimum_spend,
                $row->start_date, $row->end_date, $countries->get($row->id, []),
            );
        }

        $new = [];
        foreach ($catalog->merged() as $c) {
            $new[mb_strtolower($c->code)] = $this->fingerprint(
                $c->source, $c->ref->id, $c->type->value, (float) $c->value, (float) $c->minimum_spend,
                $c->start_date?->format('Y-m-d H:i:s'), $c->end_date?->format('Y-m-d H:i:s'), $c->countryIds(),
            );
        }

        $diffs = [];
        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $code) {
            if (($old[$code] ?? null) !== ($new[$code] ?? null)) {
                $diffs[] = ['code' => $code, 'old' => $old[$code] ?? null, 'new' => $new[$code] ?? null];
            }
        }

        $ordersPending = (int) $db->scalar(
            'SELECT COUNT(*) FROM `orders` o JOIN `coupons` c ON c.`id` = o.`discount_id`
              WHERE o.`coupon_type` IS NULL OR o.`coupon_code` IS NULL'
        );

        return [
            'diffs' => count($diffs) + $ordersPending,
            'counts' => [
                'old_coupons' => count($old), 'new_coupons' => count($new), 'coupon_diffs' => count($diffs),
                'inactive_central' => $inactive, 'orders_pending' => $ordersPending,
            ],
            'only_or_different' => array_slice($diffs, 0, self::EXAMPLES),
        ];
    }

    /** All central coupons incl. soft-deleted (keyed by id), read through the tenant connection. */
    private function centralRows()
    {
        $soft = CatalogRefColumns::has($this->db(), 'central_coupons', 'deleted_at', CentralTable::database());

        return $this->db()->table(CentralTable::qualified('central_coupons'))
            ->get(['id', 'code', 'active', ...($soft ? ['deleted_at'] : [])])
            ->keyBy('id');
    }

    private function fingerprint(string $source, int $id, string $type, float $value, float $minimum, mixed $start, mixed $end, array $countries): string
    {
        sort($countries);
        $date = fn (mixed $d) => $d === null ? '' : substr((string) $d, 0, 19);

        return implode('|', [$source, $id, $type, number_format($value, 2, '.', ''), number_format($minimum, 2, '.', ''),
            $date($start), $date($end), implode(',', $countries)]);
    }
}
