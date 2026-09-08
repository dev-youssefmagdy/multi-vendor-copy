<?php

namespace Tests\Feature;

use App\Enums\CategoryStatus;
use App\Enums\DeliveryScope;
use App\Enums\ProductStatus;
use App\Enums\ShippingZoneStatus;
use App\Enums\VariationStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\Variation;
use App\Models\VariationOption;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_service_creates_translated_product_with_media_and_delivery_zones(): void
    {
        Storage::fake('public');

        $category = Category::query()->create([
            'slug' => 'electronics',
            'status' => CategoryStatus::Published,
        ]);
        $category->syncTranslations([
            'en' => ['name' => 'Electronics'],
        ]);

        $variation = Variation::query()->create([
            'slug' => 'color',
            'status' => VariationStatus::Active,
        ]);
        $variation->syncTranslations([
            'en' => ['name' => 'Color'],
        ]);

        $zone = ShippingZone::query()->create([
            'name' => 'Cairo',
            'code' => 'cairo',
            'status' => ShippingZoneStatus::Active,
        ]);

        $product = app(ProductService::class)->save([
            'sku' => 'SKU-1001',
            'slug' => 'wireless-headphones',
            'status' => ProductStatus::Published->value,
            'delivery_scope' => DeliveryScope::SelectedZones->value,
            'base_price' => 199.99,
            'sale_price' => 149.99,
            'cost_price' => 80,
            'stock' => 18,
            'min_stock' => 3,
            'manage_stock' => true,
            'is_taxable' => true,
            'sort_order' => 1,
            'category_ids' => [$category->id],
            'variation_ids' => [$variation->id],
            'shipping_zone_ids' => [$zone->id],
            'translations' => [
                'en' => [
                    'name' => 'Wireless Headphones',
                    'summary' => 'Comfort fit',
                    'description' => 'Over-ear headphones with noise reduction.',
                ],
                'ar' => [
                    'name' => 'سماعات لاسلكية',
                    'summary' => 'مريحة',
                    'description' => 'سماعات فوق الأذن مع تقليل الضوضاء.',
                ],
            ],
            'primary_image' => UploadedFile::fake()->image('product.jpg', 1200, 900),
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sku' => 'SKU-1001',
            'status' => ProductStatus::Published->value,
            'delivery_scope' => DeliveryScope::SelectedZones->value,
        ]);
        $this->assertDatabaseCount('translations', 8);
        $this->assertCount(1, $product->categories);
        $this->assertCount(1, $product->variations);
        $this->assertCount(1, $product->shippingZones);
        $this->assertCount(4, $product->files);

        foreach ($product->files as $file) {
            $this->assertTrue(Storage::disk('public')->exists($file->path));
        }
    }

    public function test_products_admin_page_renders_created_product(): void
    {
        $product = Product::query()->create([
            'sku' => 'SKU-2002',
            'slug' => 'standing-desk',
            'status' => ProductStatus::Published,
            'delivery_scope' => DeliveryScope::AllZones,
            'base_price' => 499.99,
            'stock' => 8,
            'min_stock' => 1,
            'manage_stock' => true,
            'is_taxable' => true,
            'requires_shipping' => true,
        ]);
        $product->syncTranslations([
            'en' => [
                'name' => 'Standing Desk',
            ],
        ]);

        $response = $this->get('http://localhost/admin/products');

        $response
            ->assertOk()
            ->assertSee('Products List')
            ->assertSee('Standing Desk')
            ->assertSee('SKU-2002');
    }

    public function test_product_service_generates_variants_from_selected_variation_options(): void
    {
        $color = Variation::query()->create([
            'slug' => 'color',
            'status' => VariationStatus::Active,
        ]);
        $color->syncTranslations([
            'en' => ['name' => 'Color'],
        ]);

        $red = VariationOption::query()->create([
            'variation_id' => $color->id,
            'slug' => 'red',
            'sort_order' => 0,
        ]);
        $red->syncTranslations([
            'en' => ['name' => 'Red'],
        ]);

        $blue = VariationOption::query()->create([
            'variation_id' => $color->id,
            'slug' => 'blue',
            'sort_order' => 1,
        ]);
        $blue->syncTranslations([
            'en' => ['name' => 'Blue'],
        ]);

        $size = Variation::query()->create([
            'slug' => 'size',
            'status' => VariationStatus::Active,
        ]);
        $size->syncTranslations([
            'en' => ['name' => 'Size'],
        ]);

        $small = VariationOption::query()->create([
            'variation_id' => $size->id,
            'slug' => 'small',
            'sort_order' => 0,
        ]);
        $small->syncTranslations([
            'en' => ['name' => 'Small'],
        ]);

        $medium = VariationOption::query()->create([
            'variation_id' => $size->id,
            'slug' => 'medium',
            'sort_order' => 1,
        ]);
        $medium->syncTranslations([
            'en' => ['name' => 'Medium'],
        ]);

        $product = app(ProductService::class)->save([
            'sku' => 'SKU-3003',
            'slug' => 'variant-product',
            'status' => ProductStatus::Published->value,
            'delivery_scope' => DeliveryScope::AllZones->value,
            'base_price' => 100,
            'stock' => 12,
            'min_stock' => 1,
            'manage_stock' => true,
            'is_taxable' => true,
            'translations' => [
                'en' => [
                    'name' => 'Variant Product',
                ],
            ],
            'variation_groups' => [
                [
                    'variation_id' => $color->id,
                    'option_ids' => [$red->id, $blue->id],
                ],
                [
                    'variation_id' => $size->id,
                    'option_ids' => [$small->id, $medium->id],
                ],
            ],
        ]);

        $product->load('variations', 'variants.options');

        $this->assertCount(2, $product->variations);
        $this->assertCount(4, $product->variants);

        $signatures = $product->variants
            ->map(fn($variant) => $variant->options->pluck('slug')->sort()->values()->implode(':'))
            ->sort()
            ->values()
            ->all();

        $this->assertSame([
            'blue:medium',
            'blue:small',
            'medium:red',
            'red:small',
        ], $signatures);
    }
}
