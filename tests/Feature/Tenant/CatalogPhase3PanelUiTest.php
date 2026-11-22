<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\Tenant\CouponType;
use App\Models\CentralCoupon;
use App\Models\CentralFlashSale;
use App\Models\Country;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Order;
use App\Models\TenantCountry;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan phase 3 (Frontend): tenant panel coupons / flash-sales pages in sync vs central mode (read-only platform
 * rows, D6 error through the JSON validation pattern, attach/detach with merged data) and the order coupon display.
 */
class CatalogPhase3PanelUiTest extends TestCase
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
        $c = Country::forceCreate(['name' => 'Egypt', 'iso2' => 'EG']);
        TenantCountry::create(['tenant_id' => $this->tenant->id, 'country_id' => $c->id, 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        if (isset($this->tenant)) {
            tenancy()->end();
        }
        CatalogRefColumns::flush();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function flip(string $section, string $mode = CatalogSource::CENTRAL): void
    {
        tenancy()->end();
        CatalogSource::set($this->tenant, $section, $mode);
    }

    private function centralCoupon(string $code): CentralCoupon
    {
        tenancy()->end();

        return Model::withoutEvents(fn () => CentralCoupon::query()->create([
            'code' => $code, 'name' => "Central {$code}", 'type' => 'percentage', 'value' => 10, 'minimum_spend' => 0, 'active' => true,
        ]));
    }

    private function list(string $page): array
    {
        return $this->actingAsTenantAdmin()->getJson($this->tenantUrl("/admin/store/{$page}/data?draw=1&start=0&length=25"))->assertOk()->json('data');
    }

    public function test_coupons_page_sync_mode_has_no_platform_notice_and_central_mode_is_read_only_for_platform_rows(): void
    {
        $this->centralCoupon('PLAT10');
        $own = $this->tenant->run(fn () => Coupon::query()->create(['code' => 'MINE', 'type' => CouponType::Fixed, 'value' => 2]));
        $copy = $this->tenant->run(fn () => Coupon::query()->create(['central_coupon_id' => 999999, 'code' => 'OLDCOPY', 'type' => CouponType::Fixed, 'value' => 1]));

        $rows = $this->list('coupons');
        $this->assertStringNotContainsString('Managed by platform', json_encode($rows));
        $this->assertCount(2, $rows);

        $this->flip('coupons');
        $rows = collect($this->list('coupons'));
        $this->assertCount(2, $rows, 'central PLAT10 + own MINE; the legacy copy is hidden');
        $platform = $rows->first(fn ($r) => str_contains($r['coupon'], 'Central PLAT10'));
        $this->assertStringContainsString('Platform', $platform['coupon']);
        $this->assertStringContainsString('Managed by platform', $platform['actions']);
        $this->assertStringNotContainsString('data-modal-open', $platform['actions']);
        $this->assertStringNotContainsString('data-action-url', $platform['actions']);
        $mine = $rows->first(fn ($r) => str_contains($r['coupon'], 'MINE'));
        $this->assertStringContainsString('coupon-modal', $mine['actions']);
        $this->assertStringContainsString('/coupons/item/' . $own->id, $mine['actions']);

        // Legacy copy is not the tenant's in central mode.
        $this->actingAsTenantAdmin()->getJson($this->tenantUrl('/admin/store/coupons/item/' . $copy->id))->assertNotFound();
        $this->actingAsTenantAdmin()->deleteJson($this->tenantUrl('/admin/store/coupons/item/' . $copy->id))->assertNotFound();
        $this->actingAsTenantAdmin()->getJson($this->tenantUrl('/admin/store/coupons/item/' . $own->id))->assertOk();
    }

    public function test_d6_error_is_a_json_validation_error_on_the_code_field_for_create_and_edit(): void
    {
        $this->centralCoupon('TAKEN');
        $own = $this->tenant->run(fn () => Coupon::query()->create(['code' => 'MINE', 'type' => CouponType::Fixed, 'value' => 2]));
        $this->flip('coupons');
        $payload = ['code' => 'TAKEN', 'name_text' => 'Mine', 'type' => 'fixed', 'value' => 5];

        foreach ([
            ['postJson', '/admin/store/coupons'],
            ['postJson', '/admin/store/coupons/validate'],
            ['putJson', '/admin/store/coupons/item/' . $own->id],
            ['postJson', '/admin/store/coupons/item/' . $own->id . '/validate'],
        ] as [$verb, $path]) {
            $res = $this->actingAsTenantAdmin()->{$verb}($this->tenantUrl($path), $payload)->assertStatus(422)->assertJsonValidationErrors('code');
            $this->assertStringContainsString('platform coupon', $res->json('errors.code.0'));
        }
        $this->assertSame('MINE', $this->tenant->run(fn () => Coupon::query()->find($own->id)->code), 'nothing was saved');

        // Saving an own coupon under its own (non-colliding) code still works.
        $this->actingAsTenantAdmin()->putJson($this->tenantUrl('/admin/store/coupons/item/' . $own->id), ['code' => 'MINE'] + $payload)->assertOk();
    }

    public function test_flash_sales_page_central_mode_marks_platform_rows_read_only_and_attach_works_on_own_rows(): void
    {
        [$central, $own] = $this->tenant->run(fn () => [$this->createCentralCatalogVariant(price: 40.0), $this->createOwnProductVariant(price: 25.0)]);
        tenancy()->end();
        $sale = Model::withoutEvents(function () use ($central) {
            $s = CentralFlashSale::query()->create(['discount_percentage' => 10, 'active' => true]);
            DB::table('central_flash_sale_product')->insert(['central_flash_sale_id' => $s->id, 'product_id' => $central['central_product']->id, 'created_at' => now(), 'updated_at' => now()]);

            return $s;
        });
        $ownSale = $this->tenant->run(function () use ($own) {
            $s = FlashSale::query()->create(['product_id' => $own->product_id, 'discount_percentage' => 15, 'active' => true]);
            $s->products()->sync([$own->product_id]);

            return $s;
        });
        $legacy = $this->tenant->run(fn () => FlashSale::query()->create(['product_id' => $own->product_id, 'central_flash_sale_id' => $sale->id, 'discount_percentage' => 10, 'active' => true]));

        $this->assertStringNotContainsString('"Managed by platform"', json_encode($this->list('flash-sales')) ?: '');

        $this->flip('flash-sales');
        $rows = collect($this->list('flash-sales'));
        $this->assertCount(2, $rows, 'merged central sale + own sale; legacy copy hidden');
        $platform = $rows->first(fn ($r) => str_contains($r['actions'], 'Managed by platform'));
        $this->assertNotNull($platform);
        $this->assertStringContainsString('Platform', $platform['source']);
        $this->assertStringNotContainsString('data-action-url', $platform['actions']);
        $mine = $rows->first(fn ($r) => str_contains($r['source'], 'Store'));
        $this->assertStringContainsString('/flash-sales/item/' . $ownSale->id, $mine['actions']);

        // attach / detach toggles on an own sale; a platform copy and the legacy copy are 404.
        $url = fn (int $id) => $this->tenantUrl("/admin/store/flash-sales/item/{$id}/attach-product");
        $this->actingAsTenantAdmin()->postJson($url($ownSale->id), ['product_id' => $central['product']->id])->assertOk()->assertJsonPath('data.attached', true);
        $this->assertSame(1, DB::table('flash_sale_product')->where('flash_sale_id', $ownSale->id)->where('product_id', $central['product']->id)->count());
        $this->actingAsTenantAdmin()->postJson($url($ownSale->id), ['product_id' => $central['product']->id])->assertOk()->assertJsonPath('data.attached', false);
        $this->actingAsTenantAdmin()->postJson($url($legacy->id), ['product_id' => $central['product']->id])->assertNotFound();
        $this->actingAsTenantAdmin()->deleteJson($this->tenantUrl('/admin/store/flash-sales/item/' . $legacy->id))->assertNotFound();
    }

    public function test_order_coupon_code_prefers_the_snapshot_and_falls_back_to_the_tenant_row(): void
    {
        $this->tenant->run(function () {
            $customer = $this->createCustomer();
            $coupon = Coupon::query()->create(['code' => 'LEGACY5', 'type' => CouponType::Fixed, 'value' => 5]);

            $legacy = $this->createOrder($customer, [], attributes: ['discount_id' => $coupon->id]);
            $this->assertSame('LEGACY5', $legacy->appliedCouponCode());

            $central = $this->createOrder($customer, [], attributes: ['coupon_code' => 'PLAT10', 'coupon_type' => 'central', 'catalog_coupon_id' => 7, 'coupon_source' => 'central']);
            $this->assertNull($central->discount_id);
            $this->assertSame('PLAT10', Order::query()->find($central->id)->appliedCouponCode());

            $none = $this->createOrder($customer, []);
            $this->assertNull($none->appliedCouponCode());
        });
    }
}
