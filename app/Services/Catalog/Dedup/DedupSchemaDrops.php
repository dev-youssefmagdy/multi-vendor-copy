<?php

namespace App\Services\Catalog\Dedup;

use App\Models\CatalogDedupRun;
use App\Models\Tenant;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/**
 * Dedup plan phase 7, schema part: dropping the copy-only columns of the tenant catalog tables.
 *
 * The drops live in `database/migrations/tenant_dedup_drops/` (one file per section), which is NOT a tenant
 * migrations path: `tenants:migrate` never runs them. Only `catalog:dedup-cleanup <section> --drop-schema` loads the
 * section's file and calls up() (and `--undrop-schema` calls down(), which re-adds the columns NULLable so a backup
 * can be restored). Every up() calls assertAllowed() itself, so running the file any other way is a no-op error.
 *
 * Allowed for a tenant + section only when ALL hold:
 *  - config `catalog_dedup.schema_drops_code_ready` is true (the final-removal commit of plan §12 flips it: until the
 *    sync / rollback code that reads these columns is deleted, dropping them would break running code);
 *  - the section is `central` for the tenant and its latest done cleanup / restore run is a cleanup;
 *  - not one copy row is left: every `link` column is NULL on every row (a kept copy, e.g. a product referenced by a
 *    favourite, blocks the drop until that reference is gone).
 * Never listed: price / profit / stock columns (own products use them), config identity link columns (Phase 6 keeps
 * them), anything on orders / order_items / invoices / payouts.
 */
final class DedupSchemaDrops
{
    public const PATH = 'database/migrations/tenant_dedup_drops';

    public const STEP = 'schema-drop';
    public const STEP_UNDO = 'schema-undrop';

    /** section => [[table, column, 'link' | 'marker']] (marker = copy-only flag, link = central link column). */
    public const DROPS = [
        'badges' => [['product_badges', 'central_product_badge_id', 'link']],
        'labels' => [['product_labels', 'central_product_label_id', 'link']],
        'coupons' => [['coupons', 'central_coupon_id', 'link']],
        'flash_sales' => [['flash_sales', 'central_flash_sale_id', 'link']],
        'categories' => [['categories', 'central_category_id', 'link'], ['categories', 'central_visible', 'marker']],
        'products' => [
            ['product_variants', 'central_product_variant_id', 'link'],
            ['products', 'central_product_id', 'link'],
            ['products', 'central_visible', 'marker'],
            ['products', 'has_custom_translations', 'marker'],
        ],
    ];

    public static function codeReady(): bool
    {
        return (bool) config('catalog_dedup.schema_drops_code_ready', false);
    }

    /**
     * Must run inside the tenant (tenancy initialised). @return list<string> reasons (empty = allowed)
     */
    public static function reasons(Tenant $tenant, string $section): array
    {
        if (! isset(self::DROPS[$section])) {
            return ["section [{$section}] has no copy columns to drop"];
        }
        $reasons = [];
        if (! self::codeReady()) {
            $reasons[] = 'config catalog_dedup.schema_drops_code_ready is false (ship the plan §12 final-removal code first)';
        }
        if (! CatalogSource::isCentral($tenant, DedupCleanup::FLAGS[$section])) {
            $reasons[] = 'catalog_source.' . DedupCleanup::FLAGS[$section] . ' is not central';
        }
        if (! DedupCleanup::isCleaned($tenant, $section)) {
            $reasons[] = "section {$section} is not cleaned (run catalog:dedup-cleanup {$section} --backup first)";
        }
        $schema = Schema::connection('tenant');
        foreach (self::DROPS[$section] as [$table, $column, $kind]) {
            if ($kind === 'link' && $schema->hasColumn($table, $column)) {
                $left = (int) DB::connection('tenant')->table($table)->whereNotNull($column)->count();
                if ($left > 0) {
                    $reasons[] = "{$left} row(s) of {$table} still have {$column} (kept copies, see the cleanup report)";
                }
            }
        }

        return $reasons;
    }

    public static function assertAllowed(string $section): void
    {
        $tenant = tenant();
        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('dedup schema drops run inside a tenant only (catalog:dedup-cleanup --drop-schema)');
        }
        $reasons = self::reasons($tenant, $section);
        if ($reasons !== []) {
            throw new RuntimeException("schema drop for [{$section}] refused: " . implode('; ', $reasons));
        }
    }

    public static function migrationFile(string $section): string
    {
        return base_path(self::PATH . '/drop_' . $section . '_copy_columns.php');
    }

    /** Runs the section's drop file (up, or down with $undo) for one tenant; records a catalog_dedup_runs row. */
    public function run(Tenant $tenant, string $section, bool $undo = false): array
    {
        $tenantId = (string) $tenant->getTenantKey();
        $step = $undo ? self::STEP_UNDO : self::STEP;
        $run = CatalogDedupRun::query()->create(['tenant_id' => $tenantId, 'section' => $section, 'step' => $step, 'status' => CatalogDedupRun::RUNNING, 'started_at' => now()]);
        $report = ['tenant' => $tenantId, 'section' => $section, 'step' => $step];

        try {
            $file = self::migrationFile($section);
            if (! isset(self::DROPS[$section]) || ! is_file($file)) {
                throw new RuntimeException("no drop file for section [{$section}]");
            }
            /** @var Migration $migration */
            $migration = require $file;

            tenancy()->initialize($tenant);
            try {
                $before = $this->present($section);
                $undo ? $migration->down() : $migration->up();
                $after = $this->present($section);
            } finally {
                CatalogRefColumns::flush();
                tenancy()->end();
            }
            $report['columns_before'] = $before;
            $report['columns_after'] = $after;
            $report['status'] = CatalogDedupRun::DONE;
        } catch (Throwable $e) {
            $report['status'] = CatalogDedupRun::FAILED;
            $report['error'] = $e->getMessage();
        }

        $run->forceFill([
            'status' => $report['status'],
            'counts' => ['columns_before' => $report['columns_before'] ?? null, 'columns_after' => $report['columns_after'] ?? null],
            'error' => $report['error'] ?? null,
            'finished_at' => now(),
        ])->save();

        try {
            $path = DedupRunner::reportPath($section, $tenantId, $step);
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $report['report_path'] = $path;
        } catch (Throwable) {
        }

        return $report;
    }

    /** @return list<string> "table.column" of this section's drop list that exist now */
    private function present(string $section): array
    {
        $schema = Schema::connection('tenant');

        return array_values(array_map(fn ($d) => $d[0] . '.' . $d[1], array_filter(self::DROPS[$section], fn ($d) => $schema->hasColumn($d[0], $d[1]))));
    }
}
