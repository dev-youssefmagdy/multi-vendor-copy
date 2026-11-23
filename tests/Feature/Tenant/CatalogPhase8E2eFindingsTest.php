<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Livewire\Tenant\Storefront\ProductPage;
use App\Models\AppSetting;
use App\Models\Product as CentralProduct;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Setting;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as Files;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Regression tests for the app bugs the Phase 8 browser E2E suite found (dedup plan §12 "Phase 8").
 */
class CatalogPhase8E2eFindingsTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        config(['cache.stores.file.path' => sys_get_temp_dir() . '/catalog-phase8-test-cache']);
        Cache::forgetDriver('file');
        CatalogRefColumns::flush();
        $this->setUpTenantPanel();
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());
    }

    protected function tearDown(): void
    {
        if (isset($this->tenant)) {
            tenancy()->end();
            if (Schema::hasTable('catalog_dedup_runs')) {
                DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->delete();
            }
            foreach (['migrate', 'dry-run', 'verify'] as $step) {
                Files::delete(DedupRunner::reportPath('products', (string) $this->tenant->id, $step));
            }
        }
        CatalogRefColumns::flush();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    /** E2E finding 1: a central secret (encrypted openai_api_key, > 255 chars) broke the settings sync of every new tenant. */
    public function test_settings_sync_never_copies_central_secrets(): void
    {
        AppSetting::query()->updateOrCreate(['key' => 'openai_api_key'], ['value' => str_repeat('x', 480), 'type' => 'secret', 'is_public' => false]);
        AppSetting::query()->updateOrCreate(['key' => 'e2e_public_motto'], ['value' => 'hello', 'type' => 'string', 'is_public' => true]);

        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'settings');

        $this->tenant->run(function () {
            $this->assertFalse(Setting::query()->where('name', 'openai_api_key')->exists(), 'central secret copied into the tenant');
            $this->assertSame('hello', Setting::query()->where('name', 'e2e_public_motto')->value('value'));
        });
    }

    /** E2E finding 2: the product page 500'd in central products mode (whereHas('variant') on a derived relation key). */
    public function test_product_page_renders_in_central_products_mode_and_counts_sold_lines_by_typed_pair(): void
    {
        $x = $this->tenant->run(fn () => $this->createCentralCatalogVariant(price: 40.0));
        tenancy()->end();
        app(TenantCatalogSyncService::class)->syncProductToTenant(CentralProduct::query()->findOrFail($x['central_product']->id), $this->tenant, true);
        tenancy()->end();
        (new DedupRunner())->run($this->tenant, 'products', 'migrate');
        tenancy()->end();
        foreach (['badges', 'labels', 'flash-sales', 'categories', 'products'] as $section) {
            CatalogSource::set($this->tenant, $section, CatalogSource::CENTRAL);
        }
        CacheVersion::flushMemo();

        $this->tenant->run(function () use ($x) {
            $this->assertTrue(ProductCatalog::enabled());
            $customer = $this->createCustomer();
            $order = $this->createOrder($customer, []);
            // A central-mode line: no tenant product id, identified by the typed pair only.
            DB::connection('tenant')->table('order_items')->insert([
                'order_id' => $order->id, 'product_id' => null, 'product_variant_id' => null, 'qty' => 3, 'price' => 40,
                'sub_total' => 120, 'discount' => 0, 'tax' => 0, 'weight' => 0, 'shipping_fee' => 0, 'grand_total' => 120,
                'product_type' => 'central', 'catalog_product_id' => $x['central_product']->id,
                'variant_type' => 'central', 'catalog_variant_id' => $x['central_variant']->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->assertSame(1, OrderItem::query()->count());

            // ?preview_country keeps CountryDetectorService off the (absent) test request session.
            $page = Livewire::withQueryParams(['preview_country' => 'GB'])->test(ProductPage::class, ['slug' => $x['central_product']->slug])->assertOk();
            $this->assertSame(3, $page->viewData('soldCount'));
        });
    }

    /**
     * E2E findings 3 + 4: with products = central, a merged category's `products` relation and the merged flash sales'
     * products (FlashSaleCatalog::withProducts) resolved product KEYS (central ids) against the tenant COPY table, so the
     * storefront menu / category counts and the home flash strip were empty whenever copy ids differ from central ids
     * (any tenant created after other central products existed).
     */
    public function test_merged_category_and_flash_sale_products_use_merged_products_when_products_are_central(): void
    {
        // Other central products first: the central id is then far from the tenant copy id (and from any tenant row).
        CentralProduct::withoutEvents(function () {
            foreach (range(1, 5) as $i) {
                CentralProduct::query()->create(['slug' => "phase8-filler-{$i}", 'sku' => "P8-FILL-{$i}", 'status' => 'draft', 'base_price' => 1]);
            }
        });
        $x = $this->tenant->run(fn () => $this->createCentralCatalogVariant(price: 40.0));
        tenancy()->end();
        $this->assertGreaterThan((int) $x['product']->id, (int) $x['central_product']->id);
        $this->tenant->run(fn () => $this->assertNull(\App\Models\Tenant\Product::withoutGlobalScopes()->find($x['central_product']->id)));

        $category = \App\Models\Category::withoutEvents(fn () => \App\Models\Category::query()->create(['status' => 'published', 'is_featured' => false, 'order_number' => 1]));
        DB::table('category_product')->insert(['category_id' => $category->id, 'product_id' => $x['central_product']->id, 'sort_order' => 0]);
        \App\Models\CentralFlashSale::withoutEvents(function () use ($x) {
            $sale = \App\Models\CentralFlashSale::query()->create(['discount_percentage' => 20, 'active' => true, 'start_date' => now()->subDay(), 'end_date' => now()->addDay()]);
            DB::table('central_flash_sale_product')->insert(['central_flash_sale_id' => $sale->id, 'product_id' => $x['central_product']->id, 'created_at' => now(), 'updated_at' => now()]);
        });
        $this->tenant->category_ids = [$category->id];
        $this->tenant->save();
        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'categories');
        tenancy()->end();
        foreach (['categories', 'products'] as $section) {
            (new DedupRunner())->run($this->tenant, $section, 'migrate');
            tenancy()->end();
        }
        foreach (['badges', 'labels', 'flash-sales', 'categories', 'products'] as $section) {
            CatalogSource::set($this->tenant, $section, CatalogSource::CENTRAL);
        }
        \App\Repositories\Tenant\Catalog\CategoryCatalog::flushMemo();
        CacheVersion::flushMemo();

        $this->tenant->run(function () use ($x, $category) {
            $this->assertTrue(ProductCatalog::enabled());
            $merged = \App\Repositories\Tenant\Catalog\CategoryCatalog::query()->withCount('products')->get()->keyBy('id');
            $this->assertSame(1, (int) $merged[$category->id]->products_count);
            $this->assertSame([(int) $x['central_product']->id], $merged[$category->id]->products->pluck('id')->map(fn ($id) => (int) $id)->all());

            $sales = app(\App\Repositories\Tenant\Catalog\FlashSaleCatalog::class)->sales(activeOnly: true);
            $this->assertCount(1, $sales);
            $this->assertSame([(int) $x['central_product']->id], $sales->first()->products->pluck('id')->map(fn ($id) => (int) $id)->all());
        });
    }

    /** E2E finding 5: "Add to my store" did nothing for a central-mode tenant (sync skipped, no override row written). */
    public function test_adopting_new_products_in_central_mode_writes_override_rows(): void
    {
        $x = $this->tenant->run(fn () => $this->createCentralCatalogVariant(price: 40.0));
        tenancy()->end();
        (new DedupRunner())->run($this->tenant, 'products', 'migrate');
        tenancy()->end();
        $new = CentralProduct::withoutEvents(fn () => CentralProduct::query()->create(['slug' => 'phase8-new', 'sku' => 'P8-NEW', 'status' => 'published', 'base_price' => 10]));
        foreach (['badges', 'labels', 'flash-sales', 'categories', 'products'] as $section) {
            CatalogSource::set($this->tenant, $section, CatalogSource::CENTRAL);
        }
        CacheVersion::flushMemo();

        $sync = app(TenantCatalogSyncService::class);
        $pending = $this->tenant->run(fn () => $sync->pendingNewProductsQuery()->pluck('id')->map(fn ($id) => (int) $id)->all());
        tenancy()->end();
        $this->assertContains((int) $new->id, $pending);
        $this->assertNotContains((int) $x['central_product']->id, $pending, 'an adopted (override row) product is not offered again');

        $this->assertSame(1, $sync->syncNewProductsForTenant($this->tenant, [(int) $new->id]));
        tenancy()->end();

        $this->tenant->run(function () use ($new) {
            $this->assertTrue(DB::connection('tenant')->table('tenant_product_overrides')->where('central_product_id', $new->id)->exists());
            $this->assertFalse(\App\Models\Tenant\Product::withoutGlobalScopes()->where('central_product_id', $new->id)->exists(), 'no copy row');
            $this->assertNotNull(ProductCatalog::query()->where('central_product_id', $new->id)->first());
            $this->assertNotContains((int) $new->id, app(TenantCatalogSyncService::class)->pendingNewProductsQuery()->pluck('id')->map(fn ($id) => (int) $id)->all());
        });
    }

    /** E2E finding 6: the panel product sort page listed COPY ids in central mode; saving them re-sorted (and adopted) other central products. */
    public function test_product_sort_page_uses_merged_keys_in_central_mode(): void
    {
        CentralProduct::withoutEvents(function () {
            foreach (range(1, 5) as $i) {
                CentralProduct::query()->create(['slug' => "phase8-sort-{$i}", 'sku' => "P8-SORT-{$i}", 'status' => 'draft', 'base_price' => 1]);
            }
        });
        $x = $this->tenant->run(fn () => $this->createCentralCatalogVariant(price: 40.0));
        tenancy()->end();
        (new DedupRunner())->run($this->tenant, 'products', 'migrate');
        tenancy()->end();
        foreach (['badges', 'labels', 'flash-sales', 'categories', 'products'] as $section) {
            CatalogSource::set($this->tenant, $section, CatalogSource::CENTRAL);
        }
        CacheVersion::flushMemo();
        $centralId = (int) $x['central_product']->id;
        $copyId = (int) $x['product']->id;
        $this->assertNotSame($centralId, $copyId);

        $r = $this->actingAsTenantAdmin()->get($this->tenantUrl("/admin/products/sort"));
        $r->assertOk()
            ->assertSee('data-id="' . $centralId . '"', false)
            ->assertDontSee('data-id="' . $copyId . '"', false);

        $this->actingAsTenantAdmin()->postJson($this->tenantUrl('/admin/products/sort'), ['ids' => [$centralId]])->assertOk();

        $this->tenant->run(function () use ($centralId, $copyId) {
            $overrides = DB::connection('tenant')->table('tenant_product_overrides')->pluck('order_number', 'central_product_id')->all();
            $this->assertSame(0, (int) $overrides[$centralId]);
            $this->assertArrayNotHasKey($copyId, $overrides, 'the copy id must never be treated as a central key');
        });
    }

    /** E2E finding 7 (sync mode): "Recommended" home section 500'd (ambiguous `id`: category_product got a surrogate id). */
    public function test_interaction_based_recommendations_do_not_hit_an_ambiguous_id(): void
    {
        $this->tenant->run(function () {
            $category = \App\Models\Tenant\Category::query()->create(['active' => true, 'featured' => false, 'order_number' => 1]);
            $viewed = $this->createOwnProductVariant()->product;
            $other = $this->createOwnProductVariant()->product;
            foreach ([$viewed, $other] as $product) {
                DB::connection('tenant')->table('category_product')->insert(['category_id' => $category->id, 'product_id' => $product->id]);
            }
            $this->assertTrue(\Illuminate\Support\Facades\Schema::connection('tenant')->hasColumn('category_product', 'id'));
            session(['storefront_product_interactions' => ['viewed' => [$viewed->id], 'carted' => []]]);

            $ids = app(\App\Services\HomeProductService::class)->getInteractionBased(10)->pluck('id')->all();

            $this->assertContains($other->id, $ids);
            $this->assertNotContains($viewed->id, $ids);
        });
    }

    /** E2E finding 8: tenant badge sort page and badge assignment save resolved product KEYS against the copy table. */
    public function test_tenant_badge_pages_use_merged_keys_in_central_mode(): void
    {
        CentralProduct::withoutEvents(function () {
            foreach (range(1, 5) as $i) {
                CentralProduct::query()->create(['slug' => "phase8-badge-{$i}", 'sku' => "P8-BADGE-{$i}", 'status' => 'draft', 'base_price' => 1]);
            }
        });
        $x = $this->tenant->run(fn () => $this->createCentralCatalogVariant(price: 40.0));
        tenancy()->end();
        $badge = \App\Models\ProductBadge::query()->firstOrCreate(['text' => 'featured'], ['active' => true]);
        DB::table('product_badge_product')->insert(['product_badge_id' => $badge->id, 'product_id' => $x['central_product']->id, 'country_id' => null, 'sort_order' => 0]);
        foreach (['badges', 'labels', 'products'] as $section) {
            (new DedupRunner())->run($this->tenant, $section, 'migrate');
            tenancy()->end();
        }
        foreach (['badges', 'labels', 'flash-sales', 'categories', 'products'] as $section) {
            CatalogSource::set($this->tenant, $section, CatalogSource::CENTRAL);
        }
        CacheVersion::flushMemo();
        $centralId = (int) $x['central_product']->id;
        $this->assertNotSame($centralId, (int) $x['product']->id);

        $this->actingAsTenantAdmin()->get($this->tenantUrl('/admin/badges/featured/sort'))
            ->assertOk()
            ->assertSee('data-id="' . $centralId . '"', false);

        $this->actingAsTenantAdmin()->postJson($this->tenantUrl('/admin/badges/featured/save'), ['product_ids' => [$centralId]])->assertOk();
        $this->tenant->run(function () use ($centralId) {
            $catalog = app(\App\Repositories\Tenant\Catalog\BadgeCatalog::class);
            $featured = $catalog->find('c:' . \App\Models\ProductBadge::query()->where('text', 'featured')->value('id'))
                ?? $catalog->all()->firstWhere('text', 'featured');
            $this->assertContains($centralId, $catalog->productIds($featured, null), 'the central product stays assigned after a save');
        });
    }
}
