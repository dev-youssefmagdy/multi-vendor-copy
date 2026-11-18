<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Tenant\Product;
use App\Repositories\Tenant\TenantPanelRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Client review 11/18:
 * - "when moving between pages, some products show on more than one page" (non-unique sort),
 * - "a product entered as an own product shows in both the Products and Own products tabs".
 */
class VendorProductListsTest extends TestCase
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

    private function product(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'slug' => 'p-'.Str::lower(Str::random(8)),
            'price' => ['default' => 10.0],
            'default_price' => 10.0,
            'active' => true,
            'is_own_product' => false,
            'manage_stock' => false,
            'order_number' => 0, // same value for all: the case that broke paging
        ], $attributes));
    }

    public function test_pages_never_repeat_or_skip_products_when_sort_values_tie(): void
    {
        $this->tenant->run(function (): void {
            Product::query()->delete();
            $ids = collect(range(1, 30))->map(fn () => $this->product()->id)->sort()->values()->all();

            $repo = app(TenantPanelRepository::class);
            $seen = [];
            foreach ([1, 2, 3] as $page) {
                $seen = array_merge($seen, $repo->queryProducts([])->paginate(12, ['*'], 'page', $page)->getCollection()->modelKeys());
            }

            $this->assertSame(count($seen), count(array_unique($seen)), 'a product appeared on two pages');
            sort($seen);
            $this->assertSame($ids, $seen, 'every product appears exactly once across the pages');
        });
    }

    public function test_own_products_only_appear_in_the_own_products_tab(): void
    {
        $this->tenant->run(function (): void {
            $catalog = $this->product();
            $own = $this->product(['is_own_product' => true]);
            $repo = app(TenantPanelRepository::class);

            $productsTab = $repo->queryProducts([])->get()->modelKeys();
            $this->assertContains($catalog->id, $productsTab);
            $this->assertNotContains($own->id, $productsTab);

            foreach (['', 'in', 'out', 'partial'] as $stock) {
                $ownTab = $repo->queryOwnProducts(['stock' => $stock])->get()->modelKeys();
                $this->assertNotContains($catalog->id, $ownTab, "catalog product leaked into Own products (stock={$stock})");
            }
            $this->assertContains($own->id, $repo->queryOwnProducts(['stock' => 'in'])->get()->modelKeys());
        });
    }

    public function test_search_still_respects_the_other_filters(): void
    {
        $this->tenant->run(function (): void {
            $active = $this->product(['slug' => 'blue-strap-active']);
            $inactive = $this->product(['slug' => 'blue-strap-inactive', 'active' => false]);

            $found = app(TenantPanelRepository::class)
                ->queryProducts(['search' => 'blue-strap', 'status' => 'active'])->get()->modelKeys();

            $this->assertContains($active->id, $found);
            $this->assertNotContains($inactive->id, $found);
        });
    }
}
