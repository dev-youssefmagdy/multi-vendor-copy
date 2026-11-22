<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Product as CentralProduct;
use App\Models\ProductBadge as CentralBadge;
use App\Models\ProductLabel as CentralLabel;
use App\Models\Tenant\Catalog\MergedProductBadge;
use App\Models\Tenant\Catalog\MergedProductLabel;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Models\Tenant\ProductLabel;
use App\Repositories\Tenant\Catalog\BadgeAssignments;
use App\Repositories\Tenant\Catalog\BadgeCatalog;
use App\Repositories\Tenant\Catalog\ProductBadgeSelection;
use App\Services\Catalog\CatalogSyncCheck;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\HomeProductService;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan phase 2 (docs/TENANT_CENTRAL_DEDUP_PLAN.md §4 badges/labels, §5a.2): merged badge read layer (central live
 * ∪ tenant own, sort overrides, tenant attaches, vendor hides), labels read from central directly (soft-deleted hidden),
 * flag off = synced tables unchanged, the sync skips central-mode tenants, panel endpoints, and
 * `catalog:dedup-migrate badges|labels` (dry-run writes nothing, migrate idempotent, verify 0 diffs).
 */
class CatalogPhase2BadgesTest extends TestCase
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
            if (Schema::hasTable('catalog_dedup_runs')) {
                DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->delete();
            }
            foreach (['badges', 'labels'] as $section) {
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
        return $this->tenant->run($callback);
    }

    private function central(callable $callback): mixed
    {
        tenancy()->end();

        return Model::withoutEvents($callback);
    }

    private function flip(string $section, string $mode = CatalogSource::CENTRAL): void
    {
        tenancy()->end();
        CatalogSource::set($this->tenant, $section, $mode);
    }

    /** A central badge with central pairs [central product id => sort] (default country). */
    private function centralBadge(string $text, array $pairs, ?int $countryId = null): CentralBadge
    {
        return $this->central(function () use ($text, $pairs, $countryId) {
            $badge = CentralBadge::query()->create(['text' => $text, 'active' => true]);
            foreach ($pairs as $centralProductId => $sort) {
                DB::table('product_badge_product')->insert(['product_badge_id' => $badge->id, 'product_id' => $centralProductId, 'country_id' => $countryId, 'sort_order' => $sort]);
            }

            return $badge;
        });
    }

    /** @return array{a: array, b: array, own: \App\Models\Tenant\ProductVariant} */
    private function products(): array
    {
        return $this->inTenant(fn () => [
            'a' => $this->createCentralCatalogVariant(price: 30.0),
            'b' => $this->createCentralCatalogVariant(price: 40.0),
            'own' => $this->createOwnProductVariant(price: 20.0),
        ]);
    }

    // ── flag off ────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_flag_off_keeps_reading_the_synced_tables(): void
    {
        $p = $this->products();
        $this->centralBadge('p2-off', [$p['a']['central_product']->id => 0]);
        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'badges');
        tenancy()->end();

        $this->inTenant(function () use ($p) {
            $this->assertFalse(BadgeCatalog::enabled());
            $product = Product::query()->findOrFail($p['a']['product']->id);
            $this->assertInstanceOf(BelongsToMany::class, $product->badges());
            $this->assertInstanceOf(ProductBadge::class, $product->badges->first());
            $this->assertSame('p2-off', $product->badges->first()->text);
            $this->assertInstanceOf(BelongsToMany::class, $product->labels());
            $this->assertInstanceOf(ProductBadge::class, app(ProductBadgeSelection::class)->options()->first());
        });
    }

    // ── merged read + writes ────────────────────────────────────────────────────────────────────────────────────

    public function test_merged_badges_central_only_tenant_only_both_sort_override_attach_and_hide(): void
    {
        $p = $this->products();
        $a = $p['a']['central_product']->id;
        $b = $p['b']['central_product']->id;
        $central = $this->centralBadge('p2-central', [$a => 1, $b => 0]);
        $ownBadge = $this->inTenant(function () use ($p) {
            $badge = ProductBadge::query()->create(['text' => 'p2-own', 'active' => true]);
            DB::table('product_badge_product')->insert(['product_badge_id' => $badge->id, 'product_id' => $p['own']->product_id, 'country_id' => null, 'sort_order' => 0]);

            return $badge;
        });
        $this->flip('badges');

        $this->inTenant(function () use ($p, $central, $ownBadge, $a) {
            $catalog = app(BadgeCatalog::class);
            $this->assertTrue(BadgeCatalog::enabled());

            // Central only (no tenant copy exists) + tenant only, merged; seeded tenant rows with a central text are shadowed.
            $refs = $catalog->all()->pluck('id')->all();
            $this->assertContains('c:' . $central->id, $refs);
            $this->assertContains('t:' . $ownBadge->id, $refs);
            $this->assertSame(1, $catalog->all()->where('text', 'recommended')->count(), 'seeded tenant "recommended" shadowed by central');
            $this->assertTrue($catalog->resolve('recommended')->isCentral());

            $badge = $catalog->resolve('p2-central');
            $idsA = $p['a']['product']->id;
            $idsB = $p['b']['product']->id;
            $own = $p['own']->product_id;
            $this->assertSame([$idsB, $idsA], $catalog->productIds($badge, null), 'central sort');

            $productA = Product::query()->findOrFail($idsA);
            $this->assertInstanceOf(HasMany::class, $productA->badges());
            $this->assertInstanceOf(MergedProductBadge::class, $productA->badges->first());
            $this->assertSame(['c:' . $central->id], $productA->badges->pluck('id')->all());
            $this->assertSame('p2-central', $productA->cardBadgeText()); // untranslated text as-is (Product::cardBadgeText())
            $this->assertSame(['t:' . $ownBadge->id], Product::query()->findOrFail($own)->badges->pluck('id')->all());
            // whereHas through the merged relation (StorefrontRepository::productsByBadgeQuery()).
            $this->assertSame([$idsA, $idsB], Product::query()->whereHas('badges', fn ($q) => $q->where('text', 'p2-central')->where('active', true))->orderBy('id')->pluck('id')->all());

            // Both: tenant attaches its own product to the central badge (existing order kept, new one appended).
            $assign = app(BadgeAssignments::class);
            $assign->saveForBadge($badge, null, [$idsA, $idsB, $own]);
            $this->assertSame([$idsB, $idsA, $own], $catalog->productIds($badge, null));
            $row = DB::table('product_badge_product')->where('product_id', $own)->whereNull('product_badge_id')->first();
            $this->assertSame(['central', $central->id, 'tenant', $own], [$row->badge_type, (int) $row->catalog_badge_id, $row->product_type, (int) $row->catalog_product_id]);

            // Sort override wins over central's sort.
            $assign->saveSort($badge, null, [$own, $idsA, $idsB]);
            $this->assertSame([$own, $idsA, $idsB], $catalog->productIds($badge, null));
            $this->assertSame(2, DB::table(BadgeCatalog::OVERRIDES)->whereNotNull('sort_order')->count());

            // Vendor removes a central pair (hidden override), then re-adds it (appended).
            $assign->saveForBadge($badge, null, [$own, $idsB]);
            $this->assertSame([$own, $idsB], $catalog->productIds($badge, null));
            $this->assertSame(1, DB::table(BadgeCatalog::OVERRIDES)->where('hidden', true)->where('product_id', $a)->count());
            $assign->saveForBadge($badge, null, [$own, $idsB, $idsA]);
            $this->assertSame([$own, $idsB, $idsA], $catalog->productIds($badge, null));

            // Product form: ref strings in, unselected badges removed (central pair hidden, tenant row deleted).
            app(ProductBadgeSelection::class)->save(Product::query()->findOrFail($own), ['t:' . $ownBadge->id]);
            $this->assertSame(['t:' . $ownBadge->id], Product::query()->findOrFail($own)->badges->pluck('id')->all());
            app(ProductBadgeSelection::class)->save(Product::query()->findOrFail($idsA), []);
            $this->assertSame([], Product::query()->findOrFail($idsA)->badges->pluck('id')->all());
            app(ProductBadgeSelection::class)->save(Product::query()->findOrFail($idsA), ['c:' . $central->id]);
            $this->assertSame(['c:' . $central->id], Product::query()->findOrFail($idsA)->badges->pluck('id')->all());
            $this->assertSame(['string', 'Closure'], array_map(fn ($r) => is_string($r) ? 'string' : 'Closure', app(ProductBadgeSelection::class)->itemRules()));

            // Nothing was written to central (invariant 1).
            $this->assertSame(0, DB::table(BadgeCatalog::OVERRIDES)->where('badge_type', 'tenant')->count());
        });

        // A later central change shows through live.
        $c = $this->inTenant(fn () => $this->createCentralCatalogVariant(price: 50.0));
        $this->central(fn () => DB::table('product_badge_product')->insert(['product_badge_id' => $central->id, 'product_id' => $c['central_product']->id, 'country_id' => null, 'sort_order' => 99]));
        $this->inTenant(function () use ($central, $c) {
            $badge = app(BadgeCatalog::class)->find('c:' . $central->id);
            $this->assertContains($c['product']->id, app(BadgeCatalog::class)->productIds($badge, null));
            $this->assertSame(0, ProductBadge::query()->where('central_product_badge_id', $central->id)->count(), 'no tenant copy');
        });
    }

    public function test_home_badge_sections_and_best_selling_order_use_the_merged_membership(): void
    {
        $p = $this->products();
        $this->central(fn () => DB::table('product_badge_product')->insert([
            ['product_badge_id' => CentralBadge::query()->where('text', 'recommended')->value('id'), 'product_id' => $p['b']['central_product']->id, 'country_id' => null, 'sort_order' => 0],
            ['product_badge_id' => CentralBadge::query()->where('text', 'recommended')->value('id'), 'product_id' => $p['a']['central_product']->id, 'country_id' => null, 'sort_order' => 1],
        ]));
        $this->flip('badges');

        $this->inTenant(function () use ($p) {
            $ids = app(HomeProductService::class)->getRecommended(10)->pluck('id')->all();
            $this->assertSame([$p['b']['product']->id, $p['a']['product']->id], $ids);

            $order = HomeProductService::applyBestSellingOrder(Product::query(), 30)->pluck('products.id')->all();
            $this->assertCount(3, $order);
        });
    }

    // ── labels ──────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_labels_read_central_directly_and_soft_deleted_central_label_is_hidden(): void
    {
        $p = $this->products();
        [$live, $gone] = $this->central(function () use ($p) {
            $live = CentralLabel::query()->create(['text' => 'Only {stock} left', 'translations' => ['en' => 'Only {stock} left'], 'icon' => 'box', 'active' => true, 'sort_order' => 2]);
            $gone = CentralLabel::query()->create(['text' => 'gone', 'active' => true, 'sort_order' => 1]);
            // Central allows one label per product (unique product_id).
            DB::table('product_label_product')->insert([
                ['product_label_id' => $live->id, 'product_id' => $p['a']['central_product']->id],
                ['product_label_id' => $gone->id, 'product_id' => $p['b']['central_product']->id],
            ]);

            return [$live, $gone];
        });

        $this->flip('labels');
        $this->inTenant(function () use ($p, $live, $gone) {
            $product = Product::query()->findOrFail($p['a']['product']->id);
            $this->assertInstanceOf(HasMany::class, $product->labels());
            $this->assertSame([$live->id], $product->labels->pluck('id')->all());
            $this->assertInstanceOf(MergedProductLabel::class, $product->labels->first());
            $this->assertSame([$gone->id], Product::query()->findOrFail($p['b']['product']->id)->labels->pluck('id')->all());
            $this->assertSame(0, ProductLabel::query()->count(), 'no tenant copy needed');
        });

        $this->central(fn () => CentralLabel::query()->findOrFail($gone->id)->delete()); // soft delete (phase 0)
        $this->inTenant(function () use ($p, $live) {
            $product = Product::query()->findOrFail($p['a']['product']->id);
            $this->assertSame([$live->id], $product->labels->pluck('id')->all());
            $this->assertSame([], Product::query()->findOrFail($p['b']['product']->id)->labels->pluck('id')->all(), 'soft-deleted central label hidden');
            $this->assertCount(1, $product->cardLabels());
            $this->assertStringStartsWith('Only ', $product->cardLabels()[0]['text']);
            $this->assertSame([], Product::query()->findOrFail($p['own']->product_id)->cardLabels());
        });
    }

    // ── sync ────────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_sync_skips_central_mode_badges_and_labels_including_the_product_push(): void
    {
        $p = $this->products();
        $badge = $this->centralBadge('p2-skip', [$p['a']['central_product']->id => 0]);
        $label = $this->central(function () use ($p) {
            $label = CentralLabel::query()->create(['text' => 'p2-skip-label', 'active' => true]);
            DB::table('product_label_product')->insert(['product_label_id' => $label->id, 'product_id' => $p['a']['central_product']->id]);

            return $label;
        });
        $this->flip('badges');
        $this->flip('labels');

        $sync = app(TenantCatalogSyncService::class);
        $this->assertSame(0, $sync->syncSection($this->tenant, 'badges'));
        $this->assertSame(0, $sync->syncSection($this->tenant, 'labels'));
        tenancy()->end();
        $sync->syncProductToTenant(CentralProduct::query()->findOrFail($p['a']['central_product']->id), $this->tenant, force: true);
        tenancy()->end();

        $this->inTenant(function () use ($badge, $label, $p) {
            $this->assertFalse(ProductBadge::query()->where('central_product_badge_id', $badge->id)->exists());
            $this->assertFalse(ProductLabel::query()->where('central_product_label_id', $label->id)->exists(), 'product push wrote no label copy');
            $this->assertSame([$label->id], Product::query()->findOrFail($p['a']['product']->id)->labels->pluck('id')->all(), 'read live');
        });
    }

    // ── panel ───────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_panel_badge_pages_work_in_central_mode(): void
    {
        $p = $this->products();
        $badge = $this->centralBadge('featured-p2', [$p['a']['central_product']->id => 0, $p['b']['central_product']->id => 1]);
        $this->flip('badges');
        $this->actingAsTenantAdmin();

        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save()); // onboarding-tour middleware
        tenancy()->end();
        $this->get($this->tenantUrl('/admin/badges/featured-p2'))->assertOk()->assertSee('featured-p2');
        $this->get($this->tenantUrl('/admin/badges/featured-p2/sort'))->assertOk();
        $this->get($this->tenantUrl('/admin/badges/no-such-badge'))->assertNotFound();

        $this->postJson($this->tenantUrl('/admin/badges/featured-p2/save'), ['product_ids' => [$p['b']['product']->id, $p['own']->product_id]])
            ->assertOk()->assertJson(['success' => true]);
        $this->postJson($this->tenantUrl('/admin/badges/featured-p2/sort'), ['ids' => [$p['own']->product_id, $p['b']['product']->id]])
            ->assertOk()->assertJson(['success' => true]);

        $this->inTenant(function () use ($badge, $p) {
            $merged = app(BadgeCatalog::class)->find('c:' . $badge->id);
            $this->assertSame([$p['own']->product_id, $p['b']['product']->id], app(BadgeCatalog::class)->productIds($merged, null));
        });
    }

    // ── legacy data tooling ─────────────────────────────────────────────────────────────────────────────────────

    public function test_dedup_migrate_badges_dry_run_migrate_idempotent_verify_and_sync_check(): void
    {
        $p = $this->products();
        $a = $p['a']['central_product']->id;
        $b = $p['b']['central_product']->id;
        $badge = $this->centralBadge('p2-legacy', [$a => 0, $b => 1]);

        // Legacy sync-mode state: copies, a vendor reorder and a vendor attach of the own product.
        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'badges');
        tenancy()->end();
        $this->inTenant(function () use ($badge, $p) {
            $copy = ProductBadge::query()->where('central_product_badge_id', $badge->id)->firstOrFail();
            DB::table('product_badge_product')->where('product_badge_id', $copy->id)->where('product_id', $p['b']['product']->id)->update(['sort_order' => 0]);
            DB::table('product_badge_product')->where('product_badge_id', $copy->id)->where('product_id', $p['a']['product']->id)->update(['sort_order' => 5]);
            DB::table('product_badge_product')->insert(['product_badge_id' => $copy->id, 'product_id' => $p['own']->product_id, 'country_id' => null, 'sort_order' => 6]);
        });

        $before = $this->inTenant(fn () => [DB::table(BadgeCatalog::OVERRIDES)->count(), DB::table('product_badge_product')->count()]);
        $this->artisan('catalog:dedup-migrate', ['section' => 'badges', '--tenant' => [$this->tenant->id], '--dry-run' => true])->assertSuccessful();
        $this->assertSame($before, $this->inTenant(fn () => [DB::table(BadgeCatalog::OVERRIDES)->count(), DB::table('product_badge_product')->count()]), 'dry run writes nothing');
        $dry = json_decode(File::get(DedupRunner::reportPath('badges', (string) $this->tenant->id, 'dry-run')), true);
        $this->assertSame(2, $dry['counts']['sort_override']);
        $this->assertSame(1, $dry['counts']['tenant_attach']);

        // sync-check shows the pending conversion before migrate.
        $check = $this->syncCheck('badges');
        $this->assertSame(2, $check['sort_overrides_pending']);
        $this->assertSame(1, $check['attaches_pending']);
        $this->assertSame(0, $check['pairs_missing_in_copy']);

        $this->artisan('catalog:dedup-migrate', ['section' => 'badges', '--tenant' => [$this->tenant->id]])->assertSuccessful();
        $after = $this->inTenant(fn () => [DB::table(BadgeCatalog::OVERRIDES)->count(), DB::table('product_badge_product')->count()]);
        $this->assertSame([$before[0] + 2, $before[1] + 1], $after);

        $this->artisan('catalog:dedup-migrate', ['section' => 'badges', '--tenant' => [$this->tenant->id]])->assertSuccessful();
        $this->assertSame($after, $this->inTenant(fn () => [DB::table(BadgeCatalog::OVERRIDES)->count(), DB::table('product_badge_product')->count()]), 'idempotent');

        $this->artisan('catalog:dedup-migrate', ['section' => 'badges', '--tenant' => [$this->tenant->id], '--verify' => true])->assertSuccessful();
        $verify = json_decode(File::get(DedupRunner::reportPath('badges', (string) $this->tenant->id, 'verify')), true);
        $this->assertSame(0, $verify['diffs'], json_encode($verify));

        $check = $this->syncCheck('badges');
        $this->assertSame([0, 0, 'done', 0], [$check['sort_overrides_pending'], $check['attaches_pending'], $check['dedup_migrate'], $check['dedup_verify_diffs']]);

        // Old order == new order after the flip.
        $old = $this->inTenant(fn () => ProductBadge::query()->where('central_product_badge_id', $badge->id)->firstOrFail()->productsForCountry(null)->pluck('products.id')->all());
        $this->flip('badges');
        $this->inTenant(fn () => $this->assertSame($old, app(BadgeCatalog::class)->productIds(app(BadgeCatalog::class)->find('c:' . $badge->id), null)));

        // Labels: migrate writes nothing, verify 0 diffs after a labels sync.
        $this->central(function () use ($a) {
            $label = CentralLabel::query()->create(['text' => 'p2-l', 'translations' => ['en' => 'x', 'ar' => 'y'], 'active' => true]);
            DB::table('product_label_product')->insert(['product_label_id' => $label->id, 'product_id' => $a]);
        });
        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'labels');
        tenancy()->end();
        $this->artisan('catalog:dedup-migrate', ['section' => 'labels', '--tenant' => [$this->tenant->id]])->assertSuccessful();
        $this->artisan('catalog:dedup-migrate', ['section' => 'labels', '--tenant' => [$this->tenant->id], '--verify' => true])->assertSuccessful();
        $this->assertSame(0, json_decode(File::get(DedupRunner::reportPath('labels', (string) $this->tenant->id, 'verify')), true)['diffs']);
        $labels = $this->syncCheck('labels');
        $this->assertSame([0, 0, 0], [$labels['pairs_missing_in_copy'], $labels['pairs_extra_in_copy'], $labels['stale_copies']]);
    }

    private function syncCheck(string $section): array
    {
        tenancy()->end();
        $report = app(CatalogSyncCheck::class)->run([(string) $this->tenant->id], [$section]);

        return $report[0]['sections'][$section];
    }
}
