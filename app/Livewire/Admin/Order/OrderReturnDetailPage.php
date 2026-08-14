<?php

namespace App\Livewire\Admin\Order;

use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\AdminUser;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestNote;
use App\Models\Tenant;
use App\Repositories\OrderRepository;
use App\Services\ReturnRequestService;
use Illuminate\Support\Facades\Auth;

class OrderReturnDetailPage extends AdminPage
{
    use InteractsWithAdminUi;

    public int    $returnId    = 0;
    public array  $returnRecord = [];
    public array  $order        = [];
    public string $noteText     = '';
    public string $rejectReason = '';
    public string $infoMessage  = '';
    public string $refundAmount = '';
    public bool   $showRejectModal   = false;
    public bool   $showInfoModal     = false;
    public bool   $showRefundModal   = false;

    public function mount(int $id): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);

        $record = ReturnRequest::with(['reviewer', 'media', 'notes'])->findOrFail($id);
        $this->returnId = $record->id;
        $this->hydrateReturn($record);

        $orderRecord = app(OrderRepository::class)->find($record->tenant_id, $record->order_number);
        if ($orderRecord) {
            $this->order = app(OrderRepository::class)->orderDetail($orderRecord);
        }
    }

    protected function pageMeta(): array
    {
        return [
            'title'       => 'Return #' . $this->returnId,
            'badge'       => 'Return Management',
            'description' => 'Review, approve or reject this product return request and manage the refund.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.order.order-return-detail-page';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'returnRecord' => $this->returnRecord,
            'order'        => $this->order,
        ]);
    }

    public function addNote(): void
    {
        $this->validate(['noteText' => ['required', 'string', 'max:2000']]);

        $record = ReturnRequest::findOrFail($this->returnId);

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();

        app(ReturnRequestService::class)->addNote($record, $this->noteText, ReturnRequestNote::AUTHOR_ADMIN, $admin?->id, false);
        $this->noteText = '';
        $this->hydrateReturn($record->fresh(['reviewer', 'media', 'notes']));
        $this->toast('Note added.');
    }

    public function approve(): void
    {
        $record = ReturnRequest::findOrFail($this->returnId);

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();

        app(ReturnRequestService::class)->approve($record, $admin?->id);

        $this->hydrateReturn($record->fresh(['reviewer', 'media', 'notes']));
        $this->toast('Return request approved.');
    }

    public function reject(): void
    {
        $this->validate(['rejectReason' => ['required', 'string', 'max:2000']]);

        $record = ReturnRequest::findOrFail($this->returnId);

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();

        app(ReturnRequestService::class)->reject($record, $this->rejectReason, $admin?->id);

        $this->showRejectModal = false;
        $this->rejectReason = '';
        $this->hydrateReturn($record->fresh(['reviewer', 'media', 'notes']));
        $this->toast('Return request rejected.');
    }

    public function requestMoreInfo(): void
    {
        $this->validate(['infoMessage' => ['required', 'string', 'max:2000']]);

        $record = ReturnRequest::findOrFail($this->returnId);

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();

        app(ReturnRequestService::class)->requestMoreInfo($record, $this->infoMessage, $admin?->id);

        $this->showInfoModal = false;
        $this->infoMessage = '';
        $this->hydrateReturn($record->fresh(['reviewer', 'media', 'notes']));
        $this->toast('Requested more information from the customer.');
    }

    public function markItemReceived(): void
    {
        $record = ReturnRequest::findOrFail($this->returnId);

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();

        app(ReturnRequestService::class)->markItemReceived($record, $admin?->id);

        $this->hydrateReturn($record->fresh(['reviewer', 'media', 'notes']));
        $this->toast('Return marked as item received.');
    }

    public function markRefunded(): void
    {
        $this->validate([
            'refundAmount' => ['required', 'numeric', 'min:0'],
        ]);

        $record = ReturnRequest::findOrFail($this->returnId);

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();

        app(ReturnRequestService::class)->markRefunded($record, (float) $this->refundAmount, $admin?->id);

        $this->showRefundModal = false;
        $this->hydrateReturn($record->fresh(['reviewer', 'media', 'notes']));
        $this->toast('Return marked as refunded.');
    }

    public function close(): void
    {
        $record = ReturnRequest::findOrFail($this->returnId);

        app(ReturnRequestService::class)->close($record);

        $this->hydrateReturn($record->fresh(['reviewer', 'media', 'notes']));
        $this->toast('Return request closed.');
    }

    private function hydrateReturn(ReturnRequest $record): void
    {
        $this->returnRecord = [
            'id'            => $record->id,
            'tenant_id'     => $record->tenant_id,
            'tenant_name'   => Tenant::find($record->tenant_id)?->name ?? $record->tenant_id,
            'order_number'  => $record->order_number,
            'status'        => $record->status,
            'status_label'  => $record->status->label(),
            'status_color'  => $record->status->color(),
            'reason'        => $record->reason->label(),
            'description'   => $record->description,
            'refund_amount' => $record->refund_amount,
            'reviewed_by'   => $record->reviewer?->name,
            'reviewed_at'   => $record->reviewed_at?->format('M d, Y H:i'),
            'created_at'    => $record->created_at?->format('M d, Y H:i'),
            'media'         => $record->media->map(fn($m) => [
                'url'  => $m->url(),
                'type' => $m->type->value,
            ])->all(),
            'notes'         => $record->notes->sortByDesc('id')->map(fn($n) => [
                'author_type' => $n->author_type,
                'note'        => $n->note,
                'created_at'  => $n->created_at?->format('M d, Y H:i'),
            ])->values()->all(),
        ];
    }
}
