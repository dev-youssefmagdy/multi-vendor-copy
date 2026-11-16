<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Http\Middleware\ApplyStorefrontSessionContext;
use App\Models\Country;
use App\Models\Tenant\Product;
use App\Models\TenantCountry;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\CountryDetectorService;
use App\Support\Tenant\StorefrontCountryShortcuts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Client request 2026-11-16:
 *  - "Sold in countries" of own products: Egypt only => an Egyptian visitor sees it; all
 *    countries => everyone sees it; nothing ticked => nobody; Morocco only => not Egypt.
 *    Hidden products can't be added to the cart either.
 *  - Dashboard "Edit your storefront" country selector: the preview renders the store as
 *    the chosen country without touching the vendor's own session, and every shortcut
 *    points at what that country sees.
 */
class StorefrontCountrySelectionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;

    private Country $egypt;
    private Country $morocco;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.stores.file.path' => sys_get_temp_dir().'/storefront-country-selection-test-cache']);
        Cache::forgetDriver('file');

        $this->setUpTenantPanel();
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());

        $this->egypt = Country::forceCreate(['name' => 'Egypt', 'iso2' => 'EG']);
        $this->morocco = Country::forceCreate(['name' => 'Morocco', 'iso2' => 'MA']);

        foreach ([$this->egypt, $this->morocco] as $country) {
            TenantCountry::create(['tenant_id' => $this->tenant->id, 'country_id' => $country->id, 'is_active' => true]);
        }
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function ownProduct(?array $allowedCountryIds): Product
    {
        return Product::create([
            'slug' => 'own-'.Str::lower(Str::random(8)),
            'price' => ['default' => 60.0],
            'default_price' => 60.0,
            'sale_price' => 49.0,
            'active' => true,
            'is_own_product' => true,
            'requires_shipping' => false,
            'stock' => null,
            'manage_stock' => false,
            'allowed_country_ids' => $allowedCountryIds,
        ]);
    }

    #[Test]
    public function sold_in_countries_matrix(): void
    {
        $this->tenant->run(function (): void {
            $egyptOnly = $this->ownProduct([$this->egypt->id]);
            $moroccoOnly = $this->ownProduct([$this->morocco->id]);
            $allCountries = $this->ownProduct(null);
            $noCountry = $this->ownProduct([]);

            $repository = app(StorefrontRepository::class);
            $visible = fn (Country $country, Product $product) => $repository->forCountry($country)->productBySlug($product->slug) !== null;

            $this->assertTrue($visible($this->egypt, $egyptOnly), 'Egypt only, Egyptian visitor');
            $this->assertFalse($visible($this->morocco, $egyptOnly), 'Egypt only, Moroccan visitor');
            $this->assertFalse($visible($this->egypt, $moroccoOnly), 'Morocco only, Egyptian visitor');
            $this->assertTrue($visible($this->morocco, $moroccoOnly), 'Morocco only, Moroccan visitor');
            $this->assertTrue($visible($this->egypt, $allCountries), 'all countries');
            $this->assertTrue($visible($this->morocco, $allCountries), 'all countries');
            $this->assertFalse($visible($this->egypt, $noCountry), 'no country ticked');
            $this->assertFalse($visible($this->morocco, $noCountry), 'no country ticked');
        });
    }

    #[Test]
    public function a_product_hidden_in_the_visitors_country_cannot_be_added_to_the_cart(): void
    {
        $moroccoOnly = $this->tenant->run(fn () => $this->ownProduct([$this->morocco->id]));

        $this->withHeader('CF-IPCountry', 'EG')
            ->postJson($this->tenantUrl('/cart/add'), ['product_id' => $moroccoOnly->id])
            ->assertNotFound();

        $this->withHeader('CF-IPCountry', 'EG')
            ->postJson($this->tenantUrl('/cart/add'), ['slug' => $moroccoOnly->slug])
            ->assertNotFound();
    }

    #[Test]
    public function preview_country_renders_as_that_country_and_leaves_the_session_alone(): void
    {
        $this->tenant->run(function (): void {
            $session = $this->app['session']->driver();
            $session->start();
            $session->put('storefront_country', 'MA');
            $session->put('storefront_country_id', $this->morocco->id);
            $session->put('storefront_currency', 'MAD');

            $request = Request::create('/', 'GET', [CountryDetectorService::PREVIEW_PARAM => 'EG']);
            $request->setLaravelSession($session);
            $this->app->instance('request', $request);

            $seen = null;
            app(ApplyStorefrontSessionContext::class)->handle($request, function (Request $request) use (&$seen): Response {
                $seen = [
                    'country' => $request->attributes->get('storefrontCurrentCountry')?->iso2,
                    'session_country_id' => session('storefront_country_id'),
                ];

                return new Response('ok');
            });

            $this->assertSame(['country' => 'EG', 'session_country_id' => $this->egypt->id], $seen, 'rendered as Egypt');
            $this->assertSame('MA', $session->get('storefront_country'), 'vendor session keeps its country');
            $this->assertSame($this->morocco->id, $session->get('storefront_country_id'));
            $this->assertSame('MAD', $session->get('storefront_currency'));
            $this->assertFalse($session->has('storefront_country_override'));
        });
    }

    #[Test]
    public function dashboard_shortcuts_follow_the_selected_country(): void
    {
        $options = $this->tenant->run(fn () => collect(StorefrontCountryShortcuts::options())->keyBy('code'));

        $this->assertSame(['all', 'eg', 'ma'], $options->keys()->sort()->values()->all());
        $this->assertStringNotContainsString('preview_country', $options['all']['preview_url']);
        $this->assertStringEndsWith('?preview_country=EG', $options['eg']['preview_url']);
        $this->assertStringContainsString('country='.$this->egypt->id, $options['eg']['links']['banners']);
        $this->assertStringContainsString('country='.$this->morocco->id, $options['ma']['links']['banners']);
        $this->assertStringContainsString('tab=general', $options['eg']['links']['logo']);

        $this->actingAsTenantAdmin()
            ->get($this->tenantUrl('/admin/dashboard'))
            ->assertOk()
            ->assertSee('data-store-country-map', false)
            ->assertSee('data-code="eg"', false)
            ->assertSee('data-code="ma"', false)
            ->assertSee('data-store-shortcut="banners"', false);
    }
}
