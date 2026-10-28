<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Enums\ReturnMethod;
use App\Enums\ReturnReason;
use App\Enums\ReturnType;
use App\Exceptions\ReturnActionException;
use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestNote;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\ReturnRequestService;
use App\Services\ReturnRequestValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Returns / exchanges: same validation + service path as the storefront RequestReturnForm. */
class ReturnController extends Controller
{
    public function options(): JsonResponse
    {
        $vals = fn (array $cases) => array_map(fn ($c) => $c->value, $cases);

        return response()->json(['success' => true, 'data' => [
            'reasons' => $vals(ReturnReason::cases()),
            'methods' => $vals(ReturnMethod::cases()),
            'types' => $vals(ReturnType::cases()),
            'limits' => ['photo_max_kb' => config('returns.photo_max_kb'), 'video_max_kb' => config('returns.video_max_kb')],
        ]]);
    }

    public function index(Request $request): JsonResponse
    {
        $rows = ReturnRequest::query()
            ->where('tenant_id', tenant('id'))->where('customer_id', $request->user()->id)
            ->latest('id')->get()->map(fn (ReturnRequest $r) => $this->summary($r))->values();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $r = ReturnRequest::query()->with('notes')
            ->where('tenant_id', tenant('id'))->where('customer_id', $request->user()->id)->find($id);
        if (!$r) {
            return response()->json(['success' => false, 'message' => 'Return not found.', 'code' => 'not_found'], 404);
        }

        $notes = $r->notes->where('customer_visible', true)->sortBy('id')->map(fn ($n) => [
            'author_type' => $n->author_type, 'note' => $n->note, 'created_at' => $n->created_at?->toIso8601String(),
        ])->values();

        return response()->json(['success' => true, 'data' => $this->summary($r) + [
            'description' => $r->description,
            'customer_note' => $r->customer_note,
            'return_method' => $r->return_method?->value ?? $r->return_method,
            'notes' => $notes,
            'actions' => app(ReturnRequestService::class)->availableActions($r, 'customer'),
        ]]);
    }

    /** POST /orders/{uuid}/return (multipart for photos[] / video) */
    public function store(Request $request, string $uuid): JsonResponse
    {
        $customer = $request->user();
        $request->validate([
            'order_item_id' => 'required|integer',
            'reason' => ['required', Rule::in(array_column(ReturnReason::cases(), 'value'))],
            'quantity' => 'required|integer|min:1',
            'type' => ['required', Rule::in(array_column(ReturnType::cases(), 'value'))],
            'return_method' => ['required', Rule::in(array_column(ReturnMethod::cases(), 'value'))],
            'description' => 'nullable|string|max:2000',
            'customer_note' => 'nullable|string|max:1000',
            'replacement_variant_id' => 'nullable|integer|required_if:type,'.ReturnType::Exchange->value,
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:'.config('returns.photo_max_kb'),
            'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/webm|max:'.config('returns.video_max_kb'),
        ]);

        $order = app(StorefrontRepository::class)->orderByUuid($uuid);
        if (!$order || $order->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Order not found.', 'code' => 'not_found'], 404);
        }
        $item = $order->items->firstWhere('id', (int) $request->input('order_item_id'));
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Order item not found.', 'code' => 'not_found'], 404);
        }

        $data = [
            'customer_id' => $customer->id,
            'order_item_id' => $item->id,
            'quantity' => (int) $request->input('quantity'),
            'type' => $request->input('type'),
            'return_method' => $request->input('return_method'),
            'reason' => $request->input('reason'),
            'description' => $request->input('description') ?: null,
            'customer_note' => $request->input('customer_note') ?: null,
            'replacement_product_variant_id' => $request->input('type') === ReturnType::Exchange->value ? $request->input('replacement_variant_id') : null,
        ];
        $photos = $request->file('photos', []);
        $video = $request->file('video');

        $errors = app(ReturnRequestValidationService::class)->validate($data + ['photos' => $photos, 'video' => $video], tenant('id'), $order->uuid, $item->product_id);
        if (!empty($errors)) {
            return response()->json(['success' => false, 'message' => 'Return request is not valid.', 'code' => 'return_invalid', 'errors' => $errors], 422);
        }

        try {
            $return = app(ReturnRequestService::class)->create(
                $data + ['tenant_id' => tenant('id'), 'order_number' => $order->uuid],
                $photos ?: [],
                $video ? [$video] : [],
            );
        } catch (ReturnActionException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'return_rejected', 'errors' => $e->errors ?: null], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'return_rejected'], 422);
        }

        return response()->json(['success' => true, 'data' => $this->summary($return)], 201);
    }

    private function summary(ReturnRequest $r): array
    {
        return [
            'id' => $r->id,
            'order_number' => $r->order_number,
            'type' => $r->type?->value ?? $r->type,
            'status' => $r->status?->value ?? $r->status,
            'status_label' => method_exists($r->status, 'label') ? $r->status->label() : null,
            'quantity' => $r->quantity,
            'reason' => $r->reason?->value ?? $r->reason,
            'refund_amount' => $r->refund_amount,
            'created_at' => $r->created_at?->toIso8601String(),
        ];
    }
}
