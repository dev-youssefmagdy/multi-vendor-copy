<?php

namespace Tests\Unit;

use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StorefrontProductPricingTest extends TestCase
{
    public function test_it_uses_variant_discount_when_no_flash_sale_is_active(): void
    {
        Carbon::setTestNow('2026-04-08 12:00:00');

        $product = new Product(['price' => 100]);
        $variant = new ProductVariant(['sell_price' => 80, 'real_price' => 120, 'active' => true]);

        $product->setRelation('variants', new Collection([$variant]));
        $product->setRelation('flashSales', new Collection());

        $pricing = $product->storefrontPricing($variant);

        $this->assertSame(80.0, $pricing['current_price']);
        $this->assertSame(120.0, $pricing['original_price']);
        $this->assertTrue($pricing['has_discount']);
        $this->assertSame(33.33, $pricing['discount_percentage']);
        $this->assertFalse($pricing['is_flash_sale']);
    }

    public function test_it_applies_active_flash_sale_on_top_of_the_storefront_price(): void
    {
        Carbon::setTestNow('2026-04-08 12:00:00');

        $product = new Product(['price' => 100]);
        $variant = new ProductVariant(['sell_price' => 80, 'real_price' => 120, 'active' => true]);
        $flashSale = new FlashSale([
            'discount_percentage' => 25,
            'active' => true,
            'start_date' => Carbon::now()->subHour(),
            'end_date' => Carbon::now()->addHour(),
        ]);

        $product->setRelation('variants', new Collection([$variant]));
        $product->setRelation('flashSales', new Collection([$flashSale]));

        $pricing = $product->storefrontPricing($variant);

        $this->assertSame(60.0, $pricing['current_price']);
        $this->assertSame(120.0, $pricing['original_price']);
        $this->assertTrue($pricing['has_discount']);
        $this->assertSame(50.0, $pricing['discount_percentage']);
        $this->assertTrue($pricing['is_flash_sale']);
        $this->assertSame(25.0, $pricing['flash_sale_percentage']);
    }

    public function test_it_ignores_inactive_or_expired_flash_sales(): void
    {
        Carbon::setTestNow('2026-04-08 12:00:00');

        $product = new Product(['price' => 100]);
        $variant = new ProductVariant(['sell_price' => 90, 'active' => true]);

        $inactiveFlashSale = new FlashSale([
            'discount_percentage' => 50,
            'active' => false,
            'start_date' => Carbon::now()->subHour(),
            'end_date' => Carbon::now()->addHour(),
        ]);

        $expiredFlashSale = new FlashSale([
            'discount_percentage' => 40,
            'active' => true,
            'start_date' => Carbon::now()->subDays(2),
            'end_date' => Carbon::now()->subDay(),
        ]);

        $product->setRelation('variants', new Collection([$variant]));
        $product->setRelation('flashSales', new Collection([$inactiveFlashSale, $expiredFlashSale]));

        $pricing = $product->storefrontPricing($variant);

        $this->assertSame(90.0, $pricing['current_price']);
        $this->assertNull($pricing['original_price']);
        $this->assertFalse($pricing['has_discount']);
        $this->assertSame(0.0, $pricing['discount_percentage']);
        $this->assertFalse($pricing['is_flash_sale']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
