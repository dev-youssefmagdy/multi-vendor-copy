<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Catalog\CatalogRefBackfill;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 1: fill the typed reference pairs (`<x>_type` + `catalog_<x>_id`) of existing rows, tenant by
 * tenant. Idempotent and resumable (only NULL pairs are written); unmapped rows are reported, never dropped.
 * Tenants without a database are skipped; databases without a `tenants` row (orphans) are listed and never touched.
 */
class CatalogBackfillRefsCommand extends Command
{
    protected $signature = 'catalog:backfill-refs
        {--tenant=* : Tenant id(s); default every tenant}
        {--dry-run : Count what would be filled and write the report; change nothing}
        {--chunk=1000 : Distinct ids per SELECT/UPDATE}';

    protected $description = 'Backfill typed catalog references (product_type/catalog_product_id, …) per tenant (dedup plan phase 1)';

    public function handle(CatalogRefBackfill $backfill): int
    {
        if (! Schema::connection(config('tenancy.database.central_connection'))->hasTable('catalog_dedup_runs')) {
            $this->error('Central table catalog_dedup_runs is missing: run `php artisan migrate` first.');

            return self::FAILURE;
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $ids = array_values(array_filter((array) $this->option('tenant')));

        $tenants = Tenant::query()->when($ids !== [], fn ($q) => $q->whereIn('id', $ids))->orderBy('id')->get();
        $unknown = array_diff($ids, $tenants->map(fn (Tenant $t) => (string) $t->getTenantKey())->all());
        foreach ($unknown as $id) {
            $this->warn("Unknown tenant [{$id}] skipped.");
        }

        if ($ids === []) {
            foreach ($this->orphanDatabases($tenants) as $schema) {
                $this->warn("Orphan database [{$schema}] (no tenants row) skipped.");
            }
        }

        $failed = 0;
        $rows = [];
        foreach ($tenants as $tenant) {
            $report = $backfill->run($tenant, $dryRun, $chunk);
            $failed += $report['status'] === 'failed' ? 1 : 0;
            $rows[] = [
                $report['tenant'],
                $report['status'] . ($report['skipped'] ? " ({$report['skipped']})" : ''),
                $report['totals']['filled'],
                $report['totals']['pending'],
                $report['totals']['unmapped'],
                $report['error'] ?? '',
            ];
            if (($report['totals']['unmapped'] ?? 0) > 0 && $report['report_path']) {
                $this->line("  {$report['tenant']}: unmapped rows listed in {$report['report_path']}");
            }
        }

        $this->table(['Tenant', 'Status', $dryRun ? 'Would fill' : 'Filled', 'Pending (NULL left)', 'Unmapped', 'Error'], $rows);
        $this->info($dryRun ? 'Dry run: nothing was changed.' : 'Done. Re-running is a no-op for filled rows.');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @return list<string> tenant-prefixed schemas that belong to no tenant row */
    private function orphanDatabases($tenants): array
    {
        $db = DB::connection(config('tenancy.database.central_connection'));
        if ($db->getDriverName() !== 'mysql') {
            return [];
        }

        $prefix = (string) config('tenancy.database.prefix');
        $suffix = (string) config('tenancy.database.suffix');
        if ($prefix === '') {
            return [];
        }

        $known = array_flip($tenants->map(fn (Tenant $t) => $t->database()->getName())->all());
        $schemas = array_map(fn ($r) => $r->s, $db->select(
            'SELECT SCHEMA_NAME AS s FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE ?',
            [str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $prefix) . '%'],
        ));

        // UUID-shaped tenant databases only (skips e.g. dokan_v2_testing*).
        $pattern = '/^' . preg_quote($prefix, '/') . '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}' . preg_quote($suffix, '/') . '$/i';

        return array_values(array_filter($schemas, fn ($s) => ! isset($known[$s]) && preg_match($pattern, $s)));
    }
}
