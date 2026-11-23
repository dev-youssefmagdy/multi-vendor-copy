<?php

namespace App\Services\Catalog\Dedup;

use App\Models\CatalogDedupRun;
use App\Models\Tenant;
use App\Repositories\Tenant\Catalog\CategoryCatalog;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use App\Services\Catalog\CatalogSyncCheck;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Dedup plan phase 7: `catalog:dedup-cleanup` — the ONLY code path that deletes legacy sync copies, per tenant and per
 * section, after the section runs on `central`.
 *
 * Refuses (nothing deleted, run recorded `failed`) unless ALL of these hold for the tenant:
 *  1. `catalog_source.<section>` is `central` (products: badges, labels, flash-sales and categories too, and those four
 *     are already cleaned);
 *  2. the latest `catalog:dedup-migrate <section> --verify` run is `done`, finished, newer than the latest migrate run,
 *     and has 0 diffs;
 *  3. the section's `catalog:sync-check` gates pass (fail closed, see GATES; catalog sections also `refs.null_refs:0`);
 *  4. (not for --dry-run) a backup of every row that will be deleted was written and read back
 *     (JSON: storage/app/dedup-backups/<tenant>/<section>/<timestamp>/{rows.json,manifest.json}, sha256-checked).
 *
 * What is deleted: ONLY central-linked copy rows (+ their copy pivot rows and their copy `translations`). A copy is KEPT
 * (reported under `kept`, never deleted) when anything that is not itself part of the deleted set still references it:
 * any foreign key found in information_schema (favourites, ratings, own-category links, own flash sales, vendor badge
 * attaches, `order_items.product_variant_id` …; a cascade / SET NULL would otherwise change tenant or order rows), the
 * listed non-FK references (`order_items.product_id`, `orders.discount_id`, central `return_requests` /
 * `product_edit_requests`), a child row that is not a copy, or a translation in a language central does not have that was
 * not re-keyed to the central id. The check runs to a fixpoint and again inside the delete transaction with the parent
 * rows locked; if anything changed the transaction is rolled back. Order / order item snapshots, invoices, payout rows,
 * own rows and override rows are never selected. Config sections (languages, currencies, settings, payment_gateways,
 * themes, email_templates) keep their rows: they are the identity rows of the Phase 6 overlay (nothing to delete);
 * `pages` deletes only the translations of `source = central` pages in the locales the platform manages.
 *
 * restore(): re-inserts a backup (refuses on primary-key conflicts or missing columns), the rollback path.
 */
final class DedupCleanup
{
    public const STEP = 'cleanup';
    public const STEP_DRY = 'cleanup-dry-run';
    public const STEP_RESTORE = 'restore';

    /** Recommended per-tenant order (products needs the four sections before it cleaned). */
    public const ORDER = [
        'badges', 'labels', 'coupons', 'flash_sales', 'categories', 'products', 'pages',
        'languages', 'currencies', 'settings', 'payment_gateways', 'themes', 'email_templates',
    ];

    /** dedup section => catalog_source / sync-check section. */
    public const FLAGS = [
        'badges' => 'badges', 'labels' => 'labels', 'coupons' => 'coupons', 'flash_sales' => 'flash-sales',
        'categories' => 'categories', 'products' => 'products', 'pages' => 'pages', 'languages' => 'languages',
        'currencies' => 'currencies', 'settings' => 'settings', 'payment_gateways' => 'payment-gateways',
        'themes' => 'themes', 'email_templates' => 'email-templates',
    ];

    /** products: these must be `central` AND cleaned first (their copy pivots reference product copies). */
    public const PRODUCT_PREREQUISITES = ['badges', 'labels', 'flash_sales', 'categories'];

    /** sync-check gates per section (metric => max). Catalog sections also gate `null_refs` of the `refs` section. */
    public const GATES = [
        'badges' => ['dedup_verify_diffs' => 0, 'sort_overrides_pending' => 0, 'attaches_pending' => 0],
        'labels' => ['dedup_verify_diffs' => 0],
        'coupons' => ['dedup_verify_diffs' => 0],
        'flash_sales' => ['dedup_verify_diffs' => 0],
        'categories' => ['dedup_verify_diffs' => 0],
        'products' => [
            'dedup_verify_diffs' => 0, 'carried_without_override' => 0, 'mobile_cart_items_without_ref' => 0,
            'field_overrides_dedup_verify_diffs' => 0, 'orphan_variants' => 0,
        ],
        'pages' => ['dedup_verify_diffs' => 0],
        'languages' => ['dedup_verify_diffs' => 0],
        'currencies' => ['dedup_verify_diffs' => 0],
        'settings' => ['dedup_verify_diffs' => 0],
        'payment_gateways' => ['dedup_verify_diffs' => 0],
        'themes' => ['dedup_verify_diffs' => 0],
        'email_templates' => ['dedup_verify_diffs' => 0],
    ];

    private const REF_SECTIONS = ['badges', 'labels', 'coupons', 'flash_sales', 'categories', 'products'];

