<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\FileType;
use App\Livewire\Tenant\Storefront\ProductPage;
use App\Models\Country;
use App\Models\FixedShippingCost;
use App\Models\Tenant\File;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\HomeProductService;
use App\Services\Tenant\Checkout\OrderComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Vendor-own products (client report, vendor "gogo"):
 *  - "sold in" countries (products.allowed_country_ids) hide the product from other countries;
 *  - the storefront price is the vendor's Sale Price in every country (was: only the
 *    weight-based fixed shipping, $20 in Morocco and $0.00 where none is configured);
 *  - gallery videos render as video slides on the product page.
 */
class OwnProductStorefrontVisibilityTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;
    use SetsUpTenantPanel;

    private int $russia;
    private int $morocco;
    private int $egypt;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.stores.file.path' => sys_get_temp_dir().'/own-product-storefront-test-cache']);
        Cache::forgetDriver('file');

        $this->setUpTenantPanel();

        $this->russia = Country::forceCreate(['name' => 'Russia', 'iso2' => 'RU'])->id;
        $this->morocco = Country::forceCreate(['name' => 'Morocco', 'iso2' => 'MA'])->id;
        $this->egypt = Country::forceCreate(['name' => 'Egypt', 'iso2' => 'EG'])->id;

        // Morocco has a weight-based fixed shipping rate (200 g x 0.1 = 20.00), Egypt none:
        // exactly the $20 / $0.00 the client saw. Own products must ignore it.
        FixedShippingCost::create(['country_id' => $this->morocco, 'price_per_gram' => 0.1, 'currency_code' => 'USD', 'is_active' => true]);

        $session = $this->app['session']->driver();
        $session->start();
        request()->setLaravelSession($session);
        Event::listen(RouteMatched::class, fn (RouteMatched $event) => $event->request->setLaravelSession($session));
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function visitFrom(?int $countryId): StorefrontRepository
    {
        session(['storefront_country_id' => $countryId]);
        request()->attributes->remove(Product::FIXED_SHIPPING_MEMO_ATTRIBUTE);
        $this->app->forgetInstance(StorefrontRepository::class);

        return app(StorefrontRepository::class);
    }

    private function ownProduct(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'slug' => 'silicone-strap-'.Str::lower(Str::random(6)),
            'price' => ['default' => 60.0],
            'default_price' => 60.0,
            'sale_price' => 49.0,
            'active' => true,
            'is_own_product' => true,
            'requires_shipping' => false,
            'weight_grams' => 200,
            'stock' => null,
            'manage_stock' => false,
        ], $attributes));
    }

    #[Test]
    public function own_product_sold_in_one_country_is_hidden_everywhere_else(): void
    {
        $this->tenant->run(function (): void {
            $restricted = $this->ownProduct(['allowed_country_ids' => [$this->russia]]);
            $everywhere = $this->ownProduct();
            $catalog = $this->createCentralCatalogVariant()['product'];
            // For catalog products the column is only a ranking preference: never hidden.
            $catalog->update(['allowed_country_ids' => [$this->russia]]);

            foreach ([$this->morocco, $this->egypt] as $countryId) {
                $repo = $this->visitFrom($countryId);
                $listed = $repo->paginatedSearchProducts([], 50)->getCollection()->modelKeys();
                $home = app(HomeProductService::class)->getNewIn(50, $countryId)->modelKeys();

                $this->assertNull($repo->productBySlug($restricted->slug), "product page, country {$countryId}");
                $this->assertNotContains($restricted->id, $listed, "listing, country {$countryId}");
                $this->assertNotContains($restricted->id, $home, "home section, country {$countryId}");
                $this->assertContains($everywhere->id, $listed);
                $this->assertContains($catalog->id, $listed);
            }

            $this->visitFrom($this->morocco);
            Livewire::test(ProductPage::class, ['slug' => $restricted->slug])->assertStatus(404);

            // Unknown country (first visit from a shared link, nothing picked yet): shown.
            $repo = $this->visitFrom(null);
            $this->assertNotNull($repo->productBySlug($restricted->slug), 'product page, unknown country');
            $this->assertContains($restricted->id, $repo->paginatedSearchProducts([], 50)->getCollection()->modelKeys());

            // A published product with stock 0 opens (shows "Out of Stock") instead of 404.
            $soldOut = $this->ownProduct(['manage_stock' => true, 'stock' => 0]);
            $this->assertNotNull($this->visitFrom($this->morocco)->productBySlug($soldOut->slug), 'out-of-stock product page');

            $repo = $this->visitFrom($this->russia);
            $this->assertNotNull($repo->productBySlug($restricted->slug));
            $this->assertContains($restricted->id, $repo->paginatedSearchProducts([], 50)->getCollection()->modelKeys());
            $this->assertContains($restricted->id, app(HomeProductService::class)->getNewIn(50, $this->russia)->modelKeys());
        });
    }

    #[Test]
    public function own_product_shows_the_vendor_sale_price_in_every_country_and_its_gallery_video(): void
    {
        $this->tenant->run(function (): void {
            $product = $this->ownProduct();
            File::create(['key' => 'gallery', 'path' => 'product-images/1/gallery/photo.avif', 'file_type' => FileType::Image,
                'model_id' => $product->id, 'model_type' => $product->getMorphClass(), 'sort_order' => 1]);
            File::create(['key' => 'gallery', 'path' => 'product-images/1/gallery/clip.mp4', 'file_type' => FileType::Video, 'mime_type' => 'video/mp4',
                'model_id' => $product->id, 'model_type' => $product->getMorphClass(), 'sort_order' => 2]);

            foreach ([$this->morocco, $this->egypt, $this->russia, null] as $countryId) {
                $this->visitFrom($countryId);
                $fresh = Product::with('variants', 'flashSales')->find($product->id);
                $pricing = $fresh->storefrontPricing(null, $countryId);

                $this->assertSame(49.0, $pricing['current_price'], "country {$countryId}");
                $this->assertSame(60.0, $pricing['original_price']);
                $this->assertSame(0.0, $pricing['fixed_shipping']);

                // The order line records the same 49.00 (cost 0, all vendor profit, no fixed shipping).
                $line = OrderComposer::line($fresh, null, 1, $countryId);
                $this->assertSame(49.0, (float) $line['grand_total']);
                $this->assertSame(0.0, (float) $line['fixed_shipping']);
            }

            $this->visitFrom($this->morocco);
            $page = Livewire::test(ProductPage::class, ['slug' => $product->slug])->assertOk();
            $media = collect($page->viewData('mediaItems'));

            $this->assertSame(['image', 'video'], $media->pluck('type')->all());
            $this->assertStringEndsWith('clip.mp4', $media->firstWhere('type', 'video')['src']);
            $this->assertSame('49.00', number_format((float) $page->viewData('sellPrice'), 2));
            $page->assertSeeHtml('clip.mp4');
        });
    }
}
