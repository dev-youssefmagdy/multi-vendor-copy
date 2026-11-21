<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\Tenant\CouponType;
use App\Models\CatalogDedupRun;
use App\Models\Category as CentralCategory;
use App\Models\Tenant\Category;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Services\Catalog\CatalogRefBackfill;
use App\Services\Tenant\Checkout\OrderComposer;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\OrderPricing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * QA, dedup plan phase 1 (typed catalog references): gaps not covered by CatalogPhase1RefsTest.
 *  - the FULL products sync (`syncProducts` → `syncCentralCategoryLinks`, insertOrIgnore) and the single-product push
 *    fill the category_product pairs;
 *  - order money + `platform_profit + vendor_profit = grand_total` for central / own / mixed carts with central and
 *    vendor coupons and central and vendor flash sales are identical with and without the phase-1 columns;
 *  - backfill dry-run issues no write except its catalog_dedup_runs row (query log), real run / re-run, report content,
 *    run rows, tenants whose database does not exist are skipped.
 */
class CatalogPhase1QaTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    private const TENANT_MIGRATION = 'database/migrations/tenant/2026_11_23_000001_add_catalog_refs_to_tenant_reference_tables.php';

    private ?string $ghostTenantId = null;

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
            foreach (array_filter([(string) $this->tenant->id, $this->ghostTenantId]) as $id) {
                DB::table('catalog_dedup_runs')->where('tenant_id', $id)->delete();
                DB::table('return_requests')->where('tenant_id', $id)->delete();
                foreach ([false, true] as $dry) {
                    File::delete(CatalogRefBackfill::reportPath($id, $dry));
                }
            }
            if ($this->ghostTenantId !== null) {
                DB::table('tenants')->where('id', $this->ghostTenantId)->delete();
            }
        }
        CatalogRefColumns::flush();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function central(callable $callback): mixed
    {
        tenancy()->end();

        return Model::withoutEvents($callback);
    }

    private function syncSection(string $section): void
    {
        try {
            app(TenantCatalogSyncService::class)->syncSection($this->tenant, $section);
        } finally {
            tenancy()->end();
        }
    }

    // ---- writers: full products sync + single push --------------------------------------------------------------

    public function test_full_products_sync_and_single_push_fill_category_link_refs(): void
    {
        $catalog = $this->tenant->run(fn () => $this->createCentralCatalogVariant(price: 40.0));
        $centralProductId = $catalog['central_product']->id;

        $category = $this->central(function () use ($centralProductId) {
            $category = CentralCategory::query()->create(['status' => 'published', 'is_featured' => false, 'order_number' => 1]);
            DB::table('category_product')->insert(['category_id' => $category->id, 'product_id' => $centralProductId]);

            return $category;
        });

        $this->syncSection('categories');
        $this->syncSection('products'); // TenantCatalogSyncService::syncProducts() → syncCentralCategoryLinks() (insertOrIgnore)

        $tenantProductId = $catalog['product']->id;
        $this->tenant->run(function () use ($tenantProductId, $centralProductId, $category) {
            $tenantCategory = Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)
                ->where('central_category_id', $category->id)->firstOrFail();
            $link = DB::table('category_product')->where('product_id', $tenantProductId)->where('category_id', $tenantCategory->id)->first();
            $this->assertNotNull($link, 'full products sync attached the central category link');
            $this->assertSame(['central', $centralProductId, 'central', $category->id],
                [$link->product_type, (int) $link->catalog_product_id, $link->category_type, (int) $link->catalog_category_id]);
            $this->assertSame(0, DB::table('category_product')->whereNull('product_type')->count());

            // Drop the link so the single-product push has to insert it again.
            DB::table('category_product')->where('product_id', $tenantProductId)->delete();
        });
        tenancy()->end();

        app(TenantCatalogSyncService::class)->syncProductToTenant($catalog['central_product']->fresh(), $this->tenant, force: true);
        tenancy()->end();

        $this->tenant->run(function () use ($tenantProductId, $centralProductId, $category) {
            $link = DB::table('category_product')->where('product_id', $tenantProductId)->first();
            $this->assertNotNull($link, 'single-product push re-attached the link');
            $this->assertSame(['central', $centralProductId, 'central', $category->id],
                [$link->product_type, (int) $link->catalog_product_id, $link->category_type, (int) $link->catalog_category_id]);
        });
    }

    // ---- order invariant ----------------------------------------------------------------------------------------

    /** @return array<string, array{0: array<int, array<string, mixed>>, 1: ?Coupon}> */
    private function orderScenarios(array $central, $own, Coupon $centralCoupon, Coupon $vendorCoupon): array
    {
        // Central line with a CENTRAL flash sale, central line with a VENDOR flash sale, own line with a VENDOR flash sale.
        $centralFlashCentral = OrderPricing::item(30.0, 10.0, 2, 5.0, 10.0, OrderPricing::SOURCE_CENTRAL) + ['is_own' => false];
        $centralFlashVendor = OrderPricing::item(30.0, 10.0, 1, 5.0, 15.0, OrderPricing::SOURCE_VENDOR) + ['is_own' => false];
        $ownFlashVendor = OrderPricing::item(0.0, 25.0, 3, 0.0, 20.0, OrderPricing::SOURCE_VENDOR) + ['is_own' => true];

        $cLine = fn (array $line) => ['key' => 'v_' . $central['variant']->id, 'product' => $central['product'], 'variant' => $central['variant'], 'product_variant_id' => $central['variant']->id, 'line' => $line];
        $oLine = fn (array $line) => ['key' => 'v_' . $own->id, 'product' => $own->product, 'variant' => $own, 'product_variant_id' => $own->id, 'line' => $line];

        $carts = [
            'central' => [$cLine($centralFlashCentral), $cLine($centralFlashVendor)],
            'own' => [$oLine($ownFlashVendor)],
            'mixed' => [$cLine($centralFlashCentral), $cLine($centralFlashVendor), $oLine($ownFlashVendor)],
        ];

        $out = [];
        foreach ($carts as $cart => $lines) {
            foreach (['none' => null, 'central-coupon' => $centralCoupon, 'vendor-coupon' => $vendorCoupon] as $c => $coupon) {
                $out["{$cart}/{$c}"] = [$lines, $coupon];
            }
        }

        return $out;
    }

    /** Money columns of the order(s) one cart produced. */
    private function placeAndSnapshot($customer, array $lines, ?Coupon $coupon): array
    {
        $created = app(OrderComposer::class)->create($lines, [], [
            'customer' => $customer, 'coupon' => $coupon, 'gateway' => null, 'gateway_id' => null,
            'shipping_amount' => 4.25, 'zone_rate_id' => null,
            'shipping_address' => ['name' => 'T', 'address' => '1 St', 'city' => 'C'],
        ]);

        $snap = [];
        foreach (array_filter([$created['primary'], $created['companion']]) as $order) {
            $order = Order::query()->with('items')->findOrFail($order->id);
            $grand = (float) $order->grand_total;
            $this->assertEqualsWithDelta($grand, (float) $order->platform_profit + (float) $order->vendor_profit, 0.011,
                "order {$order->id}: platform_profit + vendor_profit = order_grand_total");

            // Independent recomputation with OrderPricing (formulas unchanged).
            $expected = OrderPricing::order($order->items->map(fn (OrderItem $i) => $i->only([
                'cost', 'profit_amount', 'qty', 'fixed_shipping', 'discount', 'discount_source', 'tax', 'grand_total',
            ]))->all(), (float) $order->shipping_charge, (float) $order->coupon_discount, $order->coupon_source);
            $this->assertEqualsWithDelta($expected['platform_profit'], (float) $order->platform_profit, 0.001);
            $this->assertEqualsWithDelta($expected['vendor_profit'], (float) $order->vendor_profit, 0.001);
            $this->assertEqualsWithDelta($expected['grand_total'], $grand, 0.001);

            $snap[] = [
                'order' => array_map(fn ($v) => is_numeric($v) ? round((float) $v, 2) : $v, $order->only([
                    'shipping_charge', 'coupon_discount', 'coupon_source', 'discount_percentage', 'platform_profit', 'vendor_profit', 'collected_by',
                ])) + ['grand_total' => round($grand, 2)],
                'items' => $order->items->map(fn (OrderItem $i) => array_map(fn ($v) => is_numeric($v) ? round((float) $v, 2) : $v, $i->only([
                    'product_id', 'product_variant_id', 'cost', 'profit_amount', 'qty', 'price', 'sub_total', 'fixed_shipping',
                    'discount', 'discount_source', 'tax', 'shipping_fee', 'grand_total',
                ])))->all(),
                'refs' => $order->items->map(fn (OrderItem $i) => [$i->getAttribute('product_type'), $i->getAttribute('variant_type')])->all(),
            ];
        }

        return $snap;
    }

    public function test_profit_invariant_with_coupons_and_flash_sales_is_unchanged_by_phase1(): void
    {
        $this->tenant->run(function () {
            $central = $this->createCentralCatalogVariant(price: 40.0);
            $own = $this->createOwnProductVariant(price: 25.0);
            $customer = $this->createCustomer();
            $centralCoupon = Coupon::query()->create(['central_coupon_id' => 991, 'code' => 'QA-P1-C', 'type' => CouponType::Percentage, 'value' => 10]);
            $vendorCoupon = Coupon::query()->create(['code' => 'QA-P1-V', 'type' => CouponType::Fixed, 'value' => 7.5]);
            $scenarios = $this->orderScenarios($central, $own, $centralCoupon, $vendorCoupon);

            $with = [];
            foreach ($scenarios as $name => [$lines, $coupon]) {
                $with[$name] = $this->placeAndSnapshot($customer, $lines, $coupon);
            }

            $migration = require base_path(self::TENANT_MIGRATION);
            $migration->down();
            CatalogRefColumns::flush();
            try {
                $without = [];
                foreach ($scenarios as $name => [$lines, $coupon]) {
                    $without[$name] = $this->placeAndSnapshot($customer, $lines, $coupon);
                }
            } finally {
                $migration->up();
                CatalogRefColumns::flush();
            }

            foreach ($scenarios as $name => $_) {
                $strip = fn (array $orders) => array_map(fn ($o) => ['order' => $o['order'], 'items' => $o['items']], $orders);
                $this->assertEquals($strip($without[$name]), $strip($with[$name]), "{$name}: money identical with and without phase-1 columns");
                $this->assertSame(str_starts_with($name, 'mixed') ? 2 : 1, count($with[$name]), "{$name}: order count");

                foreach ($with[$name] as $order) {
                    foreach ($order['refs'] as [$productType, $variantType]) {
                        $this->assertContains($productType, ['central', 'tenant'], "{$name}: product ref written");
                        $this->assertSame($productType, $variantType);
                    }
                }
            }

            // Spot-check the source split survives: central line refs are central, own line refs tenant.
            $this->assertSame([['central', 'central'], ['central', 'central']], $with['central/none'][0]['refs']);
            $this->assertSame([['tenant', 'tenant']], $with['own/vendor-coupon'][0]['refs']);
            $this->assertSame('central', $with['mixed/central-coupon'][0]['order']['coupon_source']);
            $this->assertSame('vendor', $with['mixed/vendor-coupon'][1]['order']['coupon_source']);
        });
    }

    // ---- backfill command QC ----------------------------------------------------------------------------------

    public function test_backfill_dry_run_writes_nothing_and_missing_tenant_databases_are_skipped(): void
    {
        $tenantId = (string) $this->tenant->id;
        $this->tenant->run(function () {
            $central = $this->createCentralCatalogVariant(price: 40.0);
            $own = $this->createOwnProductVariant(price: 25.0);
            $customer = $this->createCustomer();
            app(OrderComposer::class)->create([
                ['key' => 'a', 'product' => $central['product'], 'variant' => $central['variant'], 'product_variant_id' => $central['variant']->id,
                    'line' => OrderPricing::item(30.0, 10.0, 1, 0.0, 0.0, null) + ['is_own' => false]],
                ['key' => 'b', 'product' => $own->product, 'variant' => $own, 'product_variant_id' => $own->id,
                    'line' => OrderPricing::item(0.0, 25.0, 1) + ['is_own' => true]],
            ], [], ['customer' => $customer, 'coupon' => null, 'gateway' => null, 'gateway_id' => null,
                'shipping_amount' => 0, 'zone_rate_id' => null, 'shipping_address' => ['name' => 'T']]);
            $order = Order::query()->firstOrFail();
            DB::table('order_items')->insert(['order_id' => $order->id, 'product_id' => 654321, 'qty' => 2, 'price' => 1, 'sub_total' => 2, 'grand_total' => 2, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('order_items')->update(['product_type' => null, 'catalog_product_id' => null, 'variant_type' => null, 'catalog_variant_id' => null]);
        });
        tenancy()->end();

        // A tenants row whose database was never created (e.g. failed provisioning).
        $this->ghostTenantId = (string) Str::uuid();
        DB::table('tenants')->insert(['id' => $this->ghostTenantId, 'data' => '{}', 'created_at' => now(), 'updated_at' => now()]);

        // Dry run: the query log shows no write except the run row.
        $central = DB::connection(config('tenancy.database.central_connection'));
        $central->flushQueryLog();
        $central->enableQueryLog();
        $this->artisan('catalog:backfill-refs', ['--tenant' => [$tenantId, $this->ghostTenantId], '--dry-run' => true])->assertExitCode(0);
        $central->disableQueryLog();
        tenancy()->end();
        $writes = collect($central->getQueryLog())->pluck('query')
            ->filter(fn (string $q) => preg_match('/^\s*(insert|update|delete|replace|alter|create|drop|truncate)\b/i', $q))
            ->reject(fn (string $q) => str_contains($q, 'catalog_dedup_runs'))
            ->values()->all();
        $this->assertSame([], $writes, 'dry-run must not write outside catalog_dedup_runs');
        $this->tenant->run(fn () => $this->assertSame(3, DB::table('order_items')->whereNull('product_type')->count()));
        tenancy()->end();

        $dry = json_decode(File::get(CatalogRefBackfill::reportPath($tenantId, true)), true);
        $this->assertSame(4, $dry['tables']['order_items']['filled'], 'would fill 2 mappable lines x (product + variant)');
        $this->assertSame([654321 => 1], array_map('intval', $dry['tables']['order_items']['unmapped']['product_id']));

        $ghost = CatalogDedupRun::query()->where('tenant_id', $this->ghostTenantId)->sole();
        $this->assertSame(['skipped', 'dry-run', 'refs'], [$ghost->status, $ghost->step, $ghost->section]);
        $this->assertSame('tenant database does not exist', $ghost->counts['skipped']);

        // Real run, then re-run = no-op.
        $this->artisan('catalog:backfill-refs', ['--tenant' => [$tenantId]])->assertExitCode(0);
        tenancy()->end();
        $this->artisan('catalog:backfill-refs', ['--tenant' => [$tenantId]])->assertExitCode(0);
        tenancy()->end();

        $runs = CatalogDedupRun::query()->where('tenant_id', $tenantId)->orderBy('id')->get();
        $this->assertSame([['dry-run', 'done'], ['migrate', 'done'], ['migrate', 'done']], $runs->map(fn ($r) => [$r->step, $r->status])->all());
        $this->assertSame(4, $runs[1]->counts['filled'], '2 lines x (product + variant) pairs');
        $this->assertSame(0, $runs[2]->counts['filled'], 're-run is a no-op');
        $this->assertSame(1, $runs[2]->counts['unmapped']);
        $this->assertNotNull($runs[2]->finished_at);

        $report = json_decode(File::get(CatalogRefBackfill::reportPath($tenantId, false)), true);
        $this->assertFalse($report['dry_run']);
        $this->assertSame([654321 => 1], array_map('intval', $report['tables']['order_items']['unmapped']['product_id']));
        $this->assertSame(1, $report['tables']['order_items']['pending']);

        $this->tenant->run(function () {
            $this->assertSame(1, DB::table('order_items')->whereNull('product_type')->count(), 'only the unmapped line stays NULL');
            $this->assertSame(1, DB::table('order_items')->where('product_id', 654321)->count(), 'unmapped line kept');
        });
    }
}
