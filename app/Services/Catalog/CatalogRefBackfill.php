<?php

namespace App\Services\Catalog;

use App\Models\CatalogDedupRun;
use App\Models\Tenant;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogRefResolver;
use App\Support\Catalog\CatalogRefWriter;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Dedup plan phase 1 backfill (§5, §5a): fills the NULL typed reference pairs of one tenant from its rows' central
 * links, for every tenant table (CatalogRefColumns::TENANT_TABLES), the `mobile_carts.items` JSON and the central
 * tables keyed by this tenant (return_requests, product_edit_requests).
 *
 * Runs on the CENTRAL connection with schema-qualified tenant tables (no tenancy initialisation). Only NULL pairs are
 * written, so a second run is a no-op and an interrupted run resumes. Rows that cannot be mapped (tenant row deleted /
 * link missing) are reported, never changed or dropped. Each run is recorded in `catalog_dedup_runs` and writes
 * storage/app/dedup-reports/refs/<tenant>[.dry-run].json.
 */
class CatalogRefBackfill
{
    public const SECTION = 'refs';

    private Connection $central;

    public function __construct()
    {
        $this->central = DB::connection(config('tenancy.database.central_connection'));
    }

    /** @return array<string, mixed> report */
    public function run(Tenant $tenant, bool $dryRun = false, int $chunk = 1000): array
    {
        $tenantId = (string) $tenant->getTenantKey();
        $schema = $tenant->database()->getName();
        $report = [
            'tenant' => $tenantId,
            'database' => $schema,
            'dry_run' => $dryRun,
            'status' => CatalogDedupRun::RUNNING,
            'skipped' => null,
            'started_at' => now()->toIso8601String(),
            'tables' => [],
            'mobile_carts' => null,
            'central' => [],
            'totals' => ['filled' => 0, 'pending' => 0, 'unmapped' => 0],
        ];

        $run = CatalogDedupRun::query()->create([
            'tenant_id' => $tenantId,
            'section' => self::SECTION,
            'step' => $dryRun ? 'dry-run' : 'migrate',
            'status' => CatalogDedupRun::RUNNING,
            'started_at' => now(),
        ]);

        try {
            if (! $this->schemaExists($schema)) {
                $report['skipped'] = 'tenant database does not exist';
                $report['status'] = CatalogDedupRun::SKIPPED;
            } else {
                $writer = new CatalogRefWriter(new CatalogRefResolver($this->central, $schema), $dryRun);

                foreach (array_keys(CatalogRefColumns::TENANT_TABLES) as $table) {
                    $report['tables'][$table] = $writer->fillTable($table, [], $chunk);
                }
                $report['mobile_carts'] = $this->carts($writer, $schema, $dryRun, $chunk);
                foreach (array_keys(CatalogRefColumns::CENTRAL_TABLES) as $table) {
                    $report['central'][$table] = $writer->fillTable($table, [], $chunk, $tenantId);
                }
                $report['central']['product_edit_requests']['central_product_id'] = $this->editRequestCentralIds($tenantId, $dryRun);

                $report['status'] = CatalogDedupRun::DONE;
            }
        } catch (Throwable $e) {
            $report['status'] = CatalogDedupRun::FAILED;
            $report['error'] = $e->getMessage();
        }

        $report['totals'] = $this->totals($report);
        $report['finished_at'] = now()->toIso8601String();

        $run->forceFill([
            'status' => $report['status'],
            'counts' => $report['totals'] + ['skipped' => $report['skipped']],
            'error' => $report['error'] ?? null,
            'finished_at' => now(),
        ])->save();

        $report['report_path'] = $this->writeReport($tenantId, $dryRun, $report);

        return $report;
    }

    /** Report path for a tenant (storage/app/dedup-reports/refs/<tenant>[.dry-run].json). */
    public static function reportPath(string $tenantId, bool $dryRun): string
    {
        return storage_path('app/dedup-reports/refs/' . preg_replace('/[^A-Za-z0-9_-]/', '_', $tenantId) . ($dryRun ? '.dry-run' : '') . '.json');
    }

