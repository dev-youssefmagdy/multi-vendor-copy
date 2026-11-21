<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\CentralCoupon;
use App\Models\CentralFlashSale;
use App\Models\Product;
use App\Models\ProductLabel;
use App\Models\ProductVariant;
use App\Models\Variation;
use App\Models\VariationOption;
use App\Support\Catalog\CatalogSoftDeletingScope;
use App\Support\Catalog\LiveCatalogRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * QA, dedup plan phase 0: production QC of the central migration 2026_11_22_000001 and of the code on both sides
 * of it (live is "before" until the deploy, the test DB is "after").
 *
 *  - up() twice is a no-op, down() restores the old schema (and hard-deletes trashed rows), up() again = same schema;
 *  - before the migration: models hard-delete, no `deleted_at` in SQL, LiveCatalogRule is a plain `exists`;
 *  - after: LiveCatalogRule rejects soft-deleted ids of every SoftDeletesCatalogRow model.
 *
 * DDL commits the RefreshDatabase transaction implicitly, so the test cleans up the rows it wrote and always
 * leaves the schema migrated.
 */
class CentralSoftDeletesMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const TABLES = ['product_variants', 'categories', 'central_coupons', 'central_flash_sales', 'product_labels', 'variations', 'variation_options'];

    private const MODELS = [
        ProductVariant::class, Category::class, CentralCoupon::class, CentralFlashSale::class,
        ProductLabel::class, Variation::class, VariationOption::class,
    ];

    private string $marker;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake(); // observers fan out to tenants
        $this->marker = 'QAM' . Str::upper(Str::random(6));
    }

    protected function tearDown(): void
    {
        // Rows written after the implicit commit are not rolled back: remove them.
        $products = DB::table('products')->where('sku', 'like', $this->marker . '%')->pluck('id');
        DB::table('product_variants')->whereIn('product_id', $products)->delete();
        DB::table('products')->whereIn('id', $products)->delete();
        DB::table('central_coupons')->where('code', 'like', $this->marker . '%')->delete();
        DB::table('categories')->where('external_id', '>=', 990000000)->delete();
        DB::table('product_labels')->where('text', 'like', $this->marker . '%')->delete();
        DB::table('central_flash_sales')->where('discount_percentage', 77)->delete();
        $variations = DB::table('variations')->where('status', 'inactive')->where('created_at', '>=', now()->subHour())->pluck('id');
        DB::table('variation_options')->whereIn('variation_id', $variations)->delete();
        DB::table('variations')->whereIn('id', $variations)->delete();

        if (! Schema::hasColumn('product_variants', 'deleted_at')) {
            $this->migration()->up();
        }
        CatalogSoftDeletingScope::flushColumnCache();

        parent::tearDown();
    }

    private function migration(): Migration
    {
        return require base_path('database/migrations/2026_11_22_000001_add_soft_deletes_to_central_catalog_tables.php');
    }

    /** @return array<string, array{columns: list<string>, indexes: list<string>}> */
    private function schemaState(): array
    {
        $state = [];
        foreach (self::TABLES as $table) {
            $indexes = collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->map(fn ($row) => $row->Key_name . ($row->Non_unique ? '' : ':unique') . '(' . ($row->Column_name ?? 'expr') . ')')
                ->unique()->sort()->values()->all();
            $state[$table] = [
                'deleted_at' => Schema::hasColumn($table, 'deleted_at'),
                'indexes' => $indexes,
            ];
        }

        return $state;
    }

    private function product(): Product
    {
        return Product::withoutEvents(fn () => Product::query()->create([
            'slug' => Str::lower($this->marker) . '-' . Str::lower(Str::random(6)),
            'sku' => $this->marker . '-' . Str::upper(Str::random(6)),
            'status' => ProductStatus::Published,
            'base_price' => 10,
            'stock' => 5,
        ]));
    }

    /** One live + one deleted row per SoftDeletesCatalogRow model. @return array<class-string, array{0: int, 1: int}> */
    private function liveAndDeletedRows(): array
    {
        $product = $this->product();
        $make = [
            ProductVariant::class => fn () => ProductVariant::query()->create(['product_id' => $product->id, 'sku' => $this->marker . '-V' . Str::random(4), 'price' => 1, 'stock' => 1]),
            Category::class => fn () => Category::query()->create(['status' => 'published', 'external_id' => random_int(990000000, 999999999)]),
            CentralCoupon::class => fn () => CentralCoupon::query()->create(['code' => $this->marker . Str::upper(Str::random(4)), 'type' => 'fixed', 'value' => 1]),
            CentralFlashSale::class => fn () => CentralFlashSale::query()->create(['discount_percentage' => 77, 'active' => true]),
            ProductLabel::class => fn () => ProductLabel::query()->create(['text' => $this->marker . ' label', 'active' => true]),
            Variation::class => fn () => Variation::query()->create(['status' => 'inactive']),
            VariationOption::class => fn () => VariationOption::query()->create(['variation_id' => Variation::query()->create(['status' => 'inactive'])->id]),
        ];

        $rows = [];
        foreach ($make as $class => $factory) {
            [$live, $deleted] = $class::withoutEvents(fn () => [$factory(), $factory()]);
            $class::withoutEvents(fn () => $deleted->delete());
            $rows[$class] = [$live->id, $deleted->id];
        }

        return $rows;
    }

    private function ruleAccepts(string $model, int $id): bool
    {
        return Validator::make(['id' => $id], ['id' => ['integer', LiveCatalogRule::exists($model)]])->passes();
    }

    #[Test]
    public function migration_is_idempotent_reversible_and_code_works_before_and_after_it(): void
    {
        $migration = $this->migration();
        $after = $this->schemaState();

        foreach (self::TABLES as $table) {
            $this->assertTrue($after[$table]['deleted_at'], $table);
        }
        $this->assertContains('product_variants_sku_live_unique:unique(expr)', $after['product_variants']['indexes']);
        $this->assertContains('product_variants_sku_index(sku)', $after['product_variants']['indexes']);

        // ── up() again: no-op ─────────────────────────────────────────────────────────────────────────────
        $migration->up();
        $this->assertSame($after, $this->schemaState(), 'second up() changes nothing');

        // ── AFTER: LiveCatalogRule rejects soft-deleted ids of every model ───────────────────────────────────
        CatalogSoftDeletingScope::flushColumnCache();
        $rows = $this->liveAndDeletedRows();
        foreach ($rows as $class => [$live, $deleted]) {
            $this->assertTrue($class::softDeletesReady(), $class);
            $this->assertTrue($this->ruleAccepts($class, $live), "{$class}: live id accepted");
            $this->assertFalse($this->ruleAccepts($class, $deleted), "{$class}: soft-deleted id rejected after the migration");
            $this->assertNotNull($class::withTrashed()->find($deleted), "{$class}: row kept");
            $this->assertStringContainsString('deleted_at', (string) LiveCatalogRule::exists($class));
        }

        // ── down(): trashed rows hard-deleted, old schema back ──────────────────────────────────────────────
        $migration->down();
        CatalogSoftDeletingScope::flushColumnCache();
        $before = $this->schemaState();
        foreach (self::TABLES as $table) {
            $this->assertFalse($before[$table]['deleted_at'], $table);
        }
        $this->assertContains('product_variants_sku_unique:unique(sku)', $before['product_variants']['indexes']);
        $this->assertContains('categories_external_id_unique:unique(external_id)', $before['categories']['indexes']);
        $this->assertContains('central_coupons_code_unique:unique(code)', $before['central_coupons']['indexes']);
        $this->assertContains('variation_options_external_id_unique:unique(external_id)', $before['variation_options']['indexes']);
        $this->assertNotContains('product_variants_sku_index(sku)', $before['product_variants']['indexes']);
        foreach ($rows as $class => [$live, $deleted]) {
            $table = (new $class())->getTable();
            $this->assertTrue(DB::table($table)->where('id', $live)->exists(), "{$table}: live row kept by down()");
            $this->assertFalse(DB::table($table)->where('id', $deleted)->exists(), "{$table}: trashed row hard-deleted by down()");
        }

        // ── BEFORE (= live today): plain exists rule, hard deletes, no deleted_at in SQL ─────────────────────
        foreach (self::MODELS as $class) {
            $this->assertFalse($class::softDeletesReady(), $class);
            $this->assertStringNotContainsString('deleted_at', $class::query()->toSql(), $class);
            $this->assertStringNotContainsString('deleted_at', (string) LiveCatalogRule::exists($class), $class);
            [$live] = $rows[$class];
            $this->assertTrue($this->ruleAccepts($class, $live), "{$class}: plain exists before the migration");
        }
        $product = $this->product();
        $variant = ProductVariant::withoutEvents(fn () => ProductVariant::query()->create(['product_id' => $product->id, 'sku' => $this->marker . '-OLD', 'price' => 1, 'stock' => 1]));
        ProductVariant::withoutEvents(fn () => $variant->delete());
        $this->assertFalse(DB::table('product_variants')->where('id', $variant->id)->exists(), 'hard delete before the migration');
        $this->assertFalse($this->ruleAccepts(ProductVariant::class, $variant->id));
        $parent = Category::withoutEvents(fn () => Category::query()->create(['status' => 'published', 'external_id' => 990000001]));
        $child = Category::withoutEvents(fn () => Category::query()->create(['status' => 'published', 'parent_id' => $parent->id, 'external_id' => 990000002]));
        Category::withoutEvents(fn () => $parent->delete());
        $this->assertNull(DB::table('categories')->where('id', $child->id)->value('parent_id'), 'real FK SET NULL before the migration');
        ProductVariant::withoutEvents(fn () => ProductVariant::query()->create(['product_id' => $product->id, 'sku' => $this->marker . '-DUP', 'price' => 1, 'stock' => 1]));
        try {
            ProductVariant::withoutEvents(fn () => ProductVariant::query()->create(['product_id' => $product->id, 'sku' => $this->marker . '-DUP', 'price' => 1, 'stock' => 1]));
            $this->fail('old unique index enforced before the migration');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        // ── up() again after down(): identical schema ───────────────────────────────────────────────────────
        $migration->up();
        CatalogSoftDeletingScope::flushColumnCache();
        $this->assertSame($after, $this->schemaState(), 'down() + up() round trip');
        $this->assertTrue(ProductVariant::softDeletesReady());
    }
}
