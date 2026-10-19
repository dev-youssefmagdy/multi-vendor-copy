<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Eloquent\Relations\CachedBelongsTo;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Storefront performance regressions + tenant isolation of the structures the
 * optimisations rely on (CachedBelongsTo identity map, versioned list caches).
 * Uses two tenants with deliberately different products / languages / settings.
 */
class StorefrontPerformanceTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;
    use SetsUpTenantPanel;

    private Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();

        $this->tenantB = Tenant::create([
            'status' => TenantStatus::Active->value,
            'shop_name' => 'Second Store',
            'phone' => '+15559998888',
            'launch_ready' => true,
        ]);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        $this->tenantB->delete();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    #[Test]
    public function product_lists_hydrate_cards_in_a_bounded_number_of_queries(): void
    {
        $this->tenant->run(function () {
            for ($i = 0; $i < 12; $i++) {
                $this->createOwnProductVariant(stock: 5, price: 10 + $i);
            }

            $repo = app(StorefrontRepository::class);
            DB::flushQueryLog();
            DB::enableQueryLog();

            $newIn = $repo->newInProducts(10, 1)->items();
            $featured = $repo->featuredProducts(10);
            $trending = $repo->trendingNowProducts(10);

            // Everything a product card touches must already be loaded.
            foreach ([...$newIn, ...$featured, ...$trending] as $product) {
                foreach (['variants', 'translations', 'files', 'rates', 'flashSales'] as $relation) {
                    $this->assertTrue($product->relationLoaded($relation), "{$relation} not eager loaded");
                }
            }

            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            // 3 lists x 12 products would be ~100+ queries with per-card lazy loading.
            $this->assertLessThan(60, $queries, "Product lists issued {$queries} queries");
        });
    }

    #[Test]
    public function cached_belongs_to_identity_map_never_crosses_tenants(): void
    {
        CachedBelongsTo::flushCache();

        $this->tenantB->run(function () {
            Language::create(['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr', 'is_active' => true, 'is_default' => true, 'sort_order' => 1]);
        });

        $a = $this->tenant->run(fn () => $this->languageViaRelation());
        $b = $this->tenantB->run(fn () => $this->languageViaRelation());

        // Same primary key (1) in both tenants, different rows: must not be shared.
        $this->assertSame('en', $a);
        $this->assertSame('fr', $b);
    }

    #[Test]
    public function version_tagged_caches_are_isolated_per_tenant(): void
    {
        $this->tenant->run(function () {
            $this->createOwnProductVariant(stock: 5, price: 10);
            $this->assertCount(1, app(StorefrontRepository::class)->featuredProducts(10)
                ->merge(app(StorefrontRepository::class)->newInProducts(10, 1)->items()) ->unique('id'));
        });

        $this->tenantB->run(function () {
            // Tenant B has no products: must not see tenant A's cached list.
            $repo = app(StorefrontRepository::class);
            $this->assertCount(0, $repo->featuredProducts(10));
            $this->assertCount(0, $repo->newInProducts(10, 1)->items());
            $this->assertSame(0, Product::query()->count());
        });
    }

    #[Test]
    public function product_list_cache_is_invalidated_when_a_product_changes(): void
    {
        $this->tenant->run(function () {
            $variant = $this->createOwnProductVariant(stock: 5, price: 10);
            $repo = fn () => app(StorefrontRepository::class)->newInProducts(10, 1)->items();

            $this->assertCount(1, $repo());

            $variant->product->update(['active' => false]);

            $this->assertCount(0, $repo());
        });
    }

    private function languageViaRelation(): ?string
    {
        // Any model whose eager load goes through CachedBelongsTo -> Language.
        $product = Product::create(['slug' => 'p-' . uniqid(), 'sku' => uniqid(), 'price' => ['default' => 1], 'active' => true, 'is_own_product' => true, 'is_tenant_owned' => true]);
        $product->translations()->create(['language_id' => Language::query()->value('id'), 'field' => 'name', 'value' => 'x']);

        return Product::with('translations.language')->find($product->id)->translations->first()->language?->code;
    }
}
