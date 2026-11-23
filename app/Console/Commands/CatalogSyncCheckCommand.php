<?php

namespace App\Console\Commands;

use App\Services\Catalog\CatalogSyncCheck;
use App\Support\Catalog\CatalogSource;
use Illuminate\Console\Command;

/**
 * Dedup plan §2b.1: read-only drift report between central and every tenant's synced catalog copies.
 * Exit code 1 when a gate fails (default gate: no orphan variants), so it can run in CI/deploy or on a schedule.
 * Section `refs` (dedup plan phase 1): typed reference pairs still NULL / unmappable / dangling per table
 * (details per table with --json); optional gate e.g. --gate=unmapped_refs:0.
 *
 * Gates fail closed (QA D-P2-3): a gated metric that a section reports as NULL / non-numeric (e.g.
 * `dedup_verify_diffs` of a tenant that was never verified, or whose last verify failed) FAILS. An explicit --gate
 * whose metric no selected section of a tenant reports at all, or a tenant that was skipped (no database), FAILS too;
 * the default gate only checks the sections that report it.
 */
class CatalogSyncCheckCommand extends Command
{
    protected $signature = 'catalog:sync-check
        {--tenant=* : Tenant id(s); default every tenant}
        {--section=* : Section(s); default every section}
        {--gate=* : metric:max, e.g. orphan_variants:0, translations.different:100 or unmapped_refs:0 (replaces the default gates). Fails closed: a gated metric that is NULL (e.g. dedup_verify_diffs never verified) or not reported by any selected section fails}
        {--json : Print the report as JSON}';

    protected $description = 'Read-only report: drift between central catalog data and the tenant copies (dedup plan §2b.1)';

    /** metric => max allowed per tenant + section */
    public const DEFAULT_GATES = ['orphan_variants' => 0];

    public function handle(CatalogSyncCheck $check): int
    {
        $sections = array_map(fn ($s) => strtolower(trim((string) $s)), (array) $this->option('section'));
        $valid = [...CatalogSource::SECTIONS, CatalogSyncCheck::REFS];
        $unknown = array_diff($sections, $valid);
        if ($unknown !== []) {
            $this->error('Unknown section(s): ' . implode(', ', $unknown) . '. Valid: ' . implode(', ', $valid));

            return self::INVALID;
        }

        $gates = $this->gates();
        if ($gates === null) {
            return self::INVALID;
        }

        $report = $check->run((array) $this->option('tenant'), $sections);
        $failures = CatalogSyncCheck::evaluateGates($report, $gates, (array) $this->option('gate') !== []);

        if ($this->option('json')) {
            $this->line(json_encode(['gates' => $gates, 'failures' => $failures, 'tenants' => $report], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->render($report, $gates, $failures);
        }

        return $failures === [] ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<string, int>|null */
    private function gates(): ?array
    {
        $options = (array) $this->option('gate');
        if ($options === []) {
            return self::DEFAULT_GATES;
        }

        $gates = [];
        foreach ($options as $option) {
            if (! preg_match('/^([a-z_.]+):(\d+)$/', (string) $option, $m)) {
                $this->error("Invalid --gate [{$option}]; expected metric:max, e.g. orphan_variants:0.");

                return null;
            }
            $gates[$m[1]] = (int) $m[2];
        }

        return $gates;
    }

    private function render(array $report, array $gates, array $failures): void
    {
        foreach ($report as $tenant) {
            $this->newLine();
            $this->info("Tenant {$tenant['tenant']}  db={$tenant['database']}  sync_status=" . ($tenant['sync_status'] ?? '-'));

            if ($tenant['skipped'] !== null) {
                $this->warn("  skipped: {$tenant['skipped']}");

                continue;
            }

            $rows = [];
            foreach ($tenant['sections'] as $section => $m) {
                $other = collect($m)->except(['mode', 'rows', 'linked', 'own', 'missing', 'extra', 'stale_missing', 'stale_trashed', 'duplicate_links', 'tables'])
                    ->map(fn ($v, $k) => $k . '=' . (is_array($v) ? json_encode($v) : var_export($v, true)))
                    ->implode('  ');

                $rows[] = [
                    $section, $m['mode'], $m['rows'] ?? '-', $m['linked'] ?? '-', $m['own'] ?? '-', $m['missing'] ?? '-',
                    $m['extra'] ?? '-', ($m['stale_missing'] ?? '-') . '/' . ($m['stale_trashed'] ?? '-'), $m['duplicate_links'] ?? '-', $other,
                ];
            }

            $this->table(['Section', 'Mode', 'Rows', 'Linked', 'Own', 'Missing', 'Extra', 'Stale gone/trashed', 'Dup links', 'Other'], $rows);
        }

        $this->newLine();
        $this->line('Gates: ' . collect($gates)->map(fn ($max, $metric) => "{$metric} <= {$max}")->implode(', '));
        foreach ($failures as $failure) {
            $this->error("FAIL {$failure}");
        }
        if ($failures === []) {
            $this->info('All gates passed.');
        }
    }
}
