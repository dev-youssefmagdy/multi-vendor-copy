<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Product as CentralProduct;
use App\Models\Tenant\Catalog\MergedProduct;
use App\Models\Tenant\Category;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Repositories\Tenant\Catalog\BadgeAssignments;
use App\Repositories\Tenant\Catalog\BadgeCatalog;
use App\Repositories\Tenant\Catalog\CategoryAssignments;
use App\Repositories\Tenant\Catalog\CategoryCatalog;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as Files;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan phase 5 writers: link writers (badges / categories / flash sales) store typed pairs for CENTRAL products, the
 * pivots accept NULL product_id, `{product}` route binding resolves keys, MergedProduct::update() writes override rows only,
 * and `catalog:dedup-migrate products --remap-carts` rewrites mobile cart ids (and back).
 */
class CatalogPhase5WritersTest extends TestCase
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
            foreach (['migrate', 'dry-run', 'verify'] as $step) {
                Files::delete(DedupRunner::reportPath('products', (string) $this->tenant->id, $step));
            }
        }
        CatalogRefColumns::flush();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    /** @return array{central: CentralProduct, product: MergedProduct} adopted central product in central mode */
    private function central(): array
    {
        $x = $this->tenant->run(fn () => $this->createCentralCatalogVariant(price: 100.0));
        tenancy()->end();
        foreach (['badges', 'labels', 'flash-sales', 'categories', 'products'] as $section) {
            CatalogSource::set($this->tenant, $section, CatalogSource::CENTRAL);
        }
        CacheVersion::flushMemo();
        $this->tenant->run(fn () => (new ProductCatalog())->adopt($x['central_product']->id));

        return ['central' => $x['central_product'], 'product' => $this->tenant->run(fn () => ProductCatalog::query()->where('central_product_id', $x['central_product']->id)->firstOrFail())];
    }

    public function test_route_binding_resolves_merged_keys_and_update_writes_overrides_only(): void
    {
        $x = $this->central();
        $this->tenant->run(function () use ($x) {
            $bound = (new Product())->resolveRouteBinding((string) $x['central']->id);
            $this->assertInstanceOf(MergedProduct::class, $bound);
            $this->assertTrue($bound->isCentral());

            $bound->update(['featured' => true, 'active' => false, 'price' => 999]); // price is derived: ignored
            $row = DB::connection('tenant')->table('tenant_product_overrides')->first();
            $this->assertSame(1, (int) $row->featured);
            $this->assertSame(0, (int) $row->active);

            $this->expectException(\LogicException::class);
            $bound->update(['slug' => 'nope']); // central field: read-only
        });
    }

    public function test_pivots_accept_null_product_id_and_link_writers_use_typed_pairs(): void
    {
        $x = $this->central();
        $this->tenant->run(function () use ($x) {
            $db = DB::connection('tenant');
            $key = (int) $x['product']->id;

            // own badge x central product
            $badge = ProductBadge::create(['text' => 'Hot', 'active' => true]);
            app(BadgeAssignments::class)->syncProduct($x['product'], ['t:' . $badge->id]);
            $row = $db->table('product_badge_product')->where('product_badge_id', $badge->id)->first();
            $this->assertNotNull($row);
            $this->assertNull($row->product_id);
            $this->assertSame('central', $row->product_type);
            $this->assertSame($x['central']->id, (int) $row->catalog_product_id);
            $this->assertSame([$key], app(BadgeCatalog::class)->productIds(new \App\Repositories\Tenant\Catalog\CatalogBadge(\App\Support\Catalog\CatalogRef::tenant($badge->id), 'Hot', true), null));

            // own category x central product
            $cat = Category::create(['active' => true, 'order_number' => 1]);
            $catKey = \App\Support\Catalog\CategoryKey::of(\App\Support\Catalog\CatalogRef::tenant($cat->id));
            CategoryCatalog::flushMemo();
            app(CategoryAssignments::class)->syncProduct($x['product'], [$catKey]);
            $link = $db->table('category_product')->where('category_id', $cat->id)->first();
            $this->assertNotNull($link);
            $this->assertNull($link->product_id);
            $this->assertSame('central', $link->product_type);
            $this->assertContains($key, app(CategoryCatalog::class)->productIds($catKey));

            // own flash sale x central product (toggle on / off)
            $sale = FlashSale::create(['discount_percentage' => 10, 'active' => true]);
            $catalog = new ProductCatalog();
            $this->assertTrue($catalog->toggleFlashSaleProduct($sale->id, $key));
            $this->assertSame(1, $db->table('flash_sale_product')->where('flash_sale_id', $sale->id)->whereNull('product_id')->where('catalog_product_id', $x['central']->id)->count());
            $this->assertFalse($catalog->toggleFlashSaleProduct($sale->id, $key));
            $this->assertSame(0, $db->table('flash_sale_product')->where('flash_sale_id', $sale->id)->count());
        });
    }

    public function test_cart_remap_rewrites_mobile_cart_ids_forward_and_back(): void
    {
        $this->tenant->run(fn () => $this->createOwnProductVariant()); // shifts tenant product ids away from central ids
        $x = $this->tenant->run(fn () => $this->createCentralCatalogVariant(price: 100.0));
        $customer = $this->tenant->run(fn () => $this->createCustomer());
        $this->tenant->run(fn () => DB::connection('tenant')->table('mobile_carts')->insert([
            'customer_id' => $customer->id, 'created_at' => now(), 'updated_at' => now(),
            'items' => json_encode([['product_id' => $x['product']->id, 'variant_id' => $x['variant']->id, 'qty' => 2,
                'product_type' => 'central', 'catalog_product_id' => $x['central_product']->id,
                'variant_type' => 'central', 'catalog_variant_id' => $x['central_variant']->id]]),
        ]));
        tenancy()->end();
        $runner = new DedupRunner();

        // blocked until the flip
        $blocked = $runner->run($this->tenant, 'products', 'migrate', ['remap_carts' => true]);
        $this->assertSame('failed', $blocked['status']);

        foreach (['badges', 'labels', 'flash-sales', 'categories', 'products'] as $section) {
            CatalogSource::set($this->tenant, $section, CatalogSource::CENTRAL);
        }
        $dry = $runner->run($this->tenant, 'products', 'dry-run', ['remap_carts' => true]);
        $this->assertGreaterThan(0, $dry['counts']['items_remapped']);
        $this->assertNotSame($x['product']->id, $x['central_product']->id, 'seed must make tenant copy ids differ from central ids');
        $before = $this->tenant->run(fn () => DB::connection('tenant')->table('mobile_carts')->value('items'));

        $this->assertSame($before, $this->tenant->run(fn () => DB::connection('tenant')->table('mobile_carts')->value('items')), 'dry run must not write');

        $runner->run($this->tenant, 'products', 'migrate', ['remap_carts' => true]);
        $items = json_decode((string) $this->tenant->run(fn () => DB::connection('tenant')->table('mobile_carts')->value('items')), true);
        $this->assertSame($x['central_product']->id, $items[0]['product_id']);
        $this->assertSame($x['central_variant']->id, $items[0]['variant_id']);

        $runner->run($this->tenant, 'products', 'migrate', ['remap_carts' => true, 'reverse' => true]);
        $items = json_decode((string) $this->tenant->run(fn () => DB::connection('tenant')->table('mobile_carts')->value('items')), true);
        $this->assertSame($x['product']->id, $items[0]['product_id']);
        $this->assertSame($x['variant']->id, $items[0]['variant_id']);
    }

    public function test_sync_check_reports_cart_items_without_a_typed_ref(): void
    {
        $x = $this->tenant->run(fn () => $this->createCentralCatalogVariant(price: 10.0));
        $customer = $this->tenant->run(fn () => $this->createCustomer());
        $this->tenant->run(fn () => DB::connection('tenant')->table('mobile_carts')->insert([
            'customer_id' => $customer->id, 'created_at' => now(), 'updated_at' => now(),
            'items' => json_encode([['product_id' => $x['product']->id, 'qty' => 1]]),
        ]));
        tenancy()->end();
        $m = (new \App\Services\Catalog\CatalogSyncCheck())->run([(string) $this->tenant->id], ['products'])[0]['sections']['products'];
        $this->assertSame(1, $m['mobile_cart_items_without_ref']);
    }

    public function test_own_product_routes_bind_real_rows_and_delete_purges_refs_by_pair(): void
    {
        $x = $this->central();
        $this->tenant->run(function () use ($x) {
            $own = Product::create(['slug' => 'own-one', 'is_own_product' => true, 'active' => true, 'price' => ['default' => 5], 'default_price' => 5]);
            $route = (new \Illuminate\Routing\Route(['GET'], '/own-products/{product}/edit', []))->name('tenant.own-products.edit');
            request()->setRouteResolver(fn () => $route);

            $this->assertSame($own->id, (int) (new Product())->resolveRouteBinding((string) $own->id)->id);
            $this->assertFalse((new Product())->resolveRouteBinding((string) $own->id) instanceof MergedProduct);
            $viaKey = (new Product())->resolveRouteBinding((string) (\App\Support\Catalog\ProductKey::OWN_OFFSET + $own->id));
            $this->assertSame($own->id, (int) $viaKey->id);

            $db = DB::connection('tenant');
            $db->table('favorites')->insert(['customer_id' => $this->createCustomer()->id, 'product_id' => $own->id, 'product_type' => 'tenant', 'catalog_product_id' => $own->id]);
            ProductCatalog::purgeOwnProductRefs($own->id);
            $this->assertSame(0, $db->table('favorites')->where('catalog_product_id', $own->id)->count());
        });
    }

    public function test_central_rows_store_social_and_ai_price_data_and_read_only_fields_do_not_crash(): void
    {
        $x = $this->central();
        $this->tenant->run(function () use ($x) {
            $x['product']->update(['social_posts' => ['en' => ['instagram' => 'Hi']], 'ai_price_data' => ['avg' => 12]]);
            $fresh = ProductCatalog::query()->where('central_product_id', $x['central']->id)->firstOrFail();
            $this->assertSame('Hi', $fresh->social_posts['en']['instagram']);
            $this->assertSame(12, $fresh->ai_price_data['avg']);

            try {
                $fresh->update(['sku' => 'X']);
                $this->fail('expected ReadOnlyCatalogRowException');
            } catch (\App\Support\Catalog\ReadOnlyCatalogRowException $e) {
                $this->assertNotSame('', $e->userMessage());
            }
        });
    }

    public function test_panel_raw_sql_readers_run_through_the_merged_catalog(): void
    {
        $x = $this->central();
        $this->tenant->run(function () use ($x) {
            $repo = app(\App\Repositories\Tenant\TenantPanelRepository::class);
            $found = $repo->searchLinkableProducts('', 1, 15);
            $this->assertContains((int) $x['central']->id, collect($found->items())->pluck('id')->map(fn ($i) => (int) $i)->all());
            $this->assertIsArray($repo->productProfitabilityRows(10));
        });
    }
}