    /** Phase 6 decision: the tenant row is the identity / override row of the live overlay → nothing is a copy. */
    private const IDENTITY_SECTIONS = ['languages', 'currencies', 'settings', 'payment_gateways', 'themes', 'email_templates'];

    public static function sections(): array
    {
        return self::ORDER;
    }

    /**
     * Row specs: parent copy table (+ copy predicate, link column, translation morph and the re-keyed morph type of
     * tenant-only-language rows), copy children deleted with it, and non-FK references that keep a copy.
     */
    private static function spec(string $section): ?array
    {
        return match ($section) {
            'badges' => [
                'parent' => ['table' => 'product_badges', 'link' => 'central_product_badge_id', 'copy' => '`central_product_badge_id` IS NOT NULL'],
                'children' => [['table' => 'product_badge_product', 'fk' => 'product_badge_id']],
            ],
            'labels' => [
                'parent' => ['table' => 'product_labels', 'link' => 'central_product_label_id', 'copy' => '`central_product_label_id` IS NOT NULL'],
                'children' => [['table' => 'product_label_product', 'fk' => 'product_label_id']],
            ],
            'coupons' => [
                'parent' => ['table' => 'coupons', 'link' => 'central_coupon_id', 'copy' => '`central_coupon_id` IS NOT NULL',
                    'morph' => (new \App\Models\Tenant\Coupon())->getMorphClass()],
                'children' => [['table' => 'coupon_country', 'fk' => 'coupon_id']],
                // Legacy order → coupon link (Order::coupon(), appliedCouponCode() fallback): the order keeps its coupon row.
                'blockers' => [['tenant', 'orders', 'discount_id']],
            ],
            'flash_sales' => [
                'parent' => ['table' => 'flash_sales', 'link' => 'central_flash_sale_id', 'copy' => '`central_flash_sale_id` IS NOT NULL'],
                'children' => [['table' => 'flash_sale_product', 'fk' => 'flash_sale_id'], ['table' => 'flash_sale_country', 'fk' => 'flash_sale_id']],
            ],
            'categories' => [
                'parent' => ['table' => 'categories', 'link' => 'central_category_id', 'copy' => '`central_category_id` IS NOT NULL',
                    'morph' => (new \App\Models\Tenant\Category())->getMorphClass(), 'rekeyed' => CategoryCatalog::CENTRAL_TRANSLATION_TYPE],
                // Every pivot row of a copy category is a copy: vendor links were converted by `catalog:dedup-migrate categories` (verify = 0).
                'children' => [['table' => 'category_product', 'fk' => 'category_id']],
            ],
            'products' => [
                'parent' => ['table' => 'products', 'link' => 'central_product_id', 'copy' => '`central_product_id` IS NOT NULL', 'not_own' => true,
                    'morph' => (new \App\Models\Tenant\Product())->getMorphClass(), 'rekeyed' => ProductCatalog::CENTRAL_TRANSLATION_TYPE],
                'children' => [['table' => 'product_variants', 'fk' => 'product_id', 'copy' => '`central_product_variant_id` IS NOT NULL',
                    'morph' => (new \App\Models\Tenant\ProductVariant())->getMorphClass()]],
                'blockers' => [
                    ['tenant', 'order_items', 'product_id'],
                    ['central', 'return_requests', 'product_id'],
                    ['central', 'product_edit_requests', 'product_id'],
                ],
            ],
            default => null,
        };
    }

    // ── public API ───────────────────────────────────────────────────────────────────────────────────────────────

