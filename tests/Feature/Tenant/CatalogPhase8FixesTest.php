<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\OrderStatus;
use App\Livewire\Tenant\Storefront\OrderStatusPage;
use App\Models\Product as CentralProduct;
use App\Models\Tenant\ProductRate;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\Tenant\TemplateRegistryService;
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
 * Regression tests for the two bugs Phase 8 left open (dedup plan §12 "Phase 8 fixes"):
 * B-P8-3 (OrderStatusPage review / reorder on central lines) and B-P8-7 (panel product edit in central products mode).
 */
class CatalogPhase8FixesTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    /** @var array{central_product: CentralProduct, central_variant: \App\Models\ProductVariant} */
    private array $x;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        config(['cache.stores.file.path' => sys_get_temp_dir() . '/catalog-phase8fix-test-cache']);
        Cache::forgetDriver('file');
        CatalogRefColumns::flush();
        $this->setUpTenantPanel();
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());

        $this->x = $this->tenant->run(fn () => $this->createCentralCatalogVariant(price: 40.0));
        tenancy()->end();
        app(TenantCatalogSyncService::class)->syncProductToTenant(CentralProduct::query()->findOrFail($this->x['central_product']->id), $this->tenant, true);
        tenancy()->end();
        (new DedupRunner())->run($this->tenant, 'products', 'migrate');
        tenancy()->end();
        foreach (['badges', 'labels', 'flash-sales', 'categories', 'products'] as $section) {
            CatalogSource::set($this->tenant, $section, CatalogSource::CENTRAL);
        }
        CacheVersion::flushMemo();
    }

    protected function tearDown(): void
    {
        TemplateRegistryService::clearForce();
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

    /** @return array{0: \App\Models\Tenant\Customer, 1: \App\Models\Tenant\Order} delivered order with ONE central-mode line (no tenant product id) */
    private function deliveredCentralOrder(): array
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, [], OrderStatus::Delivered);
        DB::connection('tenant')->table('order_items')->insert([
            'order_id' => $order->id, 'product_id' => null, 'product_variant_id' => null, 'qty' => 2, 'price' => 40,
            'sub_total' => 80, 'discount' => 0, 'tax' => 0, 'weight' => 0, 'shipping_fee' => 0, 'grand_total' => 80,
            'product_type' => 'central', 'catalog_product_id' => $this->x['central_product']->id,
            'variant_type' => 'central', 'catalog_variant_id' => $this->x['central_variant']->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$customer, $order];
    }

    /** Rollout finding: best-selling / trending / sold counts joined order_items.product_id (tenant id) to merged keys. */
    public function test_sales_queries_credit_central_lines_to_the_merged_product_key(): void
    {
        $this->deliveredCentralOrder();

        $this->tenant->run(function () {
            $this->assertTrue(ProductCatalog::enabled());
            $key = (int) $this->x['central_product']->id; // a central product's merged key is its central id

            $sales = \App\Services\HomeProductService::salesSubquery(30)->get()->keyBy('sold_product_id');
            $this->assertSame(2, (int) ($sales[$key]->qty_all ?? 0), 'the central line is credited to its merged key');

            $top = \App\Services\HomeProductService::applyBestSellingOrder(ProductCatalog::query()->where('active', true), 30)->first();
            $this->assertSame($key, (int) $top->id, 'the sold central product ranks first');

            $sold = app(\App\Services\Tenant\HomeViewData::class)->soldQtyLast30Days([$key], 0);
            $this->assertSame(2, (int) ($sold[$key] ?? 0));
        });
    }

    public function test_order_status_review_and_reorder_resolve_central_lines_by_typed_pair(): void
    {
        $this->tenant->run(function () {
            $this->assertTrue(ProductCatalog::enabled());
            [$customer, $order] = $this->deliveredCentralOrder();
            $this->actingAs($customer, 'storefront');
            view()->share('storefrontThemeVariant', null);
            // Livewire::test() builds no web request: detection needs a session request: stub it out.
            $this->partialMock(\App\Services\CountryDetectorService::class, fn ($m) => $m->shouldReceive('detect')->andReturn(null));
            $key = (int) $this->x['central_product']->id;
            $grandBefore = $order->fresh()->items->sum('grand_total');

            foreach (['elora', 'souqify'] as $i => $theme) {
                TemplateRegistryService::force($theme);
                session()->forget('storefront_cart');

                $page = Livewire::test(OrderStatusPage::class, ['uuid' => $order->uuid])
                    ->call('openReviewModal')
                    ->assertSet('reviewProductId', $key)
                    ->set('reviewStars', 4 - $i)
                    ->set('reviewComment', "great {$theme}")
                    ->call('submitReview')
                    ->assertHasNoErrors()
                    ->assertDispatched('order-status-swal');

                // the rating is stored by typed pair (no tenant product id) and is edited, not duplicated, the 2nd time
                $this->assertSame(1, ProductRate::query()->where('product_type', 'central')->where('catalog_product_id', $key)->count(), $theme);
                $this->assertSame(4 - $i, (int) ProductRate::query()->where('catalog_product_id', $key)->value('stars'));

                // reorder: cart entry keyed by merged product / variant key, same as the product page
                $page->call('reorder')->assertDispatched('cartUpdated');
                $cart = session('storefront_cart');
                $this->assertSame(2, (int) collect($cart)->sum('qty'), $theme);
                $entry = collect($cart)->first();
                $this->assertSame($key, (int) $entry['product_id']);
                $this->assertSame((int) $this->x['central_variant']->id, (int) $entry['variant_id']);
            }

            // an order line can only rate its own product
            Livewire::test(OrderStatusPage::class, ['uuid' => $order->uuid])
                ->call('openReviewModal')->set('reviewProductId', $key + 12345)->call('submitReview')
                ->assertDispatched('order-status-swal', message: __('Invalid product selected.'), type: 'error');

            // money snapshot untouched
            $this->assertEquals($grandBefore, $order->fresh()->items->sum('grand_total'));
        });
    }

    public function test_panel_edit_of_a_central_product_saves_overrides_and_submits_an_edit_request(): void
    {
        $key = (int) $this->x['central_product']->id;
        $url = $this->tenantUrl("/admin/products/{$key}");

        $before = $this->tenant->run(fn () => [
            'row' => ProductCatalog::query()->where('central_product_id', $key)->first(),
            'copies' => \App\Models\Tenant\Product::withoutGlobalScopes()->count(),
        ]);
        $this->assertNotNull($before['row']);
        $centralSlug = $before['row']->slug;
        $newPrice = round((float) $before['row']->default_price + 7, 2);

        $res = $this->actingAsTenantAdmin()->putJson($url, [
            'active_locale' => 'en',
            'central_product_id' => $key,
            'slug' => 'hacked-slug',                       // platform-owned: must be ignored
            'price' => $newPrice,
            'active' => '1',
            'featured' => '1',
            'stock_override' => 5,
            'weight_grams_override' => 123,
            'translations' => ['en' => ['name' => 'Vendor wants a new name', 'description' => '']],
            'variants' => [[
                'id' => $this->x['central_variant']->id,    // merged variant key, not a tenant row id
                'central_product_variant_id' => $this->x['central_variant']->id,
                'real_price' => 40, 'sell_price' => 55, 'active' => '1',
            ]],
        ]);
        $res->assertOk();

        $this->tenant->run(function () use ($key, $centralSlug, $newPrice, $before) {
            $row = ProductCatalog::query()->where('central_product_id', $key)->first();
            $this->assertSame($centralSlug, $row->slug, 'slug is central-owned');
            $this->assertTrue((bool) $row->featured);
            $this->assertEquals($newPrice, round((float) $row->default_price, 2), 'vendor sale price derived from the stored profit override');
            $ov = DB::connection('tenant')->table('tenant_product_overrides')->where('central_product_id', $key)->first();
            $this->assertSame(5, (int) $ov->stock_override);
            $this->assertSame(123, (int) $ov->weight_grams_override);
            $this->assertNotNull($ov->profit);
            $this->assertSame($before['copies'], \App\Models\Tenant\Product::withoutGlobalScopes()->count(), 'no copy row created');
        });
        tenancy()->end();

        $request = \App\Models\ProductEditRequest::query()->where('tenant_id', $this->tenant->id)->where('central_product_id', $key)->where('status', 'pending')->first();
        $this->assertNotNull($request, 'name change became an edit request');
        $this->assertSame('Vendor wants a new name', data_get($request->requested_translations, 'en.name'));

        // central-owned data is untouched
        $this->assertSame($centralSlug, CentralProduct::query()->find($key)->slug);
    }

    public function test_panel_edit_form_renders_for_a_central_product_with_read_only_slug(): void
    {
        $key = (int) $this->x['central_product']->id;
        $html = $this->actingAsTenantAdmin()->get($this->tenantUrl("/admin/products/{$key}/edit"))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<input[^>]*name="slug"[^>]*disabled|<input[^>]*disabled[^>]*name="slug"/', $html);
        $this->assertStringContainsString('name="stock_override"', $html);
    }
}
