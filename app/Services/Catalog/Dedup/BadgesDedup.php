<?php

namespace App\Services\Catalog\Dedup;

use App\Models\Tenant;
use App\Repositories\Tenant\Catalog\BadgeCatalog;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRef;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogRefWriter;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * `catalog:dedup-migrate badges` (dedup plan phase 2, §5a). Legacy data: tenant copies of central badges
 * (`product_badges.central_product_badge_id` set) and their pivot rows. What the tenant really owns there:
 *
 *  - sort order of a central pair that differs from central's  → `tenant_badge_product_overrides.sort_order`;
 *  - a pair on a central badge that central does not have       → tenant attach row (`product_badge_id` NULL,
 *    `badge_type = central`, `catalog_badge_id` = central id, typed product pair, same country / sort);
 *  - everything else is a copy (left in place until phase 7 cleanup).
 *
 * Before converting, unlinked tenant badges whose text equals a central badge are linked (what the next badges sync
 * does anyway; Phase -1 backfill rule: oldest row per text). Own badges and their rows need nothing.
 * Idempotent; additive except one case (QA D-P2-5): while the tenant's badges section is still `sync`, an existing
 * sort override is refreshed to the copy's current sort (NULL when it is back to central's), so a vendor reorder made
 * after an earlier run does not make verify fail. Attach rows are never changed; after the flip nothing is changed.
 * The pivot unique index cannot stop duplicate central-attach rows (`product_badge_id` NULL: NULLs are distinct in a
 * unique index, QA D-P2-6): the existence check above and BadgeAssignments are the only guards.
 *
 * verify: multiset diff of (text, active, product, country, sort_order) between the synced tables (old read) and
 * BadgeCatalog::membershipQuery() (new read). Central pairs missing from the copy (sync lag) show up as diffs: run
 * the badges sync first.
 */
final class BadgesDedup implements DedupSection
{
    private const EXAMPLES = 200;

    public function name(): string
    {
        return 'badges';
    }

    private function db(): ConnectionInterface
    {
        return DB::connection('tenant');
    }

    public function notReadyReason(): ?string
    {
        return BadgeCatalog::schemaReady($this->db())
            ? null
            : 'tenant badge schema missing (run tenants:migrate: 2026_11_21_000003, 2026_11_23_000001, 2026_11_24_000001)';
    }

