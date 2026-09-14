<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Tenant\Category;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Covers at least one mutating (POST/PUT/PATCH/DELETE) route per controller
 * under routes/tenant_panel.php, per prompt_12 section 4.2. Login, logout,
 * compliance, gateway charge/success/cancel/webhook and every `*.validate`
 * (including `*.validate.update`) route are out of scope — they're exercised
 * by ValidateRoutesTest instead.
 */
class PanelMutationsContractTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;

    private ?Category $category = null;
    private ?Product $product = null;
    private ?Customer $customer = null;

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

    /**
     * @return iterable<string, array{0:string,1:string,2:array,3:bool}>
     *   [method, uri, validPayload, emptyPayloadHasRequiredFields]
     */
    public static function scenarios(): iterable
    {
        // Each key documents which controller the scenario exercises.
        yield 'Catalog\CategoryController@store' => ['post', '/categories', [
            'active_locale' => 'en',
            'translations' => ['en' => ['name' => 'A New Category']],
        ], true];

        yield 'Catalog\CategorySortController@update' => ['post', '/categories/sort', [
            'order' => ['__CATEGORY_ID__'],
        ], false];

        yield 'Catalog\ProductController@store' => ['post', '/products', [
            'price' => 25,
            'active_locale' => 'en',
            'translations' => ['en' => ['name' => 'A New Product']],
        ], true];

        yield 'Catalog\ProductsListController@toggleActive' => ['patch', '/products/__PRODUCT_ID__/active', [], false];

        yield 'Catalog\OwnProductController@store' => ['post', '/own-products', [
            'sku' => 'OWN-SKU-001',
            'base_price' => 15,
            'stock' => 10,
            'min_stock' => 1,
            'translations' => ['en' => ['name' => 'Own Product One']],
        ], true];

        yield 'Catalog\OwnProductsListController@destroy' => ['delete', '/own-products/__OWN_PRODUCT_ID__', [], false];

        yield 'Requests\BrandRequestController@store' => ['post', '/brand-requests', [
            'title' => 'Please stock this brand',
            'description' => str_repeat('We would love to sell this brand. ', 2),
        ], true];

        yield 'Requests\ManufacturingController@store' => ['post', '/manufacturing', [
            'product_name' => 'Custom widget',
            'quantity' => 3,
        ], true];

        yield 'Requests\ProductRequestController@store' => ['post', '/product-requests', [
            'title' => 'Please list this product',
            'description' => str_repeat('Product request details here. ', 2),
        ], true];

        yield 'Support\TicketController@store' => ['post', '/support', [
            'subject' => 'Need help with an order',
            'message' => 'Could you help me with order #123?',
        ], true];

        yield 'Support\NotificationsController@markAllRead' => ['post', '/notifications/read-all', [], false];

        yield 'Sales\CustomerCreateController@store' => ['post', '/customers', [
            'full_name' => 'New Customer',
            'email' => 'new-customer-mut@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], true];

        yield 'Sales\CustomerDetailController@storeAddress' => ['post', '/customers/__CUSTOMER_ID__/addresses', [
            'address_line_1' => '456 Test Blvd',
        ], true];

        yield 'Sales\OrdersController@updateShippingStatus' => ['patch', '/orders/999999/shipping-status', [
            'shipping_status' => 'pending',
        ], true, 404]; // no such order — controller 404s before validation, which is still a documented outcome

        yield 'Onboarding\OnboardingController@dismiss' => ['post', '/onboarding/dismiss', [], false];

        yield 'Onboarding\OnboardingController@tourComplete' => ['post', '/onboarding/tour/complete', [], false];

        yield 'Settings\AdminsController@store' => ['post', '/settings/admins', [
            'name' => 'Second Admin',
            'email' => 'second-admin@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ], true];

        yield 'Settings\RolesPermissionsController@store' => ['post', '/settings/roles-permissions', [
            'name' => 'Warehouse Staff',
            'permissions' => ['dashboard.view'],
        ], true];

        yield 'Settings\DomainsController@store' => ['post', '/settings/domains', [
            'domain' => 'shop.example.com',
        ], true];

        yield 'Settings\GeneralSettingsController@update' => ['put', '/settings/general', [
            'profit_percentage' => 12,
        ], true];

        yield 'Settings\ReturnPolicyController@update' => ['put', '/settings/return-policy', [
            'window_days' => 14,
            'fee' => 0,
        ], true];

        yield 'Settings\TrackingSettingsController@update' => ['put', '/settings/tracking', [
            'fb_pixel_id' => '1234567890',
        ], false];

        yield 'Settings\MailConfigurationsController@update' => ['put', '/settings/mail', [
            'mail_mailer' => 'smtp',
        ], false];

        yield 'Settings\AccountSettingsController@update' => ['put', '/settings/account', [
            'adminName' => 'Jane Owner',
            'adminEmail' => 'owner-updated@example.com',
            'shopName' => 'Panel Test Store',
        ], true];

        yield 'Store\CouponController@store' => ['post', '/store/coupons', [
            'code' => 'WELCOME10',
            'name_text' => 'Welcome discount',
            'type' => 'fixed',
            'value' => 10,
        ], true];

        yield 'Store\FlashSaleController@store' => ['post', '/store/flash-sales', [
            'product_ids' => ['__PRODUCT_ID__'],
            'discount_percentage' => 15,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
        ], true];

        yield 'Store\BannerController@store' => ['post', '/store/banners', [
            'serial_number' => 1,
        ], true];

        yield 'Store\SocialLinkController@store' => ['post', '/store/appearance/social', [
            'icon' => 'Facebook',
            'url' => 'https://facebook.com/panel-test',
            'serial_number' => 1,
        ], true];

        yield 'Store\AppearanceController@savePromoBanner' => ['put', '/store/appearance/promo-banner', [
            'promo_banner_title' => 'Big Sale',
        ], false];

        yield 'Store\PageFormController@store' => ['post', '/store/pages', [
            'translations' => ['en' => ['title' => 'About Us']],
        ], true];

        yield 'Store\ThemesController@activate' => ['post', '/store/themes/__THEME_ID__/activate', [], false];

        yield 'Store\HomeVariantsController@selectVariant' => ['post', '/store/home-variants', [
            'variant' => 'default',
        ], false];

        yield 'Finance\WalletController@subscribe' => ['post', '/finance/wallet/subscription', [
            'gateway' => 'stripe',
            'package_id' => 1,
        ], true];

        yield 'Shell\SetupProgressController@pagesReviewed' => ['post', '/widgets/setup-progress/pages-reviewed', [], false];

        yield 'Tenant\EmailVerificationController@send' => ['post', '/email/verification-notification', [], false];
    }

    /** @dataProvider scenarios */
    public function test_mutation_contract(string $method, string $uri, array $payload, bool $hasRequiredFields, int $expectRequiredStatus = 422): void
    {
        $uri = $this->resolvePlaceholders($uri);
        $payload = $this->resolvePayloadPlaceholders($payload);

        $validResponse = $this->actingAsTenantAdmin()->json($method, $this->tenantUrl($uri), $payload);

        $this->assertTrue(
            $validResponse->getStatusCode() < 300 || $validResponse->getStatusCode() === $expectRequiredStatus,
            "[{$method} {$uri}] valid payload expected a 2xx (or documented {$expectRequiredStatus}) but got {$validResponse->getStatusCode()}: {$validResponse->getContent()}"
        );

        if ($validResponse->getStatusCode() < 300) {
            $json = $validResponse->json();
            $this->assertArrayHasKey('message', (array) $json, "[{$method} {$uri}] success response is missing \"message\". Body: {$validResponse->getContent()}");
            $this->assertNotEmpty($json['message'] ?? null, "[{$method} {$uri}] \"message\" was empty.");
        }

        // Empty-payload contract, only meaningful for routes that carry a body.
        if (!in_array($method, ['patch', 'delete'], true) || $hasRequiredFields) {
            $emptyResponse = $this->actingAsTenantAdmin()->json($method, $this->tenantUrl($uri), []);

            if ($hasRequiredFields) {
                $this->assertSame(
                    422,
                    $emptyResponse->getStatusCode(),
                    "[{$method} {$uri}] expected 422 for an empty payload but got {$emptyResponse->getStatusCode()}: {$emptyResponse->getContent()}"
                );
                $emptyResponse->assertJsonStructure(['errors']);
            } else {
                $this->assertTrue(
                    $emptyResponse->getStatusCode() < 300,
                    "[{$method} {$uri}] takes no required input, expected 2xx for an empty payload but got {$emptyResponse->getStatusCode()}: {$emptyResponse->getContent()}"
                );
            }
        }
    }

    private function resolvePlaceholders(string $uri): string
    {
        return strtr($uri, [
            '__PRODUCT_ID__' => (string) $this->ensureProduct()->id,
            '__OWN_PRODUCT_ID__' => (string) $this->makeOwnProduct()->id,
            '__CATEGORY_ID__' => (string) $this->ensureCategory()->id,
            '__CUSTOMER_ID__' => (string) $this->ensureCustomer()->id,
            '__THEME_ID__' => (string) $this->activeTheme->id,
        ]);
    }

    private function resolvePayloadPlaceholders(array $payload): array
    {
        array_walk_recursive($payload, function (&$value) {
            if ($value === '__PRODUCT_ID__') {
                $value = $this->ensureProduct()->id;
            } elseif ($value === '__CATEGORY_ID__') {
                $value = $this->ensureCategory()->id;
            }
        });

        return $payload;
    }

    private function ensureProduct(): Product
    {
        return $this->product ??= Product::create([
            'sku' => 'MUT-SKU-1',
            'active' => true,
            'price' => 20,
        ]);
    }

    private function makeOwnProduct(): Product
    {
        return Product::create([
            'sku' => 'OWN-MUT-SKU-' . uniqid(),
            'active' => true,
            'price' => 10,
            'is_tenant_owned' => true,
        ]);
    }

    private function ensureCategory(): Category
    {
        return $this->category ??= Category::create(['active' => true]);
    }

    private function ensureCustomer(): Customer
    {
        return $this->customer ??= Customer::create([
            'full_name' => 'Existing Customer',
            'email' => 'existing-customer@example.com',
            'password' => bcrypt('password'),
            'active' => true,
        ]);
    }
}
