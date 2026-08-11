<?php

namespace App\Livewire\Tenant\Storefront\Concerns;

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;

trait ChecksCartStock
{
    /**
     * Validate stock availability for a product/variant.
     *
     * @param  Product             $product
     * @param  ProductVariant|null $variant
     * @param  int                 $requestedQty   Qty being added or set as new total
     * @param  int                 $existingCartQty Already in the cart for this line
     * @return string|null  null = OK, string = error message
     */
    protected function checkProductStock(
        Product $product,
        ?ProductVariant $variant,
        int $requestedQty,
        int $existingCartQty = 0
    ): ?string {
        $central = $product->centralProduct
            ?? $product->load('centralProduct')->centralProduct;

        $productName = $product->translationValue('name') ?? $product->slug;

        // Own products (no central) use the tenant product's (or variant's) own stock field.
        // A null stock value means unlimited — skip the check.
        if (!$central) {
            // Fall back to product-level stock when the variant has no per-variant stock set.
            $ownStock = $variant ? ($variant->stock ?? $product->stock ?? null) : $product->stock;
            if ($ownStock === null) {
                return null;
            }
            $stock = (int) $ownStock;
            $available = $stock - $existingCartQty;
            if ($stock <= 0 || $available <= 0) {
                return __('Sorry, ":name" is out of stock.', ['name' => $productName]);
            }
            if ($requestedQty > $available) {
                return __('Sorry, only :count item(s) of ":name" are available to add (stock: :stock, already in cart: :in_cart).', [
                    'count'   => $available,
                    'name'    => $productName,
                    'stock'   => $stock,
                    'in_cart' => $existingCartQty,
                ]);
            }
            return null;
        }

        if (!($central->manage_stock ?? false)) {
            return null;
        }

        if ($variant) {
            $stock = (int) ($variant->stock ?? $variant->centralVariant?->stock ?? 0);
        } else {
            $stock = (int) ($product->stock ?? 0);
        }

        // How many units are still purchasable (stock minus what's already in cart)
        $available = $stock - $existingCartQty;

        if ($stock <= 0 || $available <= 0) {
            return __('Sorry, ":name" is out of stock.', ['name' => $productName]);
        }

        if ($requestedQty > $available) {
            return __('Sorry, only :count item(s) of ":name" are available to add (stock: :stock, already in cart: :in_cart).', [
                'count'   => $available,
                'name'    => $productName,
                'stock'   => $stock,
                'in_cart' => $existingCartQty,
            ]);
        }

        return null;
    }

    /**
     * Resolve the Product and active ProductVariant models from a cart session key
     * and its raw entry array.
     *
     * @return array{0: Product|null, 1: ProductVariant|null}
     */
    protected function resolveCartEntryModels(string $key, array $entry): array
    {
        if (str_starts_with($key, 'v_') && !empty($entry['variant_id'])) {
            $variant = ProductVariant::with([
                'product.centralProduct',
                'product.variants',
                'centralVariant',
            ])->find((int) $entry['variant_id']);

            return [$variant?->product, $variant];
        }

        $product = Product::with(['variants', 'centralProduct'])
            ->find((int) ($entry['product_id'] ?? 0));

        $variant = $product?->variants->where('active', true)->first();

        return [$product, $variant];
    }
}

