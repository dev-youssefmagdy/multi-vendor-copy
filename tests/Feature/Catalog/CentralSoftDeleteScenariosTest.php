<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Enums\DeliveryScope;
use App\Enums\ProductStatus;
use App\Livewire\Admin\Category\AddEditCategory;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Variation;
use App\Models\VariationOption;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * QA, dedup plan phase 0 (Frontend handoff scenarios, central side): the central admin save paths on top of
 * soft-deleted variants / categories.
 */
class CentralSoftDeleteScenariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake(); // observers fan out to tenants
    }

    private function save(array $variants, ?Product $product = null): Product
    {
        return app(ProductService::class)->save([
            'sku' => 'QA-P0',
            'slug' => 'qa-phase0-product',
            'status' => ProductStatus::Published->value,
            'delivery_scope' => DeliveryScope::AllZones->value,
            'base_price' => 10,
            'stock' => 5,
            'min_stock' => 0,
            'translations' => ['en' => ['name' => 'QA product']],
            'variants' => $variants,
        ], $product);
    }

    #[Test]
    public function removed_variant_sku_can_be_re_added_in_a_later_save_and_product_restore_keeps_it_deleted(): void
    {
        $variation = Variation::query()->create(['status' => 'active']);
        [$red, $blue] = [
            VariationOption::query()->create(['variation_id' => $variation->id]),
            VariationOption::query()->create(['variation_id' => $variation->id]),
        ];

        $product = $this->save([
            ['option_ids' => [$red->id], 'sku' => 'QA-RED', 'price' => 10, 'stock' => 1],
            ['option_ids' => [$blue->id], 'sku' => 'QA-BLUE', 'price' => 10, 'stock' => 1],
        ]);
        $ids = $product->variants()->pluck('id', 'sku');
        $this->assertCount(2, $ids);

        // Save 1: remove BLUE.
        $product = $this->save([['id' => $ids['QA-RED'], 'option_ids' => [$red->id], 'sku' => 'QA-RED', 'price' => 10, 'stock' => 1]], $product);
        $this->assertSame(['QA-RED'], $product->variants()->pluck('sku')->all());
        $this->assertTrue(ProductVariant::withTrashed()->findOrFail($ids['QA-BLUE'])->trashed(), 'removed variant is soft-deleted');

        // Save 2 (separate): re-add BLUE with the same SKU and options -> a new row, no unique-index clash.
        $product = $this->save([
            ['id' => $ids['QA-RED'], 'option_ids' => [$red->id], 'sku' => 'QA-RED', 'price' => 10, 'stock' => 1],
            ['option_ids' => [$blue->id], 'sku' => 'QA-BLUE', 'price' => 12, 'stock' => 3],
        ], $product);
        $newBlue = $product->variants()->where('sku', 'QA-BLUE')->firstOrFail();
        $this->assertNotSame($ids['QA-BLUE'], $newBlue->id);
        $this->assertSame([$blue->id], $newBlue->options()->pluck('variation_options.id')->all());
        $this->assertSame(2, ProductVariant::withTrashed()->where('sku', 'QA-BLUE')->count());

        // A stale form that still posts the deleted variant id creates a new row instead of reviving it.
        $this->assertNull($product->variants()->find($ids['QA-BLUE']));

        // Product delete + restore: the individually deleted variant stays deleted.
        $product->delete();
        Product::withTrashed()->findOrFail($product->id)->restore();
        $this->assertEqualsCanonicalizing([$ids['QA-RED'], $newBlue->id], $product->fresh()->variants()->pluck('id')->all());
        $this->assertTrue(ProductVariant::withTrashed()->findOrFail($ids['QA-BLUE'])->trashed());
    }

    #[Test]
    public function category_form_rejects_a_parent_deleted_while_the_form_was_open(): void
    {
        $parent = Category::withoutEvents(fn () => Category::query()->create(['status' => 'published']));
        $live = Category::withoutEvents(fn () => Category::query()->create(['status' => 'published']));
        Category::withoutEvents(fn () => $parent->delete());

        Livewire::test(AddEditCategory::class)
            ->set('parentPath', [$parent->id])
            ->call('save')
            ->assertHasErrors(['parentPath.0']);

        Livewire::test(AddEditCategory::class)
            ->set('parentPath', [$live->id])
            ->call('save')
            ->assertHasNoErrors(['parentPath.0']);
    }
}
