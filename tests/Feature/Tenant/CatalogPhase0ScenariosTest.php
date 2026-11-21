<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\ActivationStatus;
use App\Enums\OrderStatus;
use App\Jobs\ResyncTenantChangeRequestJob;
use App\Jobs\SyncCentralProductStockToTenantsJob;
use App\Jobs\SyncTenantSectionJob;
use App\Jobs\SyncTenantSectionsJob;
use App\Livewire\Admin\Order\OrderDetailPage;
use App\Livewire\Tenant\Storefront\OrderStatusPage;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Category as CentralCategory;
use App\Models\CentralCoupon;
use App\Models\CentralFlashSale;
use App\Models\ProductLabel as CentralLabel;
use App\Models\Tenant\Category;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\ProductLabel;
use App\Models\Tenant\ProductVariant;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\Catalog\CatalogSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * QA, dedup plan phase 0 (Frontend handoff scenarios, tenant side): central soft deletes reach tenants through the
 * sync exactly like hard deletes did, order pages survive a soft-deleted central variant, and a `central`-mode section
 * receives no writes from any guarded entry point (§2b).
 */
class CatalogPhase0ScenariosTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function syncSection(string $section): int
    {
        try {
            return app(TenantCatalogSyncService::class)->syncSection($this->tenant, $section);
        } finally {
            tenancy()->end();
        }
    }

    /** Central fixtures / deletes without observer fan-out (the sync is run explicitly). */
    private function central(callable $callback): mixed
    {
        tenancy()->end();

        return Model::withoutEvents($callback);
    }

    #[Test]
    public function deleted_central_rows_leave_the_tenant_on_the_next_sync_and_codes_are_reusable(): void
    {
        // ── Category: delete parent -> children become roots centrally, tenant copy dropped, child re-rooted ──
        [$parent, $child] = $this->central(fn () => [
            $p = CentralCategory::query()->create(['status' => 'published']),
            CentralCategory::query()->create(['status' => 'published', 'parent_id' => $p->id]),
        ]);
        $this->syncSection('categories');
        $tenantCat = fn (int $centralId) => $this->tenant->run(fn () => Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->where('central_category_id', $centralId)->first());
        $this->assertSame($tenantCat($parent->id)->id, $tenantCat($child->id)->parent_id);

        $this->central(fn () => $parent->delete());
        $this->assertNull($child->fresh()->parent_id, 'central child is a root now');
        $this->assertFalse(CentralCategory::query()->whereKey($parent->id)->exists(), 'gone from central lists');
        $this->syncSection('categories');
        $this->assertNull($tenantCat($parent->id), 'tenant copy of the deleted parent removed');
        $this->assertNull($tenantCat($child->id)->parent_id, 'tenant child re-rooted');

        // ── Coupon: delete X, sync, recreate X, sync -> tenant has the new one ─────────────────────────────────
        $old = $this->central(fn () => CentralCoupon::query()->create(['code' => 'QAX', 'type' => 'fixed', 'value' => 5]));
        $this->syncSection('coupons');
        $tenantCoupon = fn () => $this->tenant->run(fn () => Coupon::query()->where('code', 'QAX')->get(['id', 'central_coupon_id', 'value']));
        $this->assertSame([$old->id], $tenantCoupon()->pluck('central_coupon_id')->all());

        $this->central(fn () => $old->delete());
        $this->syncSection('coupons');
        $this->assertCount(0, $tenantCoupon(), 'deleted coupon removed from the tenant');

        $new = $this->central(fn () => CentralCoupon::query()->create(['code' => 'QAX', 'type' => 'fixed', 'value' => 7]));
        $this->assertNotSame($old->id, $new->id);
        $this->syncSection('coupons');
        $this->assertSame([$new->id], $tenantCoupon()->pluck('central_coupon_id')->all(), 'recreated code synced');
        $this->assertEquals(7, $tenantCoupon()->first()->value);

        // ── QA D-P0-1: delete X and re-create X with NO sync in between (queue backlog) -> one sync swaps them ──
        $this->central(fn () => $new->delete());
        $newer = $this->central(fn () => CentralCoupon::query()->create(['code' => 'QAX', 'type' => 'fixed', 'value' => 9]));
        $this->syncSection('coupons'); // used to throw on coupons_code_unique
        $this->assertSame([$newer->id], $tenantCoupon()->pluck('central_coupon_id')->all(), 'stale copy removed before upsert');
        $this->assertEquals(9, $tenantCoupon()->first()->value);

        // ── Flash sale / label: gone centrally and from the tenant after the next sync ───────────────────────
        $catalog = $this->tenant->run(fn () => $this->createCentralCatalogVariant());
        [$sale, $label] = $this->central(function () use ($catalog) {
            $sale = CentralFlashSale::query()->create(['discount_percentage' => 15, 'active' => true]);
            $sale->products()->attach($catalog['central_product']->id);
            $label = CentralLabel::query()->create(['text' => 'QA label', 'active' => true]);
            $label->products()->attach($catalog['central_product']->id);

            return [$sale, $label];
        });
        $this->syncSection('flash-sales');
        $this->syncSection('labels');
        $this->assertTrue($this->tenant->run(fn () => FlashSale::query()->where('central_flash_sale_id', $sale->id)->exists()));
        $this->assertTrue($this->tenant->run(fn () => ProductLabel::query()->where('central_product_label_id', $label->id)->exists()));

        $this->central(fn () => [$sale->delete(), $label->delete()]);
        $this->assertFalse(CentralFlashSale::query()->whereKey($sale->id)->exists());
        $this->assertFalse(CentralLabel::query()->ordered()->whereKey($label->id)->exists());
        $this->syncSection('flash-sales');
        $this->syncSection('labels');
        $this->assertFalse($this->tenant->run(fn () => FlashSale::query()->where('central_flash_sale_id', $sale->id)->exists()), 'tenant flash sale removed');
        $this->assertFalse($this->tenant->run(fn () => ProductLabel::query()->where('central_product_label_id', $label->id)->exists()), 'tenant label removed');
    }

    #[Test]
    public function order_pages_render_with_a_soft_deleted_central_variant_before_and_after_the_sync(): void
    {
        Bus::fake([SyncCentralProductStockToTenantsJob::class]);
        // Storefront Livewire components read the session (as in StorefrontAfterSalesUiTest).
        $session = $this->app['session']->driver();
        $session->start();
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Routing\Events\RouteMatched::class, fn ($event) => $event->request->setLaravelSession($session));

        $fx = $this->tenant->run(function () {
            $customer = $this->createCustomer();
            $catalog = $this->createCentralCatalogVariant(price: 40.0);
            $order = $this->createOrder($customer, [[$catalog['variant'], 2]], OrderStatus::Processing, paid: true);

            return ['customer' => $customer, 'catalog' => $catalog, 'order' => $order];
        });
        ['customer' => $customer, 'catalog' => $catalog, 'order' => $order] = $fx;
        $lineBefore = $this->tenant->run(fn () => OrderItem::query()->where('order_id', $order->id)->firstOrFail()->only(['price', 'sub_total', 'qty']));

        $centralAdmin = $this->centralAdmin();
        $centralVariant = $catalog['central_variant'];
        $this->central(fn () => $centralVariant->delete());
        $this->assertTrue($centralVariant->fresh() === null || $centralVariant->fresh()->trashed());

        $renderAll = function (string $stage) use ($customer, $order, $centralAdmin): void {
            // Tenant panel order detail.
            $this->actingAsTenantAdmin()->get($this->tenantUrl('/admin/orders/' . $order->id))->assertOk();
            tenancy()->end();

            // Storefront: account order page + invoice.
            $this->tenant->run(function () use ($customer, $order) {
                $this->actingAs($customer, 'storefront');
                Livewire::test(OrderStatusPage::class, ['uuid' => $order->uuid])->assertOk();
            });
            $this->actingAs($customer, 'storefront')->get($this->tenantUrl('/orders/' . $order->uuid . '/invoice'))->assertOk();
            tenancy()->end();

            // Central admin order detail + receipt.
            $this->actingAs($centralAdmin, 'admin');
            Livewire::test(OrderDetailPage::class, ['tenantId' => (string) $this->tenant->id, 'orderNumber' => $order->uuid])->assertOk();
            $this->get('http://localhost/admin/orders/' . $this->tenant->id . '/' . $order->uuid . '/receipt')->assertOk();
            tenancy()->end();
        };

        // Before the next sync: tenant variant still there, its central variant is soft-deleted.
        $renderAll('before sync');

        // After the products sync: tenant variant removed, the order line keeps its money snapshot.
        Queue::fake();
        $this->syncSection('products');
        $this->tenant->run(function () use ($catalog, $order, $lineBefore) {
            $this->assertFalse(ProductVariant::query()->whereKey($catalog['variant']->id)->exists(), 'tenant copy removed by the sync');
            $line = OrderItem::query()->where('order_id', $order->id)->firstOrFail();
            $this->assertNull($line->product_variant_id, 'B1: FK set null, line kept');
            $this->assertEquals($lineBefore, $line->only(['price', 'sub_total', 'qty']));
        });
        $renderAll('after sync');
    }

    #[Test]
    public function a_central_mode_section_gets_no_writes_from_any_guarded_entry_point(): void
    {
        // Price/stock/weight/shipping/profit jobs are documented as not guarded until phase 5 (plan §12 phase 0 row);
        // they rewrite prices of existing copies, not rows, and are faked here.
        Bus::fake([
            \App\Jobs\ApplyTenantProfitPercentageJob::class,
            \App\Jobs\SyncCentralProductPriceToTenantsJob::class,
            \App\Jobs\SyncCentralProductWeightToTenantsJob::class,
            \App\Jobs\SyncFixedShippingCostsToTenantsJob::class,
            SyncCentralProductStockToTenantsJob::class,
        ]);
        foreach (['labels', 'coupons', 'flash-sales', 'categories', 'products'] as $section) {
            CatalogSource::set($this->tenant, $section, CatalogSource::CENTRAL);
        }
        $catalog = $this->tenant->run(fn () => $this->createCentralCatalogVariant());
        $before = $this->tenant->run(fn () => [
            'labels' => ProductLabel::query()->count(),
            'coupons' => Coupon::query()->count(),
            'flash' => FlashSale::query()->count(),
            'categories' => Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->count(),
            'variants' => ProductVariant::query()->count(),
        ]);

        // Real observers (QUEUE_CONNECTION=sync in tests): create central rows -> fan-out to tenants.
        tenancy()->end();
        CentralLabel::query()->create(['text' => 'QA guard label', 'active' => true]);
        CentralCoupon::query()->create(['code' => 'QAGUARD', 'type' => 'fixed', 'value' => 1]);
        CentralFlashSale::query()->create(['discount_percentage' => 5, 'active' => true])->products()->attach($catalog['central_product']->id);
        CentralCategory::query()->create(['status' => 'published']);
        \App\Models\ProductVariant::query()->create(['product_id' => $catalog['central_product']->id, 'sku' => 'QA-GUARD-V2', 'price' => 1, 'stock' => 1]);
        tenancy()->end();

        $sections = ['labels', 'coupons', 'flash-sales', 'categories', 'products'];
        $tenantId = (string) $this->tenant->id;

        // Admin sync pages / TenantService::save / fan-out → SyncTenantSectionsJob → SyncTenantSectionJob.
        SyncTenantSectionsJob::dispatchSync($tenantId, $sections);
        foreach ($sections as $section) {
            SyncTenantSectionJob::dispatchSync($tenantId, $section, true); // onboarding chain (ProvisionTenantJob)
        }
        tenancy()->end();
        // Store change request.
        ResyncTenantChangeRequestJob::dispatchSync($tenantId, $sections);
        tenancy()->end();
        // CLI.
        $this->artisan('tenants:sync-data', ['tenant' => $tenantId, '--section' => $sections])->assertSuccessful();
        tenancy()->end();
        // Core atomic sync (ProvisionTenantJob) and the product entry points.
        $service = app(TenantCatalogSyncService::class);
        $service->syncSectionsAtomically($this->tenant, $sections);
        tenancy()->end();
        $service->syncProductToTenant($catalog['central_product']->fresh(), $this->tenant, force: true);
        tenancy()->end();
        $this->assertSame(0, $service->syncNewProductsForTenant($this->tenant));
        tenancy()->end();

        $after = $this->tenant->run(fn () => [
            'labels' => ProductLabel::query()->count(),
            'coupons' => Coupon::query()->count(),
            'flash' => FlashSale::query()->count(),
            'categories' => Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->count(),
            'variants' => ProductVariant::query()->count(),
        ]);
        $this->assertSame($before, $after, 'no entry point wrote a central-mode section');
        $this->assertFalse($this->tenant->run(fn () => Coupon::query()->where('code', 'QAGUARD')->exists()));
    }

    private function centralAdmin(): AdminUser
    {
        $permissions = ['sales.orders.manage', 'sales.orders.view'];
        $role = AdminRole::query()->create(['name' => 'QA role ' . uniqid(), 'permissions' => $permissions, 'permissions_count' => count($permissions)]);

        return AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => 'QA Admin',
            'email' => uniqid('qa') . '@example.com',
            'password' => Hash::make('password12345'),
            'status' => ActivationStatus::Active,
        ]);
    }
}
