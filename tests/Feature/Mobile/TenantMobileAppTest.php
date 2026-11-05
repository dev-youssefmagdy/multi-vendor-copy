<?php

declare(strict_types=1);

namespace Tests\Feature\Mobile;

use App\Enums\ActivationStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\TenantMobileApp;
use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use App\PaymentGateway\Facades\Payment;
use App\PaymentGateway\PaymentManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\Support\FakePaymentGateway;
use Tests\Support\FakePaymentManager;
use Tests\TestCase;

/** Phase 8 (PUBLISH_TO_STORES.md): per-tenant white-label app ids, well-known files and return scheme. */
class TenantMobileAppTest extends TestCase
{
    use SetsUpTenantPanel, BuildsOrders;

    private const FP = '14:6D:E9:83:C5:73:06:50:D8:EE:B9:95:2F:34:FC:64:16:A0:83:42:E6:1D:BE:A8:8A:04:96:B2:3F:CF:44:E5';

    private bool $createdTable = false;
    private bool $createdNotifications = false;

    protected function setUp(): void
    {
        parent::setUp();
        // The shared test database is not migrated by the suite: create the table from its migration when missing.
        if (!Schema::hasTable('tenant_mobile_apps')) {
            (require database_path('migrations/2026_11_05_000001_create_tenant_mobile_apps_table.php'))->up();
            $this->createdTable = true;
        }
        $this->setUpTenantPanel();
        config([
            'mobile.android_package' => 'com.platform.app',
            'mobile.android_sha256' => 'AA:BB',
            'mobile.ios_app_id' => 'PLATFORM01.com.platform.app',
            'mobile.deep_link_scheme' => 'storeapp',
        ]);
    }

    protected function tearDown(): void
    {
        TenantMobileApp::query()->where('tenant_id', $this->tenant->id)->delete();
        if ($this->createdNotifications) {
            Schema::dropIfExists('admin_notifications');
        }
        $this->tearDownTenantPanel();
        if ($this->createdTable) {
            Schema::dropIfExists('tenant_mobile_apps');
        }
        parent::tearDown();
    }

