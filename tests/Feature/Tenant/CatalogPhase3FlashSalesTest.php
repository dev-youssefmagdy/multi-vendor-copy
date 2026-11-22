<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\CentralFlashSale;
use App\Models\Country;
use App\Models\Tenant\Catalog\MergedFlashSale;
use App\Models\Tenant\Catalog\MergedProductFlashSale;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\TenantCountry;
use App\Repositories\Tenant\Catalog\FlashSaleCatalog;
use App\Services\Catalog\CatalogSyncCheck;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\Tenant\Checkout\OrderComposer;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\OrderPricing;
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
 * Dedup plan phase 3 — flash sales (plan §4 DROP): merged FlashSaleCatalog (live central sales of carried products ∪ own
 * sales), Product::flashSales() / storefront pricing / discount_source in central mode, SQL discount expression, home
 * strip, panel list, order lines identical sync vs central (central flash line, vendor flash line, mixed cart) with the
 * profit invariant, flag off unchanged, sync skip, `catalog:dedup-migrate flash_sales` dry-run / idempotent / verify 0.
 */
class CatalogPhase3FlashSalesTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    private Country $served;

    private Country $other;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        CatalogRefColumns::flush();
        $this->setUpTenantPanel();
        $this->served = Country::forceCreate(['name' => 'Egypt', 'iso2' => 'EG']);
        $this->other = Country::forceCreate(['name' => 'Peru', 'iso2' => 'PE']);
        TenantCountry::create(['tenant_id' => $this->tenant->id, 'country_id' => $this->served->id, 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        if (isset($this->tenant)) {
            tenancy()->end();
            if (Schema::hasTable('catalog_dedup_runs')) {
                DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->delete();
            }
            foreach (['migrate', 'dry-run', 'verify'] as $step) {
                File::delete(DedupRunner::reportPath('flash_sales', (string) $this->tenant->id, $step));
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

    private function flip(string $mode = CatalogSource::CENTRAL): void
    {
        tenancy()->end();
        CatalogSource::set($this->tenant, 'flash-sales', $mode);
    }

    private function centralSale(array $centralProductIds, float $pct, array $attributes = [], array $countryIds = []): CentralFlashSale
    {
        tenancy()->end();

        return Model::withoutEvents(function () use ($centralProductIds, $pct, $attributes, $countryIds) {
            $sale = CentralFlashSale::query()->create(array_merge(['discount_percentage' => $pct, 'active' => true], $attributes));
            foreach ($centralProductIds as $id) {
                DB::table('central_flash_sale_product')->insert(['central_flash_sale_id' => $sale->id, 'product_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
            }
            foreach ($countryIds as $id) {
                DB::table('central_flash_sale_country')->insert(['central_flash_sale_id' => $sale->id, 'country_id' => $id]);
            }

            return $sale;
        });
    }

    private function ownSale(array $productIds, float $pct, array $attributes = [], array $countryIds = []): FlashSale
    {
        $sale = FlashSale::query()->create(array_merge(['product_id' => $productIds[0], 'discount_percentage' => $pct, 'active' => true], $attributes));
        $sale->products()->sync($productIds);
        \App\Support\Catalog\CatalogRefWriter::fillQuietly('flash_sale_product');
        if ($countryIds !== []) {
            $sale->syncCountries($countryIds);
        }

        return $sale;
    }

    private function syncFlashSales(): void
    {
        tenancy()->end();
        try {
            app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'flash-sales');
        } finally {
            tenancy()->end();
        }
    }

    /** @return array{a: array, b: array, own: \App\Models\Tenant\ProductVariant} */
    private function products(): array
    {
        return $this->inTenant(fn () => [
            'a' => $this->createCentralCatalogVariant(price: 40.0),
            'b' => $this->createCentralCatalogVariant(price: 30.0),
            'own' => $this->createOwnProductVariant(price: 25.0),
        ]);
    }

    private function pricing(int $productId): array
    {
        $product = Product::query()->findOrFail($productId);
        $sale = $product->activeStorefrontFlashSale();

        return [
            'pct' => (float) ($sale?->discount_percentage ?? 0),
            'source' => $sale === null ? null : FlashSaleCatalog::discountSource($sale),
        ];
    }

    public function test_flag_off_keeps_the_synced_relation(): void
    {
        $p = $this->products();
        $this->inTenant(function () use ($p) {
            $this->assertFalse(FlashSaleCatalog::enabled());
            $this->assertInstanceOf(BelongsToMany::class, $p['a']['product']->flashSales());
        });
    }

    public function test_merged_read_sources_windows_countries_and_sql_discount(): void
    {
        $p = $this->products();
        $central = $this->centralSale([$p['a']['central_product']->id], 10.0);
        $this->centralSale([$p['a']['central_product']->id], 50.0, ['active' => false]);
        $this->centralSale([$p['a']['central_product']->id], 60.0, ['end_date' => now()->subDay()]);
        $this->centralSale([$p['b']['central_product']->id], 20.0, [], [$this->other->id]);
        $notCarried = Model::withoutEvents(fn () => \App\Models\Product::query()->create([
            'slug' => 'not-carried-' . uniqid(), 'sku' => 'NC-' . uniqid(), 'status' => \App\Enums\ProductStatus::Published, 'base_price' => 10,
        ]));
        $this->centralSale([$notCarried->id], 70.0); // product the tenant does not carry: invisible
        $trashed = $this->centralSale([$p['b']['central_product']->id], 80.0);
        Model::withoutEvents(fn () => $trashed->delete());

        $this->inTenant(function () use ($p) {
            $this->ownSale([$p['own']->product_id, $p['b']['product']->id], 15.0);
            // Legacy copy of a central sale: ignored in central mode.
            FlashSale::query()->create(['central_flash_sale_id' => 424242, 'product_id' => $p['b']['product']->id, 'discount_percentage' => 90, 'active' => true])
                ->products()->sync([$p['b']['product']->id]);
        });
        $this->flip();

        $this->inTenant(function () use ($p, $central) {
            $this->assertTrue(FlashSaleCatalog::enabled());
            $this->assertInstanceOf(HasMany::class, $p['a']['product']->flashSales());

            $this->assertSame(['pct' => 10.0, 'source' => OrderPricing::SOURCE_CENTRAL], $this->pricing($p['a']['product']->id));
            $this->assertSame(['pct' => 15.0, 'source' => OrderPricing::SOURCE_VENDOR], $this->pricing($p['own']->product_id));
            // b: own 15 % everywhere; central 20 % only in the other country.
            $this->assertSame(['pct' => 15.0, 'source' => OrderPricing::SOURCE_VENDOR], $this->pricing($p['b']['product']->id));

            $eager = Product::query()->whereKey($p['b']['product']->id)
                ->with(['flashSales' => fn ($q) => $q->activeWindow()->forCountry($this->other->id)->orderByDesc('discount_percentage')])
                ->first();
            $this->assertInstanceOf(MergedProductFlashSale::class, $eager->flashSales->first());
            $this->assertSame(['20.00', '15.00'], $eager->flashSales->pluck('discount_percentage')->all());

            $sql = fn (?int $country) => DB::table('products')->selectRaw('products.id, (' . FlashSaleCatalog::discountExpression($country) . ') AS d')
                ->orderBy('products.id')->pluck('d', 'id')->map(fn ($v) => (float) $v)->all();
            $this->assertSame([$p['a']['product']->id => 10.0, $p['b']['product']->id => 15.0, $p['own']->product_id => 15.0], $sql($this->served->id));
            $this->assertSame(20.0, $sql($this->other->id)[$p['b']['product']->id]);

            $this->assertSame(3, Product::query()->whereHas('flashSales', fn ($q) => $q->activeWindow()->forCountry($this->served->id))->count());

            $sales = app(FlashSaleCatalog::class)->sales(activeOnly: true, country: $this->served->id);
            $this->assertEqualsCanonicalizing(['c:' . $central->id, $sales->firstWhere('source', 'tenant')->id], $sales->pluck('id')->all());
            $this->assertInstanceOf(MergedFlashSale::class, $sales->first());
            $this->assertCount(2, $sales->firstWhere('source', 'tenant')->products);
            $this->assertSame([$p['a']['product']->id], $sales->firstWhere('source', 'central')->products->pluck('id')->all());
        });

        // Panel list (central read-only + own).
        $rows = $this->actingAsTenantAdmin()->getJson($this->tenantUrl('/admin/store/flash-sales/data?draw=1&start=0&length=10'))->assertOk()->json('data');
        $this->assertCount(5, $rows, 'live central sales with carried products (4) + own (1)');
        $this->assertSame(4, collect($rows)->filter(fn ($r) => str_contains($r['actions'], 'Managed by platform'))->count());
    }

    public function test_order_lines_identical_between_sync_and_central_mode(): void
    {
        $p = $this->products();
        $this->centralSale([$p['a']['central_product']->id], 10.0);
        $this->inTenant(fn () => $this->ownSale([$p['own']->product_id, $p['b']['product']->id], 20.0));
        $this->syncFlashSales(); // sync-mode copies of the central sale

        $run = fn () => $this->inTenant(function () use ($p) {
            $lines = [];
            $cart = [];
            foreach (['a' => $p['a']['variant'], 'b' => $p['b']['variant'], 'own' => $p['own']] as $k => $variant) {
                $product = Product::query()->findOrFail($variant->product_id);
                $line = OrderComposer::line($product, $variant, 2, null);
                $lines[$k] = $line;
                $cart[] = ['key' => 'v_' . $variant->id, 'product' => $product, 'variant' => $variant, 'product_variant_id' => $variant->id, 'line' => $line];
            }
            $created = app(OrderComposer::class)->create($cart, [], [
                'customer' => $this->createCustomer(), 'coupon' => null, 'gateway' => null, 'gateway_id' => null,
                'shipping_amount' => 3.0, 'zone_rate_id' => null, 'shipping_address' => ['name' => 'T', 'address' => '1', 'city' => 'C'],
            ]);
            $orders = [];
            foreach (array_filter([$created['primary'], $created['companion']]) as $order) {
                $order = Order::query()->findOrFail($order->id);
                $this->assertEqualsWithDelta((float) $order->grand_total, (float) $order->platform_profit + (float) $order->vendor_profit, 0.011);
                $orders[] = array_map(fn ($v) => round((float) $v, 2), $order->only(['platform_profit', 'vendor_profit', 'shipping_charge'])) + ['grand' => round((float) $order->grand_total, 2)];
            }

            return [$lines, $orders];
        });

        [$syncLines, $syncOrders] = $run();
        $this->flip();
        [$centralLines, $centralOrders] = $run();

        $this->assertEquals($syncLines, $centralLines, 'lines identical (discount + discount_source)');
        $this->assertEquals($syncOrders, $centralOrders, 'orders identical (mixed cart → 2 orders)');
        $this->assertCount(2, $centralOrders);
        $this->assertSame(OrderPricing::SOURCE_CENTRAL, $centralLines['a']['discount_source']);
        $this->assertSame(OrderPricing::SOURCE_VENDOR, $centralLines['b']['discount_source']);
        $this->assertSame(OrderPricing::SOURCE_VENDOR, $centralLines['own']['discount_source']);
        $this->assertGreaterThan(0, $centralLines['a']['discount']);
    }

    public function test_sync_skips_central_mode_tenants(): void
    {
        $p = $this->products();
        $this->flip();
        $this->centralSale([$p['a']['central_product']->id], 10.0);
        $this->syncFlashSales();

        $this->inTenant(fn () => $this->assertSame(0, FlashSale::query()->count()));
    }

    public function test_dedup_migrate_dry_run_idempotent_and_verify_zero(): void
    {
        $p = $this->products();
        $this->centralSale([$p['a']['central_product']->id, $p['b']['central_product']->id], 10.0, [], [$this->served->id]);
        $this->centralSale([$p['b']['central_product']->id], 25.0);
        $this->inTenant(fn () => $this->ownSale([$p['own']->product_id], 5.0, [], [$this->other->id]));
        $this->syncFlashSales();
        $this->inTenant(fn () => DB::table('flash_sale_product')->update(['flash_sale_type' => null, 'catalog_flash_sale_id' => null]));

        $dry = app(DedupRunner::class)->run($this->tenant, 'flash_sales', 'dry-run');
        $this->assertSame('done', $dry['status']);
        $this->assertSame(4, $dry['counts']['pivot_refs_filled'], 'would fill');
        $this->inTenant(fn () => $this->assertSame(4, DB::table('flash_sale_product')->whereNull('flash_sale_type')->count(), 'dry run writes nothing'));

        $this->artisan('catalog:dedup-migrate', ['section' => 'flash_sales', '--tenant' => [$this->tenant->id]])->assertExitCode(0);
        $again = app(DedupRunner::class)->run($this->tenant, 'flash_sales', 'migrate');
        $this->assertSame(0, $again['counts']['pivot_refs_filled'], 'idempotent');
        $this->assertSame(2, $again['counts']['copies']);

        $this->artisan('catalog:dedup-migrate', ['section' => 'flash_sales', '--tenant' => [$this->tenant->id], '--verify' => true])->assertExitCode(0);
        $verify = json_decode(File::get(DedupRunner::reportPath('flash_sales', (string) $this->tenant->id, 'verify')), true);
        $this->assertSame(0, $verify['diffs'], json_encode($verify));
        $this->assertGreaterThan(0, $verify['counts']['discount_checks']);

        // A drifted copy is a diff (run the sync before verifying).
        $this->inTenant(fn () => FlashSale::query()->whereNotNull('central_flash_sale_id')->update(['discount_percentage' => 99]));
        $drift = app(DedupRunner::class)->run($this->tenant, 'flash_sales', 'verify');
        $this->assertGreaterThan(0, $drift['diffs']);

        $metrics = app(CatalogSyncCheck::class)->run([(string) $this->tenant->id], ['flash-sales'])[0]['sections']['flash-sales'];
        $this->assertSame(0, $metrics['pairs_missing_in_copy']);
        $this->assertSame(2, $metrics['stale_copies']);
        $this->assertSame(0, $metrics['country_mismatch']);
        $this->assertGreaterThan(0, $metrics['dedup_verify_diffs']);
    }

    public function test_d_p3_1_home_and_price_order_caches_carry_the_central_flash_sale_tag(): void
    {
        $this->flip();
        request()->setLaravelSession(app('session')->driver('array'));
        $this->inTenant(function () {
            $repo = new class extends \App\Repositories\Tenant\StorefrontRepository {
                public array $seen = [];
                protected function cacheRemember(string $key, array $tags, \Closure $callback, ?int $ttl = null): mixed
                {
                    $this->seen[$key] = $tags;
                    return $callback();
                }
            };
            $repo->flashProductsByPrice();
            $key = array_key_first(array_filter($repo->seen, fn ($t, $k) => str_starts_with($k, 'flash_products_by_price_'), ARRAY_FILTER_USE_BOTH));
            $this->assertNotNull($key);
            $this->assertContains(FlashSaleCatalog::CACHE_TAG, $repo->seen[$key]);
        });
    }
}
