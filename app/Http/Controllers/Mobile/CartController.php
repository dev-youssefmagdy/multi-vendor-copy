<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\CartController as WebCartController;
use App\Models\Tenant\Product;
use App\Services\Mobile\MobileCartPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Server-side cart for the authenticated customer. Mutations delegate to the /api/v1 CartController
 * (same stock checks); AuthenticateMobileToken persists the session-shaped cart to mobile_carts.
 */
class CartController extends Controller
{
    public function __construct(private readonly MobileCartPresenter $presenter, private readonly WebCartController $web)
    {
    }

    public function show(Request $request): JsonResponse
    {
        return $this->ok($request);
    }

    public function add(Request $request): JsonResponse
    {
        return $this->mutate($request, fn () => $this->web->add($request));
    }

    public function update(Request $request): JsonResponse
    {
        return $this->mutate($request, fn () => $this->web->update($request));
    }

    public function remove(Request $request): JsonResponse
    {
        return $this->mutate($request, fn () => $this->web->remove($request));
    }

    public function clear(Request $request): JsonResponse
    {
        session(['storefront_cart' => []]);

        return $this->ok($request);
    }

    /** POST /cart/merge {items:[{product_id|slug, variant_id?, qty}]} merges a guest cart kept on the device. */
    public function merge(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|max:100',
            'items.*.product_id' => 'nullable|integer',
            'items.*.slug' => 'nullable|string',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.qty' => 'nullable|integer|min:1|max:99',
        ]);

        $skipped = [];
        foreach ($request->input('items') as $i => $row) {
            // Only identifiers and qty are read; any client-sent price field is ignored.
            $slug = $row['slug'] ?? (isset($row['product_id']) ? Product::query()->whereKey((int) $row['product_id'])->value('slug') : null);
            $sub = Request::create('/', 'POST', ['slug' => $slug, 'variant_id' => $row['variant_id'] ?? null, 'qty' => $row['qty'] ?? 1]);
            $res = $this->web->add($sub);
            if ($res->getStatusCode() >= 400) {
                $skipped[] = ['index' => $i, 'reason' => $res->getData(true)['message'] ?? 'rejected'];
            }
        }

        return $this->ok($request, ['skipped' => $skipped]);
    }

    private function mutate(Request $request, \Closure $call): JsonResponse
    {
        $res = $call();
        if ($res->getStatusCode() >= 400) {
            return $res;
        }

        return $this->ok($request);
    }

    private function ok(Request $request, array $extra = []): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->presenter->build($request->user()) + $extra]);
    }
}
