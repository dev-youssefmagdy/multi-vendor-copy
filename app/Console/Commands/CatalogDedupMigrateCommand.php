<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Catalog\Dedup\DedupRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan §5a.2: legacy-data step for one section, tenant by tenant.
 *
 *   catalog:dedup-migrate badges --dry-run     classify, write the report, change nothing
 *   catalog:dedup-migrate badges               additive migrate (idempotent)
 *   catalog:dedup-migrate badges --verify      read-only shadow-read diff (must be 0 before flipping the flag)
 *   catalog:dedup-migrate field_overrides --accept-stale
 *                                              acknowledge the reported stale pairs (approved-request pairs whose tenant
 *                                              value is back to the pre-approval value): they follow central afterwards.
 *                                              Without it a tenant with stale pairs is `failed` (freeze kept, exit 1).
 *
 * Sections: badges, labels, field_overrides, coupons, flash_sales, categories, products (DedupRunner::SECTIONS). Progress rows in `catalog_dedup_runs`, reports in
 * storage/app/dedup-reports/<section>/<tenant>[.dry-run|.verify].json. No cleanup here (phase 7).
 */
class CatalogDedupMigrateCommand extends Command
{
    protected $signature = 'catalog:dedup-migrate
        {section : badges | labels | field_overrides | coupons | flash_sales | categories | products}
        {--tenant=* : Tenant id(s); default every tenant}
        {--dry-run : Classify and report only; write nothing}
        {--verify : Read-only shadow-read diff old (synced) vs new (merged) read}
        {--remap-carts : products only, run AFTER the flip: rewrite mobile cart item ids into merged product keys (use with --dry-run first)}
        {--reverse : products --remap-carts only: rewrite back to tenant copy ids before a rollback to sync}
        {--accept-stale : field_overrides only: drop the reported stale pairs (they follow central); without it a tenant with stale pairs fails and keeps its legacy freeze}';

    protected $description = 'Dedup plan §5a: migrate / verify one section\'s legacy tenant data (additive, per tenant)';

    public function handle(DedupRunner $runner): int
    {
        $section = (string) $this->argument('section');
        if (! array_key_exists($section, DedupRunner::SECTIONS)) {
            $this->error("Unknown section [{$section}]. Valid: " . implode(', ', array_keys(DedupRunner::SECTIONS)));

            return self::INVALID;
        }
        if ($this->option('dry-run') && $this->option('verify')) {
            $this->error('Use either --dry-run or --verify.');

            return self::INVALID;
        }
        if ($this->option('accept-stale') && ($section !== 'field_overrides' || $this->option('verify'))) {
            $this->error('--accept-stale only applies to a field_overrides migrate / dry run.');

            return self::INVALID;
        }
        if (($this->option('remap-carts') || $this->option('reverse')) && ($section !== 'products' || $this->option('verify'))) {
            $this->error('--remap-carts / --reverse only apply to `products` migrate / dry run.');

            return self::INVALID;
        }
        if (! Schema::connection(config('tenancy.database.central_connection'))->hasTable('catalog_dedup_runs')) {
            $this->error('Central table catalog_dedup_runs is missing: run `php artisan migrate` first.');

            return self::FAILURE;
        }

        $step = $this->option('verify') ? 'verify' : ($this->option('dry-run') ? 'dry-run' : 'migrate');
        $ids = array_values(array_filter((array) $this->option('tenant')));
        $tenants = Tenant::query()->when($ids !== [], fn ($q) => $q->whereIn('id', $ids))->orderBy('id')->get();
        foreach (array_diff($ids, $tenants->map(fn (Tenant $t) => (string) $t->getTenantKey())->all()) as $unknown) {
            $this->warn("Unknown tenant [{$unknown}] skipped.");
        }

        $failed = 0;
        $diffs = 0;
        $rows = [];
        foreach ($tenants as $tenant) {
            $report = $runner->run($tenant, $section, $step, ['accept_stale' => (bool) $this->option('accept-stale'), 'remap_carts' => (bool) $this->option('remap-carts'), 'reverse' => (bool) $this->option('reverse')]);
            $failed += $report['status'] === 'failed' ? 1 : 0;
            $diffs += (int) ($report['diffs'] ?? 0);
            $rows[] = [
                $report['tenant'],
                $report['status'] . (isset($report['skipped']) ? " ({$report['skipped']})" : ''),
                collect($report['counts'] ?? [])->map(fn ($v, $k) => "{$k}={$v}")->implode(' '),
                $report['error'] ?? '',
            ];
        }

        $this->table(['Tenant', 'Status', 'Counts', 'Error'], $rows);
        $this->line("Reports: storage/app/dedup-reports/{$section}/");

        if ($step === 'verify') {
            $flag = ['flash_sales' => 'flash-sales'][$section] ?? $section;
            $diffs === 0 ? $this->info('Verify: 0 diffs.') : $this->error("Verify: {$diffs} diff(s); do not flip catalog_source.{$flag} for those tenants.");

            return $failed === 0 && $diffs === 0 ? self::SUCCESS : self::FAILURE;
        }

        $this->info($step === 'dry-run' ? 'Dry run: nothing was changed.' : 'Done (additive; re-running adds nothing).');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
