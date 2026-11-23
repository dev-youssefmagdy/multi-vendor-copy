<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Catalog\Dedup\DedupCleanup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 7, all tenants at once: runs catalog:dedup-cleanup for every tenant and every section in the
 * recommended order (DedupCleanup::ORDER), never skipping the gates.
 *
 *   catalog:dedup-cleanup-all                       DRY RUN for every tenant / section (default, changes nothing)
 *   catalog:dedup-cleanup-all --execute             real cleanup: a section that passes its dry run is cleaned with a
 *                                                   verified JSON backup; one that does not is skipped as "not ready"
 *   catalog:dedup-cleanup-all --execute --tenant=a --tenant=b --section=badges --section=labels
 *
 * A tenant / section that is not on `central`, not verified with 0 diffs, or whose gates fail is only reported as
 * "not ready" (nothing is deleted). A real cleanup that fails stops THAT tenant (its later sections depend on it) and
 * continues with the next tenant; the command then exits 1. Config sections (languages, currencies, settings,
 * payment_gateways, themes, email_templates) delete nothing: they are recorded as cleaned only with --with-config.
 *
 * Restore is per tenant and section: `catalog:dedup-cleanup <section> --tenant=<id> --restore=<backup dir>`.
 */
class CatalogDedupCleanupAllCommand extends Command
{
    private const CONFIG_SECTIONS = ['languages', 'currencies', 'settings', 'payment_gateways', 'themes', 'email_templates'];

    protected $signature = 'catalog:dedup-cleanup-all
        {--execute : Really delete (with a verified backup). Without it the command only dry-runs}
        {--tenant=* : Limit to these tenant id(s); default every tenant}
        {--section=* : Limit to these section(s); default every catalog section in the recommended order}
        {--with-config : Also run the config sections (they delete nothing; this records them as cleaned)}';

    protected $description = 'Dedup plan phase 7: gated cleanup of the legacy sync copies for ALL tenants and sections (dry-run by default)';

    public function handle(DedupCleanup $cleanup): int
    {
        if (! Schema::connection(config('tenancy.database.central_connection'))->hasTable('catalog_dedup_runs')) {
            $this->error('Central table catalog_dedup_runs is missing: run `php artisan migrate` first.');

            return self::FAILURE;
        }

        $sections = array_values(array_filter((array) $this->option('section')));
        if ($sections === []) {
            $sections = array_values(array_filter(
                DedupCleanup::ORDER,
                fn (string $s) => $this->option('with-config') || ! in_array($s, self::CONFIG_SECTIONS, true),
            ));
        } else {
            $unknown = array_diff($sections, DedupCleanup::sections());
            if ($unknown !== []) {
                $this->error('Unknown section(s): ' . implode(', ', $unknown) . '. Valid: ' . implode(', ', DedupCleanup::sections()));

                return self::INVALID;
            }
            // Always follow the recommended order, whatever order the flags were typed in.
            $sections = array_values(array_intersect(DedupCleanup::ORDER, $sections));
        }

        $ids = array_values(array_filter((array) $this->option('tenant')));
        $tenants = Tenant::query()->when($ids !== [], fn ($q) => $q->whereIn('id', $ids))->orderBy('id')->get();
        foreach (array_diff($ids, $tenants->map(fn (Tenant $t) => (string) $t->getTenantKey())->all()) as $unknown) {
            $this->warn("Unknown tenant [{$unknown}] skipped.");
        }

        $execute = (bool) $this->option('execute');
        $this->info($execute
            ? 'EXECUTE: ready sections are cleaned with a verified backup.'
            : 'DRY RUN: nothing is changed. Pass --execute to really clean the sections that are ready.');

        $rows = [];
        $failed = 0;
        foreach ($tenants as $tenant) {
            $tenantId = (string) $tenant->getTenantKey();
            foreach ($sections as $section) {
                $dry = $cleanup->cleanup($tenant, $section, ['dry_run' => true, 'backup' => false]);
                if (($dry['status'] ?? null) !== 'done') {
                    $rows[] = [$tenantId, $section, 'not ready', $this->reason($dry), ''];

                    continue;
                }
                if (! $execute) {
                    $rows[] = [$tenantId, $section, 'ready', $this->summary($dry), ''];

                    continue;
                }

                $this->line("Cleaning {$tenantId} / {$section} ...");
                $report = $cleanup->cleanup($tenant, $section, ['dry_run' => false, 'backup' => true]);
                if (($report['status'] ?? null) === 'done') {
                    $rows[] = [$tenantId, $section, 'cleaned', $this->summary($report), (string) ($report['backup'] ?? '')];

                    continue;
                }

                $failed++;
                $rows[] = [$tenantId, $section, 'FAILED', $this->reason($report), (string) ($report['backup'] ?? '')];
                $this->error("Cleanup failed for {$tenantId} / {$section}: later sections of this tenant are skipped.");

                break;
            }
        }

        $this->table(['Tenant', 'Section', 'Result', 'Details', 'Backup'], $rows);
        $this->line('Reports: storage/app/dedup-reports/<section>/<tenant>.*.json   Backups: storage/app/dedup-backups/<tenant>/<section>/');
        $this->line('Restore one: php artisan catalog:dedup-cleanup <section> --tenant=<id> --restore=<backup dir name>');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @param array<string, mixed> $report */
    private function reason(array $report): string
    {
        $failures = $report['gate_failures'] ?? [];

        return $failures !== [] ? implode('; ', $failures) : (string) ($report['error'] ?? $report['skipped'] ?? '');
    }

    /** @param array<string, mixed> $report */
    private function summary(array $report): string
    {
        $counts = collect($report['counts'] ?? [])->map(fn ($v, $k) => $k . '=' . (is_array($v) ? json_encode($v) : $v))->implode(' ');
        $kept = collect($report['kept'] ?? [])->map(fn ($v, $k) => "kept {$k}={$v}")->implode(' ');

        return trim($counts . ' ' . $kept);
    }
}
