<?php

declare(strict_types=1);

namespace Tests\Feature\Mobile;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use App\Models\Tenant\ProductVariant;
use App\Services\Mobile\MobilePasswordReset;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class MobileCommerceTest extends TestCase
{
    use SetsUpTenantPanel, BuildsOrders;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();
        // The shared test database is not fully migrated centrally; order placement notifies admins.
        if (!\Illuminate\Support\Facades\Schema::hasTable('admin_notifications')) {
            \Illuminate\Support\Facades\Schema::create('admin_notifications', function ($t) {
                $t->id(); $t->string('type'); $t->string('title'); $t->text('message')->nullable();
                $t->json('data')->nullable(); $t->boolean('is_read')->default(false); $t->timestamps();
            });
            $this->createdAdminNotifications = true;
        }
        $this->tenant->run(function () {
            $this->variant = $this->createOwnProductVariant(stock: 20, price: 100.0);
        });
    }

    private bool $createdAdminNotifications = false;

    protected function tearDown(): void
    {
        if ($this->createdAdminNotifications) {
            \Illuminate\Support\Facades\Schema::dropIfExists('admin_notifications');
        }
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function h(?string $token = null, ?Tenant $tenant = null): array
    {
        return array_filter([
            'X-Tenant' => ($tenant ?? $this->tenant)->id,
            'Accept' => 'application/json',
            'Authorization' => $token ? 'Bearer '.$token : null,
        ]);
    }

    private function register(string $email = 'buyer@example.com', ?Tenant $tenant = null): string
    {
        return $this->postJson('/api/mobile/v1/auth/register', [
            'name' => 'Buyer', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
        ], $this->h(null, $tenant))->assertCreated()->json('data.token');
    }

    private function shipping(): array
    {
        return ['name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '+15550001111', 'address' => '1 Test St, Testville'];
    }

    private function addToCart(string $token, int $qty = 2, array $extra = []): void
    {
        $this->postJson('/api/mobile/v1/cart/add', [
            'slug' => $this->variant->product->slug, 'variant_id' => $this->variant->id, 'qty' => $qty,
        ] + $extra, $this->h($token))->assertOk()->assertJsonPath('success', true);
    }

    public function test_resolve_by_code_and_clean_not_found(): void
    {
        $this->getJson('/api/mobile/v1/resolve?code='.$this->tenant->id)
            ->assertOk()->assertJsonPath('data.tenant_id', $this->tenant->id)->assertJsonPath('data.domain', $this->tenantHost);
        foreach (['', '?host=', '?code=', '?code=%00bad%20code', '?host=!!!', '?code=no-such-store'] as $q) {
            $this->getJson('/api/mobile/v1/resolve'.$q)->assertNotFound()->assertJsonPath('data.status', 'not_found');
        }
    }

    public function test_envelope_wraps_v1_controller_responses(): void
    {
        $token = $this->register();
        $this->getJson('/api/mobile/v1/me', $this->h($token))->assertOk()->assertJsonStructure(['success', 'data']);
        $this->getJson('/api/mobile/v1/coupon', $this->h($token))->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/mobile/v1/orders/does-not-exist', $this->h($token))->assertNotFound()->assertJsonPath('success', false);
    }

    public function test_cart_is_server_side_isolated_and_ignores_client_prices(): void
    {
        $a = $this->register('a@example.com');
        $this->addToCart($a, 2, ['price' => 0.01, 'unit_price' => 0.01]);

        $cart = $this->getJson('/api/mobile/v1/cart', $this->h($a))->assertOk();
        $this->assertSame(2, $cart->json('data.count'));
        $this->assertEquals(200.0, $cart->json('data.items_total'));
        $this->assertEquals(100.0, $cart->json('data.items.0.unit_price'));

        // survives across requests / tokens of the same customer, not visible to another customer
        $b = $this->register('b@example.com');
        $this->assertSame(0, $this->getJson('/api/mobile/v1/cart', $this->h($b))->json('data.count'));

        $this->postJson('/api/mobile/v1/cart/update', ['key' => 'v_'.$this->variant->id, 'qty' => 3], $this->h($a))->assertOk()->assertJsonPath('data.count', 3);
        $this->postJson('/api/mobile/v1/cart/remove', ['key' => 'v_'.$this->variant->id], $this->h($a))->assertOk()->assertJsonPath('data.count', 0);

        $this->postJson('/api/mobile/v1/cart/merge', ['items' => [
            ['product_id' => $this->variant->product_id, 'variant_id' => $this->variant->id, 'qty' => 1, 'price' => 0.01],
        ]], $this->h($a))->assertOk()->assertJsonPath('data.items_total', 100);
    }

    public function test_cross_tenant_token_cannot_read_cart_or_checkout(): void
    {
        $token = $this->register();
        $other = Tenant::create(['status' => TenantStatus::Active->value, 'shop_name' => 'Other']);
        try {
            $this->getJson('/api/mobile/v1/cart', $this->h($token, $other))->assertUnauthorized();
            $this->postJson('/api/mobile/v1/checkout', [], $this->h($token, $other))->assertUnauthorized();
        } finally {
            tenancy()->end();
            $other->delete();
        }
    }

    public function test_checkout_price_tamper_rejected_then_cod_order_uses_server_prices(): void
    {
        $token = $this->register();
        $this->addToCart($token, 2);

        $this->getJson('/api/mobile/v1/coupon', $this->h($token))->assertOk();
        $quote = $this->postJson('/api/mobile/v1/checkout/quote', [], $this->h($token))->assertOk();
        $this->assertEquals(200.0, $quote->json('data.grand_total'));

        // Client claims a (tampered) lower total: nothing is created.
        $this->postJson('/api/mobile/v1/checkout', [
            'payment_method' => 'cod', 'expected_total' => 1.0, 'shipping' => $this->shipping(), 'price' => 1,
        ], $this->h($token))->assertStatus(409)->assertJsonPath('code', 'price_changed');
        $this->assertSame(0, $this->tenant->run(fn () => Order::count()));

        $res = $this->postJson('/api/mobile/v1/checkout', [
            'payment_method' => 'cod', 'expected_total' => 200.0, 'shipping' => $this->shipping(),
            'grand_total' => 1, 'items' => [['price' => 1]],
        ], $this->h($token) + ['Idempotency-Key' => 'k1'])->assertCreated();
        $uuid = $res->json('data.order.uuid');

        $order = $this->tenant->run(fn () => Order::where('uuid', $uuid)->first());
        $this->assertEquals(200.0, (float) $order->grand_total);
        $this->assertSame(0, $this->tenant->run(fn () => Order::where('uuid', '!=', $uuid)->count()));

        // retry with the same key does not create a second order; cart is emptied
        $this->postJson('/api/mobile/v1/checkout', ['payment_method' => 'cod', 'shipping' => $this->shipping()], $this->h($token) + ['Idempotency-Key' => 'k1'])->assertOk();
        $this->assertSame(1, $this->tenant->run(fn () => Order::count()));
        $this->assertSame(0, $this->getJson('/api/mobile/v1/cart', $this->h($token))->json('data.count'));

        // order extras
        $this->getJson("/api/mobile/v1/orders/$uuid/tracking", $this->h($token))->assertOk()->assertJsonPath('data.uuid', $uuid);
        $this->postJson("/api/mobile/v1/orders/$uuid/reorder", [], $this->h($token))->assertOk();
        $this->assertSame(2, $this->getJson('/api/mobile/v1/cart', $this->h($token))->json('data.count'));
        if (\Illuminate\Support\Facades\Schema::hasTable('return_requests')) { // central table absent in the shared test DB
            $this->getJson('/api/mobile/v1/returns', $this->h($token))->assertOk()->assertJsonPath('data', []);
        }
        $this->getJson('/api/mobile/v1/returns/options', $this->h($token))->assertOk()->assertJsonStructure(['data' => ['reasons', 'methods', 'types']]);
        $this->postJson("/api/mobile/v1/orders/$uuid/pay", [], $this->h($token))->assertStatus(422)->assertJsonPath('code', 'not_online_payment');
    }

    public function test_online_payment_is_verified_server_side(): void
    {
        $central = \App\Models\PaymentGateway::create([
            'name' => 'Stripe', 'code' => 'stripe', 'type' => \App\Enums\PaymentGatewayType::Orders->value,
            'status' => 'active', 'mode' => 'test',
        ]);
        try {
            $this->onlinePaymentFlow();
        } finally {
            $central->delete();
        }
    }

    private function onlinePaymentFlow(): void
    {
        FakePaymentGateway::install('stripe');
        $this->tenant->run(function () {
            $g = \App\Models\Tenant\PaymentGateway::query()->where('code', 'stripe')->first();
            $g->required_keys = ['public_key', 'secret_key'];
            $g->required_values = ['public_key' => 'pk_test_x', 'secret_key' => 'sk_test_x'];
            $g->save();
        });
        $token = $this->register();
        $this->addToCart($token, 1);

        $gw = $this->getJson('/api/mobile/v1/checkout/gateways', $this->h($token))->assertOk()->json('data.gateways.0');
        $this->assertArrayNotHasKey('creds', $gw);

        $res = $this->postJson('/api/mobile/v1/checkout', [
            'payment_method' => $gw['code'], 'payment_gateway_id' => $gw['id'], 'shipping' => $this->shipping(),
        ], $this->h($token))->assertCreated()->assertJsonPath('data.requires_payment', true);
        $uuid = $res->json('data.order.uuid');

        // Forged success callback without a started payment must not mark the order paid.
        $cb = '/api/mobile/v1/payments/'.$this->tenant->id.'/stripe/success?order_id='.$uuid;
        $this->get($cb)->assertRedirect();
        $this->assertFalse((bool) $this->tenant->run(fn () => Order::where('uuid', $uuid)->value('paid')));

        $this->postJson("/api/mobile/v1/orders/$uuid/pay", [], $this->h($token))->assertOk();
        $this->assertTrue((bool) $this->tenant->run(fn () => Order::where('uuid', $uuid)->value('paid')));
        $this->getJson("/api/mobile/v1/orders/$uuid/payment-status", $this->h($token))->assertOk()->assertJsonPath('data.paid', true);
        // another customer cannot read or pay it
        $other = $this->register('other@example.com');
        $this->getJson("/api/mobile/v1/orders/$uuid/payment-status", $this->h($other))->assertNotFound();
        $this->postJson("/api/mobile/v1/orders/$uuid/pay", [], $this->h($other))->assertNotFound();
    }

    public function test_password_reset_and_device_token(): void
    {
        $token = $this->register('reset@example.com');
        $this->postJson('/api/mobile/v1/auth/forgot-password', ['email' => 'nobody@example.com'], $this->h())->assertOk();

        $plain = $this->tenant->run(fn () => app(MobilePasswordReset::class)->issue('reset@example.com'));
        $this->postJson('/api/mobile/v1/auth/reset-password', ['email' => 'reset@example.com', 'token' => 'wrong', 'password' => 'newpassword1', 'password_confirmation' => 'newpassword1'], $this->h())->assertStatus(422);
        $this->postJson('/api/mobile/v1/auth/reset-password', ['email' => 'reset@example.com', 'token' => $plain, 'password' => 'newpassword1', 'password_confirmation' => 'newpassword1'], $this->h())->assertOk();

        $this->getJson('/api/mobile/v1/me', $this->h($token))->assertUnauthorized();
        $new = $this->postJson('/api/mobile/v1/auth/login', ['email' => 'reset@example.com', 'password' => 'newpassword1'], $this->h())->assertOk()->json('data.token');

        $this->postJson('/api/mobile/v1/device-tokens', ['token' => 'fcm-123', 'platform' => 'android'], $this->h($new))->assertCreated();
        $this->getJson('/api/mobile/v1/preferences/options', $this->h())->assertOk()->assertJsonStructure(['data' => ['locales', 'currencies', 'countries']]);
    }

    public function test_bootstrap_applies_panel_color_overrides(): void
    {
        $created = false;
        if (!\Illuminate\Support\Facades\Schema::hasTable('home_variants')) {
            \Illuminate\Support\Facades\Schema::create('home_variants', function ($t) {
                $t->id(); $t->string('theme_slug'); $t->string('key'); $t->string('name')->nullable();
                $t->text('description')->nullable(); $t->string('preview_image')->nullable();
                $t->json('sections')->nullable(); $t->json('colors')->nullable(); $t->string('view')->nullable();
                $t->boolean('is_default')->default(false); $t->boolean('is_active')->default(true);
                $t->unsignedBigInteger('blade_theme_id')->nullable(); $t->timestamps();
            });
            $created = true;
        }
        \App\Models\HomeVariant::query()->where('theme_slug', 'ecommet')->delete();
        \App\Models\HomeVariant::create(['theme_slug' => 'ecommet', 'key' => 'v1', 'name' => 'Default', 'is_default' => true, 'is_active' => true, 'colors' => ['--color-primary' => '#111827', '--color-secondary' => '#4b5563']]);
        try {
            $this->tenant->run(function () {
                \App\Models\Tenant\MobileAppSetting::create(['key' => 'primary_color', 'value' => '#123456']);
                \App\Models\Tenant\MobileAppSetting::create(['key' => 'color_overrides', 'value' => ['secondary' => '#abcdef', 'bg_main' => '#ffffff']]);
            });
            $colors = $this->getJson('/api/mobile/v1/bootstrap', $this->h())->assertOk()->json('data.theme.colors');
            $this->assertSame('#123456', $colors['primary']);
            $this->assertSame('#abcdef', $colors['secondary']);
            $this->assertSame('#ffffff', $colors['bg_main']);
        } finally {
            \App\Models\HomeVariant::query()->where('theme_slug', 'ecommet')->delete();
            if ($created) {
                \Illuminate\Support\Facades\Schema::dropIfExists('home_variants');
            }
        }
    }
}
