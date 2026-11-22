<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\Tenant\CouponType;
use App\Models\CentralCoupon;
use App\Models\Country;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\TenantCountry;
use App\Repositories\Tenant\Catalog\CatalogCoupon;
use App\Repositories\Tenant\Catalog\CouponCatalog;
use App\Services\Catalog\CatalogSyncCheck;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\Tenant\Checkout\OrderComposer;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\Catalog\CatalogRef;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\OrderPricing;
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
 * Dedup plan phase 3 — coupons (plan §4 DROP, §11 "Backend — Phase 3 design"): CouponCatalog central-only / tenant-only /
 * both / code collision (D6) / expired / inactive / country filter / usage (D4), checkout money identical between sync
 * mode and central mode with `platform_profit + vendor_profit = grand_total`, flag off unchanged, sync skip, panel
 * validation, `catalog:dedup-migrate coupons` dry-run / idempotent migrate / verify 0, sync-check metrics.
 */
class CatalogPhase3CouponsTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    private Country $served;

    private Country $servedToo;

    private Country $notServed;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        CatalogRefColumns::flush();
        $this->setUpTenantPanel();

        $this->served = Country::forceCreate(['name' => 'Egypt', 'iso2' => 'EG']);
        $this->servedToo = Country::forceCreate(['name' => 'Morocco', 'iso2' => 'MA']);
        $this->notServed = Country::forceCreate(['name' => 'Peru', 'iso2' => 'PE']);
        foreach ([$this->served, $this->servedToo] as $country) {
            TenantCountry::create(['tenant_id' => $this->tenant->id, 'country_id' => $country->id, 'is_active' => true]);
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->tenant)) {
            tenancy()->end();
            if (Schema::hasTable('catalog_dedup_runs')) {
                DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->delete();
            }
            foreach (['migrate', 'dry-run', 'verify'] as $step) {
                File::delete(DedupRunner::reportPath('coupons', (string) $this->tenant->id, $step));
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
        CatalogSource::set($this->tenant, 'coupons', $mode);
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

    private function syncCoupons(): void
    {
        tenancy()->end();
        try {
            app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'coupons');
        } finally {
            tenancy()->end();
        }
    }

    // ── flag off ──────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_flag_off_reads_the_synced_table_unchanged(): void
    {
        $this->centralCoupon('NOT-SYNCED');
        $this->inTenant(function () {
            Coupon::query()->create(['code' => 'OWN-OFF', 'type' => CouponType::Fixed, 'value' => 5]);
            $catalog = app(CouponCatalog::class);

            $this->assertFalse(CouponCatalog::enabled());
            $this->assertInstanceOf(Coupon::class, $catalog->findValidByCode('OWN-OFF'));
            $this->assertNull($catalog->findValidByCode('NOT-SYNCED'), 'sync mode does not see an un-synced central coupon');
            $this->assertSame('OWN-OFF', $catalog->latestValidCodeForCountry(null));
        });
    }

    // ── merged read ───────────────────────────────────────────────────────────────────────────────────────────────

    public function test_central_only_tenant_only_both_collision_expired_inactive_and_countries(): void
    {
        $central = $this->centralCoupon('CENTRAL10');
        $dup = $this->centralCoupon('DUP');
        $this->centralCoupon('EXPIRED', ['end_date' => now()->subDay()]);
        $this->centralCoupon('OFF', ['active' => false]);
        $trimmed = $this->centralCoupon('TRIM', [], [$this->served->id, $this->notServed->id]);
        $this->centralCoupon('ELSEWHERE', [], [$this->notServed->id]);
        $deleted = $this->centralCoupon('GONE');
        Model::withoutEvents(fn () => $deleted->delete());

        $this->inTenant(function () {
            // Legacy sync copy of CENTRAL10 with a stale value: ignored in central mode.
            Coupon::query()->create(['central_coupon_id' => 999999, 'code' => 'COPY-ONLY', 'type' => CouponType::Fixed, 'value' => 1]);
            Coupon::query()->create(['code' => 'OWN5', 'type' => CouponType::Fixed, 'value' => 5]);
            Coupon::query()->create(['code' => 'DUP', 'type' => CouponType::Fixed, 'value' => 3]); // legacy collision
        });
        $this->flip();

        $this->inTenant(function () use ($central, $dup, $trimmed) {
            $catalog = app(CouponCatalog::class);
            $this->assertTrue(CouponCatalog::enabled());

            $c = $catalog->findValidByCode('central10'); // case-insensitive like the column collation
            $this->assertInstanceOf(CatalogCoupon::class, $c);
            $this->assertSame((string) CatalogRef::central($central->id), (string) $c->ref);
            $this->assertNull($c->id);
            $this->assertSame($central->id, $c->central_coupon_id);
            $this->assertSame(OrderPricing::SOURCE_CENTRAL, CouponCatalog::orderSource($c));

            $own = $catalog->findValidByCode('OWN5');
            $this->assertSame('tenant', $own->source);
            $this->assertSame(OrderPricing::SOURCE_VENDOR, CouponCatalog::orderSource($own));
            $this->assertNotNull($own->id);

            // D6 legacy collision: tenant wins; the central code still blocks new own coupons.
            $collision = $catalog->findByCode('DUP');
            $this->assertSame('tenant', $collision->source);
            $this->assertSame('3.00', $collision->value);
            $this->assertTrue($catalog->centralCodeExists('DUP'));
            $this->assertFalse($catalog->centralCodeExists('OWN5'));

            $this->assertNull($catalog->findValidByCode('EXPIRED'), 'expired');
            $this->assertNotNull($catalog->findByCode('EXPIRED'), 'checkout re-read ignores the window, like before');
            $this->assertNull($catalog->findByCode('OFF'), 'switched-off central coupon is hidden (plan §11)');
            $this->assertNull($catalog->findByCode('GONE'), 'soft-deleted central coupon');
            $this->assertNull($catalog->findByCode('COPY-ONLY'), 'legacy copies are not read');
            $this->assertNull($catalog->findByCode('ELSEWHERE'), 'targets only a country the tenant does not serve');

            $trim = $catalog->findByCode('TRIM');
            $this->assertSame([$this->served->id], $trim->countryIds(), 'country set trimmed to the tenant countries');
            $this->assertTrue($trim->appliesToCountry($this->served->id));
            $this->assertFalse($trim->appliesToCountry($this->servedToo->id));
            $this->assertFalse($trim->appliesToCountry(null));

            $codes = $catalog->all()->map(fn (CatalogCoupon $c) => $c->code)->sort()->values()->all();
            $this->assertSame(['CENTRAL10', 'DUP', 'EXPIRED', 'OWN5', 'TRIM'], $codes);
            $this->assertSame(['TRIM'], $catalog->all($this->served->id)->reject->isAllCountries()->map->code->values()->all());
            $this->assertNotContains('TRIM', $catalog->all('all')->map->code->all());
        });
    }

    public function test_panel_blocks_a_code_used_by_a_central_coupon_only_in_central_mode(): void
    {
        $this->centralCoupon('TAKEN');
        $payload = ['code' => 'TAKEN', 'name_text' => 'Mine', 'type' => 'fixed', 'value' => 5];

        $this->actingAsTenantAdmin()->postJson($this->tenantUrl('/admin/store/coupons/validate'), $payload)->assertOk();

        $this->flip();
        $this->actingAsTenantAdmin()->postJson($this->tenantUrl('/admin/store/coupons/validate'), $payload)
            ->assertStatus(422)->assertJsonValidationErrors('code');
        $this->actingAsTenantAdmin()->postJson($this->tenantUrl('/admin/store/coupons/validate'), ['code' => 'FREE-CODE'] + $payload)->assertOk();

        // The merged list renders central (read-only) + own rows.
        $this->inTenant(fn () => Coupon::query()->create(['code' => 'MINE', 'type' => CouponType::Fixed, 'value' => 2]));
        $json = $this->actingAsTenantAdmin()->getJson($this->tenantUrl('/admin/store/coupons/data?draw=1&start=0&length=10'))->assertOk()->json('data');
        $this->assertCount(2, $json);
        $this->assertStringContainsString('Managed by platform', collect($json)->firstWhere(fn ($r) => str_contains($r['coupon'], 'TAKEN'))['actions']);
    }

    // ── checkout money: sync mode == central mode ──────────────────────────────────────────────────────────────────

    /** @return list<array<string, mixed>> money snapshot of the order(s) one cart produced */
    private function place(array $lines, Coupon|CatalogCoupon|null $coupon, $customer): array
    {
        $created = app(OrderComposer::class)->create($lines, [], [
            'customer' => $customer, 'coupon' => $coupon, 'gateway' => null, 'gateway_id' => null,
            'shipping_amount' => 4.25, 'zone_rate_id' => null, 'shipping_address' => ['name' => 'T', 'address' => '1 St', 'city' => 'C'],
        ]);

        $out = [];
        foreach (array_filter([$created['primary'], $created['companion']]) as $order) {
            $order = Order::query()->with('items')->findOrFail($order->id);
            $this->assertEqualsWithDelta((float) $order->grand_total, (float) $order->platform_profit + (float) $order->vendor_profit, 0.011,
                'platform_profit + vendor_profit = order_grand_total');
            $out[] = [
                'order' => array_map(fn ($v) => is_numeric($v) ? round((float) $v, 2) : $v, $order->only([
                    'shipping_charge', 'coupon_discount', 'coupon_source', 'discount_percentage', 'platform_profit', 'vendor_profit',
                ])) + ['grand_total' => round((float) $order->grand_total, 2)],
                'items' => $order->items->map(fn (OrderItem $i) => array_map(fn ($v) => is_numeric($v) ? round((float) $v, 2) : $v, $i->only([
                    'cost', 'profit_amount', 'qty', 'price', 'sub_total', 'fixed_shipping', 'discount', 'discount_source', 'grand_total',
                ])))->all(),
                'ref' => [$order->coupon_code, $order->coupon_type, $order->catalog_coupon_id === null ? null : (int) $order->catalog_coupon_id],
                'discount_id' => $order->discount_id,
            ];
        }

        return $out;
    }

    private function carts(array $central, $own): array
    {
        $cLine = fn (array $line) => ['key' => 'v_' . $central['variant']->id, 'product' => $central['product'], 'variant' => $central['variant'], 'product_variant_id' => $central['variant']->id, 'line' => $line];
        $oLine = fn (array $line) => ['key' => 'v_' . $own->id, 'product' => $own->product, 'variant' => $own, 'product_variant_id' => $own->id, 'line' => $line];
        $centralFlash = OrderPricing::item(30.0, 10.0, 2, 5.0, 10.0, OrderPricing::SOURCE_CENTRAL) + ['is_own' => false];
        $vendorFlash = OrderPricing::item(30.0, 10.0, 1, 5.0, 15.0, OrderPricing::SOURCE_VENDOR) + ['is_own' => false];
        $ownLine = OrderPricing::item(0.0, 25.0, 3, 0.0, 20.0, OrderPricing::SOURCE_VENDOR) + ['is_own' => true];

        return [
            'central' => [$cLine($centralFlash), $cLine($vendorFlash)],
            'own' => [$oLine($ownLine)],
            'mixed' => [$cLine($centralFlash), $cLine($vendorFlash), $oLine($ownLine)],
        ];
    }

    public function test_order_totals_identical_between_sync_and_central_mode_with_usage_counts(): void
    {
        $centralCoupon = $this->centralCoupon('PCT10', ['type' => 'percentage', 'value' => 10]);
        $this->syncCoupons(); // sync mode: tenant copy of PCT10
        [$central, $own, $customer] = $this->inTenant(function () {
            Coupon::query()->create(['code' => 'VEND7', 'type' => CouponType::Fixed, 'value' => 7.5]);

            return [$this->createCentralCatalogVariant(price: 40.0), $this->createOwnProductVariant(price: 25.0), $this->createCustomer()];
        });

        $run = function () use ($central, $own, $customer) {
            return $this->inTenant(function () use ($central, $own, $customer) {
                $catalog = app(CouponCatalog::class);
                $out = [];
                foreach ($this->carts($central, $own) as $cart => $lines) {
                    foreach (['none' => null, 'central' => 'PCT10', 'vendor' => 'VEND7'] as $c => $code) {
                        $out["{$cart}/{$c}"] = $this->place($lines, $code ? $catalog->findValidByCode($code) : null, $customer);
                    }
                }

                return $out;
            });
        };

        $sync = $run();
        $this->flip();
        $merged = $run();

        $money = fn (array $orders) => array_map(fn ($o) => ['order' => $o['order'], 'items' => $o['items']], $orders);
        foreach ($sync as $name => $orders) {
            $this->assertEquals($money($orders), $money($merged[$name]), "{$name}: money identical in sync and central mode");
            $this->assertSame(str_starts_with($name, 'mixed') ? 2 : 1, count($orders));
        }

        // Identity columns: a sync copy and the live central coupon give the same typed ref; discount_id only for own rows.
        $this->assertSame(['PCT10', 'central', $centralCoupon->id], $sync['central/central'][0]['ref']);
        $this->assertSame(['PCT10', 'central', $centralCoupon->id], $merged['central/central'][0]['ref']);
        $this->assertNotNull($sync['central/central'][0]['discount_id']);
        $this->assertNull($merged['central/central'][0]['discount_id']);
        $this->assertSame('central', $merged['mixed/central'][0]['order']['coupon_source']);
        $this->assertSame('vendor', $merged['mixed/vendor'][1]['order']['coupon_source']);
        $this->assertSame([null, null, null], $merged['own/none'][0]['ref']);

        // D4: per-tenant usage = checkouts by typed ref (mixed cart counts once): 3 carts × 2 modes.
        $this->inTenant(function () use ($centralCoupon) {
            $catalog = app(CouponCatalog::class);
            $this->assertSame(6, $catalog->usageCount(CatalogRef::central($centralCoupon->id)));
            $own = $catalog->findByCode('VEND7');
            $this->assertSame(6, $catalog->usageCount($own->ref));
            $this->assertSame(0, $catalog->usageCount(CatalogRef::central(987654)));
        });
    }

    public function test_sync_skips_central_mode_tenants(): void
    {
        $this->flip();
        $this->centralCoupon('SKIPPED');
        $this->syncCoupons();

        $this->inTenant(fn () => $this->assertSame(0, Coupon::query()->count(), 'no coupon copies for a central-mode tenant'));
    }

    // ── dedup-migrate + sync-check ────────────────────────────────────────────────────────────────────────────────

    public function test_dedup_migrate_dry_run_idempotent_and_verify_zero(): void
    {
        $central = $this->centralCoupon('LEGACY', ['type' => 'fixed', 'value' => 4], [$this->served->id]);
        $this->centralCoupon('INACTIVE', ['active' => false]);
        $this->syncCoupons();

        $orderId = $this->inTenant(function () {
            Coupon::query()->create(['code' => 'MINE', 'type' => CouponType::Fixed, 'value' => 2]);
            $copy = Coupon::query()->where('code', 'LEGACY')->firstOrFail();
            $order = $this->createOrder($this->createCustomer(), [[$this->createOwnProductVariant(), 1]], attributes: ['discount_id' => $copy->id]);

            return $order->id;
        });

        $dry = $this->artisan('catalog:dedup-migrate', ['section' => 'coupons', '--tenant' => [$this->tenant->id], '--dry-run' => true]);
        $dry->assertExitCode(0);
        $this->inTenant(function () use ($orderId) {
            $this->assertNull(Order::query()->find($orderId)->coupon_type, 'dry run writes nothing');
        });

        $this->artisan('catalog:dedup-migrate', ['section' => 'coupons', '--tenant' => [$this->tenant->id]])->assertExitCode(0);
        $this->inTenant(function () use ($orderId, $central) {
            $order = Order::query()->find($orderId);
            $this->assertSame(['LEGACY', 'central', $central->id], [$order->coupon_code, $order->coupon_type, (int) $order->catalog_coupon_id]);
            $this->assertSame('LEGACY', $order->appliedCouponCode());
        });

        $report = app(DedupRunner::class)->run($this->tenant, 'coupons', 'migrate');
        $this->assertSame('done', $report['status']);
        $this->assertSame(0, $report['counts']['orders_ref_filled'], 'second migrate adds nothing');
        $this->assertSame(1, $report['counts']['inactive_central']);

        $verify = app(DedupRunner::class)->run($this->tenant, 'coupons', 'verify');
        $this->assertSame('done', $verify['status']);
        $this->assertSame(0, $verify['diffs'], json_encode($verify));
        $this->assertSame(1, $verify['counts']['inactive_central']);

        $metrics = app(CatalogSyncCheck::class)->run([(string) $this->tenant->id], ['coupons'])[0]['sections']['coupons'];
        $this->assertSame(0, $metrics['applicable_missing']);
        $this->assertSame(1, $metrics['inactive_central_copies']);
        $this->assertSame(0, $metrics['orders_coupon_refs_pending']);
        $this->assertSame(0, $metrics['dedup_verify_diffs']);
    }

    public function test_dedup_migrate_skips_tenant_without_phase3_columns(): void
    {
        $migration = require base_path('database/migrations/tenant/2026_11_25_000001_add_coupon_refs_to_orders_table.php');
        $this->inTenant(fn () => $migration->down());
        CatalogRefColumns::flush();
        try {
            $report = app(DedupRunner::class)->run($this->tenant, 'coupons', 'migrate');
            $this->assertSame('skipped', $report['status']);

            // Flag on without the schema: readers fall back to the synced table (no crash).
            $this->flip();
            $this->inTenant(function () {
                Coupon::query()->create(['code' => 'FALLBACK', 'type' => CouponType::Fixed, 'value' => 1]);
                $this->assertFalse(CouponCatalog::enabled());
                $this->assertInstanceOf(Coupon::class, app(CouponCatalog::class)->findByCode('FALLBACK'));
                $this->assertSame([], OrderComposer::couponRef(Coupon::query()->first()));
            });
        } finally {
            $this->inTenant(fn () => $migration->up());
            CatalogRefColumns::flush();
        }
    }
}
