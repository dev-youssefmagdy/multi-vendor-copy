<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Livewire\Tenant\Storefront\Concerns\CalculatesFreeShipping;
use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ChecksCartStock;
    use CalculatesFreeShipping;

    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'nullable|string',
            'product_id' => 'nullable|integer',
            'variant_id' => 'nullable|integer',
            'qty' => 'nullable|integer|min:1',
        ]);

        $repo = app(StorefrontRepository::class);
        $qty = max(1, (int) ($validated['qty'] ?? 1));

        if (!empty($validated['product_id']) && empty($validated['slug'])) {
            // Product-card "quick add" — always qty 1, first active variant.
            $product = Product::with(['variants', 'centralProduct'])
                ->where('active', true)
                ->find($validated['product_id']);

            if (!$product) {
                return response()->json(['success' => false, 'message' => __('Product not found.')], 404);
            }

            $variantId = $product->variants->where('active', true)->first()?->id;
            $qty = 1;
        } else {
            if (empty($validated['slug'])) {
                return response()->json(['success' => false, 'message' => __('Product not found.')], 422);
            }

            $product = $repo->productBySlug($validated['slug']);
            if (!$product) {
                return response()->json(['success' => false, 'message' => __('Product not found.')], 404);
            }

            $variantId = $validated['variant_id'] ?? $product->variants->where('active', true)->first()?->id;
        }

        $itemName = $product->translationValue('name') ?? $product->slug;
        $variant = $variantId ? $product->variants->firstWhere('id', $variantId) : null;

        $cart = $repo->cart();
        $key = $variantId ? 'v_' . $variantId : 'p_' . $product->id;
        $existingQty = (int) ($cart[$key]['qty'] ?? 0);

        $stockError = $this->checkProductStock($product, $variant, $qty, $existingQty);
        if ($stockError !== null) {
            return response()->json(['success' => false, 'message' => $stockError], 422);
        }

        if (isset($cart[$key])) {
            $cart[$key]['qty'] += $qty;
        } else {
            $cart[$key] = [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'qty' => $qty,
            ];
        }

        session(['storefront_cart' => $cart]);

        $shippingThreshold = $this->detectFreeShippingThreshold();
        $cartWeight = $this->cartWeightGrams($repo->cartItems());
        $shippingPct = $shippingThreshold > 0
            ? min(100, (int) round($cartWeight / $shippingThreshold * 100))
            : 0;
        $remainingForFreeShipping = max(0, $shippingThreshold - $cartWeight);

        return response()->json([
            'success' => true,
            'itemName' => $itemName,
            'qty' => $qty,
            'cartCount' => $repo->cartCount(),
            'shippingThreshold' => $shippingThreshold,
            'cartWeightGrams' => $cartWeight,
            'shippingPct' => $shippingPct,
            'remainingForFreeShipping' => $remainingForFreeShipping,
        ]);
    }

    /** POST /cart/remove — removes a line item entirely. */
    public function remove(Request $request): JsonResponse
    {
        $validated = $request->validate(['key' => 'required|string']);

        $cart = session('storefront_cart', []);
        unset($cart[$validated['key']]);
        session(['storefront_cart' => $cart]);

        return response()->json([
            'success' => true,
            'cart_count' => collect($cart)->sum('qty'),
        ]);
    }

    /** POST /cart/update — changes the quantity of an existing line item. */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'qty' => 'required|integer|min:1|max:99',
        ]);

        $cart = session('storefront_cart', []);
        if (!isset($cart[$validated['key']])) {
            return response()->json(['success' => false, 'message' => __('Item not in cart.')], 404);
        }

        $cart[$validated['key']]['qty'] = $validated['qty'];
        session(['storefront_cart' => $cart]);

        return response()->json([
            'success' => true,
            'cart_count' => collect($cart)->sum('qty'),
        ]);
    }
}
