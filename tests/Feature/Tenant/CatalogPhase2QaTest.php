<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Language as CentralLanguage;
use App\Models\Product as CentralProduct;
use App\Models\ProductBadge as CentralBadge;
use App\Models\ProductEditRequest;
use App\Models\ProductLabel as CentralLabel;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Repositories\Tenant\Catalog\BadgeCatalog;
use App\Repositories\Tenant\Catalog\FieldOverrideStore;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\HomeProductService;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * QA — dedup plan phase 2 (badges / labels / field overrides). Complements CatalogPhase2{Badges,FieldOverrides,
 * AdminScreens}Test with: storefront card output identical before/after the flip, flag-off SQL path, dedup tooling
 * edge cases (unknown section, conflicting flags, unknown tenant, dry-run query log), and red tests for the defects
 * found in QA (D-P2-2, D-P2-3; see plan §12 "QA — Phase 2"; fixed in "Backend — Phase 2 QA fixes") and the D-P2-4..6 fixes.
 */
class CatalogPhase2QaTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        CatalogRefColumns::flush();
        $this->setUpTenantPanel();
    }

    protected function tearDown(): void
    {
        if (isset($this->tenant)) {
            tenancy()->end();
            DB::table('product_edit_requests')->where('tenant_id', $this->tenant->id)->delete();
            if (Schema::hasTable('catalog_dedup_runs')) {
                DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->delete();
            }
            foreach (['badges', 'labels', 'field_overrides'] as $section) {
                foreach (['migrate', 'dry-run', 'verify'] as $step) {
                    File::delete(DedupRunner::reportPath($section, (string) $this->tenant->id, $step));
                }
            }
        }
        CatalogRefColumns::flush();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function inTenant(callable $callback): mixed
    {
        $result = $this->tenant->run($callback);
        tenancy()->end();

        return $result;
    }

    private function central(callable $callback): mixed
    {
        tenancy()->end();

        return Model::withoutEvents($callback);
    }

    // ── storefront neutrality across the flip ──────────────────────────────────────────────────────────────────

    public function test_storefront_card_output_and_home_section_are_identical_in_sync_and_central_mode(): void
    {
        $p = $this->inTenant(fn () => [
            'a' => $this->createCentralCatalogVariant(price: 30.0),
            'b' => $this->createCentralCatalogVariant(price: 40.0),
        ]);
        $a = $p['a']['central_product']->id;
        $b = $p['b']['central_product']->id;
        $this->central(function () use ($a, $b) {
            $badge = CentralBadge::query()->firstOrCreate(['text' => 'featured'], ['active' => true]);
            $badge->forceFill(['active' => true])->save();
            DB::table('product_badge_product')->insert([
                ['product_badge_id' => $badge->id, 'product_id' => $a, 'country_id' => null, 'sort_order' => 1],
                ['product_badge_id' => $badge->id, 'product_id' => $b, 'country_id' => null, 'sort_order' => 0],
            ]);
            $label = CentralLabel::query()->create(['text' => 'Only {stock} left', 'translations' => ['en' => 'Only {stock} left'], 'icon' => 'fire', 'active' => true]);
            DB::table('product_label_product')->insert(['product_label_id' => $label->id, 'product_id' => $a]);
        });
        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'badges');
        tenancy()->end();
        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'labels');
        tenancy()->end();
        $this->artisan('catalog:dedup-migrate', ['section' => 'badges', '--tenant' => [$this->tenant->id]])->assertSuccessful();

        $capture = function () use ($p): array {
            $out = [];
            foreach (['a', 'b'] as $k) {
                $product = Product::query()->with(['badges', 'labels'])->findOrFail($p[$k]['product']->id);
                $out[$k] = [
                    'badge' => $product->cardBadgeText(),
                    'labels' => $product->cardLabels(),
                    'texts' => $product->badges->pluck('text')->all(),
                ];
            }
            DB::flushQueryLog();
            DB::connection('tenant')->enableQueryLog();
            $home = app(HomeProductService::class)->getFeatured(10)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $out['home'] = $home;
            $out['home_sql'] = collect(DB::connection('tenant')->getQueryLog())->pluck('query')->implode("\n");
            DB::connection('tenant')->disableQueryLog();

            return $out;
        };

        $sync = $this->inTenant(function () use ($capture) {
            $this->assertFalse(BadgeCatalog::enabled());

            return $capture();
        });
        $this->assertStringNotContainsString('merged_', $sync['home_sql'], 'sync mode must not touch the merged layer');
        $this->assertStringNotContainsString(config('database.connections.' . config('tenancy.database.central_connection') . '.database') . '`.`product_badge_product', $sync['home_sql']);

        tenancy()->end();
        CatalogSource::set($this->tenant, 'badges', CatalogSource::CENTRAL);
        CatalogSource::set($this->tenant, 'labels', CatalogSource::CENTRAL);
        $central = $this->inTenant(function () use ($capture) {
            $this->assertTrue(BadgeCatalog::enabled());

            return $capture();
        });

        foreach (['a', 'b'] as $k) {
            $this->assertSame($sync[$k], $central[$k], "card output of product {$k} changed by the flip");
        }
        $this->assertSame($sync['home'], $central['home'], 'home featured section order changed by the flip');
    }

    // ── dedup tooling edge cases ────────────────────────────────────────────────────────────────────────────────

    public function test_dedup_command_rejects_bad_input_and_dry_run_writes_nothing_in_the_tenant_db(): void
    {
        $this->assertSame(2, Artisan::call('catalog:dedup-migrate', ['section' => 'nope']), 'unknown section = INVALID');
        $this->assertSame(2, Artisan::call('catalog:dedup-migrate', ['section' => 'badges', '--dry-run' => true, '--verify' => true]));
        $this->assertSame(0, (int) DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->count(), 'no run row for rejected input');

        // Unknown tenant id: warned, nothing processed (no run rows).
        Artisan::call('catalog:dedup-migrate', ['section' => 'labels', '--tenant' => ['does-not-exist']]);
        $this->assertStringContainsString('Unknown tenant [does-not-exist]', Artisan::output());
        $this->assertSame(0, (int) DB::table('catalog_dedup_runs')->where('tenant_id', 'does-not-exist')->count());

        // Dry run of every section: tenant connection sees no INSERT / UPDATE / DELETE / DDL.
        $p = $this->inTenant(fn () => $this->createCentralCatalogVariant(price: 30.0));
        $this->central(function () use ($p) {
            $badge = CentralBadge::query()->create(['text' => 'qa-dry', 'active' => true]);
            DB::table('product_badge_product')->insert(['product_badge_id' => $badge->id, 'product_id' => $p['central_product']->id, 'country_id' => null, 'sort_order' => 3]);
        });
        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'badges');
        tenancy()->end();
        $this->inTenant(fn () => DB::table('product_badge_product')->update(['sort_order' => 9])); // vendor reorder

        $writes = [];
        DB::listen(function ($q) use (&$writes) {
            if ($q->connectionName === 'tenant' && preg_match('/^\s*(insert|update|delete|replace|alter|create|drop|truncate)/i', $q->sql)) {
                $writes[] = $q->sql;
            }
        });
        foreach (['badges', 'labels', 'field_overrides'] as $section) {
            $this->artisan('catalog:dedup-migrate', ['section' => $section, '--tenant' => [$this->tenant->id], '--dry-run' => true])->assertSuccessful();
        }
        $this->assertSame([], $writes, 'dry run wrote to the tenant DB');
        $rows = DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->orderBy('id')->get();
        $this->assertSame(['dry-run', 'dry-run', 'dry-run'], $rows->pluck('step')->all());
        $this->assertSame(['done', 'done', 'done'], $rows->pluck('status')->all());
        $this->assertSame(1, json_decode((string) $rows[0]->counts, true)['sort_override']);
        $this->assertNotNull($rows[0]->finished_at);
    }

    public function test_badges_migrate_refreshes_an_override_after_a_later_sync_mode_reorder(): void
    {
        // D-P2-5 (fixed): while the tenant is still on `sync`, re-running migrate refreshes an existing sort override
        // to the copy's current sort (idempotent), so a vendor reorder made after a migrate run keeps verify green.
        $p = $this->inTenant(fn () => [
            'a' => $this->createCentralCatalogVariant(price: 30.0),
            'b' => $this->createCentralCatalogVariant(price: 40.0),
        ]);
        $badge = $this->central(function () use ($p) {
            $badge = CentralBadge::query()->create(['text' => 'qa-resort', 'active' => true]);
            DB::table('product_badge_product')->insert([
                ['product_badge_id' => $badge->id, 'product_id' => $p['a']['central_product']->id, 'country_id' => null, 'sort_order' => 0],
                ['product_badge_id' => $badge->id, 'product_id' => $p['b']['central_product']->id, 'country_id' => null, 'sort_order' => 1],
            ]);

            return $badge;
        });
        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'badges');
        tenancy()->end();
        $copyId = $this->inTenant(fn () => ProductBadge::query()->where('central_product_badge_id', $badge->id)->value('id'));
        $reorder = fn (int $sort) => $this->inTenant(fn () => DB::table('product_badge_product')->where('product_badge_id', $copyId)->where('product_id', $p['a']['product']->id)->update(['sort_order' => $sort]));
        $override = fn () => $this->inTenant(fn () => DB::table(BadgeCatalog::OVERRIDES)->get(['sort_order'])->pluck('sort_order')->all());
        $migrate = fn (array $extra = []) => $this->artisan('catalog:dedup-migrate', ['section' => 'badges', '--tenant' => [$this->tenant->id]] + $extra);

        $reorder(7);
        $migrate()->assertSuccessful();
        $migrate(['--verify' => true])->assertSuccessful();
        $this->assertSame([7], $override());

        // Vendor reorders again while still on sync: refreshed, verify stays green; re-run changes nothing.
        $reorder(8);
        $migrate(['--dry-run' => true])->assertSuccessful();
        $this->assertSame([7], $override(), 'dry run writes nothing');
        $migrate()->assertSuccessful();
        $this->assertSame([8], $override(), 'override refreshed');
        $last = json_decode((string) DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->where('step', 'migrate')->orderByDesc('id')->value('counts'), true);
        $this->assertSame([1, 0], [$last['sort_override_refreshed'], $last['sort_override']]);
        $migrate()->assertSuccessful();
        $last = json_decode((string) DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->where('step', 'migrate')->orderByDesc('id')->value('counts'), true);
        $this->assertSame([0, 1], [$last['sort_override_refreshed'], $last['already_migrated']], 'idempotent');
        $migrate(['--verify' => true])->assertSuccessful();

        // Back to central's sort: the override stops pinning a sort (NULL = follow central).
        $reorder(0);
        $migrate()->assertSuccessful();
        $this->assertSame([null], $override());
        $migrate(['--verify' => true])->assertSuccessful();

        // After the flip the override rows are the truth: a stale copy never overwrites them.
        $reorder(5);
        tenancy()->end();
        CatalogSource::set($this->tenant, 'badges', CatalogSource::CENTRAL);
        $migrate()->assertSuccessful();
        $this->assertSame([null], $override(), 'central mode: override untouched');
    }

    /**
     * D-P2-6: the pivot unique index cannot stop duplicate central-attach rows (`product_badge_id` NULL; NULLs are
     * distinct in a unique index), so BadgeAssignments / the dedup migrate existence check are the only guard.
     */
    public function test_duplicate_central_attach_rows_are_prevented_by_code_not_by_the_index(): void
    {
        $p = $this->inTenant(fn () => ['a' => $this->createCentralCatalogVariant(price: 30.0), 'own' => $this->createOwnProductVariant(price: 20.0)]);
        $central = $this->central(function () use ($p) {
            $badge = CentralBadge::query()->create(['text' => 'qa-attach', 'active' => true]);
            DB::table('product_badge_product')->insert(['product_badge_id' => $badge->id, 'product_id' => $p['a']['central_product']->id, 'country_id' => null, 'sort_order' => 0]);

            return $badge;
        });
        tenancy()->end();
        CatalogSource::set($this->tenant, 'badges', CatalogSource::CENTRAL);
        $own = (int) $p['own']->product_id;

        $this->inTenant(function () use ($central, $p, $own) {
            $badge = app(BadgeCatalog::class)->find('c:' . $central->id);
            $assign = app(\App\Repositories\Tenant\Catalog\BadgeAssignments::class);
            $ids = [(int) $p['a']['product']->id, $own];
            $assign->saveForBadge($badge, null, $ids);
            $assign->saveForBadge($badge, null, $ids);
            app(\App\Repositories\Tenant\Catalog\ProductBadgeSelection::class)->save(Product::query()->findOrFail($own), ['c:' . $central->id]);
            $attach = fn () => DB::table('product_badge_product')->whereNull('product_badge_id')->where('catalog_badge_id', $central->id)->where('product_id', $own)->count();
            $this->assertSame(1, $attach(), 'code keeps one attach row');

            // The DB itself accepts a duplicate (documents why the code guard is required).
            $row = (array) DB::table('product_badge_product')->whereNull('product_badge_id')->where('product_id', $own)->first();
            unset($row['id']);
            DB::table('product_badge_product')->insert($row);
            $this->assertSame(2, $attach());
        });
    }

    /** D-P2-4: in central labels mode the home lists are keyed on the central label tag too. */
    public function test_home_product_cache_is_invalidated_by_a_central_label_change(): void
    {
        $service = new class extends HomeProductService
        {
            public function remember(string $key, \Closure $callback)
            {
                return $this->cacheRemember($key, $callback);
            }
        };
        $key = 'qa-labels-' . uniqid();
        $calls = 0;
        $count = function () use (&$calls) {
            return ++$calls;
        };

        tenancy()->end();
        CatalogSource::set($this->tenant, 'labels', CatalogSource::CENTRAL);
        $this->inTenant(function () use ($service, $key, $count, &$calls) {
            $this->assertTrue(\App\Repositories\Tenant\Catalog\LabelCatalog::enabled());
            $service->remember($key, $count);
            $service->remember($key, $count);
            $this->assertSame(1, $calls, 'cached');
            \App\Support\CacheVersion::bump(\App\Repositories\Tenant\Catalog\LabelCatalog::CACHE_TAG);
            \App\Support\CacheVersion::flushMemo();
            $service->remember($key, $count);
            $this->assertSame(2, $calls, 'central label change invalidates the home lists');
        });
    }

    // ── red tests for QA defects ────────────────────────────────────────────────────────────────────────────────

    /**
     * D-P2-2: `product_edit_requests` keeps ONE row per (tenant, product) that every new request overwrites. A product
     * approved twice the old (Livewire) way — request A (name) then request B (description) — has `requested` = B only.
     * LegacyFieldOverrideMigrator then classifies A's still-live approved name as a "stale frozen copy" and the next
     * sync replaces it with central's name: the vendor's approved customisation is silently lost (verify reports 0).
     */
    public function test_D_P2_2_an_older_approved_pair_survives_the_field_overrides_migration(): void
    {
        $this->inTenant(fn () => Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_active' => true, 'is_default' => true, 'sort_order' => 1]));
        CentralLanguage::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_active' => true, 'is_default' => true]);

        $fixture = $this->inTenant(fn () => $this->createCentralCatalogVariant(price: 30.0));
        $central = $fixture['central_product'];
        $product = $fixture['product'];
        $this->central(fn () => $central->syncTranslations(['en' => ['name' => 'Central Name', 'description' => 'Central Desc']]));
        app(TenantCatalogSyncService::class)->syncProductToTenant(CentralProduct::query()->findOrFail($central->id), $this->tenant);
        tenancy()->end();

        // Legacy state after two old-style approvals: tenant has both vendor values, the request row holds only B.
        $this->inTenant(function () use ($product) {
            $p = Product::withoutGlobalScope('centralVisible')->findOrFail($product->id);
            $store = new FieldOverrideStore();
            $store->putTenantTranslation($p->getMorphClass(), $p->id, 'en', 'name', 'Vendor Name A');
            $store->putTenantTranslation($p->getMorphClass(), $p->id, 'en', 'description', 'Vendor Desc B');
            $p->forceFill(['has_custom_translations' => true])->saveQuietly();
        });
        ProductEditRequest::query()->create([
            'tenant_id' => $this->tenant->id, 'product_id' => $product->id, 'product_slug' => $product->slug,
            'requested_translations' => ['en' => ['description' => 'Vendor Desc B']],
            'current_translations' => ['en' => ['description' => 'Central Desc']],
            'status' => 'approved',
        ]);

        $this->artisan('catalog:dedup-migrate', ['section' => 'field_overrides', '--tenant' => [$this->tenant->id]])->assertSuccessful();
        app(TenantCatalogSyncService::class)->syncProductToTenant(CentralProduct::query()->findOrFail($central->id), $this->tenant);
        tenancy()->end();

        $values = $this->inTenant(fn () => Product::withoutGlobalScope('centralVisible')->findOrFail($product->id)->translationsByLocale(['name', 'description']));
        $this->assertSame('Vendor Desc B', $values['en']['description']);
        $this->assertSame('Vendor Name A', $values['en']['name'], 'D-P2-2: the earlier approved name was reverted to central');
    }

    /** D-P2-3: a dedup gate on a tenant that was never verified must FAIL, not pass (NULL is skipped today). */
    public function test_D_P2_3_dedup_verify_gate_fails_when_never_verified(): void
    {
        $code = Artisan::call('catalog:sync-check', [
            '--tenant' => [(string) $this->tenant->id],
            '--section' => ['badges'],
            '--gate' => ['dedup_verify_diffs:0'],
        ]);

        $output = Artisan::output();
        $this->assertSame(1, $code, "D-P2-3: never-verified tenant passed the flip gate.\n" . $output);
        $this->assertStringContainsString('dedup_verify_diffs = NULL', $output);

        // A gate no selected section reports fails too (typo / wrong section must not pass silently).
        $this->assertSame(1, Artisan::call('catalog:sync-check', ['--tenant' => [(string) $this->tenant->id], '--section' => ['badges'], '--gate' => ['no_such_metric:0']]));

        // Once verified with 0 diffs the same gate passes.
        $this->artisan('catalog:dedup-migrate', ['section' => 'badges', '--tenant' => [$this->tenant->id], '--verify' => true])->assertSuccessful();
        $this->assertSame(0, Artisan::call('catalog:sync-check', [
            '--tenant' => [(string) $this->tenant->id],
            '--section' => ['badges'],
            '--gate' => ['dedup_verify_diffs:0'],
        ]), Artisan::output());
    }
}
