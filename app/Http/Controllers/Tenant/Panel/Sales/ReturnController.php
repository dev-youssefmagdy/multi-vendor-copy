<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Sales;

use App\Enums\CancellationActor;
use App\Enums\InspectionResult;
use App\Enums\RefundStatus;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Sales\AddReturnNoteRequest;
use App\Http\Requests\Tenant\Panel\Sales\ApproveReturnRequest;
use App\Http\Requests\Tenant\Panel\Sales\InspectReturnRequest;
use App\Http\Requests\Tenant\Panel\Sales\IssueReturnRefundRequest;
use App\Http\Requests\Tenant\Panel\Sales\MarkExchangeShippedRequest;
use App\Http\Requests\Tenant\Panel\Sales\MarkReturnRefundedRequest;
use App\Http\Requests\Tenant\Panel\Sales\RejectReturnRequest;
use App\Http\Requests\Tenant\Panel\Sales\RequestReturnInfoRequest;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestNote;
use App\Models\Tenant\AdminUser;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product as TenantProduct;
use App\Models\Tenant\ProductVariant as TenantVariant;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Orders\OrderPolicyService;
use App\Services\Refunds\RefundActor;
use App\Services\Refunds\RefundService;
use App\Services\ReturnRequestService;
use App\Services\Tenant\StockService;
use App\Support\Tenant\Refunds\RefundPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

