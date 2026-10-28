<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderExtrasController extends Controller
{
    /** GET /orders/{uuid}/tracking : status + activity timeline of the customer's own order. */
    public function tracking(Request $request, string $uuid): JsonResponse
    {
        $order = Order::query()->with('activities')->where('uuid', $uuid)->where('customer_id', $request->user()->id)->first();
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.', 'code' => 'not_found'], 404);
        }

        return response()->json(['success' => true, 'data' => [
            'uuid' => $order->uuid,
            'status' => $order->status?->value,
            'paid' => (bool) $order->paid,
            'tracking_number' => $order->tracking_number ?? null,
            'timeline' => $order->activities->sortBy('id')->map(fn ($a) => [
                'status' => $a->status instanceof \BackedEnum ? $a->status->value : $a->status,
                'title' => $a->title,
                'description' => $a->description,
                'at' => $a->created_at?->toIso8601String(),
            ])->values(),
        ]]);
    }
}
