<?php

declare(strict_types=1);

namespace Tests\Feature\Mobile;

use App\Http\Controllers\Tenant\Panel\Store\MobileAppController;
use App\Models\HomeVariant;
use App\Models\Tenant\AdminRole;
use App\Models\Tenant\MobileAppSetting;
use App\Models\Tenant\MobileAppTheme;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

class MobilePanelTest extends TestCase
{
    use SetsUpTenantPanel;

    private bool $createdVariants = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());
        if (! Schema::hasTable('home_variants')) {
            Schema::create('home_variants', function ($t) {
                $t->id(); $t->string('theme_slug'); $t->string('key'); $t->string('name')->nullable();
                $t->text('description')->nullable(); $t->string('preview_image')->nullable();
                $t->json('sections')->nullable(); $t->json('colors')->nullable(); $t->string('view')->nullable();
                $t->boolean('is_default')->default(false); $t->boolean('is_active')->default(true);
                $t->unsignedBigInteger('blade_theme_id')->nullable(); $t->timestamps();
            });
            $this->createdVariants = true;
        }
    }

    protected function tearDown(): void
    {
        HomeVariant::query()->whereIn('theme_slug', ['souqify', 'custom'])->delete();
        if ($this->createdVariants) {
            Schema::dropIfExists('home_variants');
        }
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function base(string $p): string
    {
        return $this->tenantUrl('/admin/store/'.$p);
    }

    public function test_pages_open_and_save_settings_and_bump_version(): void
    {
        // Full-layout rendering needs the (unmigrated) central test DB; assert the view data instead.
        $this->tenant->run(function () {
            $data = app(MobileAppController::class)->index()->getData();
            $this->assertSame($this->tenant->id, $data['tenantCode']);
            $this->assertSame($this->tenantHost, $data['domain']);
        });

        $this->actingAsTenantAdmin()->putJson($this->base('mobile-app'), [
            'app_name' => 'My App', 'primary_color' => '#112233', 'min_app_version' => '1.2.0',
            'features' => ['favorites' => 0, 'guest_cart' => 1], 'nav_items' => ['home', 'cart'],
            'maintenance' => 1, 'logo' => UploadedFile::fake()->image('l.png'),
        ])->assertOk()->assertJsonPath('success', true);

        $this->tenant->run(function () {
            $s = MobileAppSetting::allKeyed();
            $this->assertSame('My App', $s['app_name']);
            $this->assertFalse($s['features']['favorites']);
            $this->assertTrue($s['features']['guest_cart']);
            $this->assertSame(['home', 'cart'], $s['nav_items']);
            $this->assertTrue($s['maintenance']);
            $this->assertNotEmpty($s['logo']);
        });
    }

    public function test_validation_rejects_bad_input(): void
    {
        $this->actingAsTenantAdmin()->putJson($this->base('mobile-app'), [
            'primary_color' => 'red', 'min_app_version' => 'abc', 'nav_items' => ['bogus'],
        ])->assertStatus(422)->assertJsonValidationErrors(['primary_color', 'min_app_version', 'nav_items.0']);
    }

    public function test_activate_theme_excludes_custom_and_saves_selection(): void
    {
        HomeVariant::create(['theme_slug' => 'souqify', 'key' => 'v3', 'name' => 'S3', 'is_active' => true]);
        HomeVariant::create(['theme_slug' => 'custom', 'key' => 'v1', 'name' => 'C', 'is_active' => true]);

        $cards = $this->tenant->run(fn () => app(MobileAppController::class)->themes()->getData()['cards']);
        $this->assertSame(['S3'], array_column($cards, 'name'));

        $this->actingAsTenantAdmin()->postJson($this->base('mobile-themes/custom/v1/activate'))->assertStatus(422);
        $this->actingAsTenantAdmin()->postJson($this->base('mobile-themes/souqify/v9/activate'))->assertStatus(422);
        $this->actingAsTenantAdmin()->postJson($this->base('mobile-themes/souqify/v3/activate'))->assertOk();

        $this->tenant->run(fn () => $this->assertSame('v3', MobileAppTheme::query()->latest('id')->value('variant_key')));

        $this->actingAsTenantAdmin()->putJson($this->base('mobile-themes/colors'), ['colors' => ['primary' => '#abcdef']])->assertOk();
        $this->actingAsTenantAdmin()->putJson($this->base('mobile-themes/colors'), ['colors' => ['primary' => 'zzz']])->assertStatus(422);
    }

    public function test_permission_required(): void
    {
        $perms = array_values(array_diff(array_keys(AdminRole::availablePermissions()), ['store.mobile-app.manage']));
        $this->tenant->run(fn () => $this->ownerRole->update(['permissions' => $perms, 'permissions_count' => count($perms)]));

        foreach (['mobile-app', 'mobile-themes'] as $p) {
            $r = $this->actingAsTenantAdmin()->get($this->base($p));
            $this->assertContains($r->getStatusCode(), [302, 403]);
        }
        $this->actingAsTenantAdmin()->postJson($this->base('mobile-themes/ecommet/v1/activate'))->assertStatus(403);
    }
}
