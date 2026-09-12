<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Sales;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Sales\AddReturnNoteRequest;
use App\Http\Requests\Tenant\Panel\Sales\MarkReturnRefundedRequest;
use App\Http\Requests\Tenant\Panel\Sales\RejectReturnRequest;
use App\Http\Requests\Tenant\Panel\Sales\RequestReturnInfoRequest;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestNote;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\ReturnRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class ReturnController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly ReturnRequestService $service,
    ) {
    }

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
            'returnRecord' => $this->hydrate($record),
            'order' => $orderData,
            'orderId' => $orderId,
        ]);
    }

    public function approve(int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->approve($record);

        return $this->success('Return request approved.', ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function validateReject(RejectReturnRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function reject(RejectReturnRequest $request, int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->reject($record, $request->validated()['reject_reason']);

        return $this->success('Return request rejected.', ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function validateRequestInfo(RequestReturnInfoRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function requestMoreInfo(RequestReturnInfoRequest $request, int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->requestMoreInfo($record, $request->validated()['info_message']);

        return $this->success('Requested more information from the customer.', ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function markItemReceived(int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->markItemReceived($record);

        return $this->success('Item marked as received.', ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
    }

    public function validateRefunded(MarkReturnRefundedRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function markRefunded(MarkReturnRefundedRequest $request, int $id): JsonResponse
    {
        $record = $this->findGuarded($id);

        $this->service->markRefunded($record, (float) $request->validated()['refund_amount']);

        return $this->success('Return marked as refunded.', ['returnRecord' => $this->hydrate($record->fresh(['media', 'notes']))]);
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

    private function hydrate(ReturnRequest $record): array
    {
        return [
            'id' => $record->id,
            'order_number' => $record->order_number,
            'status' => $record->status,
            'status_label' => $record->status->label(),
            'status_color' => $record->status->color(),
            'reason' => $record->reason->label(),
            'description' => $record->description,
            'refund_amount' => $record->refund_amount,
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
}
