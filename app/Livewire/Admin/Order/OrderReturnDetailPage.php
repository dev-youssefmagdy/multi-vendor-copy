<?php

namespace App\Livewire\Admin\Order;

use App\Enums\CancellationActor;
use App\Enums\InspectionResult;
use App\Exceptions\OrderActionException;
use App\Exceptions\RefundException;
use App\Exceptions\ReturnActionException;
use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Livewire\Admin\Concerns\ManagesRefundActions;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestNote;
use App\Models\Tenant;
use App\Repositories\OrderRepository;
use App\Services\Admin\OrderAfterSalesService;
use App\Services\Orders\OrderPolicyService;
use App\Services\Refunds\RefundActor;
use App\Services\ReturnRequestService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class OrderReturnDetailPage extends AdminPage
{
    use InteractsWithAdminUi;
    use ManagesRefundActions;

    public int $returnId = 0;

    public array $order = [];

    public string $noteText = '';

    // Modals + their inputs
    public bool $showApproveModal = false;

    public string $approveNote = '';

    public bool $showRejectModal = false;

    public string $rejectReason = '';

    public bool $showInfoModal = false;

    public string $infoMessage = '';

    public bool $showInspectModal = false;

    public string $inspectionResult = '';

    public string $inspectionNotes = '';

    public bool $restock = true;

    public bool $showRefundModal = false;

    public string $refundAmount = '';

    public bool $showShipModal = false;

    public string $trackingNumber = '';

    /** @var array<string, mixed>|null per-request cache of the hydrated return */
    protected ?array $returnCache = null;

    public function mount(int $id): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);

        $record = ReturnRequest::query()->findOrFail($id);
        $this->returnId = $record->id;

        $orderRecord = app(OrderRepository::class)->find($record->tenant_id, $record->order_number);
        if ($orderRecord) {
            $this->order = app(OrderRepository::class)->orderDetail($orderRecord);
        }
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Return #'.$this->returnId,
            'badge' => 'Return Management',
            'description' => 'Review, approve or reject this return / exchange request and manage the refund.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.order.order-return-detail-page';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'returnRecord' => $this->returnRecord(),
            'order' => $this->order,
            'inspectionResults' => collect(InspectionResult::cases())->mapWithKeys(fn (InspectionResult $result) => [$result->value => $result->label()])->all(),
        ]);
    }

    // ─── Workflow actions (buttons come from ReturnRequestService::availableActions) ───

    public function addNote(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);
        $this->validate(['noteText' => ['required', 'string', 'max:2000']]);

        app(ReturnRequestService::class)->addNote($this->record(), $this->noteText, ReturnRequestNote::AUTHOR_ADMIN, $this->adminUser()?->id, false);
        $this->noteText = '';
        $this->returnCache = null;
        $this->toast('Note added.');
    }

    public function openApproveModal(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);
        $this->resetValidation();
        $this->approveNote = '';
        $this->showApproveModal = true;
    }

    public function approve(): void
    {
        $this->validate(['approveNote' => ['nullable', 'string', 'max:2000']]);

        $this->perform(
            fn (ReturnRequestService $service, ReturnRequest $record) => $service->approve($record, $this->actor(), null, $this->approveNote ?: null),
            'Return request approved.',
            fn () => $this->showApproveModal = false,
        );
    }

    public function openRejectModal(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);
        $this->resetValidation();
        $record = $this->returnRecord();
        // A failed inspection can only end in a rejection: prefill the reason with the inspection notes.
        $this->rejectReason = $record['inspection_result_value'] === InspectionResult::Failed->value ? (string) $record['inspection_notes'] : '';
        $this->showRejectModal = true;
    }

    public function reject(): void
    {
        $this->validate(['rejectReason' => ['required', 'string', 'max:2000']]);

        $this->perform(
            fn (ReturnRequestService $service, ReturnRequest $record) => $service->reject($record, $this->rejectReason, $this->actor()),
            'Return request rejected.',
            function (): void {
                $this->showRejectModal = false;
                $this->rejectReason = '';
            },
        );
    }

    public function openInfoModal(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);
        $this->resetValidation();
        $this->showInfoModal = true;
    }

    public function requestMoreInfo(): void
    {
        $this->validate(['infoMessage' => ['required', 'string', 'max:2000']]);

        $this->perform(
            fn (ReturnRequestService $service, ReturnRequest $record) => $service->requestMoreInfo($record, $this->infoMessage, $this->actor()),
            'Requested more information from the customer.',
            function (): void {
                $this->showInfoModal = false;
                $this->infoMessage = '';
            },
        );
    }

    public function forwardToMerchant(): void
    {
        $this->perform(
            fn (ReturnRequestService $service, ReturnRequest $record) => $service->markAwaitingMerchantReview($record, $this->actor()),
            'Request forwarded to merchant for review.',
        );
    }

    public function markItemReceived(): void
    {
        $this->perform(
            fn (ReturnRequestService $service, ReturnRequest $record) => $service->markItemReceived($record, $this->actor()),
            'Return marked as item received.',
        );
    }

    public function openInspectModal(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);
        $this->resetValidation();

        $record = $this->record();
        $tenant = Tenant::query()->find($record->tenant_id);

        $this->inspectionResult = '';
        $this->inspectionNotes = '';
        // The store's restock_returned_items policy is the default for the checkbox.
        $this->restock = $tenant ? app(OrderPolicyService::class)->restockReturnedItems($tenant) : true;
        $this->showInspectModal = true;
    }

    public function inspect(): void
    {
        $this->validate([
            'inspectionResult' => ['required', 'string', Rule::enum(InspectionResult::class)],
            'inspectionNotes' => ['nullable', 'string', 'max:2000'],
            'restock' => ['boolean'],
        ], [
            'inspectionResult.required' => 'Please choose an inspection result.',
            'inspectionResult.enum' => 'Please choose an inspection result.',
        ]);

        $this->perform(
            fn (ReturnRequestService $service, ReturnRequest $record) => $service->inspect(
                $record,
                InspectionResult::from($this->inspectionResult),
                $this->inspectionNotes ?: null,
                $this->restock,
                $this->actor(),
            ),
            'Inspection recorded.',
            fn () => $this->showInspectModal = false,
        );
    }

    public function openRefundModal(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);
        $this->resetValidation();

        $max = (float) ($this->returnRecord()['refund_breakdown']['max'] ?? 0);
        $this->refundAmount = number_format($max, 2, '.', '');
        $this->showRefundModal = true;
    }

    /** Refund an inspected return: amount ≤ the calculated maximum (editable down). */
    public function issueRefund(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);

        $max = round((float) ($this->returnRecord()['refund_breakdown']['max'] ?? 0), 2);

        $this->validate([
            'refundAmount' => ['required', 'numeric', 'min:0.01', 'max:'.max($max, 0.01)],
        ], [
            'refundAmount.min' => __('The refund amount must be greater than zero.'),
            'refundAmount.max' => $max > 0
                ? __('The refund cannot be higher than the calculated maximum of :amount.', ['amount' => number_format($max, 2)])
                : __('There is nothing left to refund on this order.'),
        ]);

        $refund = null;

        $this->perform(
            function (ReturnRequestService $service, ReturnRequest $record) use (&$refund): void {
                $refund = $service->issueRefund($record, (float) $this->refundAmount, $this->actor());
            },
            null,
            fn () => $this->showRefundModal = false,
        );

        if ($refund instanceof Refund) {
            $this->toastForRefund($refund);
        }
    }

    public function openShipModal(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);
        $this->resetValidation();
        $this->trackingNumber = '';
        $this->showShipModal = true;
    }

    public function markExchangeShipped(): void
    {
        $this->validate(['trackingNumber' => ['required', 'string', 'max:190']], [
            'trackingNumber.required' => __('Please enter the tracking number of the replacement.'),
        ]);

        $this->perform(
            fn (ReturnRequestService $service, ReturnRequest $record) => $service->markExchangeShipped($record, $this->trackingNumber, $this->actor()),
            'Replacement marked as shipped.',
            fn () => $this->showShipModal = false,
        );
    }

    public function markExchangeCompleted(): void
    {
        $this->perform(
            fn (ReturnRequestService $service, ReturnRequest $record) => $service->markExchangeCompleted($record, $this->actor()),
            'Exchange completed.',
        );
    }

    public function convertToRefund(): void
    {
        $this->perform(
            fn (ReturnRequestService $service, ReturnRequest $record) => $service->convertToRefund($record, $this->actor()),
            'Exchange converted to a refund.',
        );
    }

    public function close(): void
    {
        $this->perform(
            fn (ReturnRequestService $service, ReturnRequest $record) => $service->close($record, $this->actor()),
            'Return request closed.',
        );
    }

    // ─── Refund row actions (ManagesRefundActions) ──────────────────────────

    protected function refundsScope(): Builder
    {
        return Refund::query()->where('return_request_id', $this->returnId);
    }

    protected function afterRefundAction(): void
    {
        $this->returnCache = null;
        $this->refreshOrder();
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /**
     * Run a workflow action as the admin: permission check, rule violations (ReturnActionException /
     * RefundException) become an error toast and the modal stays open; on success the state is
     * re-read, $afterSuccess closes the modal and the toast is shown.
     */
    private function perform(callable $action, ?string $successMessage, ?callable $afterSuccess = null): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);

        try {
            $action(app(ReturnRequestService::class), $this->record());
        } catch (ReturnActionException|RefundException|OrderActionException $e) {
            $this->toast($e->getMessage(), 'error');
            $this->returnCache = null;

            return;
        }

        $this->returnCache = null;
        $this->refreshOrder();

        if ($afterSuccess) {
            $afterSuccess();
        }

        if ($successMessage) {
            $this->toast($successMessage);
        }
    }

    private function actor(): RefundActor
    {
        return $this->refundActor();
    }

    private function record(): ReturnRequest
    {
        return ReturnRequest::query()->findOrFail($this->returnId);
    }

    private function refreshOrder(): void
    {
        $record = $this->record();
        $orderRecord = app(OrderRepository::class)->find($record->tenant_id, $record->order_number);

        if ($orderRecord) {
            $this->order = app(OrderRepository::class)->orderDetail($orderRecord);
        }
    }

    /** @return array<string, mixed> */
    private function returnRecord(): array
    {
        return $this->returnCache ??= $this->hydrateReturn($this->record()->load(['reviewer', 'media', 'notes']));
    }

    /** @return array<string, mixed> */
    private function hydrateReturn(ReturnRequest $record): array
    {
        $actions = app(ReturnRequestService::class)->availableActions($record, CancellationActor::Admin);
        $context = app(OrderAfterSalesService::class)->returnContext($record, in_array('issue_refund', $actions, true));
        $tenantName = Tenant::query()->find($record->tenant_id)?->name ?? $record->tenant_id;

        return [
            'id' => $record->id,
            'tenant_id' => $record->tenant_id,
            'tenant_name' => $tenantName,
            'order_number' => $record->order_number,
            'status_value' => $record->status->value,
            'status_label' => $record->status->label(),
            'status_color' => $record->status->color(),
            'is_open' => $record->status->isOpen(),
            'type_label' => $record->type?->label(),
            'is_exchange' => $record->isExchange(),
            'quantity' => (int) ($record->quantity ?: 1),
            'item' => $context['item'],
            'return_method' => $record->return_method?->label(),
            'reason' => $record->reason->label(),
            'description' => $record->description,
            'customer_note' => $record->customer_note,
            'refund_amount' => $record->refund_amount,
            'replacement' => $context['replacement'],
            'received_at' => $record->received_at?->format('M d, Y H:i'),
            'inspection_result_value' => $record->inspection_result?->value,
            'inspection_result_label' => $record->inspection_result?->label(),
            'inspection_notes' => $record->inspection_notes,
            'inspected_at' => $record->inspected_at?->format('M d, Y H:i'),
            'restocked_at' => $record->restocked_at?->format('M d, Y H:i'),
            'exchange_tracking_number' => $record->exchange_tracking_number,
            'exchange_shipped_at' => $record->exchange_shipped_at?->format('M d, Y H:i'),
            'exchange_completed_at' => $record->exchange_completed_at?->format('M d, Y H:i'),
            'forwarded_at' => $record->forwarded_at?->format('M d, Y H:i'),
            'cancelled_at' => $record->cancelled_at?->format('M d, Y H:i'),
            'reviewed_by' => $this->reviewerLabel($record, $context['vendor_reviewer']),
            'reviewed_at' => $record->reviewed_at?->format('M d, Y H:i'),
            'created_at' => $record->created_at?->format('M d, Y H:i'),
            'available_actions' => $actions,
            'refund_breakdown' => $context['refund_breakdown'],
            'refunds' => app(OrderAfterSalesService::class)->refundRows($record->refunds()->latest('id')->get()),
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

    private function reviewerLabel(ReturnRequest $record, ?string $vendorName): ?string
    {
        if (! $record->reviewed_by_type) {
            return $record->reviewer?->name;
        }

        $name = $record->reviewed_by_type === CancellationActor::Admin ? $record->reviewer?->name : $vendorName;

        return $name
            ? sprintf('%s (%s)', $name, $record->reviewed_by_type->label())
            : $record->reviewed_by_type->label();
    }
}
