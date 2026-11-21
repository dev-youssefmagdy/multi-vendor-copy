<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Models\CatalogDedupRun;
use App\Models\CentralFlashSale;
use App\Models\ProductBadge as CentralBadge;
use App\Models\ProductEditRequest;
use App\Models\ProductLabel as CentralLabel;
use App\Models\ReturnRequest;
use App\Models\Tenant\Category;
use App\Models\Tenant\Favorite;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\MobileCart;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductRate;
use App\Models\Tenant\ProductVariant;
use App\Services\Catalog\CatalogRefBackfill;
use App\Services\Catalog\CatalogSyncCheck;
use App\Services\Tenant\Checkout\OrderComposer;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Services\Tenant\TenantPanelService;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogRefResolver;
use App\Support\Catalog\CatalogRefWriter;
use App\Support\OrderPricing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan phase 1 (docs/TENANT_CENTRAL_DEDUP_PLAN.md §5, §5a, §6): typed references beside the tenant id columns.
 * Migrations (idempotent, reversible, code safe before them), dual-write on every path, backfill command, sync-check.
 */
class CatalogPhase1RefsTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    private const TENANT_MIGRATION = 'database/migrations/tenant/2026_11_23_000001_add_catalog_refs_to_tenant_reference_tables.php';
    private const CENTRAL_MIGRATION = 'database/migrations/2026_11_23_000001_add_catalog_refs_to_return_and_edit_requests.php';
    private const RUNS_MIGRATION = 'database/migrations/2026_11_23_000002_create_catalog_dedup_runs_table.php';

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        CatalogRefColumns::flush();
        $this->setUpTenantPanel();
    }

    protected function tearDown(): void
    {
        // Tenant DB creation commits the RefreshDatabase transaction: clean the central rows this test wrote.
        if (isset($this->tenant)) {
            tenancy()->end();
            DB::table('return_requests')->where('tenant_id', $this->tenant->id)->delete();
            DB::table('product_edit_requests')->where('tenant_id', $this->tenant->id)->delete();
            if (Schema::hasTable('catalog_dedup_runs')) {
                DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->delete();
            }
            foreach ([false, true] as $dry) {
                File::delete(CatalogRefBackfill::reportPath((string) $this->tenant->id, $dry));
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

    private function migration(string $path): Migration
    {
        return require base_path($path);
    }

    /** @return array{0: ?string, 1: ?int} */
    private function pair(string $table, array $where, string $entity): array
    {
        [$type, $id] = CatalogRefColumns::PAIRS[$entity];
        $row = DB::table($table)->where($where)->first([$type, $id]);

        return [$row?->{$type}, $row?->{$id} !== null ? (int) $row->{$id} : null];
    }

    /** central catalog product+variant with tenant copies, and an own product+variant. */
    private function catalogAndOwn(): array
    {
        $central = $this->createCentralCatalogVariant(price: 40.0);
        $own = $this->createOwnProductVariant(price: 25.0);

        return [$central, $own];
    }

    // ---- migrations -------------------------------------------------------------------------------------------

    public function test_migrations_are_idempotent_and_reversible(): void
    {
        $this->inTenant(function () {
            $migration = $this->migration(self::TENANT_MIGRATION);
            $check = function (bool $present) {
                foreach (CatalogRefColumns::TENANT_TABLES as $table => $columns) {
                    foreach (array_unique(array_values($columns)) as $entity) {
                        [$type, $id] = CatalogRefColumns::PAIRS[$entity];
                        $this->assertSame($present, Schema::hasColumn($table, $type), "{$table}.{$type}");
                        $this->assertSame($present, Schema::hasColumn($table, $id), "{$table}.{$id}");
                        $this->assertSame($present, Schema::hasIndex($table, "{$table}_{$entity}_ref_index"), "{$table} {$entity} index");
                    }
                }
            };

            $check(true);           // ran with the tenant's migrations
            $migration->up();       // second up = no-op
            $check(true);
            $migration->down();
            $check(false);
            $migration->down();     // down twice is safe
            $migration->up();
            $check(true);
            $this->assertSame(['product', 'variant'], array_values(CatalogRefColumns::TENANT_TABLES['order_items']));
        });

        $central = $this->migration(self::CENTRAL_MIGRATION);
        $runs = $this->migration(self::RUNS_MIGRATION);
        $centralCheck = function (bool $present) {
            foreach (CatalogRefColumns::CENTRAL_TABLES as $table => $spec) {
                foreach ($spec as [, $type, $id]) {
                    $this->assertSame($present, Schema::hasColumn($table, $type), "{$table}.{$type}");
                    $this->assertSame($present, Schema::hasColumn($table, $id), "{$table}.{$id}");
                }
            }
            $this->assertSame($present, Schema::hasColumn('product_edit_requests', 'central_product_id'));
        };

        $centralCheck(true);
        $central->up();
        $runs->up();
        $centralCheck(true);
        $this->assertTrue(Schema::hasTable('catalog_dedup_runs'));
        $central->down();
        $centralCheck(false);
        $central->up();
        $centralCheck(true);
    }

    public function test_code_is_safe_before_the_tenant_migration_runs(): void
    {
        $this->inTenant(function () {
            [$central, $own] = $this->catalogAndOwn();
            $this->migration(self::TENANT_MIGRATION)->down();
            CatalogRefColumns::flush();

            try {
                $customer = $this->createCustomer();
                Favorite::query()->create(['customer_id' => $customer->id, 'product_id' => $own->product_id]);
                ProductRate::query()->create(['customer_id' => $customer->id, 'product_id' => $central['product']->id, 'stars' => 5]);
                $created = $this->placeOrder($customer, [[$central['product'], $central['variant'], false], [$own->product, $own, true]]);
                $this->assertNotNull($created['companion']);
                $this->assertFalse(Schema::hasColumn('order_items', 'product_type'));
                $this->assertSame(['skipped' => 'typed columns missing (migration not run)', 'filled' => 0, 'pending' => 0, 'unmapped' => []],
                    CatalogRefWriter::current()->fillTable('category_product'));
            } finally {
                $this->migration(self::TENANT_MIGRATION)->up();
                CatalogRefColumns::flush();
            }
        });
    }

    // ---- dual-write -----------------------------------------------------------------------------------------

    /**
     * @param  list<array{0: Product, 1: ProductVariant, 2: bool}>  $lines  product, variant, is_own
     * @return array{primary: Order, companion: ?Order}
     */
    private function placeOrder($customer, array $lines, float $shipping = 3.5): array
    {
        $items = [];
        foreach ($lines as $i => [$product, $variant, $isOwn]) {
            $line = $isOwn
                ? OrderPricing::item(0.0, 25.0, 1)
                : OrderPricing::item(30.0, 10.0, 2, 5.0, 10.0, OrderPricing::SOURCE_CENTRAL);
            $line['is_own'] = $isOwn;
            $items[] = ['key' => "v_{$variant->id}", 'product' => $product, 'variant' => $variant, 'product_variant_id' => $variant->id, 'line' => $line];
        }

        return app(OrderComposer::class)->create($items, [], [
            'customer' => $customer,
            'coupon' => null,
            'gateway' => null,
            'gateway_id' => null,
            'shipping_amount' => $shipping,
            'zone_rate_id' => null,
            'shipping_address' => ['name' => 'T', 'address' => '1 St', 'city' => 'C'],
        ]);
    }

    private function assertProfitInvariant(Order $order): void
    {
        $order = $order->fresh('items');
        $this->assertEqualsWithDelta($order->grand_total, (float) $order->platform_profit + (float) $order->vendor_profit, 0.011,
            'platform_profit + vendor_profit = order_grand_total');
    }

    public function test_order_placement_writes_typed_refs_for_central_own_and_mixed_carts(): void
    {
        $this->inTenant(function () {
            [$central, $own] = $this->catalogAndOwn();
            $customer = $this->createCustomer();

            $centralOnly = $this->placeOrder($customer, [[$central['product'], $central['variant'], false]])['primary'];
            $ownOnly = $this->placeOrder($customer, [[$own->product, $own, true]], 0.0)['primary'];
            $mixed = $this->placeOrder($customer, [[$central['product'], $central['variant'], false], [$own->product, $own, true]]);

            foreach ([$centralOnly, $mixed['primary']] as $order) {
                $item = $order->items()->firstOrFail();
                $this->assertSame(['central', $central['central_product']->id, 'central', $central['central_variant']->id],
                    [$item->product_type, (int) $item->catalog_product_id, $item->variant_type, (int) $item->catalog_variant_id]);
                $this->assertSame($central['product']->id, (int) $item->product_id, 'existing id column unchanged');
                $this->assertProfitInvariant($order);
            }
            foreach ([$ownOnly, $mixed['companion']] as $order) {
                $item = $order->items()->firstOrFail();
                $this->assertSame(['tenant', $own->product_id, 'tenant', $own->id],
                    [$item->product_type, (int) $item->catalog_product_id, $item->variant_type, (int) $item->catalog_variant_id]);
                $this->assertProfitInvariant($order);
            }

            // Money is untouched by the refs: same line maths as OrderPricing.
            $line = OrderPricing::item(30.0, 10.0, 2, 5.0, 10.0, OrderPricing::SOURCE_CENTRAL);
            $this->assertEqualsWithDelta($line['grand_total'], (float) $centralOnly->items()->value('grand_total'), 0.001);

            // A line saved outside OrderComposer is filled by the model hook.
            $item = $centralOnly->items()->firstOrFail();
            $item->forceFill(['product_type' => null, 'catalog_product_id' => null, 'variant_type' => null, 'catalog_variant_id' => null])->saveQuietly();
            $item->refresh()->forceFill(['qty' => 3])->save();
            $this->assertSame(['central', $central['central_product']->id], [$item->fresh()->product_type, (int) $item->fresh()->catalog_product_id]);
        });
    }

    public function test_favorites_ratings_flash_sales_and_mobile_cart_write_typed_refs(): void
    {
        $this->inTenant(function () {
            [$central, $own] = $this->catalogAndOwn();
            $customer = $this->createCustomer();

            Favorite::query()->create(['customer_id' => $customer->id, 'product_id' => $central['product']->id]);
            Favorite::query()->create(['customer_id' => $customer->id, 'product_id' => $own->product_id]);
            $this->assertSame(['central', $central['central_product']->id], $this->pair('favorites', ['product_id' => $central['product']->id], 'product'));
            $this->assertSame(['tenant', $own->product_id], $this->pair('favorites', ['product_id' => $own->product_id], 'product'));

            ProductRate::query()->create(['customer_id' => $customer->id, 'product_id' => $central['product']->id, 'stars' => 4]);
            $this->assertSame(['central', $central['central_product']->id], $this->pair('product_rates', ['product_id' => $central['product']->id], 'product'));

            $cart = MobileCart::query()->updateOrCreate(['customer_id' => $customer->id], ['items' => [
                'v_' . $central['variant']->id => ['product_id' => $central['product']->id, 'variant_id' => $central['variant']->id, 'qty' => 1],
                'p_' . $own->product_id => ['product_id' => $own->product_id, 'variant_id' => null, 'qty' => 2],
                'p_999999' => ['product_id' => 999999, 'variant_id' => null, 'qty' => 1],
            ]]);
            $items = $cart->fresh()->items;
            $this->assertEquals(['product_id' => $central['product']->id, 'variant_id' => $central['variant']->id, 'qty' => 1,
                'product_type' => 'central', 'catalog_product_id' => $central['central_product']->id,
                'variant_type' => 'central', 'catalog_variant_id' => $central['central_variant']->id], $items['v_' . $central['variant']->id]);
            $this->assertSame(['tenant', $own->product_id], [$items['p_' . $own->product_id]['product_type'], $items['p_' . $own->product_id]['catalog_product_id']]);
            $this->assertArrayNotHasKey('variant_type', $items['p_' . $own->product_id]);
            $this->assertArrayNotHasKey('product_type', $items['p_999999'], 'unknown product left untouched');

            // Own flash sale through the panel service: flash_sales.product_id + flash_sale_product (both sides).
            $sale = app(TenantPanelService::class)->saveFlashSale([
                'product_ids' => [$own->product_id, $central['product']->id],
                'discount_percentage' => 5,
                'active' => true,
            ]);
            $this->assertSame(['tenant', $own->product_id], $this->pair('flash_sales', ['id' => $sale->id], 'product'));
            $this->assertSame(['central', $central['central_product']->id], $this->pair('flash_sale_product', ['flash_sale_id' => $sale->id, 'product_id' => $central['product']->id], 'product'));
            $this->assertSame(['tenant', $sale->id], $this->pair('flash_sale_product', ['flash_sale_id' => $sale->id, 'product_id' => $own->product_id], 'flash_sale'));
        });
    }

    public function test_pivots_get_typed_refs_from_sync_and_panel_saves(): void
    {
        $fixture = $this->inTenant(fn () => $this->catalogAndOwn());
        [$central, $own] = $fixture;
        $centralProductId = $central['central_product']->id;

        [$label, $badge, $sale] = $this->central(function () use ($centralProductId) {
            $label = CentralLabel::query()->create(['text' => 'p1-label', 'active' => true]);
            DB::table('product_label_product')->insert(['product_label_id' => $label->id, 'product_id' => $centralProductId]);
            $badge = CentralBadge::query()->create(['text' => 'p1-badge', 'active' => true]);
            DB::table('product_badge_product')->insert(['product_badge_id' => $badge->id, 'product_id' => $centralProductId, 'country_id' => null, 'sort_order' => 0]);
            $sale = CentralFlashSale::query()->create(['discount_percentage' => 7, 'active' => true]);
            $sale->products()->attach($centralProductId);

            return [$label, $badge, $sale];
        });

        foreach (['labels', 'badges', 'flash-sales'] as $section) {
            app(TenantCatalogSyncService::class)->syncSection($this->tenant, $section);
        }
        tenancy()->end();

        $this->inTenant(function () use ($central, $own, $label, $badge, $sale) {
            $pid = $central['product']->id;
            $cpid = $central['central_product']->id;
            $this->assertSame(['central', $cpid], $this->pair('product_label_product', ['product_id' => $pid], 'product'));
            $this->assertSame(['central', $label->id], $this->pair('product_label_product', ['product_id' => $pid], 'label'));
            $this->assertSame(['central', $cpid], $this->pair('product_badge_product', ['product_id' => $pid], 'product'));
            $this->assertSame(['central', $badge->id], $this->pair('product_badge_product', ['product_id' => $pid], 'badge'));
            $tenantSale = FlashSale::query()->where('central_flash_sale_id', $sale->id)->firstOrFail();
            $this->assertSame(['central', $sale->id], $this->pair('flash_sale_product', ['flash_sale_id' => $tenantSale->id], 'flash_sale'));
            $this->assertSame(['central', $cpid], $this->pair('flash_sales', ['id' => $tenantSale->id], 'product'));

            // Panel product save: categories (central-linked + vendor's own) on an own product.
            $linkedCategory = Category::query()->create(['central_category_id' => 8801, 'active' => true]);
            $ownCategory = Category::query()->create(['active' => true]);
            app(TenantPanelService::class)->saveProduct([
                'slug' => $own->product->slug,
                'is_own_product' => true,
                'price' => 25,
                'category_ids' => [$linkedCategory->id, $ownCategory->id],
                'translations' => [],
            ], $own->product->fresh());

            $this->assertSame(['tenant', $own->product_id], $this->pair('category_product', ['category_id' => $ownCategory->id], 'product'));
            $this->assertSame(['tenant', $ownCategory->id], $this->pair('category_product', ['category_id' => $ownCategory->id], 'category'));
            $this->assertSame(['central', 8801], $this->pair('category_product', ['category_id' => $linkedCategory->id], 'category'));

            // Raw pivot insert + the writer (what the panel badge/flash-sale endpoints call).
            $vendorBadge = \App\Models\Tenant\ProductBadge::query()->create(['text' => 'p1-vendor-badge', 'active' => true]);
            DB::table('product_badge_product')->insert(['product_badge_id' => $vendorBadge->id, 'product_id' => $own->product_id, 'country_id' => null, 'sort_order' => 0]);
            CatalogRefWriter::fillQuietly('product_badge_product', ['product_id' => [$own->product_id]]);
            $this->assertSame('tenant', DB::table('product_badge_product')->where('product_id', $own->product_id)->value('badge_type'));
            $this->assertSame(0, DB::table('product_badge_product')->whereNull('product_type')->count());
        });
    }

    public function test_return_and_edit_requests_get_typed_refs(): void
    {
        [$central, $own] = $this->inTenant(fn () => $this->catalogAndOwn());
        $base = ['order_number' => (string) Str::uuid(), 'customer_id' => 1, 'reason' => ReturnReason::Other, 'status' => ReturnStatus::Pending];

        // Created while the tenant is initialised (storefront / panel): resolved on the tenant connection.
        $inTenant = $this->inTenant(fn () => ReturnRequest::query()->create($base + [
            'tenant_id' => $this->tenant->id,
            'product_id' => $central['product']->id,
            'product_variant_id' => $central['variant']->id,
            'replacement_product_variant_id' => $central['variant']->id,
        ]));
        // Created from central (admin): resolved cross-schema.
        tenancy()->end();
        $fromCentral = ReturnRequest::query()->create($base + ['tenant_id' => $this->tenant->id, 'product_id' => $own->product_id, 'product_variant_id' => $own->id]);

        $row = DB::table('return_requests')->find($inTenant->id);
        $this->assertSame(['central', $central['central_product']->id, 'central', $central['central_variant']->id, 'central', $central['central_variant']->id],
            [$row->product_type, (int) $row->catalog_product_id, $row->variant_type, (int) $row->catalog_variant_id, $row->replacement_variant_type, (int) $row->catalog_replacement_variant_id]);
        $row = DB::table('return_requests')->find($fromCentral->id);
        $this->assertSame(['tenant', $own->product_id, 'tenant', $own->id], [$row->product_type, (int) $row->catalog_product_id, $row->variant_type, (int) $row->catalog_variant_id]);

        // Edit request submit (tenant panel service).
        $this->inTenant(fn () => app(TenantPanelService::class)->submitProductEditRequest($central['product']->fresh(), ['en' => ['name' => 'New']], ['en' => ['name' => 'Old']]));
        tenancy()->end();
        $request = ProductEditRequest::query()->where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertSame(['central', $central['central_product']->id, $central['central_product']->id],
            [$request->product_type, (int) $request->catalog_product_id, (int) $request->central_product_id]);
        $this->assertSame($central['product']->id, (int) $request->product_id, 'tenant id column unchanged');
    }

    // ---- backfill + sync-check ---------------------------------------------------------------------------------

    public function test_backfill_dry_run_fill_rerun_unmapped_report_and_sync_check(): void
    {
        $tenantId = (string) $this->tenant->id;
        [$central, $own] = $this->inTenant(function () {
            [$central, $own] = $this->catalogAndOwn();
            $customer = $this->createCustomer();
            $this->placeOrder($customer, [[$central['product'], $central['variant'], false], [$own->product, $own, true]]);
            Favorite::query()->create(['customer_id' => $customer->id, 'product_id' => $own->product_id]);
            $category = Category::query()->create(['central_category_id' => 7701, 'active' => true]);
            $central['product']->categories()->attach($category->id);
            DB::table('mobile_carts')->insert(['customer_id' => $customer->id, 'items' => json_encode([
                'p_' . $own->product_id => ['product_id' => $own->product_id, 'variant_id' => null, 'qty' => 1],
                'p_888888' => ['product_id' => 888888, 'variant_id' => null, 'qty' => 1],
            ]), 'created_at' => now(), 'updated_at' => now()]);
            // Unmapped: an order line whose tenant product is gone.
            $order = Order::query()->firstOrFail();
            DB::table('order_items')->insert(['order_id' => $order->id, 'product_id' => 777777, 'qty' => 1, 'price' => 1, 'sub_total' => 1, 'grand_total' => 1, 'created_at' => now(), 'updated_at' => now()]);

            // Legacy state: no typed pairs anywhere.
            foreach (CatalogRefColumns::TENANT_TABLES as $table => $columns) {
                $nulls = [];
                foreach (array_unique(array_values($columns)) as $entity) {
                    [$type, $id] = CatalogRefColumns::PAIRS[$entity];
                    $nulls += [$type => null, $id => null];
                }
                DB::table($table)->update($nulls);
            }

            return [$central, $own];
        });
        tenancy()->end();
        ReturnRequest::withoutEvents(fn () => ReturnRequest::query()->create([
            'tenant_id' => $tenantId, 'order_number' => 'X', 'customer_id' => 1, 'reason' => ReturnReason::Other,
            'status' => ReturnStatus::Pending, 'product_id' => $central['product']->id,
        ]));

        $snapshot = fn () => $this->inTenant(fn () => [
            DB::table('order_items')->orderBy('id')->get()->toArray(),
            DB::table('category_product')->get()->toArray(),
            DB::table('mobile_carts')->pluck('items')->all(),
        ]) + [3 => DB::table('return_requests')->where('tenant_id', $tenantId)->get()->toArray()];

        $check = fn () => app(CatalogSyncCheck::class)->run([$tenantId], [CatalogSyncCheck::REFS])[0]['sections']['refs'];
        $before = $check();
        $this->assertGreaterThan(0, $before['null_refs']);
        $this->assertSame(2, $before['unmapped_refs'], 'order line 777777 + cart item 888888');

        // Dry run: nothing changes, report written, run recorded.
        $state = $snapshot();
        $this->artisan('catalog:backfill-refs', ['--tenant' => [$tenantId], '--dry-run' => true])->assertExitCode(0);
        tenancy()->end();
        $this->assertEquals($state, $snapshot());
        $dry = json_decode(File::get(CatalogRefBackfill::reportPath($tenantId, true)), true);
        $this->assertTrue($dry['dry_run']);
        $this->assertGreaterThan(0, $dry['totals']['filled']);
        $this->assertSame(['dry-run' => 'done'], CatalogDedupRun::query()->where('tenant_id', $tenantId)->pluck('status', 'step')->all());

        // Real run fills; unmapped rows are reported and left NULL (nothing dropped).
        $this->artisan('catalog:backfill-refs', ['--tenant' => [$tenantId], '--chunk' => 1])->assertExitCode(0);
        tenancy()->end();
        $report = json_decode(File::get(CatalogRefBackfill::reportPath($tenantId, false)), true);
        $this->assertSame('done', $report['status']);
        $this->assertSame([777777 => 1], array_map('intval', $report['tables']['order_items']['unmapped']['product_id']));
        $this->assertArrayHasKey('p_888888', array_values($report['mobile_carts']['unmapped'])[0]);

        $this->inTenant(function () use ($central, $own) {
            $this->assertSame(['central', $central['central_product']->id], $this->pair('order_items', ['product_id' => $central['product']->id], 'product'));
            $this->assertSame(['central', $central['central_variant']->id], $this->pair('order_items', ['product_id' => $central['product']->id], 'variant'));
            $this->assertSame(['tenant', $own->product_id], $this->pair('order_items', ['product_id' => $own->product_id], 'product'));
            $this->assertSame([null, null], $this->pair('order_items', ['product_id' => 777777], 'product'));
            $this->assertSame(1, DB::table('order_items')->where('product_id', 777777)->count(), 'unmapped row kept');
            $this->assertSame(['tenant', $own->product_id], $this->pair('favorites', ['product_id' => $own->product_id], 'product'));
            $this->assertSame(['central', 7701], $this->pair('category_product', ['product_id' => $central['product']->id], 'category'));
            $items = json_decode((string) DB::table('mobile_carts')->value('items'), true);
            $this->assertSame('tenant', $items['p_' . $own->product_id]['product_type']);
            $this->assertArrayNotHasKey('product_type', $items['p_888888']);
        });
        $row = DB::table('return_requests')->where('tenant_id', $tenantId)->first();
        $this->assertSame(['central', $central['central_product']->id], [$row->product_type, (int) $row->catalog_product_id]);

        // Second run: no-op.
        $again = app(CatalogRefBackfill::class)->run($this->tenant->fresh());
        $this->assertSame(0, $again['totals']['filled']);
        $this->assertSame(2, $again['totals']['unmapped']);

        // sync-check: only the 2 unmapped refs remain; optional gate.
        $after = $check();
        $this->assertSame(2, $after['null_refs']);
        $this->assertSame(2, $after['unmapped_refs']);
        $this->assertSame(1, $after['dangling_refs'], 'category_product -> central category 7701 does not exist in central');
        $this->artisan('catalog:sync-check', ['--tenant' => [$tenantId], '--section' => ['refs'], '--gate' => ['unmapped_refs:0']])->assertExitCode(1);
        $this->artisan('catalog:sync-check', ['--tenant' => [$tenantId], '--section' => ['refs'], '--gate' => ['unmapped_refs:2']])->assertExitCode(0);
        $this->artisan('catalog:backfill-refs', ['--tenant' => ['no-such-tenant']])->assertExitCode(0);
    }

    /** QA D-P1-1: the save-path UPDATE … JOIN (fillJoined) classifies exactly like the resolver path (fillTable). */
    public function test_joined_fill_matches_resolver_fill(): void
    {
        $this->inTenant(function () {
            [$central, $own] = $this->catalogAndOwn();
            $customer = $this->createCustomer();
            Favorite::query()->create(['customer_id' => $customer->id, 'product_id' => $own->product_id]);
            Favorite::query()->create(['customer_id' => $this->createCustomer()->id, 'product_id' => $central['product']->id]);
            $this->placeOrder($customer, [[$central['product'], $central['variant'], false], [$own->product, $own, true]]);

            $snapshot = function (string $table): array {
                $cols = ['id'];
                foreach (CatalogRefColumns::specFor($table) as [, $type, $id]) {
                    array_push($cols, $type, $id);
                }

                return DB::table($table)->orderBy('id')->get($cols)->map(fn ($r) => (array) $r)->all();
            };
            $reset = function (string $table): void {
                $set = [];
                foreach (CatalogRefColumns::specFor($table) as [, $type, $id]) {
                    $set[$type] = null;
                    $set[$id] = null;
                }
                DB::table($table)->update($set);
            };

            foreach (['order_items' => 'product_id', 'favorites' => 'product_id'] as $table => $column) {
                $ids = DB::table($table)->pluck($column)->all();
                $this->assertNotEmpty($ids, $table);

                $reset($table);
                CatalogRefWriter::current()->fillTable($table, [$column => $ids]);
                $viaResolver = $snapshot($table);

                $reset($table);
                $written = CatalogRefWriter::current()->fillJoined($table, [$column => $ids]);
                $this->assertGreaterThan(0, $written, $table);
                $this->assertSame($viaResolver, $snapshot($table), $table);
                $this->assertContains('central', array_column($viaResolver, 'product_type'), $table);
                $this->assertContains('tenant', array_column($viaResolver, 'product_type'), $table);
            }
        });
    }
}
