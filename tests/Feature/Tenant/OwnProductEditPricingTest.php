<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Support\Tenant\PanelLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Own products: the edit form must show (prefilled) and save the same pricing +
 * stock fields as the create form, even when opened from the Products tab
 * (tenant.products.edit redirects to the own-product form). Also covers the
 * Share link and the vendor-panel language switcher.
 */
class OwnProductEditPricingTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;

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

    public function test_own_product_edit_shows_and_saves_pricing_and_stock(): void
    {
        $payload = [
            'sku' => 'OWN-EDIT-1',
            'slug' => 'own-edit-product',
            'base_price' => 50,
            'sale_price' => 45,
            'cost_price' => 20,
            'stock' => 7,
            'min_stock' => 2,
            'manage_stock' => 1,
            'active' => 1,
            'featured' => 0,
            'is_taxable' => 1,
            'weight_grams' => 300,
            'translations' => [
                'en' => ['name' => 'Own Edit Product', 'summary' => 'Sum', 'description' => 'Desc'],
            ],
        ];

        $this->actingAsTenantAdmin()
            ->json('post', $this->tenantUrl('/admin/own-products'), $payload)
            ->assertSuccessful();

        $product = $this->inTenant(fn () => Product::query()->where('sku', 'OWN-EDIT-1')->firstOrFail());

        // Products tab "edit" link → own-product form.
        $this->actingAsTenantAdmin()
            ->get($this->tenantUrl("/admin/products/{$product->id}/edit"))
            ->assertRedirect($this->tenantUrl("/admin/own-products/{$product->id}/edit"));

        $html = $this->actingAsTenantAdmin()
            ->get($this->tenantUrl("/admin/own-products/{$product->id}/edit"))
            ->assertOk()
            ->getContent();

        foreach (['base_price' => '50.00', 'sale_price' => '45.00', 'cost_price' => '20.00', 'stock' => '7', 'min_stock' => '2'] as $field => $value) {
            $this->assertMatchesRegularExpression(
                '/<input[^>]*name="' . $field . '"[^>]*value="' . preg_quote($value, '/') . '"|<input[^>]*value="' . preg_quote($value, '/') . '"[^>]*name="' . $field . '"/s',
                $html,
                "edit form must show {$field} prefilled with {$value}"
            );
        }

        $update = array_merge($payload, ['base_price' => 60, 'sale_price' => 55.5, 'cost_price' => 25, 'stock' => 12, 'min_stock' => 3]);
        $this->actingAsTenantAdmin()
            ->json('put', $this->tenantUrl("/admin/own-products/{$product->id}"), $update)
            ->assertSuccessful();

        $this->inTenant(function () use ($product) {
            $fresh = Product::query()->findOrFail($product->id);
            $this->assertEquals(60, (float) $fresh->default_price);
            $this->assertEquals(55.5, (float) $fresh->sale_price);
            $this->assertEquals(25, (float) $fresh->cost_price);
            $this->assertSame(12, (int) $fresh->stock);
            $this->assertSame(3, (int) $fresh->min_stock);
        });

        // Platform-catalog save endpoint must not touch an own product.
        $status = $this->actingAsTenantAdmin()
            ->json('put', $this->tenantUrl("/admin/products/{$product->id}"), ['price' => 1, 'translations' => $payload['translations']])
            ->getStatusCode();
        $this->assertContains($status, [404, 422]);
        $this->assertEquals(55.5, (float) $this->inTenant(fn () => Product::query()->findOrFail($product->id)->sale_price));

        // Share modal: direct storefront link by slug on the tenant host.
        $this->actingAsTenantAdmin()
            ->getJson($this->tenantUrl("/admin/products/{$product->id}/share"))
            ->assertOk()
            ->assertJsonPath('data.url', $this->tenantUrl('/products/own-edit-product'));
    }

    public function test_panel_language_switch_translates_and_sets_rtl(): void
    {
        $this->inTenant(fn () => Language::query()->where('code', 'ar')->delete());

        $this->actingAsTenantAdmin()
            ->post($this->tenantUrl('/admin/locale/ar'))
            ->assertRedirect()
            ->assertCookie(PanelLocale::COOKIE, 'ar');

        $html = $this->actingAsTenantAdmin()
            ->withCookie(PanelLocale::COOKIE, 'ar')
            ->get($this->tenantUrl('/admin/own-products'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('lang="ar"', $html);
        $this->assertStringContainsString('لوحة التحكم', $html); // "Dashboard" in the sidebar

        $this->actingAsTenantAdmin()
            ->post($this->tenantUrl('/admin/locale/xx'))
            ->assertNotFound();
    }

    private function inTenant(callable $fn): mixed
    {
        $wasInitialized = tenancy()->initialized;
        if (! $wasInitialized) {
            tenancy()->initialize($this->tenant);
        }
        try {
            return $fn();
        } finally {
            if (! $wasInitialized) {
                tenancy()->end();
            }
        }
    }
}
