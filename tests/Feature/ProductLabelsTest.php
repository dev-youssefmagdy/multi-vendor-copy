<?php

namespace Tests\Feature;

use App\Enums\DeliveryScope;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductLabel;
use App\Support\ProductLabelIcons;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLabelsTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $sku): Product
    {
        return Product::query()->create([
            'sku' => $sku,
            'slug' => strtolower($sku),
            'status' => ProductStatus::Published,
            'delivery_scope' => DeliveryScope::AllZones,
            'base_price' => 10,
            'stock' => 3,
            'min_stock' => 1,
            'manage_stock' => true,
            'is_taxable' => true,
            'requires_shipping' => true,
        ]);
    }

    public function test_label_text_resolves_stock_and_delivery_date_tokens(): void
    {
        Carbon::setTestNow('2026-11-01 10:00:00');

        $label = new ProductLabel(['text' => 'Only {stock} left, delivered by {delivery_date}']);

        $this->assertSame('Only 5 left, delivered by 04 November', $label->resolveText(5));
        $this->assertStringStartsWith('Only +99 left', $label->resolveText(250));
        $this->assertStringStartsWith('Only 0 left', $label->resolveText(-3));
    }

    public function test_labels_can_be_assigned_from_both_sides_and_ordered(): void
    {
        $a = $this->makeProduct('LBL-1');
        $b = $this->makeProduct('LBL-2');
        $fast = ProductLabel::query()->create(['text' => 'Fast delivery', 'icon' => 'truck', 'sort_order' => 2]);
        $hot = ProductLabel::query()->create(['text' => 'Hot', 'icon' => 'fire', 'sort_order' => 1]);

        // Label side (label form).
        $fast->products()->sync([$a->id, $b->id]);
        // Product side (product form).
        $a->labels()->sync([$fast->id, $hot->id]);

        $this->assertSame(['Hot', 'Fast delivery'], $a->labels()->pluck('text')->all());
        $this->assertSame([$a->id], $hot->products()->pluck('products.id')->all());

        $hot->update(['active' => false]);
        $this->assertSame(['Fast delivery'], ProductLabel::query()->active()->ordered()->pluck('text')->all());
    }

    public function test_icon_registry_exposes_markup_for_every_key_and_rejects_unknown(): void
    {
        foreach (array_keys(ProductLabelIcons::all()) as $key) {
            $this->assertNotEmpty(ProductLabelIcons::svg($key), $key);
        }

        $this->assertNull(ProductLabelIcons::svg('nope'));
        $this->assertNull(ProductLabelIcons::svg(null));
    }
}
