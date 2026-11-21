<?php

namespace App\Console\Commands;

use App\Services\Catalog\CatalogSyncCheck;
use App\Support\Catalog\CatalogSource;
use Illuminate\Console\Command;

/**
 * Dedup plan §2b.1: read-only drift report between central and every tenant's synced catalog copies.
 * Exit code 1 when a gate fails (default gate: no orphan variants), so it can run in CI/deploy or on a schedule.
 */
class CatalogSyncCheckCommand extends Command
{
    protected $signature = 'catalog:sync-check
        {--tenant=* : Tenant id(s); default every tenant}
        {--section=* : Section(s); default every section}
        {--gate=* : metric:max, e.g. orphan_variants:0 or translations.different:100 (replaces the default gates)}
        {--json : Print the report as JSON}';

    protected $description = 'Read-only report: drift between central catalog data and the tenant copies (dedup plan §2b.1)';

    /** metric => max allowed per tenant + section */
    public const DEFAULT_GATES = ['orphan_variants' => 0];

    public function handle(CatalogSyncCheck $check): int
    {
        $sections = array_map(fn ($s) => strtolower(trim((string) $s)), (array) $this->option('section'));
        $unknown = array_diff($sections, CatalogSource::SECTIONS);
        if ($unknown !== []) {
            $this->error('Unknown section(s): ' . implode(', ', $unknown) . '. Valid: ' . implode(', ', CatalogSource::SECTIONS));

            return self::INVALID;
        }

        $gates = $this->gates();
        if ($gates === null) {
            return self::INVALID;
        }

        $report = $check->run((array) $this->option('tenant'), $sections);
        $failures = $this->evaluate($report, $gates);

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

    /** @return list<string> */
    private function evaluate(array $report, array $gates): array
    {
        $failures = [];
        foreach ($report as $tenant) {
            foreach ($tenant['sections'] as $section => $metrics) {
                foreach ($gates as $metric => $max) {
                    $value = data_get($metrics, $metric);
                    if (is_int($value) && $value > $max) {
                        $failures[] = "{$tenant['tenant']} / {$section}: {$metric} = {$value} (max {$max})";
                    }
                }
            }
        }

        return $failures;
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
                $other = collect($m)->except(['mode', 'rows', 'linked', 'own', 'missing', 'extra', 'stale_missing', 'stale_trashed', 'duplicate_links'])
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
