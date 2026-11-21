<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\CentralCoupon;
use App\Models\CentralFlashSale;
use App\Models\Concerns\SoftDeletesCatalogRow;
use App\Models\Product;
use App\Models\ProductLabel;
use App\Models\ProductVariant;
use App\Models\Variation;
use App\Models\VariationOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dedup plan phase 0, §0.3-Q3 / invariant 6: central catalog rows tenants reference are soft-deleted
 * (migration 2026_11_22_000001 + SoftDeletesCatalogRow). Soft-deleted rows must look deleted to every Eloquent
 * reader, the old FK side effects must still happen, and unique values must be reusable as after a hard delete.
 */
class CentralSoftDeletesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake(); // observers fan out to tenants on delete
    }

    private function product(): Product
    {
        return Product::withoutEvents(fn () => Product::query()->create([
            'slug' => 'p-' . Str::lower(Str::random(8)),
            'sku' => 'P-' . Str::upper(Str::random(6)),
            'status' => ProductStatus::Published,
            'base_price' => 10,
            'stock' => 5,
        ]));
    }

    private function variant(Product $product, string $sku): ProductVariant
    {
        return ProductVariant::withoutEvents(fn () => ProductVariant::query()->create([
            'product_id' => $product->id, 'sku' => $sku, 'price' => 10, 'stock' => 5,
        ]));
    }

    #[Test]
    public function migration_adds_deleted_at_and_live_only_unique_indexes(): void
    {
        foreach (['product_variants', 'categories', 'central_coupons', 'central_flash_sales', 'product_labels', 'variations', 'variation_options'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'deleted_at'), $table);
        }
        foreach (['product_variants' => 'sku', 'categories' => 'external_id', 'central_coupons' => 'code', 'variation_options' => 'external_id'] as $table => $column) {
            $this->assertTrue(Schema::hasIndex($table, "{$table}_{$column}_live_unique"), $table);
            $this->assertTrue(Schema::hasIndex($table, "{$table}_{$column}_index"), $table);
            $this->assertFalse(Schema::hasIndex($table, "{$table}_{$column}_unique"), $table);
        }
        $this->assertFalse(Schema::hasColumn('product_badges', 'deleted_at'), 'badges have no central delete path');
        $this->assertTrue(ProductVariant::softDeletesReady());
    }

    #[Test]
    public function variant_soft_delete_hides_the_row_replays_cascades_and_frees_the_sku(): void
    {
        $product = $this->product();
        $variation = Variation::query()->create(['status' => 'active']);
        $option = VariationOption::query()->create(['variation_id' => $variation->id]);
        $variant = $this->variant($product, 'SKU-A');
        $variant->options()->attach($option->id);

        $variant->delete();

        $this->assertNull(ProductVariant::query()->find($variant->id));
        $this->assertTrue(ProductVariant::withTrashed()->findOrFail($variant->id)->trashed(), 'row kept for tenant references');
        $this->assertSame(0, $product->variants()->count());
        $this->assertSame(0, DB::table('product_variant_option')->where('product_variant_id', $variant->id)->count(), 'cascade replayed');

        // Same SKU again (deterministic variant SKUs are re-created by ProductService / the importer).
        $again = $this->variant($product, 'SKU-A');
        $this->assertNotSame($variant->id, $again->id);

        // Uniqueness among live rows is still enforced.
        try {
            $this->variant($this->product(), 'SKU-A');
            $this->fail('duplicate live SKU must be rejected');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        $again->forceDelete();
        $this->assertNull(ProductVariant::withTrashed()->find($again->id), 'forceDelete still hard-deletes');
    }

    #[Test]
    public function builder_delete_on_the_relation_soft_deletes_and_replays_cascades(): void
    {
        // ProductService::save() without a variants payload: $product->variants()->delete().
        $product = $this->product();
        $option = VariationOption::query()->create(['variation_id' => Variation::query()->create(['status' => 'active'])->id]);
        $a = $this->variant($product, 'SKU-B1');
        $b = $this->variant($product, 'SKU-B2');
        $a->options()->attach($option->id);

        $this->assertSame(2, $product->variants()->delete());

        $this->assertSame(0, $product->variants()->count());
        $this->assertSame(2, ProductVariant::onlyTrashed()->whereKey([$a->id, $b->id])->count());
        $this->assertSame(0, DB::table('product_variant_option')->where('variation_option_id', $option->id)->count());
    }

    #[Test]
    public function category_soft_delete_orphans_children_and_detaches_products_like_the_old_fks(): void
    {
        [$parent, $child] = Category::withoutEvents(fn () => [
            $p = Category::query()->create(['status' => 'published', 'external_id' => 777]),
            Category::query()->create(['status' => 'published', 'parent_id' => $p->id]),
        ]);
        $product = $this->product();
        $product->categories()->attach($parent->id);

        $parent->delete();

        $this->assertNull(Category::query()->find($parent->id));
        $this->assertNotNull(Category::withTrashed()->find($parent->id));
        $this->assertNull($child->fresh()->parent_id, 'ON DELETE SET NULL replayed');
        $this->assertSame(0, DB::table('category_product')->where('category_id', $parent->id)->count(), 'ON DELETE CASCADE replayed');
        $this->assertSame([], $product->categories()->pluck('categories.id')->all());

        // The importer can re-create a category with the same external id.
        $this->assertNotNull(Category::withoutEvents(fn () => Category::query()->create(['status' => 'published', 'external_id' => 777])));
    }

    #[Test]
    public function coupon_flash_sale_label_and_variation_soft_deletes(): void
    {
        $product = $this->product();

        $coupon = CentralCoupon::query()->create(['code' => 'SAVE10', 'type' => 'fixed', 'value' => 10]);
        $coupon->delete();
        $this->assertNull(CentralCoupon::query()->where('code', 'SAVE10')->first(), 'admin uniqueness check ignores deleted codes');
        $this->assertNotNull(CentralCoupon::query()->create(['code' => 'SAVE10', 'type' => 'fixed', 'value' => 5]), 'code reusable');
        $this->assertSame(1, CentralCoupon::withTrashed()->where('code', 'SAVE10')->onlyTrashed()->count());

        $sale = CentralFlashSale::query()->create(['discount_percentage' => 10, 'active' => true]);
        $sale->products()->attach($product->id);
        $sale->delete();
        $this->assertNull(CentralFlashSale::query()->find($sale->id));
        $this->assertSame(0, DB::table('central_flash_sale_product')->where('central_flash_sale_id', $sale->id)->count());

        $label = ProductLabel::query()->create(['text' => 'Hot', 'active' => true]);
        $label->products()->attach($product->id);
        $label->delete();
        $this->assertSame(0, $product->labels()->count());
        $this->assertSame(0, DB::table('product_label_product')->where('product_label_id', $label->id)->count());

        $variation = Variation::withoutEvents(fn () => Variation::query()->create(['status' => 'active']));
        $product->variations()->attach($variation->id);
        Variation::withoutEvents(fn () => $variation->delete());
        $this->assertSame(0, DB::table('product_variation')->where('variation_id', $variation->id)->count());
        $this->assertNotNull(Variation::withTrashed()->find($variation->id));
    }

    #[Test]
    public function before_the_migration_the_trait_hard_deletes_exactly_as_before(): void
    {
        // product_badges has no deleted_at: stands in for any table before 2026_11_22_000001 has run.
        $model = new class () extends Model {
            use SoftDeletesCatalogRow;

            protected $table = 'product_badges';

            protected $guarded = [];

            protected static function catalogDeleteActions(): array
            {
                return [];
            }
        };

        $this->assertFalse($model::softDeletesReady());
        $this->assertStringNotContainsString('deleted_at', $model->newQuery()->toSql());

        $one = $model->newQuery()->create(['text' => 'tmp-one', 'active' => true]);
        $model->newQuery()->create(['text' => 'tmp-two', 'active' => true]);

        $one->delete();
        $this->assertFalse(DB::table('product_badges')->where('text', 'tmp-one')->exists(), 'model delete is a hard delete');

        $model->newQuery()->where('text', 'tmp-two')->delete();
        $this->assertFalse(DB::table('product_badges')->where('text', 'tmp-two')->exists(), 'builder delete is a hard delete');
    }
}
