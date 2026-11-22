<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Favorite;
use App\Models\Tenant\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /** POST /favorites/toggle — add if not favorited, remove if already favorited. */
    public function toggle(Request $request): JsonResponse
    {
        $customer = auth('storefront')->user();
        if (!$customer) {
            return response()->json(['success' => false, 'message' => __('Login required.')], 401);
        }

        $central = \App\Repositories\Tenant\Catalog\ProductCatalog::enabled();
        $validated = $request->validate(['product_id' => $central ? 'required|integer|min:1' : 'required|integer|exists:products,id']);

        // Dedup plan phase 5: merged products are identified by their key (typed pair on the row).
        $ref = $central ? \App\Repositories\Tenant\Catalog\ProductCatalog::refColumnsForKey((int) $validated['product_id']) : null;
        if ($central && ($ref === null || ! \App\Repositories\Tenant\Catalog\ProductCatalog::query()->whereKey((int) $validated['product_id'])->exists())) {
            return response()->json(['success' => false, 'message' => __('Product not found.')], 404);
        }

        $existing = Favorite::where('customer_id', $customer->id)
            ->when($ref, fn ($q) => $q->where('product_type', $ref['product_type'])->where('catalog_product_id', $ref['catalog_product_id']),
                fn ($q) => $q->where('product_id', $validated['product_id']))
            ->first();

        if ($existing) {
            $existing->delete();
            $favorited = false;
        } else {
            Favorite::create($ref ? ['customer_id' => $customer->id] + $ref : ['customer_id' => $customer->id, 'product_id' => $validated['product_id']]);
            $favorited = true;
        }

        return response()->json([
            'success' => true,
            'favorited' => $favorited,
            'count' => Favorite::where('customer_id', $customer->id)->count(),
        ]);
    }

    /** GET /favorites/list — JSON list for the vendor theme to render. */
    public function list(): JsonResponse
    {
        $customer = auth('storefront')->user();
        if (!$customer) {
            return response()->json(['products' => []]);
        }

        $products = Favorite::with('product.centralProduct')
            ->where('customer_id', $customer->id)
            ->latest()
            ->get()
            ->pluck('product')
            ->filter()
            ->map(function (Product $p) {
                $pricing = $p->storefrontPricing();

                return [
                    'id' => $p->id,
                    'slug' => $p->slug,
                    'name' => $p->translationValue('name') ?? $p->slug,
                    'image' => $p->primary_image_url,
                    'price' => $pricing['current_price'],
                    'url' => route('tenant.storefront.product', $p->slug),
                ];
            })
            ->values();

        return response()->json(['products' => $products]);
    }

    /** GET /favorites/ids — lightweight ID list for marking "favorited" state on listing pages. */
    public function ids(): JsonResponse
    {
        $customer = auth('storefront')->user();
        if (!$customer) {
            return response()->json(['ids' => []]);
        }

        return response()->json([
            'ids' => \App\Repositories\Tenant\Catalog\ProductCatalog::enabled()
                ? Favorite::where('customer_id', $customer->id)->get()->map(fn ($f) => $f->product_key)->filter()->values()
                : Favorite::where('customer_id', $customer->id)->pluck('product_id'),
        ]);
    }
}
