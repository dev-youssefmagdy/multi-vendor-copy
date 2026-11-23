<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\OrderStatus;
use App\Models\CatalogDedupRun;
use App\Models\CentralCoupon;
use App\Models\Currency as CentralCurrency;
use App\Models\Language as CentralLanguage;
use App\Models\ProductBadge as CentralBadge;
use App\Models\ProductLabel as CentralLabel;
use App\Models\StaticPage as CentralStaticPage;
use App\Models\Tenant\Category;
use App\Models\Tenant\Language;
use App\Models\Tenant\Page;
use App\Models\Tenant\ProductBadge;
use App\Repositories\Tenant\Catalog\BadgeCatalog;
use App\Repositories\Tenant\Catalog\ConfigCatalog;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use App\Services\Catalog\CatalogSyncCheck;
use App\Services\Catalog\Dedup\DedupCleanup;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\Catalog\Dedup\DedupSchemaDrops;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan phase 7: `catalog:dedup-cleanup` gates (flag, latest verify, sync-check, --backup), what is deleted (only
 * central-linked copies + their copy pivots / translations) and what is kept (anything still referenced by tenant or
 * order data), the JSON backup + `--restore`, config sections deleting nothing, pages deleting only platform-locale
 * texts, and the gated `--drop-schema` path.
 */
