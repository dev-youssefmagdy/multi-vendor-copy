<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\FileType;
use App\Livewire\Tenant\Storefront\FavoritesPage;
use App\Livewire\Tenant\Storefront\ProfilePage;
use App\Models\Tenant\File;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Favorites live in localStorage as a snapshot, so the favorites grids refresh that snapshot
 * from the server: images:convert-avif deleted the jpg/png files those snapshots point at.
 */
class StorefrontFavoritesDisplayTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;
    use SetsUpTenantPanel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();

        // The component methods are called directly; pricing reads the country from the session.
        request()->setLaravelSession($this->app['session']->driver());
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function productWithImage(string $path, array $attributes = []): Product
    {
        $product = $this->createOwnProductVariant(price: 80.0, productAttributes: $attributes)->product;

        File::create([
            'key' => 'primary_medium',
            'path' => $path,
            'file_type' => FileType::Image,
            'model_id' => $product->id,
            'model_type' => $product->getMorphClass(),
        ]);

        return $product->fresh();
    }

    #[Test]
    public function display_data_returns_the_current_absolute_image_and_storefront_price(): void
    {
        $this->tenant->run(function () {
            $product = $this->productWithImage('products/1/current.avif');

            $rows = (new FavoritesPage)->favoriteDisplayData([$product->slug]);

            $this->assertCount(1, $rows);
            $this->assertSame($product->id, $rows[0]['id']);
            $this->assertSame($product->slug, $rows[0]['slug']);
            $this->assertMatchesRegularExpression('#^https?://[^/]+/.*products/1/current\.avif$#', $rows[0]['image']);

            $rate = (float) data_get(app(StorefrontRepository::class)->currentCurrency(), 'conversion_rate', 1.0);
            $this->assertEquals(round((float) $product->storefrontPricing()['current_price'] * $rate, 2), $rows[0]['price']);

            // The stored "added" timestamp belongs to the customer's list and must survive a refresh.
            $this->assertArrayNotHasKey('added', $rows[0]);
        });
    }

    #[Test]
    public function display_data_resolves_id_only_entries_and_skips_what_it_cannot_show(): void
    {
        $this->tenant->run(function () {
            $byId = $this->productWithImage('products/2/a.avif');
            $inactive = $this->productWithImage('products/3/b.avif', ['active' => false]);

            $page = new FavoritesPage;

            $this->assertSame([$byId->id], array_column($page->favoriteDisplayData([], [$byId->id]), 'id'));
            $this->assertSame([], $page->favoriteDisplayData([$inactive->slug, 'no-such-product', 7, '']));
            $this->assertSame([], $page->favoriteDisplayData([], []));
        });
    }

    #[Test]
    public function display_data_query_count_does_not_grow_with_the_number_of_favorites(): void
    {
        $this->tenant->run(function () {
            $first = $this->productWithImage('products/1/a.avif');
            $others = collect([2, 3, 4])->map(fn ($n) => $this->productWithImage("products/{$n}/a.avif"));

            $count = function (array $slugs): int {
                $connection = DB::connection();
                $connection->flushQueryLog();
                $connection->enableQueryLog();
                $rows = (new FavoritesPage)->favoriteDisplayData($slugs);
                $connection->disableQueryLog();
                $this->assertCount(count($slugs), $rows);

                return count($connection->getQueryLog());
            };

            // Warm per-request caches so only the per-product cost is compared.
            $count([$first->slug]);

            $this->assertSame(
                $count([$first->slug]),
                $count([$first->slug, ...$others->pluck('slug')->all()]),
            );
        });
    }

    #[Test]
    public function fav_data_json_keeps_its_shape_for_the_heart_buttons(): void
    {
        $this->tenant->run(function () {
            $product = $this->productWithImage('products/4/c.avif');

            $decoded = json_decode($product->storefrontFavData(), true);

            $this->assertSame(['slug', 'name', 'price', 'old_price', 'discount', 'rating', 'image', 'url', 'added'], array_keys($decoded));
            $this->assertStringContainsString('products/4/c.avif', $decoded['image']);
        });
    }

    #[Test]
    public function the_souqify_profile_wishlist_can_refresh_too(): void
    {
        $this->assertTrue(method_exists(ProfilePage::class, 'favoriteDisplayData'));
    }

    #[Test]
    public function favorites_grids_render_images_through_the_shared_safe_helper(): void
    {
        foreach (['elora/pages/favorites', 'souqify/pages/favorites', 'souqify/pages/profile'] as $view) {
            $source = file_get_contents(resource_path("views/themes/{$view}.blade.php"));

            $this->assertStringNotContainsString('src="${item.image}"', $source, $view);
            $this->assertStringContainsString('display.imgTag(item.image', $source, $view);
            $this->assertStringContainsString('favoriteDisplayData(slugs, ids)', $source, $view);
        }

        $js = view('components.storefront.favorites-display-js')->render();
        $this->assertStringContainsString('elora\/assets\/images\/product-default.png', $js);
        $this->assertStringContainsString('onerror="window.StorefrontFavoritesDisplay.fallback(this)"', $js);
    }
}
