<?php

namespace App\Livewire\Tenant\Return;

use App\Enums\ReturnStatus;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Livewire\Tenant\Base\TenantPage;
use App\Models\ReturnRequestNote;
use App\Models\ReturnRequest;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\ReturnRequestService;
use Illuminate\Support\Facades\Auth;

class ReturnDetailPage extends TenantPage
{
    use InteractsWithAdminUi;

    public int $returnId = 0;
    public array $returnRecord = [];
    public array $order = [];
    public int $orderId = 0;
    public string $noteText = '';
    public string $rejectReason = '';
    public string $infoMessage = '';
    public bool $showRejectModal = false;
    public bool $showInfoModal = false;

    public function mount(int $id): void
    {
        $record = ReturnRequest::with(['media', 'notes'])->findOrFail($id);

        if ($record->tenant_id !== tenant()->id) {
            abort(404);
        }

        $this->returnId = $record->id;
        $this->hydrate($record);

        $order = Order::query()->where('uuid', $record->order_number)->first();
        if ($order) {
            $this->orderId = $order->id;
            $this->order = app(TenantPanelRepository::class)->orderDetail($order);
        }
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Return #' . $this->returnId,
            'badge' => 'Return Management',
            'description' => 'Review this customer return request, respond, and take action.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.return.return-detail-page';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'returnRecord' => $this->returnRecord,
            'order' => $this->order,
            'orderId' => $this->orderId,
        ]);
    }

    public function approve(): void
    {
        $record = ReturnRequest::findOrFail($this->returnId);
        $this->guardTenant($record);

        app(ReturnRequestService::class)->approve($record);
        $this->refresh();
        $this->toast('Return request approved.');
    }

    public function reject(): void
    {
        $this->validate(['rejectReason' => ['required', 'string', 'max:2000']]);

        $record = ReturnRequest::findOrFail($this->returnId);
        $this->guardTenant($record);

        app(ReturnRequestService::class)->reject($record, $this->rejectReason);
        $this->showRejectModal = false;
        $this->rejectReason = '';
        $this->refresh();
        $this->toast('Return request rejected.');
    }

    public function requestMoreInfo(): void
    {
        $this->validate(['infoMessage' => ['required', 'string', 'max:2000']]);

        $record = ReturnRequest::findOrFail($this->returnId);
        $this->guardTenant($record);

        app(ReturnRequestService::class)->requestMoreInfo($record, $this->infoMessage);
        $this->showInfoModal = false;
        $this->infoMessage = '';
        $this->refresh();
        $this->toast('Requested more information from the customer.');
    }

    public function addNote(): void
    {
        $this->validate(['noteText' => ['required', 'string', 'max:2000']]);

        $record = ReturnRequest::findOrFail($this->returnId);
        $this->guardTenant($record);

        $admin = Auth::guard('tenant')->user();

        app(ReturnRequestService::class)->addNote($record, $this->noteText, ReturnRequestNote::AUTHOR_TENANT, $admin?->id, false);
        $this->noteText = '';
        $this->refresh();
        $this->toast('Note added.');
    }

    private function guardTenant(ReturnRequest $record): void
    {
        if ($record->tenant_id !== tenant()->id) {
            abort(403);
        }
    }

    private function refresh(): void
    {
        $this->hydrate(ReturnRequest::with(['media', 'notes'])->findOrFail($this->returnId));
    }

    private function hydrate(ReturnRequest $record): void
    {
        $this->returnRecord = [
            'id' => $record->id,
            'order_number' => $record->order_number,
            'status' => $record->status,
            'status_label' => $record->status->label(),
            'status_color' => $record->status->color(),
            'reason' => $record->reason->label(),
            'description' => $record->description,
            'refund_amount' => $record->refund_amount,
            'created_at' => $record->created_at?->format('M d, Y H:i'),
            'media' => $record->media->map(fn($m) => [
                'url' => $m->url(),
                'type' => $m->type->value,
            ])->all(),
            'notes' => $record->notes->sortByDesc('id')->map(fn($n) => [
                'author_type' => $n->author_type,
                'note' => $n->note,
                'created_at' => $n->created_at?->format('M d, Y H:i'),
            ])->values()->all(),
        ];
    }
}
