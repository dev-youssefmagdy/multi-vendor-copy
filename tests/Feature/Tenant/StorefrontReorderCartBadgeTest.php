<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\OrderStatus;
use App\Jobs\SyncCentralProductStockToTenantsJob;
use App\Livewire\Tenant\Storefront\OrderStatusPage;
use App\Livewire\Tenant\Storefront\ProfilePage;
use App\Services\Mail\TemplateMailService;
use App\Services\Tenant\TemplateRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * "Reorder" (order page + account order list) must dispatch the same events as the regular
 * add-to-cart, so the theme header badges (updated by JS on `storefront-cart-added`) refresh
 * without a page reload.
 */
class StorefrontReorderCartBadgeTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;
    use SetsUpTenantPanel;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.stores.file.path' => sys_get_temp_dir().'/storefront-reorder-test-cache']);
        Cache::forgetDriver('file');

        $this->setUpTenantPanel();

        Bus::fake([SyncCentralProductStockToTenantsJob::class]);
        $this->spy(TemplateMailService::class);

        $session = $this->app['session']->driver();
        $session->start();
        Event::listen(RouteMatched::class, fn (RouteMatched $event) => $event->request->setLaravelSession($session));
    }

    protected function tearDown(): void
    {
        TemplateRegistryService::clearForce();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    #[Test]
    public function reorder_dispatches_cart_badge_events_on_order_page_and_profile(): void
    {
        $this->tenant->run(function (): void {
            $customer = $this->createCustomer();
            $a = $this->createOwnProductVariant(stock: 20, price: 30.0);
            $b = $this->createOwnProductVariant(stock: 20, price: 10.0);
            $this->actingAs($customer, 'storefront');

            $order = $this->createOrder($customer, [[$a, 2], [$b, 1]], OrderStatus::Delivered);
            // Normally shared by the ApplyStorefrontSessionContext middleware, which Livewire::test() skips.
            view()->share('storefrontThemeVariant', null);

            foreach (['elora', 'souqify'] as $theme) {
                TemplateRegistryService::force($theme);
                session()->forget('storefront_cart');

                Livewire::test(OrderStatusPage::class, ['uuid' => $order->uuid])
                    ->call('reorder')
                    ->assertDispatched('cartUpdated')
                    ->assertDispatched('storefront-cart-added', qty: 3);

                $this->assertSame(3, (int) collect(session('storefront_cart'))->sum('qty'), $theme);

                Livewire::test(ProfilePage::class)
                    ->call('reorder', $order->uuid)
                    ->assertDispatched('cartUpdated')
                    ->assertDispatched('storefront-cart-added', qty: 3);

                $this->assertSame(6, (int) collect(session('storefront_cart'))->sum('qty'), $theme);
            }
        });
    }
}
