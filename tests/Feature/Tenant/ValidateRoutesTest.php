<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Tenant\Category;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

class ValidateRoutesTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    public function test_every_validate_route_carries_the_tenant_validate_throttle(): void
    {
        foreach ($this->validateRoutes() as $name => $route) {
            $this->assertContains(
                'throttle:tenant-validate',
                $route->middleware(),
                "Route [{$name}] is missing the throttle:tenant-validate middleware."
            );
        }
    }

    public function test_valid_payload_returns_valid_true(): void
    {
        foreach ($this->validateRoutes() as $name => $route) {
            $payload = $this->payloadFor($name);

            if ($payload === self::UNSUPPORTED) {
                continue; // documented skip, see payloadFor()
            }

            $uri = $this->buildUri($route);

            $response = $this->actingAsTenantAdmin()->postJson($this->tenantUrl($uri), $payload);

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "Route [{$name}] expected valid:true for payload " . json_encode($payload) . ' but got ' . $response->getContent()
            );
            $response->assertJson(['valid' => true]);
        }
    }

    public function test_empty_payload_fails_validation(): void
    {
        foreach ($this->validateRoutes() as $name => $route) {
            if (in_array($name, self::NO_REQUIRED_FIELDS, true)) {
                continue; // legitimately has nothing required — nothing to assert here
            }

            $uri = $this->buildUri($route);

            $response = $this->actingAsTenantAdmin()->postJson($this->tenantUrl($uri), []);

            $this->assertSame(
                422,
                $response->getStatusCode(),
                "Route [{$name}] expected 422 for an empty payload but got {$response->getStatusCode()}: {$response->getContent()}"
            );
            $response->assertJsonStructure(['errors']);
        }
    }

    private const UNSUPPORTED = '__unsupported__';

    /** Validate routes whose FormRequest has no required fields — an empty payload legitimately passes. */
    private const NO_REQUIRED_FIELDS = [
        'tenant.own-products.validate',
        'tenant.settings.mail.validate',
        'tenant.settings.tracking.validate',
    ];

    /** @return array<string, \Illuminate\Routing\Route> */
    private function validateRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (!$name || !str_starts_with($name, 'tenant.') || !str_ends_with($name, '.validate')) {
                continue;
            }

            if ($name === 'tenant.login.validate') {
                continue; // guest-only, unauthenticated by design
            }

            $routes[$name] = $route;
        }

        return $routes;
    }

    private function buildUri($route): string
    {
        $uri = $route->uri();

        $ids = [
            'product' => Product::query()->value('id') ?? $this->makeProduct()->id,
            'category' => Category::query()->value('id') ?? $this->makeCategory()->id,
            'customerId' => Customer::query()->value('id') ?? $this->makeCustomer()->id,
            'id' => 1,
            'ticketId' => 1,
            'requestId' => 1,
            'orderId' => 1,
            'theme' => $this->activeTheme->id,
            'variant' => 1,
            'emailTemplate' => 1,
        ];

        foreach ($ids as $key => $value) {
            $uri = str_replace('{' . $key . '}', (string) $value, $uri);
        }

        return '/' . ltrim($uri, '/');
    }

    private function makeProduct(): Product
    {
        return Product::create([
            'sku' => 'TEST-SKU-1',
            'active' => true,
            'price' => 10,
        ]);
    }

    private function makeCategory(): Category
    {
        return Category::create(['active' => true]);
    }

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'full_name' => 'Jane Buyer',
            'email' => 'jane@example.com',
            'password' => bcrypt('password'),
            'active' => true,
        ]);
    }

    /** @return array<string,mixed>|string */
    private function payloadFor(string $routeName): array|string
    {
        return match ($routeName) {
            'tenant.ui-kit.validate' => [
                'text' => 'Hello',
                'email' => 'user@example.com',
                'select' => 'one',
            ],
            'tenant.brand-requests.validate' => [
                'title' => 'New brand request',
                'description' => str_repeat('Please add this brand. ', 3),
            ],
            'tenant.brand-requests.messages.validate' => ['message' => 'Following up on this.'],
            'tenant.brand-requests.pay.validate' => ['gateway' => 'stripe', 'payment_request_id' => 1],
            'tenant.categories.validate' => [
                'active_locale' => 'en',
                'translations' => ['en' => ['name' => 'Test Category']],
            ],
            'tenant.compliance.accept.validate' => ['accept' => true],
            'tenant.customers.validate' => [
                'full_name' => 'Jane Buyer',
                'email' => 'new-customer@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ],
            'tenant.customers.addresses.validate' => ['address_line_1' => '123 Test Street'],
            'tenant.finance.buy-languages.purchase.validate' => ['gateway' => 'stripe', 'language_id' => 1],
            'tenant.finance.vendor-purchase-settle.pay.validate' => ['gateway' => 'stripe'],
            'tenant.finance.wallet.subscribe.validate' => ['gateway' => 'stripe', 'package_id' => 1],
            'tenant.manufacturing.validate' => [
                'product_name' => 'Custom widget',
                'quantity' => 5,
            ],
            'tenant.manufacturing.messages.validate' => ['message' => 'Any update?'],
            'tenant.manufacturing.pay.validate' => ['gateway' => 'stripe', 'payment_request_id' => 1],
            'tenant.onboarding.logo.validate' => self::UNSUPPORTED, // requires a real uploaded file
            'tenant.orders.shipping-status.validate' => ['shipping_status' => 'pending'],
            'tenant.own-products.validate' => [], // no required fields
            'tenant.product-requests.validate' => [
                'title' => 'Please list this product',
                'description' => str_repeat('Product request details. ', 3),
            ],
            'tenant.product-requests.replies.validate' => ['reply' => 'Sure, will do.'],
            'tenant.products.validate' => [
                'price' => 10,
                'active_locale' => 'en',
                'translations' => ['en' => ['name' => 'Test Product']],
            ],
            'tenant.returns.notes.validate' => ['note_text' => 'Internal note.'],
            'tenant.returns.refunded.validate' => ['refund_amount' => 10],
            'tenant.returns.reject.validate' => ['reject_reason' => 'Item damaged in transit.'],
            'tenant.returns.request-info.validate' => ['info_message' => 'Please share more photos.'],
            'tenant.settings.account.validate' => [
                'adminName' => 'Jane Owner',
                'adminEmail' => 'owner@example.com',
                'shopName' => 'Panel Test Store',
            ],
            'tenant.settings.admins.validate' => [
                'name' => 'New Admin',
                'email' => 'new-admin@example.com',
                'password' => 'secret123',
                'status' => 'active',
            ],
            'tenant.settings.ai-translation.purchase.validate' => ['gateway' => 'stripe'],
            'tenant.settings.compliance.validate' => self::UNSUPPORTED, // rules vary by ?section=
            'tenant.settings.domains.validate' => ['domain' => 'shop.example.com'],
            'tenant.settings.email-templates.validate' => ['subject' => 'Order confirmed'],
            'tenant.settings.general.category-request.validate' => ['requested_category_ids' => [1]],
            'tenant.settings.general.country-request.validate' => ['requested_country_ids' => [1]],
            'tenant.settings.general.validate' => ['profit_percentage' => 10],
            'tenant.settings.languages-manage.purchase.validate' => ['gateway' => 'stripe', 'language_id' => 1],
            'tenant.settings.mail.test.validate' => ['email' => 'test@example.com'],
            'tenant.settings.mail.validate' => [], // every field nullable
            'tenant.settings.return-policy.validate' => ['window_days' => 14, 'fee' => 0],
            'tenant.settings.roles-permissions.validate' => ['name' => 'New Role'],
            'tenant.settings.tracking.validate' => [], // every field nullable
            'tenant.store.appearance.colors.validate' => self::UNSUPPORTED, // dynamic field names per theme
            'tenant.store.appearance.footer.validate' => ['translations' => ['en' => ['footer_text' => 'Footer text']]],
            'tenant.store.appearance.general.validate' => self::UNSUPPORTED, // logo rules require a real file
            'tenant.store.appearance.promo-banner.validate' => ['promo_banner_title' => 'Sale!'],
            'tenant.store.appearance.social.validate' => [
                'icon' => 'Facebook',
                'url' => 'https://facebook.com/test',
                'serial_number' => 1,
            ],
            'tenant.store.banners.validate' => ['serial_number' => 1],
            'tenant.store.blade-theme.upload.validate' => self::UNSUPPORTED, // requires a real zip upload
            'tenant.store.coupons.validate' => [
                'code' => 'SAVE10',
                'name_text' => 'Save 10',
                'type' => 'fixed',
                'value' => 10,
            ],
            'tenant.store.flash-sales.validate' => [
                'product_ids' => [Product::query()->value('id') ?? $this->makeProduct()->id],
                'discount_percentage' => 10,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addWeek()->toDateString(),
            ],
            'tenant.store.pages.validate' => ['translations' => ['en' => ['title' => 'About us']]],
            'tenant.support.validate' => [
                'subject' => 'Need help',
                'message' => 'Please help with my order.',
            ],
            'tenant.support.replies.validate' => ['message' => 'Thanks, following up.'],
            default => self::UNSUPPORTED,
        };
    }
}
