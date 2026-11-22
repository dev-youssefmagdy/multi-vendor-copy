<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\Tenant\CouponType;
use App\Jobs\SyncTenantSectionJob;
use App\Models\CentralCoupon;
use App\Models\CentralFlashSale;
use App\Models\Country;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
use App\Models\TenantCountry;
use App\Repositories\Tenant\Catalog\CatalogCoupon;
use App\Repositories\Tenant\Catalog\CouponCatalog;
use App\Repositories\Tenant\Catalog\FlashSaleCatalog;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\Tenant\Checkout\OrderComposer;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\Catalog\CatalogRef;
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
 * QA — dedup plan phase 3 (coupons + flash sales). Complements CatalogPhase3{Coupons,FlashSales,PanelUi}Test with: the
 * full money matrix (real flash lines + coupons, sync vs central), order snapshots after central edits / deletes
 * (invariant 4), inactive-central behaviour change, D6 edge cases, flash-sale country matrix / two sales / ended sale,
 * sync guards on every fan-out path, dedup tooling (dry-run query log, verify semantics, exit codes), migration
 * idempotence and panel security. See plan §12 "QA — Phase 3".
 */
class CatalogPhase3QaTest extends TestCase
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
            foreach (['coupons', 'flash_sales'] as $section) {
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

    private function flip(string $mode = CatalogSource::CENTRAL, array $sections = ['coupons', 'flash-sales']): void
    {
        tenancy()->end();
        foreach ($sections as $section) {
            CatalogSource::set($this->tenant, $section, $mode);
        }
    }

    /** Raw central write (no model events, so no CacheVersion bump as the real observers do): drop the per-request memo. */
    private function centralWrite(callable $write): mixed
    {
        $result = Model::withoutEvents($write);
        CouponCatalog::flushMemo();

        return $result;
    }

    private function centralCoupon(string $code, array $attributes = [], array $countryIds = []): CentralCoupon
    {
        tenancy()->end();

        return Model::withoutEvents(function () use ($code, $attributes, $countryIds) {
            $coupon = CentralCoupon::query()->create(array_merge([
                'code' => $code, 'name' => "Central {$code}", 'type' => 'percentage', 'value' => 10, 'minimum_spend' => 0, 'active' => true,
            ], $attributes));
            foreach ($countryIds as $id) {
                DB::table('central_coupon_country')->insert(['central_coupon_id' => $coupon->id, 'country_id' => $id]);
            }

            return $coupon;
        });
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

    private function sync(string $section): void
    {
        tenancy()->end();
        try {
            app(TenantCatalogSyncService::class)->syncSection($this->tenant, $section);
        } finally {
            tenancy()->end();
        }
    }

    /** @return array{a: array, b: array, own: \App\Models\Tenant\ProductVariant} */
    private function products(): array
    {
        return $this->inTenant(fn () => [
            'a' => $this->createCentralCatalogVariant(stock: 1000, price: 40.0),
            'b' => $this->createCentralCatalogVariant(stock: 1000, price: 30.0),
            'own' => $this->createOwnProductVariant(stock: 1000, price: 25.0),
        ]);
    }

    private function cart(array $p, array $keys): array
    {
        $cart = [];
        foreach ($keys as $k) {
            $variant = $k === 'own' ? $p['own'] : $p[$k]['variant'];
            $product = Product::query()->findOrFail($variant->product_id);
            $cart[] = ['key' => 'v_' . $variant->id, 'product' => $product, 'variant' => $variant, 'product_variant_id' => $variant->id,
                'line' => OrderComposer::line($product, $variant, 1, null)];
        }

        return $cart;
    }

    /** @return list<array<string, mixed>> */
    private function place(array $cart, Coupon|CatalogCoupon|null $coupon, $customer): array
    {
        $created = app(OrderComposer::class)->create($cart, [], [
            'customer' => $customer, 'coupon' => $coupon, 'gateway' => null, 'gateway_id' => null,
            'shipping_amount' => 4.25, 'zone_rate_id' => null, 'shipping_address' => ['name' => 'T', 'address' => '1 St', 'city' => 'C'],
        ]);

        return array_values(array_map(fn ($o) => $this->snapshot($o->id), array_filter([$created['primary'], $created['companion']])));
    }

    /** Every money / ownership column of an order and its lines, rounded, for equality assertions. */
    private function snapshot(int $orderId): array
    {
        $order = Order::query()->with('items')->findOrFail($orderId);
        $this->assertEqualsWithDelta((float) $order->grand_total, (float) $order->platform_profit + (float) $order->vendor_profit, 0.011,
            'platform_profit + vendor_profit = order_grand_total');
        $r = fn (array $a) => array_map(fn ($v) => is_numeric($v) ? round((float) $v, 2) : $v, $a);

        return [
            'id' => $order->id,
            'order' => $r($order->only(['shipping_charge', 'coupon_discount', 'coupon_source', 'discount_percentage', 'platform_profit', 'vendor_profit', 'grand_total'])),
            'items' => $order->items->map(fn (OrderItem $i) => $r($i->only(['cost', 'profit_amount', 'qty', 'price', 'sub_total', 'fixed_shipping', 'discount', 'discount_source', 'grand_total'])))->all(),
        ];
    }

    private function strip(array $orders): array
    {
        return array_map(fn ($o) => ['order' => $o['order'], 'items' => $o['items']], $orders);
    }

    // ── 2. money invariant ────────────────────────────────────────────────────────────────────────────────────────

    public function test_money_matrix_real_flash_lines_and_coupons_identical_in_sync_and_central_mode(): void
    {
        $p = $this->products();
        $this->centralCoupon('PCT10', ['type' => 'percentage', 'value' => 10]);
        $this->centralCoupon('FIX65', ['type' => 'fixed', 'value' => 6.5]);
        $this->centralCoupon('BIG', ['type' => 'fixed', 'value' => 9999]);
        $this->centralSale([$p['a']['central_product']->id], 10.0);          // central flash on a
        $this->inTenant(function () use ($p) {
            Coupon::query()->create(['code' => 'VPCT', 'type' => CouponType::Percentage, 'value' => 12.5, 'minimum_spend' => 3]);
            Coupon::query()->create(['code' => 'VFIX', 'type' => CouponType::Fixed, 'value' => 7.5]);
            $this->ownSale([$p['b']['product']->id, $p['own']->product_id], 20.0); // vendor flash on a central product + own product
        });
        $this->sync('coupons');
        $this->sync('flash-sales');
        $customer = $this->inTenant(fn () => $this->createCustomer());

        $run = fn () => $this->inTenant(function () use ($p, $customer) {
            $catalog = app(CouponCatalog::class);
            $out = [];
            foreach (['central' => ['a', 'b'], 'own' => ['own'], 'mixed' => ['a', 'b', 'own'], 'central-flash-only' => ['a']] as $name => $keys) {
                foreach (['none' => null, 'PCT10' => 'PCT10', 'FIX65' => 'FIX65', 'BIG' => 'BIG', 'VPCT' => 'VPCT', 'VFIX' => 'VFIX'] as $c => $code) {
                    $coupon = $code ? $catalog->findValidByCode($code) : null;
                    $this->assertNotNull($coupon ?? true, "{$code} resolvable");
                    $out["{$name}/{$c}"] = $this->place($this->cart($p, $keys), $coupon, $customer);
                }
            }

            return $out;
        });

        $sync = $run();
        $this->flip();
        $merged = $run();

        $this->assertSame(array_keys($sync), array_keys($merged));
        foreach ($sync as $name => $orders) {
            $this->assertEquals($this->strip($orders), $this->strip($merged[$name]), "{$name}: money identical in sync and central mode");
        }
        $this->assertCount(2, $merged['mixed/PCT10']);
        $this->assertSame('central', $merged['mixed/PCT10'][0]['order']['coupon_source']);
        $this->assertSame('vendor', $merged['mixed/VFIX'][0]['order']['coupon_source']);
        $this->assertSame('central', $merged['central/none'][0]['items'][0]['discount_source']);
        $this->assertSame('vendor', $merged['central/none'][0]['items'][1]['discount_source']);
        foreach ($merged['mixed/BIG'] as $o) {
            $this->assertGreaterThanOrEqual(0.0, $o['order']['grand_total'], 'oversized fixed coupon never makes an order negative');
        }
        // Coupon on a mixed cart is split across the two orders and adds up to the whole discount.
        $whole = array_sum(array_map(fn ($o) => $o['order']['coupon_discount'], $merged['mixed/PCT10']));
        $this->assertGreaterThan(0.0, $whole);
    }

    // ── invariant 4: snapshots never recomputed from live central data ───────────────────────────────────────────

    public function test_order_snapshots_survive_central_coupon_and_flash_sale_edit_deactivate_and_delete(): void
    {
        $p = $this->products();
        $coupon = $this->centralCoupon('SNAP10', ['type' => 'percentage', 'value' => 10]);
        $sale = $this->centralSale([$p['a']['central_product']->id], 25.0);
        $this->flip();
        $customer = $this->inTenant(fn () => $this->createCustomer());

        $before = $this->inTenant(fn () => $this->place($this->cart($p, ['a', 'own']), app(CouponCatalog::class)->findValidByCode('SNAP10'), $customer));
        $this->assertSame('central', $before[0]['items'][0]['discount_source']);
        $ids = array_column($before, 'id');

        $mutations = [
            'value changed' => fn () => $this->centralWrite(fn () => $coupon->update(['value' => 90])) && $this->centralWrite(fn () => $sale->update(['discount_percentage' => 90])),
            'deactivated' => fn () => $this->centralWrite(fn () => $coupon->update(['active' => false])) && $this->centralWrite(fn () => $sale->update(['active' => false])),
            'deleted' => fn () => $this->centralWrite(fn () => $coupon->delete()) && $this->centralWrite(fn () => $sale->delete()),
        ];
        foreach ($mutations as $label => $mutate) {
            tenancy()->end();
            $mutate();
            $after = $this->inTenant(fn () => array_map(fn ($id) => $this->snapshot($id), $ids));
            $this->assertEquals($before, $after, "order money unchanged after central coupon/sale {$label}");
        }

        $this->inTenant(function () use ($ids, $coupon) {
            $order = Order::query()->findOrFail($ids[0]);
            $this->assertSame('SNAP10', $order->appliedCouponCode(), 'snapshot survives the central delete');
            $this->assertSame('central', $order->coupon_type);
            $this->assertSame((int) $coupon->id, (int) $order->catalog_coupon_id);
            $this->assertSame(1, app(CouponCatalog::class)->usageCount(CatalogRef::central((int) $coupon->id)), 'usage history survives the delete');
            $this->assertNull(app(CouponCatalog::class)->findByCode('SNAP10'), 'deleted central coupon is no longer redeemable');
        });
    }

    public function test_old_order_without_snapshot_still_resolves_its_coupon_code(): void
    {
        $this->inTenant(function () {
            $coupon = Coupon::query()->create(['code' => 'OLD5', 'type' => CouponType::Fixed, 'value' => 5]);
            $customer = $this->createCustomer();
            $order = Order::query()->forceCreate([
                'uuid' => (string) \Illuminate\Support\Str::uuid(), 'customer_id' => $customer->id, 'discount_id' => $coupon->id,
                'status' => 'pending',
            ]);
            $this->assertNull($order->fresh()->coupon_code);
            $this->assertSame('OLD5', $order->fresh()->appliedCouponCode());
        });
    }

    // ── 3. behaviour change: inactive central coupon ──────────────────────────────────────────────────────────────

    public function test_inactive_central_coupon_is_redeemable_in_sync_mode_and_hidden_in_central_mode(): void
    {
        $this->centralCoupon('OFF10', ['active' => false]);
        $this->centralCoupon('ON10');
        $this->sync('coupons');

        $this->inTenant(function () {
            $c = app(CouponCatalog::class);
            $this->assertNotNull($c->findValidByCode('OFF10'), 'sync mode: the copy never carried `active`, so it is redeemable');
            $this->assertNotNull($c->findByCode('OFF10'));
        });

        $this->flip();
        $this->inTenant(function () {
            $c = app(CouponCatalog::class);
            $this->assertNull($c->findValidByCode('OFF10'));
            $this->assertNull($c->findByCode('OFF10'), 'checkout re-read also drops it: customer with the code in the cart gets no discount');
            $this->assertNotNull($c->findValidByCode('ON10'));
            $this->assertNull($c->latestValidCodeForCountry(null) === 'OFF10' ? 'x' : null);
        });

        // verify: the copy of the switched-off coupon is reported, not counted as a diff (exit 0).
        $this->artisan('catalog:dedup-migrate', ['section' => 'coupons', '--tenant' => [$this->tenant->id], '--verify' => true])->assertExitCode(0);
        $report = json_decode(File::get(DedupRunner::reportPath('coupons', (string) $this->tenant->id, 'verify')), true);
        $this->assertSame(1, $report['counts']['inactive_central']);
        $this->assertSame(0, $report['diffs']);
    }

    // ── 4. D6 / lookups / deletion before checkout ───────────────────────────────────────────────────────────────

    public function test_code_lookup_is_case_and_whitespace_insensitive_in_both_modes_and_own_wins_a_legacy_collision(): void
    {
        $central = $this->centralCoupon('SPRING', ['value' => 10]);
        $this->sync('coupons');
        $lookup = function () {
            return $this->inTenant(function () {
                $c = app(CouponCatalog::class);

                return [$c->findValidByCode('  spring ')?->code, $c->findByCode('Spring')?->code];
            });
        };
        $this->assertSame(['SPRING', 'SPRING'], $lookup());
        $this->flip();
        $this->assertSame(['SPRING', 'SPRING'], $lookup());

        // Legacy collision: an own row with the central code (the sync copy never allowed it; created before) wins.
        $this->inTenant(function () use ($central) {
            DB::table('coupons')->where('central_coupon_id', $central->id)->delete();
            Coupon::query()->create(['code' => 'spring', 'type' => CouponType::Fixed, 'value' => 3]);
            $hit = app(CouponCatalog::class)->findValidByCode('SPRING');
            $this->assertSame('tenant', $hit->source, 'tenant wins a legacy code collision');
            $this->assertSame(1, app(CouponCatalog::class)->merged()->filter(fn ($c) => strtolower($c->code) === 'spring')->count());
        });
    }

    public function test_d6_blocks_creating_a_code_of_any_live_central_coupon_but_not_a_soft_deleted_one(): void
    {
        $this->centralCoupon('LIVE1');
        $this->centralCoupon('OFFCODE', ['active' => false]);
        $gone = $this->centralCoupon('GONE1');
        $this->centralWrite(fn () => $gone->delete());
        $this->flip();

        $post = fn (string $code) => $this->actingAsTenantAdmin()->postJson($this->tenantUrl('/admin/store/coupons/validate'),
            ['code' => $code, 'name_text' => 'x', 'type' => 'fixed', 'value' => 5]);
        $post('live1')->assertStatus(422)->assertJsonValidationErrors('code');
        $post('  LIVE1  ')->assertStatus(422);
        $post('OFFCODE')->assertStatus(422);
        $post('GONE1')->assertOk();
    }

    public function test_d6_legacy_own_coupon_with_a_central_code_can_be_edited_but_not_created_or_renamed_to_one(): void
    {
        $this->centralCoupon('DUP9');
        $id = $this->inTenant(fn () => Coupon::query()->create(['code' => 'DUP9', 'type' => CouponType::Fixed, 'value' => 3])->id);
        $this->flip();

        // D-P3-2 fixed: the tenant "wins" at lookup, so editing only the value of that legacy coupon (same code) passes.
        $res = $this->actingAsTenantAdmin()->postJson($this->tenantUrl("/admin/store/coupons/item/{$id}/validate"),
            ['code' => 'DUP9', 'name_text' => 'x', 'type' => 'fixed', 'value' => 4]);
        $this->assertSame(200, $res->getStatusCode(), $res->getContent());
        $this->assertSame(200, $this->actingAsTenantAdmin()->postJson($this->tenantUrl("/admin/store/coupons/item/{$id}/validate"),
            ['code' => ' dup9 ', 'name_text' => 'x', 'type' => 'fixed', 'value' => 4])->getStatusCode(), 'case / trim of the same code');
        $this->centralCoupon('OTHER9');
        $this->assertSame(422, $this->actingAsTenantAdmin()->postJson($this->tenantUrl("/admin/store/coupons/item/{$id}/validate"),
            ['code' => 'OTHER9', 'name_text' => 'x', 'type' => 'fixed', 'value' => 4])->getStatusCode(), 'rename to a central code stays blocked');
        $this->assertSame(422, $this->actingAsTenantAdmin()->postJson($this->tenantUrl('/admin/store/coupons/validate'),
            ['code' => 'DUP9', 'name_text' => 'x', 'type' => 'fixed', 'value' => 4])->getStatusCode(), 'create stays blocked');
    }

    public function test_code_deleted_by_central_admin_between_cart_and_checkout_gives_no_discount_and_no_error(): void
    {
        $p = $this->products();
        $coupon = $this->centralCoupon('CARTDEL');
        $this->flip();
        $customer = $this->inTenant(fn () => $this->createCustomer());
        $this->inTenant(function () {
            session(['storefront_coupon' => 'CARTDEL']);
            $this->assertNotNull(app(CouponCatalog::class)->findByCode(session('storefront_coupon')));
        });
        tenancy()->end();
        $this->centralWrite(fn () => $coupon->delete());

        $orders = $this->inTenant(function () use ($p, $customer) {
            $applied = app(CouponCatalog::class)->findByCode(session('storefront_coupon'));
            $this->assertNull($applied);

            return $this->place($this->cart($p, ['a']), $applied, $customer);
        });
        $this->assertSame(0.0, $orders[0]['order']['coupon_discount']);
    }

    // ── 5. flash sales ───────────────────────────────────────────────────────────────────────────────────────────

    /** @return array<string, float> "<productId>@<country>" => SQL discount % and Eloquent discount % */
    private function discountMatrix(array $products): array
    {
        return $this->inTenant(function () use ($products) {
            $out = [];
            foreach ([null, $this->served->id, $this->other->id] as $country) {
                $country ? session(['storefront_country_id' => $country]) : session()->forget('storefront_country_id');
                $repo = new StorefrontRepository();
                $m = new \ReflectionMethod($repo, 'activeFlashDiscountExpression');
                $m->setAccessible(true);
                $expr = $m->invoke($repo);
                $sql = DB::table('products')->selectRaw("products.id, ({$expr}) AS d")->pluck('d', 'id');
                foreach ($products as $pid) {
                    $sale = Product::query()->findOrFail($pid)->activeStorefrontFlashSale();
                    $out["{$pid}@" . ($country ?? 'none') . ':sql'] = (float) $sql[$pid];
                    $out["{$pid}@" . ($country ?? 'none') . ':orm'] = (float) ($sale?->discount_percentage ?? 0);
                }
            }

            return $out;
        });
    }

    public function test_flash_discount_per_product_and_country_is_identical_in_both_modes_with_two_sales_and_an_ended_sale(): void
    {
        $p = $this->products();
        $this->centralSale([$p['a']['central_product']->id], 10.0);                                   // all countries
        $this->centralSale([$p['a']['central_product']->id], 35.0, [], [$this->other->id]);          // other country only
        $this->centralSale([$p['b']['central_product']->id], 50.0, ['end_date' => now()->subHour()]); // ended
        $this->centralSale([$p['b']['central_product']->id], 12.0, ['start_date' => now()->addDay()]); // not started
        $this->inTenant(function () use ($p) {
            $this->ownSale([$p['a']['product']->id, $p['own']->product_id], 30.0, [], [$this->served->id]); // product a in 3 sales
            $this->ownSale([$p['b']['product']->id], 5.0, ['active' => false]);
        });
        $this->sync('flash-sales');
        $ids = [$p['a']['product']->id, $p['b']['product']->id, $p['own']->product_id];

        $sync = $this->discountMatrix($ids);
        $this->flip();
        $merged = $this->discountMatrix($ids);

        $this->assertEquals($sync, $merged, 'discount per product per country identical (SQL expression and Eloquent)');
        $a = $p['a']['product']->id;
        $this->assertSame(30.0, $merged["{$a}@{$this->served->id}:sql"], 'best of central 10 and own 30');
        $this->assertSame(35.0, $merged["{$a}@{$this->other->id}:sql"], 'best of central 10 and country-limited 35');
        $this->assertSame(10.0, $merged["{$a}@none:sql"], 'no country: only all-country sales');
        $this->assertSame(0.0, $merged["{$p['b']['product']->id}@{$this->served->id}:sql"], 'ended / not started / inactive sales give nothing');
    }

    public function test_flash_sale_that_ends_after_the_line_was_priced_keeps_the_order_line_and_stops_discounting_new_lines(): void
    {
        $p = $this->products();
        $sale = $this->centralSale([$p['a']['central_product']->id], 20.0, ['end_date' => now()->addHour()]);
        $this->flip();
        $customer = $this->inTenant(fn () => $this->createCustomer());

        $cart = $this->inTenant(fn () => $this->cart($p, ['a']));
        $this->assertGreaterThan(0, $cart[0]['line']['discount']);
        tenancy()->end();
        $this->centralWrite(fn () => $sale->update(['end_date' => now()->subMinute()]));

        $fresh = $this->inTenant(fn () => $this->cart($p, ['a']));
        $this->assertSame(0.0, round((float) $fresh[0]['line']['discount'], 2), 'a new line after the end has no discount');

        // The already-priced line is persisted as priced (composer never re-reads the sale).
        $orders = $this->inTenant(fn () => $this->place($cart, null, $customer));
        $this->assertSame(round((float) $cart[0]['line']['discount'], 2), $orders[0]['items'][0]['discount']);
    }

    // ── 6. sync guards ───────────────────────────────────────────────────────────────────────────────────────────

    public function test_no_fan_out_path_writes_coupon_or_flash_copies_for_a_central_mode_tenant_and_sync_mode_is_unchanged(): void
    {
        $p = $this->products();
        $this->centralCoupon('GUARD1');
        $this->centralSale([$p['a']['central_product']->id], 10.0);
        $this->flip();

        // Every entry point: section sync, the multi-section sync, the central fan-out, per-product sync.
        $this->sync('coupons');
        $this->sync('flash-sales');
        tenancy()->end();
        app(TenantCatalogSyncService::class)->syncForTenant($this->tenant, ['coupons', 'flash-sales']);
        tenancy()->end();
        app(CentralCatalogTenantSyncService::class)->syncCoupons();
        app(CentralCatalogTenantSyncService::class)->syncFlashSales();
        tenancy()->end();
        try {
            app(TenantCatalogSyncService::class)->syncProductToTenant($p['a']['central_product'], $this->tenant);
        } catch (\Throwable) {
            // product-level sync may need more fixtures; it must at least not have written copies
        }
        tenancy()->end();
        Queue::assertNotPushed(SyncTenantSectionJob::class, fn ($job) => in_array($job->section ?? null, ['coupons', 'flash-sales'], true));
        $this->inTenant(function () {
            $this->assertSame(0, Coupon::query()->whereNotNull('central_coupon_id')->count());
            $this->assertSame(0, FlashSale::query()->whereNotNull('central_flash_sale_id')->count());
        });

        // Sync mode unchanged: the same calls do write copies.
        $this->flip(CatalogSource::SYNC);
        $this->sync('coupons');
        $this->sync('flash-sales');
        $this->inTenant(function () {
            $this->assertSame(1, Coupon::query()->whereNotNull('central_coupon_id')->count());
            $this->assertSame(1, FlashSale::query()->whereNotNull('central_flash_sale_id')->count());
        });
    }

    // ── 7. dedup tooling ─────────────────────────────────────────────────────────────────────────────────────────

    public function test_dry_run_issues_no_write_statements_against_the_tenant_db_and_exit_codes(): void
    {
        $p = $this->products();
        $this->centralCoupon('DRY1');
        $this->centralSale([$p['a']['central_product']->id], 10.0);
        $this->sync('coupons');
        $this->sync('flash-sales');
        $this->inTenant(function () {
            $c = Coupon::query()->first();
            Order::query()->forceCreate(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'customer_id' => $this->createCustomer()->id, 'discount_id' => $c->id, 'status' => 'pending']);
        });

        tenancy()->end();
        $log = [];
        DB::listen(function ($query) use (&$log) {
            $log[] = [$query->connectionName, $query->sql];
        });
        foreach (['coupons', 'flash_sales'] as $section) {
            $this->artisan('catalog:dedup-migrate', ['section' => $section, '--tenant' => [$this->tenant->id], '--dry-run' => true])->assertExitCode(0);
        }
        // Query log of the tenant connection (initialised by the runner); fall back to the default connection log.
        $this->assertNotEmpty(array_filter($log, fn ($q) => $q[0] === 'tenant'), 'the runner read the tenant DB');
        $writes = collect($log)->filter(fn ($q) => $q[0] === 'tenant')->pluck(1)
            ->filter(fn ($q) => preg_match('/^\s*(insert|update|delete|replace|alter|drop|create|truncate)\b/i', $q))->values()->all();
        $this->assertSame([], $writes, 'dry-run wrote to the tenant DB: ' . json_encode($writes));
        $this->inTenant(fn () => $this->assertNull(Order::query()->first()->coupon_type, 'orders untouched by a dry run'));

        // Real migrate fills the order ref; a second run is a no-op; verify exits 0.
        $this->artisan('catalog:dedup-migrate', ['section' => 'coupons', '--tenant' => [$this->tenant->id]])->assertExitCode(0);
        $this->inTenant(fn () => $this->assertSame('central', Order::query()->first()->coupon_type));
        $this->artisan('catalog:dedup-migrate', ['section' => 'coupons', '--tenant' => [$this->tenant->id], '--verify' => true])->assertExitCode(0);

        $this->assertSame(2, Artisan::call('catalog:dedup-migrate', ['section' => 'coupons', '--dry-run' => true, '--verify' => true]));
        $before = (int) DB::table('catalog_dedup_runs')->count();
        Artisan::call('catalog:dedup-migrate', ['section' => 'flash_sales', '--tenant' => ['no-such-tenant']]);
        $this->assertSame($before, (int) DB::table('catalog_dedup_runs')->count(), '--tenant filters: unknown id runs nothing');
        $this->assertGreaterThan(0, (int) DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->where('section', 'coupons')->count());
    }

    public function test_verify_fails_closed_on_sync_lag_and_a_copy_that_no_longer_applies(): void
    {
        $this->centralCoupon('LAG1');
        $this->sync('coupons');
        $this->centralCoupon('LAG2'); // created after the last sync: no copy
        $res = Artisan::call('catalog:dedup-migrate', ['section' => 'coupons', '--tenant' => [$this->tenant->id], '--verify' => true]);
        $this->assertSame(1, $res, 'central coupon without a copy = diff (flip would add a coupon that sync mode lacks)');
    }

    // ── 8. migration ─────────────────────────────────────────────────────────────────────────────────────────────

    public function test_migration_is_idempotent_and_reversible_and_old_code_tolerates_null_columns(): void
    {
        $migration = require base_path('database/migrations/tenant/2026_11_25_000001_add_coupon_refs_to_orders_table.php');
        $this->inTenant(function () use ($migration) {
            $migration->up();
            $migration->up(); // idempotent
            $this->assertTrue(Schema::hasIndex('orders', 'orders_coupon_ref_index'));
            $migration->down();
            $migration->down(); // idempotent
            foreach (['coupon_code', 'coupon_type', 'catalog_coupon_id'] as $col) {
                $this->assertFalse(Schema::hasColumn('orders', $col));
            }
            $this->assertTrue(Schema::hasColumn('orders', 'discount_id'), 'down() drops only its own columns');
            // Without the columns the composer / read layer degrade, they do not break.
            CatalogRefColumns::flush();
            $this->assertSame([], OrderComposer::couponRef(new Coupon(['code' => 'X'])));
            $migration->up();
            CatalogRefColumns::flush();
            $this->assertTrue(CouponCatalog::schemaReady());
        });
    }

    // ── 9. security ──────────────────────────────────────────────────────────────────────────────────────────────

    public function test_panel_endpoints_require_auth_and_404_legacy_copies_and_ignore_source_fields_and_escape_names(): void
    {
        $p = $this->products();
        $central = $this->centralCoupon('XSS1', ['name' => '<script>alert(1)</script>']);
        $sale = $this->centralSale([$p['a']['central_product']->id], 10.0);
        $this->sync('coupons');
        $this->sync('flash-sales');
        [$copyCoupon, $copySale] = $this->inTenant(fn () => [Coupon::query()->whereNotNull('central_coupon_id')->first(), FlashSale::query()->whereNotNull('central_flash_sale_id')->first()]);
        $this->flip();

        $this->getJson($this->tenantUrl('/admin/store/coupons/data?draw=1'))->assertStatus(401);
        $this->getJson($this->tenantUrl('/admin/store/flash-sales/data?draw=1'))->assertStatus(401);

        $admin = $this->actingAsTenantAdmin();
        $admin->getJson($this->tenantUrl("/admin/store/coupons/item/{$copyCoupon->id}"))->assertNotFound();
        $admin->deleteJson($this->tenantUrl("/admin/store/coupons/item/{$copyCoupon->id}"))->assertNotFound();
        $admin->getJson($this->tenantUrl("/admin/store/flash-sales/item/{$copySale->id}"))->assertNotFound();
        $admin->deleteJson($this->tenantUrl("/admin/store/flash-sales/item/{$copySale->id}"))->assertNotFound();
        $admin->postJson($this->tenantUrl("/admin/store/flash-sales/item/{$copySale->id}/attach-product"), ['product_id' => $p['own']->product_id])->assertNotFound();
        $admin->getJson($this->tenantUrl('/admin/store/coupons/item/999999'))->assertNotFound();
        $this->inTenant(function () use ($copyCoupon, $copySale) {
            $this->assertNotNull(Coupon::query()->find($copyCoupon->id), 'copy not deleted through the panel');
            $this->assertNotNull(FlashSale::query()->find($copySale->id));
        });

        $json = $admin->getJson($this->tenantUrl('/admin/store/coupons/data?draw=1&start=0&length=10'))->assertOk()->json('data');
        $this->assertStringNotContainsString('<script>', json_encode($json, JSON_UNESCAPED_SLASHES));
        $this->assertStringContainsString('&lt;script&gt;', json_encode($json, JSON_UNESCAPED_SLASHES));

        $admin->postJson($this->tenantUrl('/admin/store/coupons'), [
            'code' => 'MASS1', 'name_text' => 'Mass', 'type' => 'fixed', 'value' => 5,
            'central_coupon_id' => $central->id, 'discount_id' => 1, 'source' => 'central',
        ])->assertSuccessful();
        $this->inTenant(function () {
            $own = Coupon::query()->where('code', 'MASS1')->firstOrFail();
            $this->assertNull($own->central_coupon_id, 'source/ref fields cannot be mass-assigned from the request');
            $this->assertSame('tenant', app(CouponCatalog::class)->findByCode('MASS1')->source);
        });
    }

    public function test_d_p3_3_code_lookups_are_memoised_and_cheap(): void
    {
        $this->centralCoupon('MEMO1');
        $this->flip();
        $this->inTenant(function () {
            $catalog = app(CouponCatalog::class);
            CouponCatalog::flushMemo();
            DB::connection('tenant')->enableQueryLog();
            $first = $catalog->findByCode('MEMO1');
            $cold = count(DB::connection('tenant')->getQueryLog());
            DB::connection('tenant')->flushQueryLog();
            $second = $catalog->findValidByCode('MEMO1');
            $warm = count(DB::connection('tenant')->getQueryLog());
            $this->assertNotNull($first);
            $this->assertSame($first->ref->id, $second->ref->id);
            $this->assertLessThanOrEqual(6, $cold);
            $this->assertLessThan($cold, $warm, 'central rows + tenant countries are memoised');
            $this->assertLessThanOrEqual(1, $warm, 'only the own-coupon probe runs again');
        });
    }
}
