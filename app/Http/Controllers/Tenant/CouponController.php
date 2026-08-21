<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\Tenant\CouponType;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Coupon;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /** GET /coupon — currently applied coupon (if any) with computed discount. */
    public function show(): JsonResponse
    {
        $code = session('storefront_coupon');
        if (!$code) {
            return response()->json(['coupon' => null]);
        }

        $coupon = Coupon::where('code', $code)->first();
        if (!$coupon) {
            session()->forget('storefront_coupon');
            return response()->json(['coupon' => null]);
        }

        return response()->json(['coupon' => $this->transform($coupon)]);
    }

    /** POST /coupon/apply */
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate(['code' => 'required|string|max:50']);
        $code = trim($validated['code']);

        $coupon = Coupon::query()
            ->where('code', $code)
            ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->first();

        if (!$coupon) {
            return response()->json(['success' => false, 'message' => __('This coupon code is invalid or has expired.')], 422);
        }

        $cartTotal = app(StorefrontRepository::class)->cartTotal();

        if ($coupon->minimum_spend !== null && $cartTotal < (float) $coupon->minimum_spend) {
            $minimumSpend = number_format((float) $coupon->minimum_spend, 2);
            return response()->json([
                'success' => false,
                'message' => __('This coupon requires a minimum spend of :amount.', ['amount' => $minimumSpend]),
            ], 422);
        }

        session(['storefront_coupon' => $coupon->code]);

        return response()->json(['success' => true, 'coupon' => $this->transform($coupon)]);
    }

    /** POST /coupon/remove */
    public function remove(): JsonResponse
    {
        session()->forget('storefront_coupon');

        return response()->json(['success' => true]);
    }

    private function transform(Coupon $coupon): array
    {
        $cartTotal = app(StorefrontRepository::class)->cartTotal();

        $discount = match ($coupon->type) {
            CouponType::Percentage => $cartTotal * (float) $coupon->value / 100,
            CouponType::Fixed => min((float) $coupon->value, $cartTotal),
        };

        return [
            'code' => $coupon->code,
            'type' => $coupon->type->value,
            'value' => (float) $coupon->value,
            'minimum_spend' => $coupon->minimum_spend !== null ? (float) $coupon->minimum_spend : null,
            'discount_amount' => round($discount, 2),
        ];
    }
}