    public function migrate(Tenant $tenant, bool $dryRun, array $options = []): array
    {
        $counts = ['linked_by_text' => 0, 'copy' => 0, 'sort_override' => 0, 'sort_override_refreshed' => 0, 'tenant_attach' => 0, 'already_migrated' => 0,
            'own_badge_rows' => 0, 'orphan_rows' => 0, 'central_pairs_missing_in_copy' => 0];
        $examples = [];

        $wouldLink = $this->linkByText($dryRun); // tenant badge id => central badge id
        $counts['linked_by_text'] = count($wouldLink);
        [$centralPairs, $centralText] = $this->centralPairs();
        $centralIds = array_flip($centralText);

        $rows = $this->db()->select(
            'SELECT t.`id`, t.`product_id`, t.`country_id`, t.`sort_order`, b.`id` AS tenant_badge_id, b.`text`,
                    b.`central_product_badge_id` AS central_badge_id, p.`central_product_id`, ' . $this->ownSql('p') . ' AS own
               FROM `product_badge_product` t
               JOIN `product_badges` b ON b.`id` = t.`product_badge_id`
               LEFT JOIN `products` p ON p.`id` = t.`product_id`'
        );

        $now = now();
        $syncMode = ! CatalogSource::isCentral($tenant, 'badges');
        foreach ($rows as $row) {
            $centralBadgeId = $row->central_badge_id ?? ($wouldLink[(int) $row->tenant_badge_id] ?? null);
            if ($centralBadgeId === null) {
                $counts['own_badge_rows']++;

                continue;
            }
            $centralBadgeId = (int) $centralBadgeId;
            if (! isset($centralIds[$centralBadgeId])) {
                $counts['orphan_rows']++; // linked to a central badge that no longer exists
                $examples[] = ['class' => 'orphan', 'row' => (int) $row->id, 'central_badge_id' => $centralBadgeId];

                continue;
            }

            $isCentralProduct = $row->central_product_id !== null && ! $row->own;
            $key = $isCentralProduct ? $this->pairKey($centralBadgeId, (int) $row->central_product_id, $row->country_id) : null;

            if ($key !== null && isset($centralPairs[$key])) {
                unset($centralPairs[$key]['missing']);
                $override = $this->db()->table(BadgeCatalog::OVERRIDES)->where([
                    'badge_type' => CatalogRef::CENTRAL, 'badge_id' => $centralBadgeId,
                    'product_type' => CatalogRef::CENTRAL, 'product_id' => (int) $row->central_product_id,
                    'country_key' => (int) ($row->country_id ?? 0),
                ])->first(['id', 'sort_order']);
                $sameAsCentral = (int) $centralPairs[$key]['sort_order'] === (int) $row->sort_order;

                if ($override !== null) {
                    // QA D-P2-5: while the tenant is still on `sync` its copy is the truth, so a later vendor reorder
                    // refreshes the override (idempotent: re-running with the same data changes nothing). A reorder
                    // back to central's value clears the sort (NULL = follow central). After the flip the override
                    // rows are the truth and are never touched here.
                    $want = $sameAsCentral ? null : (int) $row->sort_order;
                    $have = $override->sort_order === null ? null : (int) $override->sort_order;
                    if ($want === $have || ! $syncMode) {
                        $counts[$sameAsCentral && $have === null ? 'copy' : 'already_migrated']++;

                        continue;
                    }

                    $counts['sort_override_refreshed']++;
                    $this->example($examples, ['class' => 'sort_override_refreshed', 'row' => (int) $row->id, 'override_sort' => $have, 'tenant_sort' => $want]);
                    if (! $dryRun) {
                        $this->db()->table(BadgeCatalog::OVERRIDES)->where('id', $override->id)->update(['sort_order' => $want, 'updated_at' => $now]);
                    }

                    continue;
                }

                if ($sameAsCentral) {
                    $counts['copy']++;

                    continue;
                }

                $counts['sort_override']++;
                $this->example($examples, ['class' => 'sort_override', 'row' => (int) $row->id, 'central_sort' => (int) $centralPairs[$key]['sort_order'], 'tenant_sort' => (int) $row->sort_order]);
                if (! $dryRun) {
                    $this->db()->table(BadgeCatalog::OVERRIDES)->insert([
                        'badge_type' => CatalogRef::CENTRAL, 'badge_id' => $centralBadgeId,
                        'product_type' => CatalogRef::CENTRAL, 'product_id' => (int) $row->central_product_id,
                        'country_id' => $row->country_id, 'sort_order' => (int) $row->sort_order, 'hidden' => false,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }

                continue;
            }

            // Pair central does not have: the vendor's own attach to a central badge.
            $exists = $this->db()->table('product_badge_product')
                ->whereNull('product_badge_id')->where('badge_type', CatalogRef::CENTRAL)->where('catalog_badge_id', $centralBadgeId)
                ->where('product_id', (int) $row->product_id)
                ->where(fn ($q) => $row->country_id === null ? $q->whereNull('country_id') : $q->where('country_id', $row->country_id))
                ->exists();
            if ($exists) {
                $counts['already_migrated']++;

                continue;
            }

            $counts['tenant_attach']++;
            $this->example($examples, ['class' => 'tenant_attach', 'row' => (int) $row->id, 'central_badge_id' => $centralBadgeId, 'product_id' => (int) $row->product_id, 'country_id' => $row->country_id]);
            if (! $dryRun) {
                $this->db()->table('product_badge_product')->insert([
                    'product_badge_id' => null,
                    'badge_type' => CatalogRef::CENTRAL,
                    'catalog_badge_id' => $centralBadgeId,
                    'product_id' => (int) $row->product_id,
                    'country_id' => $row->country_id,
                    'sort_order' => (int) $row->sort_order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach ($centralPairs as $pair) {
            if (isset($pair['missing'])) {
                $counts['central_pairs_missing_in_copy']++;
                $this->example($examples, ['class' => 'central_pair_missing_in_copy'] + $pair);
            }
        }

        if (! $dryRun) {
            CatalogRefWriter::fillQuietly('product_badge_product', ['product_id' => array_values(array_unique(array_map(fn ($r) => (int) $r->product_id, $rows)))]);
            CacheVersion::bumpMany(['ProductBadge', 'Product']);
        }

        return ['counts' => $counts, 'examples' => $examples];
    }

    public function verify(Tenant $tenant): array
    {
        $old = $this->tuples($this->db()->select(
            'SELECT b.`text`, b.`active`, t.`product_id`, t.`country_id`, t.`sort_order`
               FROM `product_badge_product` t JOIN `product_badges` b ON b.`id` = t.`product_badge_id`'
        ));
        $new = $this->tuples($this->db()->select(
            'SELECT m.`text`, m.`active`, m.`product_id`, m.`country_id`, m.`sort_order` FROM (' . BadgeCatalog::membershipSql($this->db()) . ') m'
        ));

        [$onlyOld, $onlyNew] = $this->diff($old, $new);

        return [
            'diffs' => array_sum($onlyOld) + array_sum($onlyNew),
            'counts' => ['old_rows' => array_sum($old), 'new_rows' => array_sum($new), 'only_old' => array_sum($onlyOld), 'only_new' => array_sum($onlyNew)],
            'only_old' => array_slice(array_keys($onlyOld), 0, self::EXAMPLES),
            'only_new' => array_slice(array_keys($onlyNew), 0, self::EXAMPLES),
        ];
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /**
     * Link unlinked tenant badges to the central badge with the same text (oldest row per text).
     *
     * @return array<int, int> tenant badge id => central badge id (linked, or would be in a dry run)
     */
    private function linkByText(bool $dryRun): array
    {
        $central = $this->db()->table(CentralTable::qualified('product_badges'))->pluck('id', 'text')->all();
        $linked = $this->db()->table('product_badges')->whereNotNull('central_product_badge_id')->pluck('central_product_badge_id')->map(fn ($id) => (int) $id)->all();
        $links = [];

        foreach ($central as $text => $centralId) {
            if (in_array((int) $centralId, $linked, true)) {
                continue;
            }
            $tenantId = $this->db()->table('product_badges')->whereNull('central_product_badge_id')->where('text', $text)->orderBy('id')->value('id');
            if ($tenantId !== null) {
                $links[(int) $tenantId] = (int) $centralId;
                if (! $dryRun) {
                    $this->db()->table('product_badges')->where('id', $tenantId)->update(['central_product_badge_id' => $centralId]);
                }
            }
        }

        return $links;
    }

    /**
     * Central pivot pairs of products this tenant carries, keyed by pairKey(), each flagged `missing` until a tenant
     * copy row is seen; plus [text => central badge id].
     *
     * @return array{0: array<string, array<string, mixed>>, 1: array<string, int>}
     */
    private function centralPairs(): array
    {
        $texts = $this->db()->table(CentralTable::qualified('product_badges'))->pluck('id', 'text')->map(fn ($id) => (int) $id)->all();
        $rows = $this->db()->select(
            'SELECT cp.`product_badge_id`, cp.`product_id`, cp.`country_id`, cp.`sort_order`, MIN(p.`id`) AS tenant_product_id
               FROM ' . CentralTable::name('product_badge_product') . ' cp
               JOIN `products` p ON p.`central_product_id` = cp.`product_id` AND ' . $this->ownSql('p') . ' = 0
              GROUP BY cp.`product_badge_id`, cp.`product_id`, cp.`country_id`, cp.`sort_order`'
        );

        $pairs = [];
        foreach ($rows as $row) {
            $pairs[$this->pairKey((int) $row->product_badge_id, (int) $row->product_id, $row->country_id)] = [
                'central_badge_id' => (int) $row->product_badge_id,
                'central_product_id' => (int) $row->product_id,
                'tenant_product_id' => (int) $row->tenant_product_id,
                'country_id' => $row->country_id === null ? null : (int) $row->country_id,
                'sort_order' => (int) $row->sort_order,
                'missing' => true,
            ];
        }

        return [$pairs, $texts];
    }

    private function pairKey(int $badgeId, int $centralProductId, mixed $countryId): string
    {
        return $badgeId . '|' . $centralProductId . '|' . ($countryId === null ? '-' : (int) $countryId);
    }

    private function ownSql(string $alias): string
    {
        return CatalogRefColumns::has($this->db(), 'products', 'is_own_product') ? "COALESCE({$alias}.`is_own_product`, 0)" : '0';
    }

    /** @return array<string, int> tuple => occurrences */
    private function tuples(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $key = implode('|', [$row->text, (int) $row->active, (int) $row->product_id, $row->country_id === null ? '-' : (int) $row->country_id, (int) $row->sort_order]);
            $out[$key] = ($out[$key] ?? 0) + 1;
        }

        return $out;
    }

    /** @return array{0: array<string, int>, 1: array<string, int>} */
    private function diff(array $old, array $new): array
    {
        $onlyOld = $onlyNew = [];
        foreach ($old as $key => $n) {
            if (($d = $n - ($new[$key] ?? 0)) > 0) {
                $onlyOld[$key] = $d;
            }
        }
        foreach ($new as $key => $n) {
            if (($d = $n - ($old[$key] ?? 0)) > 0) {
                $onlyNew[$key] = $d;
            }
        }

        return [$onlyOld, $onlyNew];
    }

    private function example(array &$examples, array $example): void
    {
        if (count($examples) < self::EXAMPLES) {
            $examples[] = $example;
        }
    }
}