final class ReturnController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly ReturnRequestService $service,
    ) {}

    public function show(int $id): View
    {
        $record = ReturnRequest::with(['media', 'notes'])->findOrFail($id);

        if ($record->tenant_id !== tenant()->id) {
            abort(404);
        }

        $order = Order::query()->where('uuid', $record->order_number)->first();
        $orderId = 0;
        $orderData = [];

        if ($order) {
            $orderId = $order->id;
            $orderData = $this->repo->orderDetail($order);
        }

        return view('tenant.pages.sales.returns.show', [
            'returnRecord' => $this->hydrate($record, $order),
            'order' => $orderData,
            'orderId' => $orderId,
            'inspectionResults' => collect(InspectionResult::cases())->mapWithKeys(fn (InspectionResult $result) => [$result->value => $result->label()])->all(),
            'restockDefault' => app(OrderPolicyService::class)->restockReturnedItems(),
        ]);
    }

    public function validateApprove(ApproveReturnRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function approve(ApproveReturnRequest $request, int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->approve($record, $this->actor(), null, $request->validated('approve_note'));

        return $this->success(__('Return request approved.'), ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function validateReject(RejectReturnRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function reject(RejectReturnRequest $request, int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->reject($record, $request->validated()['reject_reason'], $this->actor());

        return $this->success(__('Return request rejected.'), ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function validateRequestInfo(RequestReturnInfoRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function requestMoreInfo(RequestReturnInfoRequest $request, int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->requestMoreInfo($record, $request->validated()['info_message'], $this->actor());

        return $this->success(__('Requested more information from the customer.'), ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function markItemReceived(int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->markItemReceived($record, $this->actor());

        return $this->success(__('Item marked as received.'), ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function validateInspect(InspectReturnRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    /** Record the inspection; passed / partial + restock puts the units back into stock once. */
    public function inspect(InspectReturnRequest $request, int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->inspect(
            $record,
            $request->result(),
            $request->validated('inspection_notes'),
            $request->restock(),
            $this->actor(),
        );

        return $this->success(__('Inspection recorded.'), ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function validateIssueRefund(IssueReturnRefundRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    /**
     * Refund an inspected return (amount ≤ the calculated maximum, enforced again by RefundService).
     * The refund goes to the original payment method right away when possible; otherwise it stays
     * pending / failed and is managed from the refunds panel.
     */
    public function issueRefund(IssueReturnRefundRequest $request, int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $refund = $this->service->issueRefund($record, (float) $request->validated('amount'), $this->actor());

        return $this->success($this->refundMessage($refund), [
            'refund' => RefundPresenter::panel($refund),
            'returnRecord' => $this->hydrate($record->fresh(['media', 'notes'])),
        ]);
    }

    public function validateExchangeShipped(MarkExchangeShippedRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function markExchangeShipped(MarkExchangeShippedRequest $request, int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->markExchangeShipped($record, (string) $request->validated('tracking_number'), $this->actor());

        return $this->success(__('Replacement marked as shipped.'), ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function markExchangeCompleted(int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->markExchangeCompleted($record, $this->actor());

        return $this->success(__('Exchange completed.'), ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function convertToRefund(int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->convertToRefund($record, $this->actor());

        return $this->success(__('Exchange converted to a refund.'), ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function close(int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->close($record, $this->actor());

        return $this->success(__('Return request closed.'), ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function validateRefunded(MarkReturnRefundedRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    /** @deprecated Legacy one-click refund kept for old clients; the panel UI uses issueRefund(). */
    public function markRefunded(MarkReturnRefundedRequest $request, int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        // Legacy one-click refund: walks received → inspected → refund (ReturnActionException /
        // RefundException render themselves as a 422 with a user-safe message).
        $refund = $this->service->markRefunded($record, (float) $request->validated()['refund_amount'], $this->actor());

        return $this->success($refund->isCompleted() ? 'Return refunded.' : 'Refund issued: '.$refund->status->label().'.', ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function validateNote(AddReturnNoteRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function addNote(AddReturnNoteRequest $request, int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $admin = Auth::guard('tenant')->user();

        $this->service->addNote($record, $request->validated()['note_text'], ReturnRequestNote::AUTHOR_TENANT, $admin?->id, false);

        return $this->success('Note added.', ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    private function actor(): RefundActor
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();

        return RefundActor::vendor($admin?->id, $admin?->name);
    }

    private function findGuarded(int $id): ReturnRequest
    {
        $record = ReturnRequest::findOrFail($id);
        $this->guardTenant($record);

        return $record;
    }

    private function guardTenant(ReturnRequest $record): void
    {
        if ($record->tenant_id !== tenant()->id) {
            abort(403);
        }
    }

    private function refundMessage(Refund $refund): string
    {
        return match ($refund->status) {
            RefundStatus::Completed => __('Refund completed.'),
            RefundStatus::Failed => __('The refund was created but the payment gateway could not process it. Retry it or complete it manually.'),
            default => __('Refund created. Mark it as completed once the money has been returned to the customer.'),
        };
    }

    private function hydrate(ReturnRequest $record, ?Order $order = null): array
    {
        $actions = $this->service->availableActions($record, CancellationActor::Vendor);
        $order ??= Order::query()->where('uuid', $record->order_number)->first();
        $item = $record->order_item_id ? OrderItem::query()->find((int) $record->order_item_id) : null;

        return [
            'id' => $record->id,
            'order_number' => $record->order_number,
            'status' => $record->status,
            'status_label' => $record->status->label(),
            'status_color' => $record->status->color(),
            'type' => $record->type?->value,
            'type_label' => $record->type?->label(),
            'is_exchange' => $record->isExchange(),
            'quantity' => (int) ($record->quantity ?: 1),
            'item' => $item ? $this->itemLabel($item) : null,
            'return_method' => $record->return_method?->label(),
            'reason' => $record->reason->label(),
            'description' => $record->description,
            'customer_note' => $record->customer_note,
            'refund_amount' => $record->refund_amount,
            'replacement' => $this->replacement($record),
            'received_at' => $record->received_at?->format('M d, Y H:i'),
            'inspection_result' => $record->inspection_result,
            'inspection_notes' => $record->inspection_notes,
            'inspected_at' => $record->inspected_at?->format('M d, Y H:i'),
            'restocked_at' => $record->restocked_at?->format('M d, Y H:i'),
            'exchange_tracking_number' => $record->exchange_tracking_number,
            'exchange_shipped_at' => $record->exchange_shipped_at?->format('M d, Y H:i'),
            'exchange_completed_at' => $record->exchange_completed_at?->format('M d, Y H:i'),
            'forwarded_at' => $record->forwarded_at?->format('M d, Y H:i'),
            'reviewed_by' => $this->reviewer($record),
            'reviewed_at' => $record->reviewed_at?->format('M d, Y H:i'),
            'cancelled_at' => $record->cancelled_at?->format('M d, Y H:i'),
            'available_actions' => $actions,
            'refund_breakdown' => in_array('issue_refund', $actions, true) ? $this->refundBreakdown($record) : null,
            'refunds' => $record->refunds()->latest('id')->get()->map(fn (Refund $refund) => RefundPresenter::panel($refund))->values()->all(),
            'created_at' => $record->created_at?->format('M d, Y H:i'),
            'media' => $record->media->map(fn ($m) => [
                'url' => $m->url(),
                'type' => $m->type->value,
            ])->all(),
            'notes' => $record->notes->sortByDesc('id')->map(fn ($n) => [
                'author_type' => $n->author_type,
                'note' => $n->note,
                'created_at' => $n->created_at?->format('M d, Y H:i'),
            ])->values()->all(),
        ];
    }

    private function itemLabel(OrderItem $item): string
    {
        $product = $item->product_id ? TenantProduct::query()->withoutGlobalScope('centralVisible')->find($item->product_id) : null;
        $name = $product ? ($product->translationValue('name') ?: $product->slug) : __('Item');
        $variant = $item->product_variant_id ? TenantVariant::query()->find($item->product_variant_id) : null;

        return $variant?->display_label ? sprintf('%s (%s)', $name, $variant->display_label) : (string) $name;
    }

    /** @return array{label: string, quantity: int, stock: int|null, reserved: bool}|null */
    private function replacement(ReturnRequest $record): ?array
    {
        if (! $record->replacement_product_variant_id) {
            return null;
        }

        $variant = TenantVariant::query()->with('centralVariant')->find((int) $record->replacement_product_variant_id);
        $product = $variant ? TenantProduct::query()->withoutGlobalScope('centralVisible')->with('centralProduct')->find($variant->product_id) : null;

        return [
            'label' => $variant
                ? (string) ($variant->display_label ?: ($variant->sku ?: '#'.$variant->id))
                : '#'.$record->replacement_product_variant_id,
            'quantity' => (int) ($record->replacement_quantity ?: $record->quantity ?: 1),
            'stock' => $variant && $product ? app(StockService::class)->availableStock($product, $variant) : 0,
            'reserved' => $record->replacement_reserved_at !== null,
        ];
    }

    private function reviewer(ReturnRequest $record): ?string
    {
        if (! $record->reviewed_by_type) {
            return null;
        }

        $name = $record->reviewed_by_type === CancellationActor::Vendor && $record->reviewed_by_id
            ? AdminUser::query()->whereKey((int) $record->reviewed_by_id)->value('name')
            : null;

        return $name ? sprintf('%s (%s)', $name, $record->reviewed_by_type->label()) : $record->reviewed_by_type->label();
    }

    /** @return array<string, mixed>|null */
    private function refundBreakdown(ReturnRequest $record): ?array
    {
        try {
            return app(RefundService::class)->calculateForReturn($record)->toArray();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
