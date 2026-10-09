<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant\Concerns;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Product as CentralProduct;
use App\Models\ProductVariant as CentralVariant;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Builds tenant customers, products (own + central-catalog) and orders for feature tests.
 *
 * Every builder must run INSIDE the tenant context, e.g.
 *
 *     $this->tenant->run(function () {
 *         $customer = $this->createCustomer();
 *         $variant  = $this->createOwnProductVariant(stock: 10);
 *         $order    = $this->createOrder($customer, [[$variant, 2]], OrderStatus::Processing, paid: true);
 *     });
 *
 * Use together with SetsUpTenantPanel (which provides $this->tenant).
 */
trait BuildsOrders
{
    protected function createCustomer(array $attributes = []): Customer
    {
        return Customer::create(array_merge([
            'full_name' => 'Test Customer',
            'email' => 'customer-'.Str::lower(Str::random(8)).'@example.com',
            'phone' => '+15550001111',
            'password' => Hash::make('password12345'),
            'active' => true,
        ], $attributes));
    }

    /**
     * An own (tenant-owned) product with one active variant.
     *
     * @param  int|null  $stock  variant stock (null = unlimited)
     * @param  int|null  $productStock  product-level aggregate stock (defaults to $stock)
     */
    protected function createOwnProductVariant(?int $stock = 10, ?int $productStock = null, float $price = 100.0, array $productAttributes = [], array $variantAttributes = []): ProductVariant
    {
        $product = Product::create(array_merge([
            'slug' => 'own-product-'.Str::lower(Str::random(8)),
            'sku' => 'OWN-'.Str::upper(Str::random(6)),
            'price' => ['default' => $price],
            'active' => true,
            'is_own_product' => true,
            'is_tenant_owned' => true,
            'requires_shipping' => true,
            'stock' => $productStock ?? $stock,
            'manage_stock' => true,
        ], $productAttributes));

        return $this->createVariantFor($product, $stock, $price, $variantAttributes);
    }

    /** Another variant (e.g. a different size) of an existing product. */
    protected function createVariantFor(Product $product, ?int $stock = 10, float $price = 100.0, array $attributes = []): ProductVariant
    {
        $variant = ProductVariant::create(array_merge([
            'product_id' => $product->id,
            'sku' => 'VAR-'.Str::upper(Str::random(6)),
            'real_price' => $price,
            'sell_price' => ['default' => $price],
            'default_sell_price' => $price,
            'stock' => $stock,
            'active' => true,
        ], $attributes));

        return $variant->setRelation('product', $product);
    }

    /**
     * A central-catalog product + variant, imported into the tenant (tenant product/variant
     * linked through central_product_id / central_product_variant_id). Central rows are
     * created without model events so the catalog-sync observers don't fan out to tenants.
     *
     * @return array{central_product: CentralProduct, central_variant: CentralVariant, product: Product, variant: ProductVariant}
     */
    protected function createCentralCatalogVariant(int $stock = 20, bool $manageStock = true, int $soldCount = 0, float $price = 50.0): array
    {
        [$centralProduct, $centralVariant] = CentralProduct::withoutEvents(function () use ($stock, $manageStock, $soldCount, $price) {
            $centralProduct = CentralProduct::create([
                'slug' => 'central-product-'.Str::lower(Str::random(8)),
                'sku' => 'CEN-'.Str::upper(Str::random(6)),
                'status' => ProductStatus::Published,
                'base_price' => $price,
                'stock' => $stock,
                'manage_stock' => $manageStock,
                'sold_count' => $soldCount,
            ]);

            $centralVariant = CentralVariant::withoutEvents(fn () => CentralVariant::create([
                'product_id' => $centralProduct->id,
                'sku' => 'CENV-'.Str::upper(Str::random(6)),
                'price' => $price,
                'stock' => $stock,
            ]));

            return [$centralProduct, $centralVariant];
        });

        $product = Product::create([
            'central_product_id' => $centralProduct->id,
            'slug' => $centralProduct->slug,
            'sku' => $centralProduct->sku,
            'price' => ['default' => $price],
            'active' => true,
            'stock' => $stock,
        ]);

        $variant = $this->createVariantFor($product, $stock, $price, [
            'central_product_variant_id' => $centralVariant->id,
        ]);

        return [
            'central_product' => $centralProduct,
            'central_variant' => $centralVariant,
            'product' => $product,
            'variant' => $variant,
        ];
    }

    /**
     * Create an order with items.
     *
     * @param  array<int, array{0: ProductVariant|Product, 1?: int, 2?: float}|array{variant?: ProductVariant, product?: Product, qty?: int, price?: float}>  $lines
     *                                                                                                                                                                Each line: [$variantOrProduct, $qty = 1, $unitPrice = variant/product price]
     *                                                                                                                                                                or ['variant' => ..., 'product' => ..., 'qty' => ..., 'price' => ...].
     * @param  bool  $stockDeducted  stamp stock_deducted_at (as if StockService already ran) — does NOT move stock
     */
    protected function createOrder(
        Customer $customer,
        array $lines,
        OrderStatus $status = OrderStatus::Pending,
        bool $paid = false,
        array $attributes = [],
        bool $stockDeducted = false,
    ): Order {
        $order = Order::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'shipping_address' => ['name' => $customer->full_name, 'address' => '1 Test St', 'city' => 'Test City'],
            'payment_method' => $paid ? 'stripe' : 'cod',
            'status' => $status,
            'paid' => $paid,
            'payment_details' => $paid ? ['transaction_id' => 'txn_'.Str::random(12), 'gateway' => 'stripe'] : null,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
            'shipping_charge' => 0,
            'stock_deducted_at' => $stockDeducted ? now() : null,
        ], $attributes));

        foreach ($lines as $line) {
            $this->createOrderItem($order, $line);
        }

        return $order->load('items.variant', 'items.product');
    }

    /** @param array{0: ProductVariant|Product, 1?: int, 2?: float}|array{variant?: ProductVariant, product?: Product, qty?: int, price?: float} $line */
    protected function createOrderItem(Order $order, array $line): OrderItem
    {
        if (array_is_list($line)) {
            $target = $line[0];
            $line = [
                'variant' => $target instanceof ProductVariant ? $target : null,
                'product' => $target instanceof Product ? $target : null,
                'qty' => $line[1] ?? 1,
                'price' => $line[2] ?? null,
            ];
        }

        /** @var ProductVariant|null $variant */
        $variant = $line['variant'] ?? null;
        /** @var Product|null $product */
        $product = $line['product'] ?? null;
        $qty = (int) ($line['qty'] ?? 1);
        $price = (float) ($line['price'] ?? ($variant
            ? (float) $variant->default_sell_price
            : (float) (($product?->price['default'] ?? 0))));

        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $variant?->product_id ?? $product?->id,
            'product_variant_id' => $variant?->id,
            'qty' => $qty,
            'price' => $price,
            'sub_total' => round($price * $qty, 2),
            'discount' => 0,
            'tax' => 0,
            'weight' => 0,
            'shipping_fee' => 0,
        ]);
    }
}