    /** @return array<string, mixed> */
    private function carts(CatalogRefWriter $writer, string $schema, bool $dryRun, int $chunk): array
    {
        $result = ['scanned' => 0, 'updated' => 0, 'items_filled' => 0, 'pending' => 0, 'unmapped' => []];
        if (! CatalogRefColumns::has($this->central, 'mobile_carts', 'items', $schema)) {
            return ['skipped' => 'table missing'] + $result;
        }

        $table = $writer->resolver->table('mobile_carts');
        $last = 0;
        while (true) {
            $rows = $this->central->select("SELECT `id`, `items` FROM {$table} WHERE `id` > ? ORDER BY `id` LIMIT " . max(1, $chunk), [$last]);
            if ($rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $last = (int) $row->id;
                $result['scanned']++;
                $items = json_decode((string) $row->items, true);
                if (! is_array($items) || $items === []) {
                    continue;
                }

                $enriched = $writer->enrichCartItems($items, $unmapped);
                $filled = 0;
                foreach ($enriched as $key => $item) {
                    if (is_array($item) && isset($item['product_type']) && ! isset($items[$key]['product_type'])) {
                        $filled++;
                    }
                }
                if ($unmapped !== []) {
                    $result['unmapped'][$row->id] = array_map(fn ($item) => [
                        'product_id' => $item['product_id'] ?? null,
                        'variant_id' => $item['variant_id'] ?? null,
                    ], $unmapped);
                    $result['pending'] += count($unmapped);
                }
                if ($filled === 0) {
                    continue;
                }

                if (! $dryRun) {
                    // Compare-and-swap (QA D-P1-2): a cart the customer changed since the SELECT is left alone and
                    // picked up by the next run (its own write already carries the keys via MobileCart).
                    $swapped = $this->central->update(
                        "UPDATE {$table} SET `items` = ? WHERE `id` = ? AND `items` = CAST(? AS JSON)",
                        [json_encode($enriched), $row->id, (string) $row->items],
                    );
                    if ($swapped === 0) {
                        $result['changed_meanwhile'] = ($result['changed_meanwhile'] ?? 0) + 1;

                        continue;
                    }
                }
                $result['items_filled'] += $filled;
                $result['updated']++;
            }
        }

        return $result;
    }

    /** product_edit_requests.central_product_id from the filled pair (§4.2a). */
    private function editRequestCentralIds(string $tenantId, bool $dryRun): int|string
    {
        if (! CatalogRefColumns::has($this->central, 'product_edit_requests', 'central_product_id')) {
            return 'column missing (migration not run)';
        }

        $query = $this->central->table('product_edit_requests')
            ->where('tenant_id', $tenantId)
            ->where('product_type', 'central')
            ->whereNull('central_product_id');

        return $dryRun ? $query->count() : $query->update(['central_product_id' => DB::raw('`catalog_product_id`')]);
    }

    /** @return array{filled: int, pending: int, unmapped: int} */
    private function totals(array $report): array
    {
        $totals = ['filled' => 0, 'pending' => 0, 'unmapped' => 0];
        foreach (array_merge(array_values($report['tables']), array_values($report['central'])) as $table) {
            $totals['filled'] += (int) ($table['filled'] ?? 0);
            $totals['pending'] += (int) ($table['pending'] ?? 0);
            foreach ($table['unmapped'] ?? [] as $ids) {
                $totals['unmapped'] += array_sum($ids);
            }
        }
        if (is_array($report['mobile_carts'])) {
            $totals['filled'] += $report['mobile_carts']['items_filled'];
            $totals['pending'] += $report['mobile_carts']['pending'];
            $totals['unmapped'] += $report['mobile_carts']['pending'];
        }

        return $totals;
    }

    private function writeReport(string $tenantId, bool $dryRun, array $report): ?string
    {
        try {
            $path = self::reportPath($tenantId, $dryRun);
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $path;
        } catch (Throwable) {
            return null;
        }
    }

    private function schemaExists(string $schema): bool
    {
        if ($this->central->getDriverName() !== 'mysql') {
            return false;
        }

        return $this->central->selectOne('SELECT 1 AS ok FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$schema]) !== null;
    }
}
