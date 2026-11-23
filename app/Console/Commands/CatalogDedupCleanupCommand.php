<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Catalog\Dedup\DedupCleanup;
use App\Services\Catalog\Dedup\DedupSchemaDrops;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 7: delete the legacy sync copies of ONE section, tenant by tenant (DedupCleanup), restore a backup,
 * or (later) drop the copy-only columns (DedupSchemaDrops).
 *
 *   catalog:dedup-cleanup badges --dry-run                       plan only (every tenant if no --tenant), writes the report
 *   catalog:dedup-cleanup badges --tenant=<id> --backup          gates → JSON backup (read back) → delete in one transaction
 *   catalog:dedup-cleanup badges --tenant=<id> --restore=<dir>   re-insert a backup (dir under storage/app/dedup-backups/<tenant>/badges)
 *   catalog:dedup-cleanup badges --tenant=<id> --drop-schema     drop the section's copy columns (refused until plan §12 final removal)
 *   catalog:dedup-cleanup badges --tenant=<id> --undrop-schema   re-add them (NULLable)
 *
 * Everything but --dry-run needs explicit --tenant ids. Runs: `catalog_dedup_runs` (step cleanup | cleanup-dry-run |
 * restore | schema-drop | schema-undrop); reports: storage/app/dedup-reports/<section>/<tenant>.<step>.json.
 */
class CatalogDedupCleanupCommand extends Command
{
    protected $signature = 'catalog:dedup-cleanup
        {section : badges | labels | coupons | flash_sales | categories | products | pages | languages | currencies | settings | payment_gateways | themes | email_templates}
        {--tenant=* : Tenant id(s); required except with --dry-run}
        {--dry-run : Check the gates and report what would be deleted / kept; change nothing}
        {--backup : Required for a real cleanup: acknowledges that a JSON backup is written (and verified) before deleting}
        {--restore= : Re-insert a backup (directory name or absolute path) for ONE tenant}
        {--drop-schema : Drop the section copy-only columns (refused unless config catalog_dedup.schema_drops_code_ready and nothing is left to clean)}
        {--undrop-schema : Re-add the dropped copy columns (NULLable), e.g. before --restore}';

    protected $description = 'Dedup plan phase 7: gated per-tenant cleanup of the legacy sync copies (backup + restore)';

    public function handle(DedupCleanup $cleanup, DedupSchemaDrops $drops): int
    {
        $section = (string) $this->argument('section');
        if (! in_array($section, DedupCleanup::sections(), true)) {
            $this->error("Unknown section [{$section}]. Valid: " . implode(', ', DedupCleanup::sections()));

            return self::INVALID;
        }
        $modes = array_filter([
            'dry-run' => (bool) $this->option('dry-run'),
            'restore' => $this->option('restore') !== null,
            'drop-schema' => (bool) $this->option('drop-schema'),
            'undrop-schema' => (bool) $this->option('undrop-schema'),
        ]);
        if (count($modes) > 1) {
            $this->error('Use only one of --dry-run, --restore, --drop-schema, --undrop-schema.');

            return self::INVALID;
        }
        $mode = array_key_first($modes) ?? 'cleanup';
        if (! Schema::connection(config('tenancy.database.central_connection'))->hasTable('catalog_dedup_runs')) {
            $this->error('Central table catalog_dedup_runs is missing: run `php artisan migrate` first.');

            return self::FAILURE;
        }

        $ids = array_values(array_filter((array) $this->option('tenant')));
        if ($ids === [] && $mode !== 'dry-run') {
            $this->error('Pass --tenant=<id> (one tenant at a time is recommended); only --dry-run may run for every tenant.');

            return self::INVALID;
        }
        if ($mode === 'restore' && count($ids) !== 1) {
            $this->error('--restore takes exactly one --tenant.');

            return self::INVALID;
        }

        $tenants = Tenant::query()->when($ids !== [], fn ($q) => $q->whereIn('id', $ids))->orderBy('id')->get();
        foreach (array_diff($ids, $tenants->map(fn (Tenant $t) => (string) $t->getTenantKey())->all()) as $unknown) {
            $this->warn("Unknown tenant [{$unknown}] skipped.");
        }

        $failed = 0;
        $rows = [];
        foreach ($tenants as $tenant) {
            $report = match ($mode) {
                'restore' => $cleanup->restore($tenant, $section, (string) $this->option('restore')),
                'drop-schema' => $drops->run($tenant, $section),
                'undrop-schema' => $drops->run($tenant, $section, true),
                default => $cleanup->cleanup($tenant, $section, ['dry_run' => $mode === 'dry-run', 'backup' => (bool) $this->option('backup')]),
            };
            $failed += $report['status'] === 'done' ? 0 : 1;
            $counts = $report['counts'] ?? [];
            $rows[] = [
                $report['tenant'],
                $report['status'],
                collect($counts)->map(fn ($v, $k) => $k . '=' . (is_array($v) ? json_encode($v) : $v))->implode(' '),
                collect($report['kept'] ?? [])->map(fn ($v, $k) => "{$k}={$v}")->implode(' '),
                $report['error'] ?? '',
            ];
            if (isset($report['backup'])) {
                $this->line("Backup ({$report['tenant']}): {$report['backup']}");
            }
        }

        $this->table(['Tenant', 'Status', 'Counts', 'Kept (reason=count)', 'Error'], $rows);
        $this->line("Reports: storage/app/dedup-reports/{$section}/");
        if ($mode === 'dry-run') {
            $this->info('Dry run: nothing was changed.');
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
