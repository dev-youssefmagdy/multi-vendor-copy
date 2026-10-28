<?php

declare(strict_types=1);

namespace Tests\Feature\Mobile;

use App\Enums\TenantStatus;
use App\Models\Tenant\MobileAppTheme;
use App\Services\Mobile\MobileSectionCatalog;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use SetsUpTenantPanel, BuildsOrders;

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

    private function h(array $extra = []): array
    {
        return array_merge(['X-Tenant' => $this->tenant->id, 'Accept' => 'application/json'], $extra);
    }

    public function test_resolve_known_and_unknown_host(): void
    {
        $this->getJson('/api/mobile/v1/resolve?host=https://'.$this->tenantHost.'/x')
            ->assertOk()->assertJsonPath('data.tenant_id', $this->tenant->id)->assertJsonPath('data.status', 'active');
        $this->getJson('/api/mobile/v1/resolve?host=nope.example.org')->assertNotFound();
    }

    public function test_suspended_tenant_rejected(): void
    {
        $this->tenant->update(['status' => TenantStatus::Suspended->value]);
        $this->getJson('/api/mobile/v1/bootstrap', $this->h())->assertForbidden()->assertJsonPath('code', 'store_suspended');
    }

    public function test_bootstrap_uses_app_theme_and_allowed_keys(): void
    {
        $this->tenant->run(fn () => MobileAppTheme::create(['theme_slug' => 'elora', 'variant_key' => 'v4']));
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
        \App\Models\HomeVariant::query()->where('theme_slug', 'elora')->delete();
        \App\Models\HomeVariant::create(['theme_slug' => 'elora', 'key' => 'v4', 'name' => 'Bold', 'is_default' => false, 'is_active' => true, 'colors' => ['--color-primary' => '#ff4d00', '--color-subtitle' => '#adadad']]);
        try {
            $this->assertBootstrap();
        } finally {
            \App\Models\HomeVariant::query()->where('theme_slug', 'elora')->delete();
            if ($created) {
                \Illuminate\Support\Facades\Schema::dropIfExists('home_variants');
            }
        }
    }

    private function assertBootstrap(): void
    {
        $res = $this->getJson('/api/mobile/v1/bootstrap', $this->h())->assertOk();
        $res->assertJsonPath('data.theme.slug', 'elora');
        $keys = array_column($res->json('data.sections'), 'key');
        $this->assertNotContains('category_carousel', $keys);
        $this->assertArrayHasKey('elora', $res->json('data.allowed_section_keys'));
        $this->assertSame('#ff4d00', $res->json('data.theme.colors.primary'));
        $this->assertSame('#adadad', $res->json('data.theme.colors.text_subtitle'));
        $etag = $res->headers->get('ETag');
        $this->getJson('/api/mobile/v1/bootstrap', $this->h(['If-None-Match' => $etag]))->assertStatus(304);
    }

    public function test_allowed_keys_skip_missing_partials(): void
    {
        $this->assertNotContains('hero_banners', MobileSectionCatalog::allowedKeys('souqify', 'v2'));
        $this->assertContains('hero_banners', MobileSectionCatalog::allowedKeys('souqify', 'v1'));
    }

    public function test_bootstrap_exposes_splash_branding(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\MobileAppSetting::query()->updateOrCreate(['key' => 'app_name'], ['value' => 'Shop App']);
            \App\Models\Tenant\MobileAppSetting::query()->updateOrCreate(['key' => 'splash'], ['value' => 'https://x.test/s.png']);
        });
        $res = $this->getJson('/api/mobile/v1/bootstrap', $this->h())->assertOk();
        $res->assertJsonPath('data.branding.app_name', 'Shop App');
        $res->assertJsonPath('data.branding.splash_image_url', 'https://x.test/s.png');
    }

    public function test_auth_flow_and_cross_tenant_token_rejected(): void
    {
        $reg = $this->postJson('/api/mobile/v1/auth/register', [
            'name' => 'Mo', 'email' => 'mo@example.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ], $this->h())->assertCreated();
        $token = $reg->json('data.token');

        $this->getJson('/api/mobile/v1/me', $this->h(['Authorization' => 'Bearer '.$token]))->assertOk();
        $this->getJson('/api/mobile/v1/me', $this->h())->assertUnauthorized();

        $this->postJson('/api/mobile/v1/auth/login', ['email' => 'mo@example.com', 'password' => 'bad'], $this->h())->assertUnauthorized();

        // second tenant: same token must not authenticate
        $other = \App\Models\Tenant::create(['status' => TenantStatus::Active->value, 'shop_name' => 'Other']);
        try {
            $this->getJson('/api/mobile/v1/me', ['X-Tenant' => $other->id, 'Authorization' => 'Bearer '.$token])->assertUnauthorized();
        } finally {
            tenancy()->end();
            $other->delete();
        }

        $this->postJson('/api/mobile/v1/auth/logout', [], $this->h(['Authorization' => 'Bearer '.$token]))->assertOk();
        $this->getJson('/api/mobile/v1/me', $this->h(['Authorization' => 'Bearer '.$token]))->assertUnauthorized();
    }

    public function test_well_known_files(): void
    {
        config(['mobile.android_package' => 'com.x.app', 'mobile.android_sha256' => 'AA:BB', 'mobile.ios_app_id' => 'T.com.x']);
        $this->getJson('/.well-known/assetlinks.json')->assertOk()->assertJsonPath('0.target.package_name', 'com.x.app');
    }

    public function test_well_known_files_content_and_empty_when_unconfigured(): void
    {
        config(['mobile.android_package' => 'com.x.app', 'mobile.android_sha256' => 'AA:BB, CC:DD', 'mobile.ios_app_id' => 'TEAM123.com.x.app']);
        $this->getJson('/.well-known/assetlinks.json')->assertOk()
            ->assertJsonPath('0.target.sha256_cert_fingerprints', ['AA:BB', 'CC:DD'])
            ->assertJsonPath('0.relation.0', 'delegate_permission/common.handle_all_urls');
        $this->getJson('/.well-known/apple-app-site-association')->assertOk()
            ->assertJsonPath('applinks.details.0.appIDs.0', 'TEAM123.com.x.app');
        $this->getJson('/apple-app-site-association')->assertOk();

        config(['mobile.android_package' => null, 'mobile.android_sha256' => null, 'mobile.ios_app_id' => null]);
        $this->getJson('/.well-known/assetlinks.json')->assertOk()->assertExactJson([]);
        $this->getJson('/.well-known/apple-app-site-association')->assertOk()->assertJsonPath('applinks.details', []);
    }

    public function test_tenant_app_landing_preserves_path_and_store_identity(): void
    {
        config(['mobile.deep_link_scheme' => 'storeapp']);
        $r = $this->get('http://'.$this->tenantHost.'/app?path=/products/shoe');
        $r->assertOk()->assertSee('storeapp://'.$this->tenantHost.'/products/shoe', false)->assertSee('Open in app');
        $this->get('http://'.$this->tenantHost.'/app?path=//evil.com')->assertOk()
            ->assertDontSee('//evil.com', false);
    }
}
