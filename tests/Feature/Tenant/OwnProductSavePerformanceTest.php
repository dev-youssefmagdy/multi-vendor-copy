<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Tenant\Category;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Guards the add/edit own-product request: every submitted value must be
 * persisted (no data loss) and the number of SQL queries must stay bounded
 * regardless of how many variants / categories / badges are submitted.
 */
class OwnProductSavePerformanceTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;

    private const VARIANTS = 12;

    /** Upper bound; the pre-optimisation baseline was far above this. */
    private const MAX_CREATE_QUERIES = 50;
    private const MAX_UPDATE_QUERIES = 50;

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

    public function test_create_persists_everything_with_bounded_queries(): void
    {
        [$categoryIds, $badgeIds] = $this->seedLookups();
        $payload = $this->payload($categoryIds, $badgeIds);

        [$response, $queries] = $this->measure(fn () => $this->actingAsTenantAdmin()
            ->json('post', $this->tenantUrl('/admin/own-products'), $payload));

        $response->assertSuccessful();
        if (getenv('DUMP_QUERIES')) { foreach (array_count_values($this->queryLog) as $q => $n) { fwrite(STDERR, "$n x $q\n"); } }
        fwrite(STDERR, "\n[create] queries={$queries['count']} time_ms={$queries['ms']}\n");
        $this->assertLessThanOrEqual(self::MAX_CREATE_QUERIES, $queries['count'], 'create query budget exceeded');

        $product = $this->inTenant(fn () => Product::query()->where('sku', 'PERF-001')->firstOrFail());
        $this->assertPersisted($product, $categoryIds, $badgeIds, $payload);
    }

    public function test_update_persists_everything_with_bounded_queries(): void
    {
        [$categoryIds, $badgeIds] = $this->seedLookups();
        $payload = $this->payload($categoryIds, $badgeIds);

        $this->actingAsTenantAdmin()->json('post', $this->tenantUrl('/admin/own-products'), $payload)->assertSuccessful();
        $product = $this->inTenant(fn () => Product::query()->where('sku', 'PERF-001')->firstOrFail());
        $existing = $this->inTenant(fn () => $product->variants()->orderBy('id')->get());

        // Edit: re-submit existing variants (by id) with changed values, drop two, add one.
        $update = $payload;
        $update['base_price'] = 77.5;
        $update['translations']['en']['name'] = 'Performance Product Edited';
        $update['translations']['en']['description'] = 'Edited description';
        $update['category_ids'] = array_slice($categoryIds, 0, 2);
        $update['badge_ids'] = array_slice($badgeIds, 0, 1);

        $variants = [];
        foreach ($existing->slice(0, self::VARIANTS - 2)->values() as $i => $variant) {
            $variants[] = [
                'id' => $variant->id,
                'pairs' => [['variation_id' => 1, 'option_id' => 100 + $i]],
                'sku' => 'PERF-V-EDIT-' . $i,
                'price' => 30 + $i,
                'stock' => 5 + $i,
                'active' => 1,
            ];
        }
        $variants[] = [
            'id' => null,
            'pairs' => [['variation_id' => 1, 'option_id' => 900]],
            'sku' => 'PERF-V-NEW',
            'price' => 99,
            'stock' => 1,
            'active' => 1,
        ];
        $update['variants'] = $variants;

        [$response, $queries] = $this->measure(fn () => $this->actingAsTenantAdmin()
            ->json('put', $this->tenantUrl("/admin/own-products/{$product->id}"), $update));

        $response->assertSuccessful();
        fwrite(STDERR, "\n[update] queries={$queries['count']} time_ms={$queries['ms']}\n");
        $this->assertLessThanOrEqual(self::MAX_UPDATE_QUERIES, $queries['count'], 'update query budget exceeded');

        $this->inTenant(function () use ($product, $update, $existing) {
            $fresh = Product::query()->findOrFail($product->id);
            $this->assertEquals(77.5, (float) $fresh->default_price);
            $this->assertSame('Performance Product Edited', $fresh->translationValue('name', 'en'));
            $this->assertSame('Edited description', $fresh->translationValue('description', 'en'));
            $this->assertEqualsCanonicalizing($update['category_ids'], $fresh->categories()->pluck('categories.id')->all());
            $this->assertEqualsCanonicalizing($update['badge_ids'], $fresh->badges()->pluck('product_badges.id')->all());

            $rows = $fresh->variants()->orderBy('id')->get();
            $this->assertCount(self::VARIANTS - 1, $rows);
            // Kept variants preserve their ids and receive the edited values.
            foreach ($existing->slice(0, self::VARIANTS - 2)->values() as $i => $old) {
                $row = $rows->firstWhere('id', $old->id);
                $this->assertNotNull($row, "variant {$old->id} must be updated in place");
                $this->assertSame('PERF-V-EDIT-' . $i, $row->sku);
                $this->assertEquals(30 + $i, (float) $row->default_sell_price);
                $this->assertSame(5 + $i, (int) $row->stock);
                $this->assertSame([100 + $i], array_map('intval', (array) $row->option_ids));
            }
            $this->assertNotNull($rows->firstWhere('sku', 'PERF-V-NEW'));
            // Omitted variants are removed.
            foreach ($existing->slice(self::VARIANTS - 2) as $gone) {
                $this->assertNull($rows->firstWhere('id', $gone->id));
            }
        });
    }

    public function test_unknown_category_and_badge_ids_are_still_rejected(): void
    {
        [$categoryIds, $badgeIds] = $this->seedLookups();
        $payload = $this->payload($categoryIds, $badgeIds);
        $payload['category_ids'][] = 999999;
        $payload['badge_ids'][] = 999998;

        $response = $this->actingAsTenantAdmin()->json('post', $this->tenantUrl('/admin/own-products'), $payload);

        $response->assertStatus(422);
        $errors = array_keys($response->json('errors'));
        $this->assertNotEmpty(preg_grep('/^category_ids\./', $errors));
        $this->assertNotEmpty(preg_grep('/^badge_ids\./', $errors));
        $this->assertSame(0, $this->inTenant(fn () => Product::query()->where('sku', 'PERF-001')->count()));
    }

    // ── helpers ───────────────────────────────────────────────────────────

    /** @return array{0: list<int>, 1: list<int>} */
    private function seedLookups(): array
    {
        return $this->inTenant(function () {
            Language::query()->firstOrCreate(['code' => 'ar'], [
                'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl',
                'is_active' => true, 'is_default' => false, 'sort_order' => 2,
            ]);

            $categoryIds = [];
            foreach (range(1, 3) as $_) {
                $categoryIds[] = Category::create(['active' => true])->id;
            }

            $badgeIds = [];
            foreach (['new-in', 'best-selling', 'featured'] as $text) {
                $badgeIds[] = ProductBadge::query()->firstOrCreate(['text' => $text], ['active' => true])->id;
            }

            return [$categoryIds, $badgeIds];
        });
    }

    private function payload(array $categoryIds, array $badgeIds): array
    {
        $variants = [];
        foreach (range(0, self::VARIANTS - 1) as $i) {
            $variants[] = [
                'id' => null,
                'pairs' => [['variation_id' => 1, 'option_id' => 100 + $i]],
                'sku' => 'PERF-V-' . $i,
                'price' => 20 + $i,
                'stock' => 10 + $i,
                'weight_grams' => 100 + $i,
                'active' => 1,
            ];
        }

        return [
            'sku' => 'PERF-001',
            'base_price' => 50,
            'sale_price' => 45,
            'cost_price' => 20,
            'stock' => 0,
            'min_stock' => 2,
            'manage_stock' => 0,
            'active' => 1,
            'featured' => 1,
            'is_taxable' => 1,
            'weight_grams' => 500,
            'category_ids' => $categoryIds,
            'badge_ids' => $badgeIds,
            'translations' => [
                'en' => ['name' => 'Performance Product', 'summary' => 'Sum', 'description' => 'Desc', 'meta_keywords' => 'a,b', 'meta_description' => 'Meta'],
                'ar' => ['name' => 'منتج الأداء', 'summary' => 'ملخص', 'description' => 'وصف'],
            ],
            'variants' => $variants,
        ];
    }

    private function assertPersisted(Product $product, array $categoryIds, array $badgeIds, array $payload): void
    {
        $this->inTenant(function () use ($product, $categoryIds, $badgeIds, $payload) {
            $fresh = Product::query()->findOrFail($product->id);
            $this->assertTrue((bool) $fresh->is_own_product);
            $this->assertEquals(50, (float) $fresh->default_price);
            $this->assertEquals(45, (float) $fresh->sale_price);
            $this->assertEquals(20, (float) $fresh->cost_price);
            $this->assertSame(500, (int) $fresh->weight_grams);
            $this->assertTrue((bool) $fresh->featured);
            $this->assertSame('Performance Product', $fresh->translationValue('name', 'en'));
            $this->assertSame('Meta', $fresh->translationValue('meta_description', 'en'));
            $this->assertSame('منتج الأداء', $fresh->translationValue('name', 'ar'));
            $this->assertEqualsCanonicalizing($categoryIds, $fresh->categories()->pluck('categories.id')->all());
            $this->assertEqualsCanonicalizing($badgeIds, $fresh->badges()->pluck('product_badges.id')->all());

            $rows = $fresh->variants()->orderBy('id')->get();
            $this->assertCount(self::VARIANTS, $rows);
            foreach ($payload['variants'] as $i => $v) {
                $row = $rows->firstWhere('sku', $v['sku']);
                $this->assertNotNull($row, "variant {$v['sku']} missing");
                $this->assertEquals($v['price'], (float) $row->default_sell_price);
                $this->assertSame($v['stock'], (int) $row->stock);
                $this->assertSame($v['weight_grams'], (int) $row->weight_grams);
                $this->assertSame([100 + $i], array_map('intval', (array) $row->option_ids));
            }
        });
    }

    /** @return array{0: mixed, 1: array{count:int, ms:float}} */
    private function measure(callable $fn): array
    {
        $count = 0;
        $log = [];
        DB::listen(function ($q) use (&$count, &$log) {
            $count++;
            $log[] = preg_replace('/\s+/', ' ', substr($q->sql, 0, 140));
        });
        $this->queryLog = &$log;
        $start = microtime(true);
        $result = $fn();

        return [$result, ['count' => $count, 'ms' => round((microtime(true) - $start) * 1000, 1)]];
    }

    public array $queryLog = [];

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
