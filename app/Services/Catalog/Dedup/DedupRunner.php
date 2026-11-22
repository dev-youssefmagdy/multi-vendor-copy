<?php

namespace App\Services\Catalog\Dedup;

use App\Models\CatalogDedupRun;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Throwable;

/**
 * Runs one `catalog:dedup-migrate` step for one tenant (dedup plan §5a.2): records a `catalog_dedup_runs` row
 * (section, step = dry-run | migrate | verify, counts, status, error), initialises tenancy around the handler, and
 * writes storage/app/dedup-reports/<section>/<tenant>[.dry-run|.verify].json. Tenants whose database does not exist
 * are skipped (status `skipped`); a handler that is not ready (tenant migrations missing) is skipped too, so the
 * products sync keeps its legacy behaviour (it only trusts a `done` migrate run).
 */
final class DedupRunner
{
    /** @var array<string, class-string<DedupSection>> */
    public const SECTIONS = [
        'badges' => BadgesDedup::class,
        'labels' => LabelsDedup::class,
        'field_overrides' => FieldOverridesDedup::class,
        // Dedup plan phase 3.
        'coupons' => CouponsDedup::class,
        'flash_sales' => FlashSalesDedup::class,
        // Dedup plan phase 4.
        'categories' => CategoriesDedup::class,
        // Dedup plan phase 5.
        'products' => ProductsDedup::class,
    ];

    public static function handler(string $section): DedupSection
    {
        $class = self::SECTIONS[$section] ?? throw new InvalidArgumentException("Unknown dedup section [{$section}]. Valid: " . implode(', ', array_keys(self::SECTIONS)));

        return app($class);
    }

    /** @return array<string, mixed> report (+ status, report_path) */
    public function run(Tenant $tenant, string $section, string $step, array $options = []): array
    {
        if (! in_array($step, ['dry-run', 'migrate', 'verify'], true)) {
            throw new InvalidArgumentException("Unknown step [{$step}].");
        }

        $handler = self::handler($section);
        $tenantId = (string) $tenant->getTenantKey();
        $report = ['tenant' => $tenantId, 'section' => $section, 'step' => $step, 'started_at' => now()->toIso8601String()];

        $run = CatalogDedupRun::query()->create([
            'tenant_id' => $tenantId,
            'section' => $section,
            'step' => $step,
            'status' => CatalogDedupRun::RUNNING,
            'started_at' => now(),
        ]);

        try {
            if (! $this->databaseExists($tenant)) {
                $report['status'] = CatalogDedupRun::SKIPPED;
                $report['skipped'] = 'tenant database does not exist';
            } else {
                tenancy()->initialize($tenant);
                try {
                    $reason = $handler->notReadyReason();
                    if ($reason !== null) {
                        $report['status'] = CatalogDedupRun::SKIPPED;
                        $report['skipped'] = $reason;
                    } else {
                        $report += $step === 'verify' ? $handler->verify($tenant) : $handler->migrate($tenant, $step === 'dry-run', $options);
                        // `blocked`: needs an explicit acknowledgement; never `done` (a done migrate run lifts freezes).
                        $report['status'] = isset($report['blocked']) ? CatalogDedupRun::FAILED : CatalogDedupRun::DONE;
                        if (isset($report['blocked'])) {
                            $report['error'] = (string) $report['blocked'];
                        }
                    }
                } finally {
                    tenancy()->end();
                }
            }
        } catch (Throwable $e) {
            $report['status'] = CatalogDedupRun::FAILED;
            $report['error'] = $e->getMessage();
        }

        $report['finished_at'] = now()->toIso8601String();
        $counts = (array) ($report['counts'] ?? []);
        if (array_key_exists('diffs', $report)) {
            $counts['diffs'] = (int) $report['diffs'];
        }

        $run->forceFill([
            'status' => $report['status'],
            'counts' => $counts + ['skipped' => $report['skipped'] ?? null],
            'error' => $report['error'] ?? null,
            'finished_at' => now(),
        ])->save();

        $report['report_path'] = $this->writeReport($section, $tenantId, $step, $report);

        return $report;
    }

    public static function reportPath(string $section, string $tenantId, string $step): string
    {
        $suffix = $step === 'migrate' ? '' : '.' . $step;

        return storage_path('app/dedup-reports/' . $section . '/' . preg_replace('/[^A-Za-z0-9_-]/', '_', $tenantId) . $suffix . '.json');
    }

    private function writeReport(string $section, string $tenantId, string $step, array $report): ?string
    {
        try {
            $path = self::reportPath($section, $tenantId, $step);
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $path;
        } catch (Throwable) {
            return null;
        }
    }

    private function databaseExists(Tenant $tenant): bool
    {
        $central = DB::connection(config('tenancy.database.central_connection'));
        if ($central->getDriverName() !== 'mysql') {
            return true;
        }

        return $central->selectOne('SELECT 1 AS ok FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$tenant->database()->getName()]) !== null;
    }
}
