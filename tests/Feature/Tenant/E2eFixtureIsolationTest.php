<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\CentralCoupon;
use App\Models\Product as CentralProduct;
use App\Models\Tenant;
use App\Models\Tenant\Catalog\MergedProduct;
use App\Repositories\Tenant\Catalog\CouponCatalog;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\E2eFixtures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Rollout QA step 4: central E2E fixtures (sku `E2E-…`, category slug `e2e-…`, coupon code `E2E…`) must never reach a
 * real tenant (they leaked into `vendor`: 8 adopted products, the "E2E Gadgets" category, the E2E10 coupon).
 */
class E2eFixtureIsolationTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        CatalogRefColumns::flush();
        E2eFixtures::flush();
        $this->setUpTenantPanel();
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        E2eFixtures::flush();
        CatalogRefColumns::flush();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function fixtureProduct(): array
    {
        $x = $this->tenant->run(fn () => $this->createCentralCatalogVariant());
        tenancy()->end();
        CentralProduct::withoutEvents(fn () => CentralProduct::query()->whereKey($x['central_product']->id)->update(['sku' => 'E2E-GUARD']));
        E2eFixtures::flush();

        return $x;
    }

    public function test_only_the_dedicated_e2e_tenants_see_fixtures(): void
    {
        $this->assertTrue(E2eFixtures::hiddenFor($this->tenant));
        $this->assertFalse(E2eFixtures::hiddenFor(new Tenant(['id' => 'e2e-central'])));
        $this->assertFalse(E2eFixtures::hiddenFor(new Tenant(['id' => 'e2e-sync'])));
        tenancy()->end();
        $this->assertFalse(E2eFixtures::hiddenFor(), 'central context (admin) sees everything');
    }

    public function test_real_tenant_cannot_adopt_or_read_a_fixture_product_in_central_mode(): void
    {
        $x = $this->fixtureProduct();
        CatalogSource::set($this->tenant, 'products', CatalogSource::CENTRAL);
        CacheVersion::flushMemo();

        $this->tenant->run(function () use ($x) {
            $id = (int) $x['central_product']->id;
            $this->assertFalse((new ProductCatalog())->adopt($id));
            // a leaked override row (written before the guard existed) is ignored by every read
            \DB::connection('tenant')->table(ProductCatalog::OVERRIDES)->insert(['central_product_id' => $id, 'adopted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            CacheVersion::flushMemo();
            $this->assertSame(0, MergedProduct::query()->where('central_product_id', $id)->count());
            $this->assertNotContains($id, app(TenantCatalogSyncService::class)->pendingNewProductsQuery()->pluck('id')->all());
        });
    }

    public function test_sync_mode_does_not_copy_a_fixture_product(): void
    {
        $x = $this->fixtureProduct();
        $id = (int) $x['central_product']->id;
        // drop the copy the helper made, then let the sync try to (re)create it
        $this->tenant->run(fn () => \DB::connection('tenant')->table('products')->where('central_product_id', $id)->delete());
        tenancy()->end();
        app(TenantCatalogSyncService::class)->syncProductToTenant($x['central_product']->fresh(), $this->tenant, true);
        tenancy()->end();
        $this->tenant->run(function () use ($id) {
            $this->assertSame(0, \DB::connection('tenant')->table('products')->where('central_product_id', $id)->count());
            $this->assertNotContains($id, app(TenantCatalogSyncService::class)->pendingNewProductsQuery()->pluck('id')->all());
        });
    }

    public function test_fixture_coupon_is_not_applicable_to_a_real_tenant(): void
    {
        tenancy()->end();
        Model::withoutEvents(fn () => CentralCoupon::query()->create([
            'code' => 'E2E10', 'name' => 'E2E', 'type' => 'percentage', 'value' => 10, 'minimum_spend' => 0, 'active' => true,
        ]));
        Model::withoutEvents(fn () => CentralCoupon::query()->create([
            'code' => 'REAL10', 'name' => 'Real', 'type' => 'percentage', 'value' => 10, 'minimum_spend' => 0, 'active' => true,
        ]));
        E2eFixtures::flush();
        CatalogSource::set($this->tenant, 'coupons', CatalogSource::CENTRAL);

        $this->tenant->run(function () {
            CouponCatalog::flushMemo();
            $codes = app(CouponCatalog::class)->applicableCentral()->map(fn ($c) => $c->code)->all();
            $this->assertContains('REAL10', $codes);
            $this->assertNotContains('E2E10', $codes);
        });
    }
}