class CatalogPhase7CleanupTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        CatalogRefColumns::flush();
        ConfigCatalog::flushMemo();
        $this->setUpTenantPanel();
    }

    protected function tearDown(): void
    {
        if (isset($this->tenant)) {
            tenancy()->end();
            $id = (string) $this->tenant->id;
            if (Schema::hasTable('catalog_dedup_runs')) {
                DB::table('catalog_dedup_runs')->where('tenant_id', $id)->delete();
            }
            foreach (DedupCleanup::sections() as $section) {
                foreach (['migrate', 'dry-run', 'verify', 'cleanup', 'cleanup-dry-run', 'restore', 'schema-drop', 'schema-undrop'] as $step) {
                    File::delete(DedupRunner::reportPath($section, $id, $step));
                }
                File::deleteDirectory(DedupCleanup::backupRoot($id, $section));
            }
            File::deleteDirectory(dirname(DedupCleanup::backupRoot($id, 'x')));
            foreach (['field_overrides'] as $section) {
                foreach (['migrate', 'dry-run', 'verify'] as $step) {
                    File::delete(DedupRunner::reportPath($section, $id, $step));
                }
            }
        }
        ConfigCatalog::flushMemo();
        CatalogRefColumns::flush();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    // ── helpers ──────────────────────────────────────────────────────────────────────────────────────────────────

    private function inTenant(callable $callback): mixed
    {
        ConfigCatalog::flushMemo();

        return $this->tenant->run($callback);
    }

    private function central(callable $callback): mixed
    {
        tenancy()->end();
        $result = Model::withoutEvents($callback);
        CacheVersion::store()->flush();
        ConfigCatalog::flushMemo();

        return $result;
    }

    private function flip(string $section, string $mode = CatalogSource::CENTRAL): void
    {
        tenancy()->end();
        CatalogSource::set($this->tenant, $section, $mode);
        CacheVersion::flushMemo();
        ConfigCatalog::flushMemo();
    }

    private function sync(string $section): void
    {
        tenancy()->end();
        try {
            app(TenantCatalogSyncService::class)->syncSection($this->tenant, $section);
        } finally {
            tenancy()->end();
        }
        ConfigCatalog::flushMemo();
    }

    private function dedup(string $section, array $options = []): void
    {
        tenancy()->end();
        $step = isset($options['--verify']) ? 'verify' : 'migrate';
        $report = app(DedupRunner::class)->run($this->tenant, $section, $step);
        tenancy()->end();
        $this->assertSame('done', $report['status'], json_encode($report));
        $this->assertSame(0, (int) ($report['diffs'] ?? 0), json_encode($report));
    }

    /** migrate + verify (0 diffs) + flip: the state a section must reach before its cleanup. */
    private function prepare(string $section): void
    {
        $this->dedup($section);
        $this->dedup($section, ['--verify' => true]);
        $this->flip(DedupCleanup::FLAGS[$section]);
    }

    private function backfillRefs(): void
    {
        tenancy()->end();
        $this->artisan('catalog:backfill-refs', ['--tenant' => [$this->tenant->id]])->assertSuccessful();
    }

    private function cleanup(string $section, array $options = ['--backup' => true]): \Illuminate\Testing\PendingCommand
    {
        tenancy()->end();

        return $this->artisan('catalog:dedup-cleanup', ['section' => $section, '--tenant' => [$this->tenant->id]] + $options);
    }

    private function report(string $section, string $step = 'cleanup'): array
    {
        return json_decode(File::get(DedupRunner::reportPath($section, (string) $this->tenant->id, $step)), true);
    }

    private function table(string $table): array
    {
        return $this->inTenant(fn () => DB::connection('tenant')->table($table)->get()->map(fn ($r) => (array) $r)->sortBy(fn ($r) => json_encode($r))->values()->all());
    }

    private function centralBadge(string $text, array $pairs): CentralBadge
    {
        return $this->central(function () use ($text, $pairs) {
            $badge = CentralBadge::query()->create(['text' => $text, 'active' => true]);
            foreach ($pairs as $centralProductId => $sort) {
                DB::table('product_badge_product')->insert(['product_badge_id' => $badge->id, 'product_id' => $centralProductId, 'country_id' => null, 'sort_order' => $sort]);
            }

            return $badge;
        });
    }

    private function syncCheck(string $section): array
    {
        tenancy()->end();

        return app(CatalogSyncCheck::class)->run([(string) $this->tenant->id], [$section])[0]['sections'][$section];
    }

    // ── gates ────────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_cleanup_refuses_until_central_verified_gated_and_backed_up(): void
    {
        $a = $this->inTenant(fn () => $this->createCentralCatalogVariant());
        $this->centralBadge('p7-gate', [$a['central_product']->id => 0]);
        $this->sync('badges');
        $this->backfillRefs();
        $copies = fn () => $this->inTenant(fn () => ProductBadge::query()->whereNotNull('central_product_badge_id')->count());
        $n = $copies(); // + the central badges the migrations seed
        $this->assertGreaterThanOrEqual(1, $n);

        // 1. sync mode.
        $this->cleanup('badges')->assertFailed();
        $this->assertStringContainsString('is not central', $this->report('badges')['error']);

        // 2. central but never verified.
        $this->flip('badges');
        $this->cleanup('badges')->assertFailed();
        $this->assertStringContainsString('--verify run recorded', $this->report('badges')['error']);

        // 3. verified with diffs (a recorded verify run with 1 diff is the latest).
        $this->flip('badges', CatalogSource::SYNC);
        $this->dedup('badges');
        $this->dedup('badges', ['--verify' => true]);
        CatalogDedupRun::query()->create(['tenant_id' => $this->tenant->id, 'section' => 'badges', 'step' => 'verify', 'status' => 'done', 'counts' => ['diffs' => 1], 'started_at' => now(), 'finished_at' => now()]);
        $this->flip('badges');
        $this->cleanup('badges')->assertFailed();
        $this->assertStringContainsString('has 1 diff(s)', $this->report('badges')['error']);

        // 4. a migrate newer than the verify.
        $this->dedup('badges', ['--verify' => true]);
        CatalogDedupRun::query()->create(['tenant_id' => $this->tenant->id, 'section' => 'badges', 'step' => 'migrate', 'status' => 'done', 'counts' => [], 'started_at' => now(), 'finished_at' => now()]);
        $this->cleanup('badges')->assertFailed();
        $this->assertStringContainsString('is newer than the latest verify run', $this->report('badges')['error']);
        $this->dedup('badges', ['--verify' => true]);

        // 5. a sync-check gate (null typed refs) fails closed.
        $this->inTenant(fn () => DB::connection('tenant')->table('product_badge_product')->update(['product_type' => null, 'catalog_product_id' => null]));
        $this->cleanup('badges')->assertFailed();
        $this->assertStringContainsString('null_refs', $this->report('badges')['error']);
        $this->backfillRefs();

        // 6. without --backup.
        $this->cleanup('badges', [])->assertFailed();
        $this->assertStringContainsString('--backup', $this->report('badges')['error']);

        // 7. --tenant is mandatory for a real cleanup.
        $this->artisan('catalog:dedup-cleanup', ['section' => 'badges', '--backup' => true])->assertExitCode(2);
        $this->artisan('catalog:dedup-cleanup', ['section' => 'field_overrides', '--tenant' => [$this->tenant->id]])->assertExitCode(2);

        $this->assertSame($n, $copies(), 'every refusal deleted nothing');
        $this->assertSame(0, CatalogDedupRun::query()->where('tenant_id', $this->tenant->id)->where('step', 'cleanup')->where('status', 'done')->count());

        // Dry run passes the gates and changes nothing.
        $this->cleanup('badges', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(['candidates' => $n, 'to_delete' => $n, 'kept' => 0, 'translations' => 0], $this->report('badges', 'cleanup-dry-run')['counts']);
        $this->assertSame($n, $copies());
        $this->assertDirectoryDoesNotExist(DedupCleanup::backupRoot((string) $this->tenant->id, 'badges'));
    }

    // ── badges + labels: delete copies, keep tenant data, restore ──────────────────────────────────────────────────

    public function test_badges_cleanup_deletes_only_copies_keeps_tenant_rows_and_restores(): void
    {
        $p = $this->inTenant(fn () => ['a' => $this->createCentralCatalogVariant(), 'b' => $this->createCentralCatalogVariant(), 'own' => $this->createOwnProductVariant()]);
        $a = $p['a']['central_product']->id;
        $b = $p['b']['central_product']->id;
        $badge = $this->centralBadge('p7-badge', [$a => 0, $b => 1]);
        $this->sync('badges');

        // Tenant data: a vendor reorder + attach on the copy (converted by migrate), an own badge on an own product.
        $ownBadgeId = $this->inTenant(function () use ($badge, $p) {
            $copy = ProductBadge::query()->where('central_product_badge_id', $badge->id)->firstOrFail();
            DB::table('product_badge_product')->where('product_badge_id', $copy->id)->where('product_id', $p['a']['product']->id)->update(['sort_order' => 7]);
            DB::table('product_badge_product')->insert(['product_badge_id' => $copy->id, 'product_id' => $p['own']->product_id, 'country_id' => null, 'sort_order' => 3]);
            $own = ProductBadge::query()->create(['text' => 'p7-own-badge', 'active' => true]);
            DB::table('product_badge_product')->insert(['product_badge_id' => $own->id, 'product_id' => $p['own']->product_id, 'country_id' => null, 'sort_order' => 0]);

            return $own->id;
        });
        $this->backfillRefs();
        $this->prepare('badges');

        $membership = fn () => $this->inTenant(fn () => app(BadgeCatalog::class)->productIds(app(BadgeCatalog::class)->find('c:' . $badge->id), null));
        $before = $membership();
        $overridesBefore = $this->table(BadgeCatalog::OVERRIDES);
        [$copyBadges, $copyPivot] = $this->inTenant(fn () => [
            ProductBadge::query()->whereNotNull('central_product_badge_id')->count(),
            DB::connection('tenant')->table('product_badge_product')->whereIn('product_badge_id', ProductBadge::query()->whereNotNull('central_product_badge_id')->pluck('id'))->count(),
        ]);
        $this->assertGreaterThanOrEqual(3, $copyPivot);

        $this->cleanup('badges')->assertSuccessful();
        $report = $this->report('badges');
        $this->assertSame('done', $report['status']);
        $this->assertSame(['translations' => 0, 'product_badge_product' => $copyPivot, 'product_badges' => $copyBadges], $report['counts']['deleted']);

        $this->inTenant(function () use ($ownBadgeId) {
            $this->assertSame(0, ProductBadge::query()->whereNotNull('central_product_badge_id')->count(), 'copy gone');
            $this->assertNotNull(ProductBadge::query()->find($ownBadgeId), 'own badge kept');
            $this->assertSame(1, DB::connection('tenant')->table('product_badge_product')->where('product_badge_id', $ownBadgeId)->count());
            $this->assertSame(1, DB::connection('tenant')->table('product_badge_product')->whereNull('product_badge_id')->count(), 'typed attach row kept');
        });
        $this->assertSame($overridesBefore, $this->table(BadgeCatalog::OVERRIDES), 'override rows untouched');
        $this->assertSame($before, $membership(), 'merged read identical after the cleanup');
        $this->assertSame('cleaned', $this->syncCheck('badges')['dedup_cleanup']);

        // Backup on disk, then restore → the copies are back byte for byte; a second restore is refused.
        $dir = $report['backup'];
        $this->assertFileExists($dir . '/rows.json');
        $manifest = json_decode(File::get($dir . '/manifest.json'), true);
        $this->assertSame(['product_badges' => $copyBadges, 'product_badge_product' => $copyPivot, 'translations' => 0], $manifest['counts']);

        $pivotAfterCleanup = $this->table('product_badge_product');
        $this->cleanup('badges', ['--restore' => basename($dir)])->assertSuccessful();
        $this->assertSame(['product_badges' => $copyBadges, 'product_badge_product' => $copyPivot, 'translations' => 0], $this->report('badges', 'restore')['counts']['inserted']);
        $this->assertSame(1, $this->inTenant(fn () => ProductBadge::query()->where('central_product_badge_id', $badge->id)->count()));
        $this->assertCount(count($pivotAfterCleanup) + $copyPivot, $this->table('product_badge_product'));
        $this->assertSame('restored', $this->syncCheck('badges')['dedup_cleanup']);
        $this->assertFalse(DedupCleanup::isCleaned($this->tenant, 'badges'));

        $this->cleanup('badges', ['--restore' => basename($dir)])->assertFailed();
        $this->assertStringContainsString('already has id', $this->report('badges', 'restore')['error']);

        // Tampered backup is refused.
        File::append($dir . '/rows.json', ' ');
        $this->cleanup('badges', ['--restore' => basename($dir)])->assertFailed();
        $this->assertStringContainsString('checksum mismatch', $this->report('badges', 'restore')['error']);
    }

    public function test_labels_cleanup_and_gated_schema_drop_undrop_and_restore(): void
    {
        $a = $this->inTenant(fn () => $this->createCentralCatalogVariant());
        $this->central(function () use ($a) {
            $label = CentralLabel::query()->create(['text' => 'p7-l', 'translations' => ['en' => 'x'], 'active' => true]);
            DB::table('product_label_product')->insert(['product_label_id' => $label->id, 'product_id' => $a['central_product']->id]);
        });
        $this->sync('labels');
        $this->backfillRefs();
        $this->prepare('labels');
        [$copyLabels, $copyLabelPivot] = $this->inTenant(fn () => [DB::connection('tenant')->table('product_labels')->count(), DB::connection('tenant')->table('product_label_product')->count()]);
        $this->assertGreaterThanOrEqual(1, $copyLabelPivot);

        // Schema drop is refused before the cleanup and while the code is not ready.
        $this->cleanup('labels', ['--drop-schema' => true])->assertFailed();
        $this->assertStringContainsString('schema_drops_code_ready', $this->report('labels', 'schema-drop')['error']);
        $this->assertStringContainsString('not cleaned', $this->report('labels', 'schema-drop')['error']);

        $this->cleanup('labels')->assertSuccessful();
        $this->assertSame(['translations' => 0, 'product_label_product' => $copyLabelPivot, 'product_labels' => $copyLabels], $this->report('labels')['counts']['deleted']);
        $backup = basename($this->report('labels')['backup']);

        $this->cleanup('labels', ['--drop-schema' => true])->assertFailed();
        $this->inTenant(fn () => $this->assertTrue(Schema::connection('tenant')->hasColumn('product_labels', 'central_product_label_id')));

        config(['catalog_dedup.schema_drops_code_ready' => true]);
        $this->cleanup('labels', ['--drop-schema' => true])->assertSuccessful();
        $this->inTenant(fn () => $this->assertFalse(Schema::connection('tenant')->hasColumn('product_labels', 'central_product_label_id')));

        // A cleanup / restore needs the column: refused until --undrop-schema.
        $this->cleanup('labels', ['--restore' => $backup])->assertFailed();
        $this->assertStringContainsString('lacks column', $this->report('labels', 'restore')['error']);
        $this->cleanup('labels', ['--undrop-schema' => true])->assertSuccessful();
        $this->cleanup('labels', ['--restore' => $backup])->assertSuccessful();
        $this->assertSame($copyLabels, $this->inTenant(fn () => DB::connection('tenant')->table('product_labels')->whereNotNull('central_product_label_id')->count()));

        // Running a drop file outside the command (no tenant) is refused by the file itself.
        tenancy()->end();
        $migration = require DedupSchemaDrops::migrationFile('labels');
        $this->expectExceptionMessage('run inside a tenant only');
        $migration->up();
    }

    // ── coupons: a copy referenced by an order is kept ─────────────────────────────────────────────────────────────

    public function test_coupon_copy_used_by_an_order_is_kept_and_orders_are_untouched(): void
    {
        $this->central(function () {
            CentralCoupon::query()->create(['code' => 'P7USED', 'name' => 'Used', 'type' => 'percentage', 'value' => 10, 'minimum_spend' => 0, 'active' => true]);
            CentralCoupon::query()->create(['code' => 'P7FREE', 'name' => 'Free', 'type' => 'percentage', 'value' => 5, 'minimum_spend' => 0, 'active' => true]);
        });
        $this->sync('coupons');
        $used = $this->inTenant(function () {
            $coupon = \App\Models\Tenant\Coupon::query()->where('code', 'P7USED')->firstOrFail();
            $variant = $this->createOwnProductVariant();
            $this->createOrder($this->createCustomer(), [[$variant, 1]], OrderStatus::Processing, paid: true, attributes: ['discount_id' => $coupon->id]);

            return $coupon->id;
        });
        $this->backfillRefs();
        $this->prepare('coupons');
        $orders = $this->table('orders');
        $items = $this->table('order_items');

        $this->cleanup('coupons')->assertSuccessful();
        $report = $this->report('coupons');
        $this->assertSame(['ref:orders.discount_id' => 1], $report['kept']);
        $this->assertSame(1, $report['counts']['deleted']['coupons']);
        $this->inTenant(function () use ($used) {
            $this->assertSame([$used], \App\Models\Tenant\Coupon::query()->whereNotNull('central_coupon_id')->pluck('id')->all());
        });
        $this->assertSame($orders, $this->table('orders'), 'orders untouched');
        $this->assertSame($items, $this->table('order_items'), 'order items untouched');
    }

    // ── pages + config ──────────────────────────────────────────────────────────────────────────────────────────

    public function test_pages_cleanup_deletes_only_platform_locale_texts_and_config_sections_delete_nothing(): void
    {
        $this->central(function () {
            $page = CentralStaticPage::query()->forceCreate(['slug' => 'privacy', 'status' => 'active']);
            $en = CentralLanguage::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_active' => true, 'is_default' => true, 'is_free' => true, 'price' => 0, 'sort_order' => 1]);
            foreach ([['title', 'Privacy Policy'], ['content', 'English text']] as [$field, $value]) {
                DB::table('translations')->insert(['language_id' => $en->id, 'translatable_type' => (new CentralStaticPage())->getMorphClass(), 'translatable_id' => $page->id, 'field' => $field, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
            }
            CentralCurrency::query()->create(['code' => 'EUR', 'name' => 'Euro', 'sign' => '€', 'conversion_rate' => 0.9]);
        });
        $this->sync('pages');
        $this->sync('currencies');
        [$deId, $ownPageId] = $this->inTenant(function () {
            $de = Language::query()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 3]);
            $privacy = Page::query()->where('slug', 'privacy')->firstOrFail();
            DB::connection('tenant')->table('translations')->insert(['language_id' => $de->id, 'translatable_type' => $privacy->getMorphClass(), 'translatable_id' => $privacy->id, 'field' => 'title', 'value' => 'Datenschutz', 'created_at' => now(), 'updated_at' => now()]);
            $own = Page::query()->create(['slug' => 'p7-own', 'active' => true]);
            DB::connection('tenant')->table('translations')->insert(['language_id' => $this->activeLanguage->id, 'translatable_type' => $own->getMorphClass(), 'translatable_id' => $own->id, 'field' => 'title', 'value' => 'Own', 'created_at' => now(), 'updated_at' => now()]);

            return [$de->id, $own->id];
        });
        $pageTexts = fn () => $this->inTenant(fn () => DB::connection('tenant')->table('translations')->where('translatable_type', (new Page())->getMorphClass())
            ->orderBy('id')->get(['translatable_id', 'language_id', 'field', 'value'])->map(fn ($r) => (array) $r)->all());
        $enRows = count(array_filter($pageTexts(), fn ($r) => $r['language_id'] === $this->activeLanguage->id && $r['translatable_id'] !== $ownPageId));
        $this->assertGreaterThan(0, $enRows, 'the sync copied the platform texts');

        $this->prepare('pages');
        $read = fn () => $this->inTenant(fn () => [Page::query()->where('slug', 'privacy')->firstOrFail()->translationValue('title', 'en'), Page::query()->where('slug', 'privacy')->firstOrFail()->translationValue('title', 'de')]);
        $this->assertSame(['Privacy Policy', 'Datenschutz'], $read());

        $this->cleanup('pages')->assertSuccessful();
        $this->assertSame($enRows, $this->report('pages')['counts']['deleted']['translations']);
        $left = $pageTexts();
        $this->assertContains(['translatable_id' => $ownPageId, 'language_id' => $this->activeLanguage->id, 'field' => 'title', 'value' => 'Own'], $left, 'own page untouched');
        $this->assertCount(1, array_filter($left, fn ($r) => $r['language_id'] === $deId), 'tenant-only locale kept');
        $this->assertCount(1, array_filter($left, fn ($r) => $r['translatable_id'] !== $ownPageId));
        $this->assertSame(['Privacy Policy', 'Datenschutz'], $read(), 'storefront read unchanged');

        // Config section: identity rows, nothing deleted (run recorded, backup written but empty).
        $currencies = $this->table('currencies');
        $this->prepare('currencies');
        $this->cleanup('currencies')->assertSuccessful();
        $this->assertSame(0, $this->report('currencies')['counts']['candidates']);
        $this->assertSame($currencies, $this->table('currencies'));
        $this->assertTrue(DedupCleanup::isCleaned($this->tenant, 'currencies'));
    }

    // ── products: prerequisites, referenced copies kept ─────────────────────────────────────────────────────────────

    public function test_products_cleanup_requires_prerequisites_and_keeps_referenced_copies(): void
    {
        $p = $this->inTenant(fn () => ['a' => $this->createCentralCatalogVariant(price: 30.0), 'b' => $this->createCentralCatalogVariant(price: 40.0), 'c' => $this->createCentralCatalogVariant(price: 45.0), 'own' => $this->createOwnProductVariant()]);
        // a: linked to an OWN category (tenant data, FK cascade) → kept. b: ordered (order_items) → kept. c: pure copy → deleted.
        $ownCategory = $this->inTenant(function () use ($p) {
            foreach (['a' => 30, 'b' => 40, 'c' => 45] as $k => $price) { // the pinned default price of a synced copy
                DB::connection('tenant')->table('products')->where('id', $p[$k]['product']->id)->update(['default_price' => $price]);
            }
            $category = Category::query()->create(['active' => true, 'order_number' => 1]);
            DB::connection('tenant')->table('category_product')->insert(['category_id' => $category->id, 'product_id' => $p['a']['product']->id, 'sort_order' => 0]);
            $this->createOrder($this->createCustomer(), [[$p['b']['variant'], 1]], OrderStatus::Processing, paid: true);

            return $category->id;
        });
        $this->backfillRefs();

        $this->prepare('products');
        $this->cleanup('products')->assertFailed();
        $error = $this->report('products')['error'];
        $this->assertStringContainsString('catalog_source.badges is not central', $error);

        foreach (DedupCleanup::PRODUCT_PREREQUISITES as $section) {
            $this->prepare($section);
        }
        $this->flip('products', CatalogSource::SYNC);
        $this->dedup('field_overrides');
        $this->dedup('field_overrides', ['--verify' => true]);
        $this->dedup('products');
        $this->dedup('products', ['--verify' => true]);
        $this->flip('products');
        $this->cleanup('products')->assertFailed();
        $this->assertStringContainsString('section badges is not cleaned yet', $this->report('products')['error']);

        foreach (DedupCleanup::PRODUCT_PREREQUISITES as $section) {
            $this->cleanup($section)->assertSuccessful();
        }

        $merged = fn () => $this->inTenant(fn () => ProductCatalog::query()->orderBy('id')->pluck('id')->all());
        $before = $merged();
        $this->assertCount(4, $before);
        $orderItems = $this->table('order_items');
        $categoryLinks = $this->table('category_product');

        $this->cleanup('products')->assertSuccessful();
        $report = $this->report('products');
        $this->assertSame(['fk:category_product.product_id' => 1, 'fk:order_items.product_variant_id' => 1], $report['kept'], json_encode($report));
        $this->assertSame(1, $report['counts']['deleted']['products']);
        $this->assertSame(1, $report['counts']['deleted']['product_variants']);

        $this->inTenant(function () use ($p, $ownCategory) {
            $db = DB::connection('tenant');
            $this->assertNull($db->table('products')->find($p['c']['product']->id), 'pure copy deleted');
            $this->assertNull($db->table('product_variants')->find($p['c']['variant']->id));
            $this->assertNotNull($db->table('products')->find($p['a']['product']->id), 'copy linked to an own category kept');
            $this->assertNotNull($db->table('products')->find($p['b']['product']->id), 'ordered copy kept');
            $this->assertNotNull($db->table('products')->find($p['own']->product_id), 'own product untouched');
            $this->assertSame(1, $db->table('category_product')->where('category_id', $ownCategory)->count());
        });
        $this->assertSame($orderItems, $this->table('order_items'), 'order items untouched');
        $this->assertSame($categoryLinks, $this->table('category_product'));
        $this->assertSame($before, $merged(), 'merged catalogue identical after the cleanup');
    }
}