    private function tenantApp(array $overrides = []): TenantMobileApp
    {
        return TenantMobileApp::query()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'android_package' => 'com.alpha.shop',
            'android_sha256' => [self::FP],
            'ios_team_id' => 'ABCDE12345',
            'ios_bundle_id' => 'com.alpha.shop',
            'url_scheme' => 'alphashop',
            'play_store_url' => 'https://play.google.com/store/apps/details?id=com.alpha.shop',
            'app_store_url' => 'https://apps.apple.com/app/id6740000000',
        ], $overrides));
    }

    // ── Well-known files ───────────────────────────────────────────────────────

    public function test_tenant_domain_lists_tenant_app_plus_platform_app(): void
    {
        $this->tenantApp();

        $links = $this->get('http://'.$this->tenantHost.'/.well-known/assetlinks.json')->assertOk();
        $this->assertStringStartsWith('application/json', (string) $links->headers->get('Content-Type'));
        $this->assertSame(['com.alpha.shop', 'com.platform.app'], array_column(array_column($links->json(), 'target'), 'package_name'));
        $links->assertJsonPath('0.target.sha256_cert_fingerprints', [self::FP])
            ->assertJsonPath('0.relation.0', 'delegate_permission/common.handle_all_urls');

        foreach (['/.well-known/apple-app-site-association', '/apple-app-site-association'] as $path) {
            $aasa = $this->get('http://'.$this->tenantHost.$path)->assertOk();
            $this->assertStringStartsWith('application/json', (string) $aasa->headers->get('Content-Type'));
            $aasa->assertJsonPath('applinks.details.0.appIDs', ['ABCDE12345.com.alpha.shop', 'PLATFORM01.com.platform.app'])
                ->assertJsonPath('applinks.details.0.components', [['/' => '/*']])
                ->assertJsonPath('webcredentials.apps', ['ABCDE12345.com.alpha.shop', 'PLATFORM01.com.platform.app']);
        }
    }

    public function test_custom_domain_serves_the_tenant_app(): void
    {
        $this->tenantApp();
        $custom = 'shop-'.uniqid().'.brand-test.com';
        $this->tenant->domains()->create(['domain' => $custom]);

        $this->get('http://'.$custom.'/.well-known/assetlinks.json')->assertOk()->assertJsonPath('0.target.package_name', 'com.alpha.shop');
        $this->get('http://'.$custom.'/.well-known/apple-app-site-association')->assertOk()
            ->assertJsonPath('applinks.details.0.appIDs.0', 'ABCDE12345.com.alpha.shop');
    }

    public function test_tenant_domain_without_tenant_app_and_central_domain_list_platform_only(): void
    {
        $this->get('http://'.$this->tenantHost.'/.well-known/assetlinks.json')->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.target.package_name', 'com.platform.app');

        $this->tenantApp();
        $this->get('http://localhost/.well-known/assetlinks.json')->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.target.package_name', 'com.platform.app');
        $this->get('http://localhost/.well-known/apple-app-site-association')->assertOk()
            ->assertJsonPath('applinks.details.0.appIDs', ['PLATFORM01.com.platform.app']);

        // Only the tenant app when the platform app is not configured; nothing at all on the central domain.
        config(['mobile.android_package' => null, 'mobile.android_sha256' => null, 'mobile.ios_app_id' => null]);
        $this->get('http://'.$this->tenantHost.'/.well-known/assetlinks.json')->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.target.package_name', 'com.alpha.shop');
        $this->get('http://localhost/.well-known/assetlinks.json')->assertOk()->assertExactJson([]);
        $this->get('http://localhost/.well-known/apple-app-site-association')->assertOk()
            ->assertJsonPath('applinks.details', [])->assertJsonPath('webcredentials.apps', []);
    }

    // ── Central admin settings ─────────────────────────────────────────────────

    private function centralAdmin(): AdminUser
    {
        $role = AdminRole::query()->create(['name' => 'Role '.uniqid(), 'permissions' => ['plans.tenants.manage'], 'permissions_count' => 1]);

        return AdminUser::query()->create([
            'role_id' => $role->id, 'name' => 'Admin', 'email' => uniqid('admin').'@example.com',
            'password' => Hash::make('password12345'), 'status' => ActivationStatus::Active,
        ]);
    }

    private function save(array $data)
    {
        return $this->actingAs($this->centralAdmin ??= $this->centralAdmin(), 'admin')
            ->from('http://localhost/admin/plans/registered-users/'.$this->tenant->id.'/edit')
            ->put('http://localhost/admin/plans/registered-users/'.$this->tenant->id.'/mobile-app', $data);
    }

    private ?AdminUser $centralAdmin = null;

    public function test_admin_saves_and_normalises_settings_and_rejects_invalid_values(): void
    {
        $other = 'other-'.uniqid();
        DB::table('tenants')->insert(['id' => $other, 'data' => '{}', 'created_at' => now(), 'updated_at' => now()]);
        TenantMobileApp::query()->create(['tenant_id' => $other, 'url_scheme' => 'takenshop']);
        $role = null;

        try {
            $lower = strtolower(str_replace(':', '', self::FP));
            $this->save([
                'android_package' => 'com.alpha.shop',
                'android_sha256' => $lower."\n".self::FP."\n".substr($lower, 0, 62).'ff',
                'ios_team_id' => 'abcde12345',
                'ios_bundle_id' => 'com.alpha.shop',
                'url_scheme' => 'AlphaShop',
                'play_store_url' => 'https://play.google.com/store/apps/details?id=com.alpha.shop',
            ])->assertRedirect()->assertSessionHasNoErrors();

            $app = TenantMobileApp::query()->where('tenant_id', $this->tenant->id)->firstOrFail();
            $this->assertSame([self::FP, implode(':', str_split(strtoupper(substr($lower, 0, 62).'ff'), 2))], $app->android_sha256);
            $this->assertSame('ABCDE12345.com.alpha.shop', $app->iosAppId());
            $this->assertSame('alphashop', $app->url_scheme);

            $this->save(['android_package' => 'com.alpha.shop', 'android_sha256' => 'AB:CD:EF'])->assertSessionHasErrors('android_sha256');
            $this->save(['android_package' => 'com.alpha.shop', 'android_sha256' => str_repeat('G', 64)])->assertSessionHasErrors('android_sha256');
            $this->save(['android_package' => 'com.alpha.shop'])->assertSessionHasErrors('android_sha256');
            $this->save(['android_package' => '1com..x', 'android_sha256' => self::FP])->assertSessionHasErrors('android_package');
            $this->save(['android_package' => 'com.platform.app', 'android_sha256' => self::FP])->assertSessionHasErrors('android_package');
            $this->save(['ios_team_id' => 'SHORT', 'ios_bundle_id' => 'com.alpha.shop'])->assertSessionHasErrors('ios_team_id');
            $this->save(['ios_bundle_id' => 'com.alpha.shop'])->assertSessionHasErrors('ios_team_id');
            $this->save(['url_scheme' => 'takenshop'])->assertSessionHasErrors('url_scheme');
            $this->save(['url_scheme' => 'storeapp'])->assertSessionHasErrors('url_scheme');
            $this->save(['url_scheme' => 'https'])->assertSessionHasErrors('url_scheme');
            $this->save(['url_scheme' => '9shop'])->assertSessionHasErrors('url_scheme');
            $this->save(['url_scheme' => 'alpha_shop'])->assertSessionHasErrors('url_scheme');
            $this->save(['play_store_url' => 'javascript:alert(1)'])->assertSessionHasErrors('play_store_url');
            // Failed saves leave the stored values alone.
            $this->assertSame('alphashop', TenantMobileApp::query()->where('tenant_id', $this->tenant->id)->value('url_scheme'));

            // Saving the same scheme again is not a duplicate of itself; clearing everything removes the row.
            $this->save(['url_scheme' => 'alphashop'])->assertSessionHasNoErrors();
            $this->save([])->assertSessionHasNoErrors();
            $this->assertFalse(TenantMobileApp::query()->where('tenant_id', $this->tenant->id)->exists());

            $this->actingAs($this->centralAdmin, 'admin')->get('http://localhost/admin/plans/registered-users/'.$this->tenant->id.'/edit')
                ->assertOk()->assertSee('White-label Mobile App')->assertSee('name="android_sha256"', false);
        } finally {
            TenantMobileApp::query()->where('tenant_id', $other)->delete();
            DB::table('tenants')->where('id', $other)->delete();
            if ($this->centralAdmin) {
                AdminRole::query()->whereKey($this->centralAdmin->role_id)->delete();
                $this->centralAdmin->delete();
            }
        }
    }

    // ── Scheme selection ───────────────────────────────────────────────────────

    public function test_landing_uses_tenant_app_scheme_and_store_urls_when_configured(): void
    {
        $url = 'http://'.$this->tenantHost.'/app?path=/products/shoe';
        $this->get($url)->assertOk()->assertSee('storeapp://'.$this->tenantHost.'/products/shoe', false);

        $this->tenantApp();
        $this->get($url)->assertOk()
            ->assertSee('alphashop://'.$this->tenantHost.'/products/shoe', false)
            ->assertSee('https://play.google.com/store/apps/details?id=com.alpha.shop', false)
            ->assertSee('https://apps.apple.com/app/id6740000000', false)
            ->assertSee('storeapp://'.$this->tenantHost.'/products/shoe', false); // secondary platform-app link

        // The platform app announcing itself gets its own scheme; a spoofed scheme is ignored.
        $this->get($url, ['X-App-Scheme' => 'storeapp'])->assertOk()->assertDontSee('alphashop://', false);
        $this->get($url, ['X-App-Scheme' => 'evil'])->assertOk()->assertDontSee('evil://', false)
            ->assertSee('alphashop://'.$this->tenantHost.'/products/shoe', false);
    }

    public function test_payment_return_uses_the_validated_app_scheme(): void
    {
        $cancel = fn (string $query = '') => '/api/mobile/v1/payments/'.$this->tenant->id.'/stripe/cancel'.$query;

        // No tenant app: always the platform scheme.
        $this->get($cancel('?app_scheme=alphashop'))->assertRedirect('storeapp://payment/result?status=cancelled');

        $this->tenantApp();
        $this->get($cancel('?app_scheme=alphashop'))->assertRedirect('alphashop://payment/result?status=cancelled');
        $this->get($cancel('?app_scheme=evil'))->assertRedirect('storeapp://payment/result?status=cancelled');
        $this->get($cancel('?app_scheme=https'))->assertRedirect('storeapp://payment/result?status=cancelled');
        $this->get($cancel())->assertRedirect('storeapp://payment/result?status=cancelled');

        // pay(): the scheme the app announces is carried into the gateway return URLs (only when allowed).
        $gateway = new class('stripe') extends FakePaymentGateway {
            public ?PaymentCharge $last = null;

            public function charge(PaymentCharge $charge): PaymentResult
            {
                $this->last = $charge;

                return PaymentResult::redirect('https://gateway.test/pay');
            }
        };
        $manager = new FakePaymentManager($gateway, ['stripe']);
        app()->instance('payment.manager', $manager);
        app()->instance(PaymentManager::class, $manager);
        Payment::clearResolvedInstance('payment.manager');
        if (!Schema::hasTable('admin_notifications')) {
            Schema::create('admin_notifications', function ($t) {
                $t->id(); $t->string('type'); $t->string('title'); $t->text('message')->nullable();
                $t->json('data')->nullable(); $t->boolean('is_read')->default(false); $t->timestamps();
            });
            $this->createdNotifications = true;
        }

        $token = $this->postJson('/api/mobile/v1/auth/register', [
            'name' => 'Buyer', 'email' => 'buyer@example.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ], ['X-Tenant' => $this->tenant->id, 'Accept' => 'application/json'])->assertCreated()->json('data.token');
        $uuid = $this->tenant->run(function () {
            $variant = $this->createOwnProductVariant(stock: 5, price: 10.0);
            $customer = Customer::query()->where('email', 'buyer@example.com')->firstOrFail();

            return $this->createOrder($customer, [[$variant, 1]], attributes: ['payment_method' => 'stripe'])->uuid;
        });
        $pay = fn (array $headers) => $this->postJson("/api/mobile/v1/orders/$uuid/pay", [], [
            'X-Tenant' => $this->tenant->id, 'Accept' => 'application/json', 'Authorization' => 'Bearer '.$token,
        ] + $headers)->assertOk()->assertJsonPath('data.payment_url', 'https://gateway.test/pay');

        $pay(['X-App-Scheme' => 'alphashop']);
        $this->assertStringContainsString('app_scheme=alphashop', $gateway->last->successUrl);
        $this->assertStringContainsString('app_scheme=alphashop', $gateway->last->cancelUrl);
        $this->get($gateway->last->cancelUrl)->assertRedirect('alphashop://payment/result?status=cancelled&order='.$uuid);

        $pay(['X-App-Scheme' => 'evilshop']);
        $this->assertStringNotContainsString('app_scheme', $gateway->last->successUrl);
        $this->get($gateway->last->cancelUrl)->assertRedirect('storeapp://payment/result?status=cancelled&order='.$uuid);

        $pay([]);
        $this->assertStringNotContainsString('app_scheme', $gateway->last->cancelUrl);
        $this->assertFalse((bool) $this->tenant->run(fn () => Order::where('uuid', $uuid)->value('paid')));
    }
}