    /**
     * @param  array{dry_run?: bool, backup?: bool}  $options
     * @return array<string, mixed> report (status, counts, kept, backup, report_path, error)
     */
    public function cleanup(Tenant $tenant, string $section, array $options = []): array
    {
        $this->assertSection($section);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $step = $dryRun ? self::STEP_DRY : self::STEP;
        $tenantId = (string) $tenant->getTenantKey();
        $report = ['tenant' => $tenantId, 'section' => $section, 'step' => $step, 'started_at' => now()->toIso8601String()];
        $run = $this->startRun($tenantId, $section, $step);

        $lock = Cache::lock('catalog-dedup-cleanup:' . $tenantId, 3600);
        if (! $lock->get()) {
            return $this->finish($run, $report + ['status' => CatalogDedupRun::FAILED, 'error' => 'another cleanup / restore of this tenant is running']);
        }

        try {
            if (! $dryRun && ! ($options['backup'] ?? false)) {
                return $this->finish($run, $report + ['status' => CatalogDedupRun::FAILED, 'error' => 'refused: pass --backup (a backup is written and verified before anything is deleted)']);
            }

            $failures = $this->gateFailures($tenant, $section);
            if ($failures !== []) {
                return $this->finish($run, $report + ['status' => CatalogDedupRun::FAILED, 'gate_failures' => $failures, 'error' => 'refused: ' . implode('; ', $failures)]);
            }

            // Resolved BEFORE tenancy: storage_path() is tenant-suffixed inside a tenant, backups live in central storage.
            $backupDir = self::backupRoot($tenantId, $section) . '/' . now()->format('Ymd_His') . '_' . bin2hex(random_bytes(3));

            tenancy()->initialize($tenant);
            try {
                $db = DB::connection('tenant');
                $plan = $this->plan($db, $tenant, $section);
                $report['counts'] = $plan['counts'];
                $report['kept'] = $plan['kept'];
                $report['kept_examples'] = $plan['kept_examples'];
                $report['note'] = $plan['note'] ?? null;

                if (! $dryRun) {
                    $rows = $this->collectRows($db, $plan);
                    $report['backup'] = $this->writeBackup($tenant, $section, $plan, $rows, $backupDir);
                    $report['counts']['deleted'] = $this->delete($db, $tenant, $section, $plan, $rows);
                }
            } finally {
                tenancy()->end();
            }

            return $this->finish($run, $report + ['status' => CatalogDedupRun::DONE]);
        } catch (Throwable $e) {
            return $this->finish($run, $report + ['status' => CatalogDedupRun::FAILED, 'error' => $e->getMessage()]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Re-inserts one backup (the dir name under storage/app/dedup-backups/<tenant>/<section>/, or an absolute path).
     * Refuses on a checksum mismatch, a missing table / column (e.g. after --drop-schema: undo it first) or a primary
     * key that exists again. Does not change any flag; to return to `sync` afterwards flip the flag and run the sync.
     */
    public function restore(Tenant $tenant, string $section, string $backup): array
    {
        $this->assertSection($section);
        $tenantId = (string) $tenant->getTenantKey();
        $report = ['tenant' => $tenantId, 'section' => $section, 'step' => self::STEP_RESTORE, 'started_at' => now()->toIso8601String()];
        $run = $this->startRun($tenantId, $section, self::STEP_RESTORE);

        $lock = Cache::lock('catalog-dedup-cleanup:' . $tenantId, 3600);
        if (! $lock->get()) {
            return $this->finish($run, $report + ['status' => CatalogDedupRun::FAILED, 'error' => 'another cleanup / restore of this tenant is running']);
        }

        try {
            $dir = $this->resolveBackup($tenantId, $section, $backup);
            $report['backup'] = $dir;
            [$manifest, $tables] = $this->readBackup($dir);
            if (($manifest['tenant'] ?? null) !== $tenantId || ($manifest['section'] ?? null) !== $section) {
                throw new RuntimeException('backup belongs to tenant [' . ($manifest['tenant'] ?? '?') . '] section [' . ($manifest['section'] ?? '?') . ']');
            }

            tenancy()->initialize($tenant);
            try {
                $db = DB::connection('tenant');
                $schema = Schema::connection('tenant');
                foreach ($tables as [$table, $rows]) {
                    if ($rows === []) {
                        continue;
                    }
                    if (! $schema->hasTable($table)) {
                        throw new RuntimeException("table [{$table}] is missing");
                    }
                    $missing = array_diff(array_keys($rows[0]), $schema->getColumnListing($table));
                    if ($missing !== []) {
                        throw new RuntimeException("table [{$table}] lacks column(s) " . implode(', ', $missing) . ' (undo --drop-schema first)');
                    }
                    if (array_key_exists('id', $rows[0])) {
                        $ids = array_map(fn ($r) => (int) $r['id'], $rows);
                        $existing = $this->pluck($db, fn (string $in) => ["SELECT `id` AS v FROM `{$table}` WHERE `id` IN ({$in})", []], $ids);
                        if ($existing !== []) {
                            throw new RuntimeException("table [{$table}] already has id(s) " . implode(', ', array_slice($existing, 0, 10)) . ' (already restored?)');
                        }
                    }
                }

                $inserted = [];
                $db->transaction(function () use ($db, $tables, &$inserted) {
                    foreach ($tables as [$table, $rows]) {
                        foreach (array_chunk($rows, 200) as $chunk) {
                            $db->table($table)->insert($chunk);
                        }
                        $inserted[$table] = ($inserted[$table] ?? 0) + count($rows);
                    }
                });
                $report['counts'] = ['inserted' => $inserted];
            } finally {
                tenancy()->end();
            }

            return $this->finish($run, $report + ['status' => CatalogDedupRun::DONE]);
        } catch (Throwable $e) {
            return $this->finish($run, $report + ['status' => CatalogDedupRun::FAILED, 'error' => $e->getMessage()]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Gate failures for a non-dry or dry cleanup (empty = may run). Runs OUTSIDE tenancy (sync-check reads through the
     * central connection in a READ ONLY session).
     *
     * @return list<string>
     */
    public function gateFailures(Tenant $tenant, string $section): array
    {
        $this->assertSection($section);
        $failures = [];
        $central = DB::connection(config('tenancy.database.central_connection'));
        if ($central->getDriverName() !== 'mysql') {
            return ['MySQL is required (information_schema reference check)'];
        }
        if ($central->selectOne('SELECT 1 AS ok FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$tenant->database()->getName()]) === null) {
            return ['tenant database does not exist'];
        }

        $flag = self::FLAGS[$section];
        if (! CatalogSource::isCentral($tenant, $flag)) {
            $failures[] = "catalog_source.{$flag} is not central";
        }
        if ($section === 'products') {
            foreach (self::PRODUCT_PREREQUISITES as $required) {
                if (! CatalogSource::isCentral($tenant, self::FLAGS[$required])) {
                    $failures[] = 'catalog_source.' . self::FLAGS[$required] . ' is not central (required before products)';
                } elseif (! self::isCleaned($tenant, $required)) {
                    $failures[] = "section {$required} is not cleaned yet (run catalog:dedup-cleanup {$required} first)";
                }
            }
        }

        $verify = $this->lastRun($tenant, $section, 'verify');
        $migrate = $this->lastRun($tenant, $section, 'migrate');
        if ($verify === null) {
            $failures[] = "no catalog:dedup-migrate {$section} --verify run recorded";
        } elseif ($verify->status !== CatalogDedupRun::DONE || $verify->finished_at === null) {
            $failures[] = "latest verify run #{$verify->id} is {$verify->status}";
        } elseif ((int) (($verify->counts ?? [])['diffs'] ?? -1) !== 0) {
            $failures[] = "latest verify run #{$verify->id} has " . (int) (($verify->counts ?? [])['diffs'] ?? -1) . ' diff(s)';
        } elseif ($migrate !== null && $migrate->id > $verify->id) {
            $failures[] = "migrate run #{$migrate->id} is newer than the latest verify run #{$verify->id} (verify again)";
        }

        $gates = self::GATES[$section];
        $sections = [$flag];
        if (in_array($section, self::REF_SECTIONS, true)) {
            $gates['null_refs'] = 0;
            $sections[] = CatalogSyncCheck::REFS;
        }
        try {
            $report = app(CatalogSyncCheck::class)->run([(string) $tenant->getTenantKey()], $sections);
            if ($report === []) {
                $failures[] = 'sync-check returned no report for the tenant';
            }
            foreach (CatalogSyncCheck::evaluateGates($report, $gates, true) as $failure) {
                $failures[] = 'sync-check ' . $failure;
            }
            foreach ($report[0]['sections'] ?? [] as $name => $metrics) {
                if (isset($metrics['error'])) {
                    $failures[] = "sync-check {$name}: {$metrics['error']}";
                }
            }
        } catch (Throwable $e) {
            $failures[] = 'sync-check failed: ' . $e->getMessage();
        }

        return $failures;
    }

    /** true when the latest DONE cleanup / restore run of this tenant + section is a cleanup (restore undoes it). */
    public static function isCleaned(Tenant $tenant, string $section): bool
    {
        $last = CatalogDedupRun::query()
            ->where('tenant_id', (string) $tenant->getTenantKey())->where('section', $section)
            ->whereIn('step', [self::STEP, self::STEP_RESTORE])->where('status', CatalogDedupRun::DONE)
            ->orderByDesc('id')->first();

        return $last !== null && $last->step === self::STEP;
    }

    public static function backupRoot(string $tenantId, string $section): string
    {
        return storage_path(trim((string) config('catalog_dedup.backup_path', 'app/dedup-backups'), '/')
            . '/' . self::safe($tenantId) . '/' . self::safe($section));
    }

    // ── plan ─────────────────────────────────────────────────────────────────────────────────────────────────────

    /**
     * @return array{counts: array<string, mixed>, kept: array<string, int>, kept_examples: array<int, string>,
     *               parents: list<int>, spec: ?array, translation_ids: list<int>, note?: string}
     */
    private function plan(ConnectionInterface $db, Tenant $tenant, string $section): array
    {
        $empty = ['counts' => [], 'kept' => [], 'kept_examples' => [], 'parents' => [], 'spec' => null, 'translation_ids' => []];

        if (in_array($section, self::IDENTITY_SECTIONS, true)) {
            return ['note' => 'identity rows of the Phase 6 live overlay: nothing is a copy, nothing deleted', 'counts' => ['candidates' => 0]] + $empty;
        }
        if ($section === 'pages') {
            $ids = $this->pageTranslationIds($db);

            return ['counts' => ['candidates' => count($ids), 'translations' => count($ids)], 'translation_ids' => $ids] + $empty;
        }

        $spec = self::spec($section);
        $parent = $spec['parent'];
        $schema = Schema::connection('tenant');
        foreach ([$parent['table'], ...array_column($spec['children'], 'table')] as $table) {
            if (! $schema->hasTable($table)) {
                throw new RuntimeException("tenant table [{$table}] is missing");
            }
        }
        if (! $schema->hasColumn($parent['table'], $parent['link'])) {
            throw new RuntimeException("column [{$parent['table']}.{$parent['link']}] is missing (already dropped?)");
        }

        $copy = $parent['copy'];
        if (($parent['not_own'] ?? false) && $schema->hasColumn($parent['table'], 'is_own_product')) {
            $copy .= ' AND COALESCE(`is_own_product`, 0) = 0';
        }
        $candidates = array_map('intval', array_column($db->select("SELECT `id` FROM `{$parent['table']}` WHERE {$copy} ORDER BY `id`"), 'id'));
        $set = array_fill_keys($candidates, true);
        $reasons = [];

        $fks = $this->foreignKeys($db, [$parent['table'], ...array_column($spec['children'], 'table')]);
        $childFk = [];
        foreach ($spec['children'] as $child) {
            $childFk[$child['table'] . '.' . $child['fk']] = true;
        }

        // Fixpoint: removing a candidate can turn a self reference into an outside one.
        do {
            $blocked = [];
            $ids = array_keys($set);
            if ($ids === []) {
                break;
            }
            $block = function (array $found, string $reason) use (&$blocked, $set) {
                foreach ($found as $id) {
                    if (isset($set[$id]) && ! isset($blocked[$id])) {
                        $blocked[$id] = $reason;
                    }
                }
            };

            foreach ($fks as $fk) {
                if ($fk->rt !== $parent['table'] || isset($childFk[$fk->t . '.' . $fk->c])) {
                    continue;
                }
                if ($fk->t === $parent['table']) {
                    $rows = $this->rows($db, fn (string $in) => ["SELECT `id`, `{$fk->c}` AS v FROM `{$fk->t}` WHERE `{$fk->c}` IN ({$in})", []], $ids);
                    $block(array_map(fn ($r) => (int) $r->v, array_filter($rows, fn ($r) => ! isset($set[(int) $r->id]))), "fk:{$fk->t}.{$fk->c}");
                } else {
                    $block($this->pluck($db, fn (string $in) => ["SELECT DISTINCT `{$fk->c}` AS v FROM `{$fk->t}` WHERE `{$fk->c}` IN ({$in})", []], $ids), "fk:{$fk->t}.{$fk->c}");
                }
            }

            foreach ($spec['children'] as $child) {
                if (isset($child['copy'])) {
                    $block($this->pluck($db, fn (string $in) => ["SELECT DISTINCT `{$child['fk']}` AS v FROM `{$child['table']}` WHERE `{$child['fk']}` IN ({$in}) AND NOT ({$child['copy']})", []], $ids), "child_not_copy:{$child['table']}");
                }
                foreach ($fks as $fk) {
                    if ($fk->rt !== $child['table'] || $fk->t === $parent['table'] || isset($childFk[$fk->t . '.' . $fk->c])) {
                        continue;
                    }
                    $block($this->pluck($db, fn (string $in) => ["SELECT DISTINCT ch.`{$child['fk']}` AS v FROM `{$child['table']}` ch JOIN `{$fk->t}` x ON x.`{$fk->c}` = ch.`{$fk->rc}` WHERE ch.`{$child['fk']}` IN ({$in})", []], $ids), "fk:{$fk->t}.{$fk->c}");
                }
                if (isset($child['morph'])) {
                    $block($this->tenantOnlyTranslations($db, $ids, $child['morph'], null, $child), 'tenant_only_language:' . $child['table']);
                }
            }

            if (isset($parent['morph'])) {
                $block($this->tenantOnlyTranslations($db, $ids, $parent['morph'], $parent['rekeyed'] ?? null), 'tenant_only_language:' . $parent['table']);
            }

            foreach ($spec['blockers'] ?? [] as [$where, $table, $column]) {
                $block($this->externalReferences($db, $tenant, $where, $table, $column, $ids), ($where === 'central' ? 'central:' : 'ref:') . "{$table}.{$column}");
            }

            foreach ($blocked as $id => $reason) {
                unset($set[$id]);
                $reasons[$id] = $reason;
            }
        } while ($blocked !== []);

        $parents = array_keys($set);
        sort($parents);
        $kept = array_count_values($reasons);
        ksort($kept);
        $examples = [];
        foreach (array_slice($reasons, 0, 25, true) as $id => $reason) {
            $examples[$id] = $reason;
        }

        $translationIds = [];
        if (isset($parent['morph'])) {
            $translationIds = $this->pluck($db, fn (string $in) => ['SELECT `id` AS v FROM `translations` WHERE `translatable_type` = ? AND `translatable_id` IN (' . $in . ')', [$parent['morph']]], $parents);
        }
        foreach ($spec['children'] as $child) {
            if (isset($child['morph'])) {
                $translationIds = [...$translationIds, ...$this->pluck($db, fn (string $in) => ["SELECT t.`id` AS v FROM `translations` t JOIN `{$child['table']}` ch ON ch.`id` = t.`translatable_id` WHERE t.`translatable_type` = ? AND ch.`{$child['fk']}` IN ({$in})", [$child['morph']]], $parents)];
            }
        }
        sort($translationIds);

        return [
            'counts' => ['candidates' => count($candidates), 'to_delete' => count($parents), 'kept' => count($reasons), 'translations' => count($translationIds)],
            'kept' => $kept,
            'kept_examples' => $examples,
            'parents' => $parents,
            'spec' => $spec + ['copy_sql' => $copy],
            'translation_ids' => $translationIds,
        ];
    }

    /**
     * Copies (alias = parent row) with a translation row in a language central does not have as an ACTIVE language and,
     * when the section re-keys such rows (`central_product` / `central_category`), no re-keyed counterpart with the same
     * language + field. Those texts are the tenant's (AI / manual) and are not read from anywhere else: keep the copy.
     *
     * @return list<int> parent ids
     */
    private function tenantOnlyTranslations(ConnectionInterface $db, array $ids, string $morph, ?string $rekeyed, ?array $child = null): array
    {
        if (! Schema::connection('tenant')->hasTable('translations') || ! Schema::connection('tenant')->hasTable('languages')) {
            return [];
        }
        $cl = CentralTable::name('languages');
        $notCentral = "NOT EXISTS (SELECT 1 FROM {$cl} cl WHERE cl.`code` = l.`code` AND cl.`is_active` = 1)";

        if ($child !== null) {
            return $this->pluck($db, fn (string $in) => [
                "SELECT DISTINCT ch.`{$child['fk']}` AS v FROM `translations` t
                   JOIN `{$child['table']}` ch ON ch.`id` = t.`translatable_id`
                   JOIN `languages` l ON l.`id` = t.`language_id`
                  WHERE t.`translatable_type` = ? AND ch.`{$child['fk']}` IN ({$in}) AND {$notCentral}", [$morph]], $ids);
        }

        $spec = null;
        foreach (['products' => 'central_product_id', 'categories' => 'central_category_id'] as $table => $link) {
            if ($rekeyed !== null && (($rekeyed === ProductCatalog::CENTRAL_TRANSLATION_TYPE && $table === 'products') || ($rekeyed === CategoryCatalog::CENTRAL_TRANSLATION_TYPE && $table === 'categories'))) {
                $spec = [$table, $link];
            }
        }
        $rekeyedSql = $spec === null ? '' : " AND NOT EXISTS (SELECT 1 FROM `translations` x JOIN `{$spec[0]}` p ON p.`id` = t.`translatable_id`
                    WHERE x.`translatable_type` = ? AND x.`translatable_id` = p.`{$spec[1]}` AND x.`language_id` = t.`language_id` AND x.`field` = t.`field`)";

        return $this->pluck($db, fn (string $in) => [
            "SELECT DISTINCT t.`translatable_id` AS v FROM `translations` t JOIN `languages` l ON l.`id` = t.`language_id`
              WHERE t.`translatable_type` = ? AND t.`translatable_id` IN ({$in}) AND {$notCentral}{$rekeyedSql}",
            [$morph], $spec === null ? [] : [$rekeyed]], $ids);
    }

    /** Ids among $ids still referenced by a non-FK column (tenant table, or central table rows of THIS tenant). */
    private function externalReferences(ConnectionInterface $db, Tenant $tenant, string $where, string $table, string $column, array $ids): array
    {
        if ($where === 'tenant') {
            if (! Schema::connection('tenant')->hasColumn($table, $column)) {
                return [];
            }

            return $this->pluck($db, fn (string $in) => ["SELECT DISTINCT `{$column}` AS v FROM `{$table}` WHERE `{$column}` IN ({$in})", []], $ids);
        }

        $central = DB::connection(config('tenancy.database.central_connection'));
        $centralSchema = Schema::connection(config('tenancy.database.central_connection'));
        if (! $centralSchema->hasColumn($table, $column) || ! $centralSchema->hasColumn($table, 'tenant_id')) {
            return [];
        }

        return $this->pluck($central, fn (string $in) => ["SELECT DISTINCT `{$column}` AS v FROM `{$table}` WHERE `{$column}` IN ({$in}) AND `tenant_id` = ?", [], [(string) $tenant->getTenantKey()]], $ids);
    }

    /** Translations of `source = central` pages (central slug live) in a locale the platform manages for that page. */
    private function pageTranslationIds(ConnectionInterface $db): array
    {
        $schema = Schema::connection('tenant');
        if (! $schema->hasColumn('pages', 'source') || ! $schema->hasTable('translations')) {
            throw new RuntimeException('tenant pages.source / translations missing (run tenants:migrate)');
        }
        $sp = CentralTable::name('static_pages');
        $ct = CentralTable::name('translations');
        $cl = CentralTable::name('languages');
        $live = Schema::connection(config('tenancy.database.central_connection'))->hasColumn('static_pages', 'deleted_at') ? ' AND sp.`deleted_at` IS NULL' : '';

        return array_map('intval', array_column($db->select(
            "SELECT t.`id` FROM `translations` t
               JOIN `pages` p ON p.`id` = t.`translatable_id` AND p.`source` = 'central'
               JOIN `languages` l ON l.`id` = t.`language_id`
               JOIN {$sp} sp ON sp.`slug` = p.`slug`{$live}
              WHERE t.`translatable_type` = ?
                AND EXISTS (SELECT 1 FROM {$ct} ct JOIN {$cl} cl ON cl.`id` = ct.`language_id`
                             WHERE ct.`translatable_type` = ? AND ct.`translatable_id` = sp.`id` AND cl.`code` = l.`code`
                               AND ct.`field` IN ('title', 'content', 'body') AND ct.`value` IS NOT NULL AND ct.`value` <> '')
              ORDER BY t.`id`",
            [(new \App\Models\Tenant\Page())->getMorphClass(), (new \App\Models\StaticPage())->getMorphClass()],
        ), 'id'));
    }

    /** @return list<object{t: string, c: string, rt: string, rc: string, rule: string}> */
    private function foreignKeys(ConnectionInterface $db, array $tables): array
    {
        $in = implode(',', array_fill(0, count($tables), '?'));

        return $db->select(
            "SELECT k.`TABLE_NAME` AS t, k.`COLUMN_NAME` AS c, k.`REFERENCED_TABLE_NAME` AS rt, k.`REFERENCED_COLUMN_NAME` AS rc, r.`DELETE_RULE` AS `rule`
               FROM information_schema.KEY_COLUMN_USAGE k
               JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.`CONSTRAINT_SCHEMA` = k.`CONSTRAINT_SCHEMA` AND r.`CONSTRAINT_NAME` = k.`CONSTRAINT_NAME`
              WHERE k.`TABLE_SCHEMA` = ? AND k.`REFERENCED_TABLE_NAME` IN ({$in})",
            [$db->getDatabaseName(), ...$tables],
        );
    }

    // ── backup / delete ──────────────────────────────────────────────────────────────────────────────────────────

    /** @return list<array{0: string, 1: list<array<string, mixed>>}> ordered for restore (parents first) */
    private function collectRows(ConnectionInterface $db, array $plan): array
    {
        $out = [];
        if ($plan['spec'] !== null) {
            $parent = $plan['spec']['parent'];
            $out[] = [$parent['table'], $this->fetch($db, "SELECT * FROM `{$parent['table']}` WHERE `id` IN (%s) ORDER BY `id`", $plan['parents'])];
            foreach ($plan['spec']['children'] as $child) {
                $out[] = [$child['table'], $this->fetch($db, "SELECT * FROM `{$child['table']}` WHERE `{$child['fk']}` IN (%s)", $plan['parents'])];
            }
        }
        $out[] = ['translations', $this->fetch($db, 'SELECT * FROM `translations` WHERE `id` IN (%s) ORDER BY `id`', $plan['translation_ids'])];

        return $out;
    }

    private function writeBackup(Tenant $tenant, string $section, array $plan, array $tables, string $dir): string
    {
        $tenantId = (string) $tenant->getTenantKey();
        File::ensureDirectoryExists($dir);

        $payload = json_encode(['tables' => $tables], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        File::put($dir . '/rows.json', $payload);
        $counts = [];
        foreach ($tables as [$table, $rows]) {
            $counts[$table] = ($counts[$table] ?? 0) + count($rows);
        }
        $manifest = [
            'tenant' => $tenantId,
            'section' => $section,
            'database' => $tenant->database()->getName(),
            'created_at' => now()->toIso8601String(),
            'format' => 'json-rows-v1',
            'order' => array_column($tables, 0),
            'counts' => $counts,
            'parents' => $plan['parents'],
            'sha256' => hash('sha256', $payload),
            'restore' => "php artisan catalog:dedup-cleanup {$section} --tenant={$tenantId} --restore=" . basename($dir),
        ];
        File::put($dir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        // Read back: the delete only proceeds when the file on disk is complete and parseable.
        [$readManifest, $readTables] = $this->readBackup($dir);
        $readCounts = [];
        foreach ($readTables as [$table, $rows]) {
            $readCounts[$table] = ($readCounts[$table] ?? 0) + count($rows);
        }
        if ($readCounts !== $counts || $readManifest['sha256'] !== $manifest['sha256']) {
            throw new RuntimeException("backup read-back mismatch in {$dir}; nothing deleted");
        }

        return $dir;
    }

    /** @return array{0: array<string, mixed>, 1: list<array{0: string, 1: list<array<string, mixed>>}>} */
    private function readBackup(string $dir): array
    {
        $manifest = json_decode((string) @file_get_contents($dir . '/manifest.json'), true);
        $payload = (string) @file_get_contents($dir . '/rows.json');
        if (! is_array($manifest) || $payload === '') {
            throw new RuntimeException("backup [{$dir}] is missing or unreadable");
        }
        if (! hash_equals((string) ($manifest['sha256'] ?? ''), hash('sha256', $payload))) {
            throw new RuntimeException("backup [{$dir}] checksum mismatch");
        }
        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return [$manifest, array_map(fn ($t) => [(string) $t[0], (array) $t[1]], (array) ($data['tables'] ?? []))];
    }

    private function resolveBackup(string $tenantId, string $section, string $backup): string
    {
        $candidates = [$backup, self::backupRoot($tenantId, $section) . '/' . basename($backup)];
        foreach ($candidates as $dir) {
            $dir = rtrim($dir, '/');
            if (str_ends_with($dir, '/manifest.json')) {
                $dir = dirname($dir);
            }
            if (is_file($dir . '/manifest.json')) {
                return $dir;
            }
        }

        throw new RuntimeException("backup [{$backup}] not found under " . self::backupRoot($tenantId, $section));
    }

    /** @return array<string, int> deleted rows per table */
    private function delete(ConnectionInterface $db, Tenant $tenant, string $section, array $plan, array $tables): array
    {
        $expected = [];
        foreach ($tables as [$table, $rows]) {
            $expected[$table] = ($expected[$table] ?? 0) + count($rows);
        }

        $deleted = [];
        $db->transaction(function () use ($db, $tenant, $section, $plan, $expected, &$deleted) {
            $spec = $plan['spec'];
            if ($spec !== null && $plan['parents'] !== []) {
                // Lock the copies: a concurrent insert referencing one (FK) waits and then fails instead of being cascaded away.
                $this->rows($db, fn (string $in) => ["SELECT `id` FROM `{$spec['parent']['table']}` WHERE `id` IN ({$in}) FOR UPDATE", []], $plan['parents']);
            }
            $again = $this->plan($db, $tenant, $section);
            if ($again['parents'] !== $plan['parents'] || $again['translation_ids'] !== $plan['translation_ids']) {
                throw new RuntimeException('references changed since the backup was written; nothing deleted, re-run the cleanup');
            }

            $deleted['translations'] = $this->exec($db, 'DELETE FROM `translations` WHERE `id` IN (%s)', $plan['translation_ids']);
            if ($spec !== null) {
                foreach ($spec['children'] as $child) {
                    $deleted[$child['table']] = $this->exec($db, "DELETE FROM `{$child['table']}` WHERE `{$child['fk']}` IN (%s)", $plan['parents']);
                }
                $parent = $spec['parent'];
                $deleted[$parent['table']] = $this->exec($db, "DELETE FROM `{$parent['table']}` WHERE `id` IN (%s) AND {$spec['copy_sql']}", $plan['parents']);
            }

            foreach ($deleted as $table => $count) {
                if ($count !== ($expected[$table] ?? 0)) {
                    throw new RuntimeException("deleted {$count} row(s) from [{$table}] but the backup holds " . ($expected[$table] ?? 0) . '; rolled back');
                }
            }
        });

        return $deleted;
    }

    // ── helpers ──────────────────────────────────────────────────────────────────────────────────────────────────

    /**
     * Runs a chunked `IN (…)` query. $build receives the `?, ?, …` list of one chunk and returns
     * [sql, bindings before the IN list, bindings after it].
     *
     * @param  callable(string): array  $build
     */
    private function rows(ConnectionInterface $db, callable $build, array $ids): array
    {
        $out = [];
        foreach (array_chunk(array_values($ids), 1000) as $chunk) {
            $built = $build(implode(',', array_fill(0, count($chunk), '?')));
            [$sql, $before, $after] = [$built[0], $built[1] ?? [], $built[2] ?? []];
            foreach ($db->select($sql, [...$before, ...$chunk, ...$after]) as $row) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /** Column `v` of rows(), as unique ints. */
    private function pluck(ConnectionInterface $db, callable $build, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return array_values(array_unique(array_map(fn ($r) => (int) $r->v, $this->rows($db, $build, $ids))));
    }

    /** @return list<array<string, mixed>> */
    private function fetch(ConnectionInterface $db, string $sql, array $ids): array
    {
        $out = [];
        foreach (array_chunk(array_values($ids), 1000) as $chunk) {
            foreach ($db->select(sprintf($sql, implode(',', array_fill(0, count($chunk), '?'))), $chunk) as $row) {
                $out[] = (array) $row;
            }
        }

        return $out;
    }

    private function exec(ConnectionInterface $db, string $sql, array $ids): int
    {
        $count = 0;
        foreach (array_chunk(array_values($ids), 1000) as $chunk) {
            $count += $db->delete(sprintf($sql, implode(',', array_fill(0, count($chunk), '?'))), $chunk);
        }

        return $count;
    }

    private function lastRun(Tenant $tenant, string $section, string $step): ?CatalogDedupRun
    {
        return CatalogDedupRun::query()->where('tenant_id', (string) $tenant->getTenantKey())
            ->where('section', $section)->where('step', $step)->orderByDesc('id')->first();
    }

    private function startRun(string $tenantId, string $section, string $step): CatalogDedupRun
    {
        return CatalogDedupRun::query()->create([
            'tenant_id' => $tenantId, 'section' => $section, 'step' => $step,
            'status' => CatalogDedupRun::RUNNING, 'started_at' => now(),
        ]);
    }

    private function finish(CatalogDedupRun $run, array $report): array
    {
        $report['finished_at'] = now()->toIso8601String();
        $counts = (array) ($report['counts'] ?? []);
        if (isset($report['kept']) && $report['kept'] !== []) {
            $counts['kept_by_reason'] = $report['kept'];
        }
        if (isset($report['backup'])) {
            $counts['backup'] = $report['backup'];
        }
        $run->forceFill([
            'status' => $report['status'],
            'counts' => $counts,
            'error' => isset($report['error']) ? mb_substr((string) $report['error'], 0, 60000) : null,
            'finished_at' => now(),
        ])->save();
        $report['run_id'] = $run->id;

        try {
            $path = DedupRunner::reportPath($report['section'], $report['tenant'], $report['step']);
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
            $report['report_path'] = $path;
        } catch (Throwable) {
            $report['report_path'] = null;
        }

        return $report;
    }

    private function assertSection(string $section): void
    {
        if (! in_array($section, self::ORDER, true)) {
            throw new InvalidArgumentException("Unknown cleanup section [{$section}]. Valid: " . implode(', ', self::ORDER));
        }
    }

    private static function safe(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '_', $value);
    }
}
