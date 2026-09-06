<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /** POST /reviews — submit a product review (one per customer/product). */
    public function store(Request $request): JsonResponse
    {
        $customer = Auth::guard('storefront')->user();

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        if (!Product::find($validated['product_id'])) {
            return response()->json(['success' => false, 'message' => __('Product not found.')], 404);
        }

        if (ProductRate::where('product_id', $validated['product_id'])->where('customer_id', $customer->id)->exists()) {
            return response()->json(['success' => false, 'message' => __('You have already reviewed this product.')], 422);
        }

        $review = ProductRate::create([
            'product_id' => $validated['product_id'],
            'customer_id' => $customer->id,
            'stars' => $validated['stars'],
            'comment' => trim((string) ($validated['comment'] ?? '')) ?: null,
        ]);

        return response()->json(['success' => true, 'review' => $this->transform($review)], 201);
    }

    /** GET /products/{product}/reviews — paginated review list for a product. */
    public function index(Product $product): JsonResponse
    {
        $reviews = ProductRate::with('customer:id,full_name')
            ->where('product_id', $product->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $reviews->getCollection()->map(fn(ProductRate $r) => $this->transform($r))->values(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
            'summary' => [
                'average' => round((float) ProductRate::where('product_id', $product->id)->avg('stars'), 2),
                'count' => ProductRate::where('product_id', $product->id)->count(),
            ],
        ]);
    }

    private function transform(ProductRate $review): array
    {
        return [
            'id' => $review->id,
            'product_id' => $review->product_id,
            'customer_name' => $review->customer?->full_name,
            'stars' => $review->stars,
            'comment' => $review->comment,
            'created_at' => $review->created_at?->toIso8601String(),
        ];
    }
}
