<?php

namespace App\Livewire\Admin\Order;

use App\Enums\ReturnStatus;
use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\AdminUser;
use App\Models\OrderReturn;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Auth;

class OrderReturnDetailPage extends AdminPage
{
    use InteractsWithAdminUi;

    public int    $returnId    = 0;
    public array  $returnRecord = [];
    public array  $order        = [];
    public string $adminNotes   = '';
    public string $refundAmount = '';
    public bool   $showApproveModal  = false;
    public bool   $showRejectModal   = false;
    public bool   $showRefundModal   = false;

    public function mount(int $id): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);

        $record = OrderReturn::with('reviewer')->findOrFail($id);
        $this->returnId     = $record->id;
        $this->adminNotes   = (string) $record->admin_notes;
        $this->refundAmount = $record->refund_amount !== null ? (string) $record->refund_amount : '';
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

    public function saveNotes(): void
    {
        $this->validate(['adminNotes' => ['nullable', 'string', 'max:2000']]);

        $record = OrderReturn::findOrFail($this->returnId);
        $record->update(['admin_notes' => $this->adminNotes ?: null]);
        $this->hydrateReturn($record->fresh());
        $this->toast('Notes saved.');
    }

    public function approve(): void
    {
        $record = OrderReturn::findOrFail($this->returnId);

        if ($record->status === ReturnStatus::Refunded) {
            $this->toast('This return has already been refunded.', 'error');
            return;
        }

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();

        $record->update([
            'status'               => ReturnStatus::Approved,
            'admin_notes'          => $this->adminNotes ?: $record->admin_notes,
            'reviewed_by_admin_id' => $admin?->id,
            'reviewed_at'          => now(),
        ]);

        $this->showApproveModal = false;
        $this->hydrateReturn($record->fresh());
        $this->toast('Return request approved.');
    }

    public function reject(): void
    {
        $record = OrderReturn::findOrFail($this->returnId);

        if (in_array($record->status, [ReturnStatus::Refunded, ReturnStatus::Rejected], true)) {
            $this->toast('Cannot reject this return.', 'error');
            return;
        }

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();

        $record->update([
            'status'               => ReturnStatus::Rejected,
            'admin_notes'          => $this->adminNotes ?: $record->admin_notes,
            'reviewed_by_admin_id' => $admin?->id,
            'reviewed_at'          => now(),
        ]);

        $this->showRejectModal = false;
        $this->hydrateReturn($record->fresh());
        $this->toast('Return request rejected.');
    }

    public function markRefunded(): void
    {
        $this->validate([
            'refundAmount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $record = OrderReturn::findOrFail($this->returnId);

        if ($record->status === ReturnStatus::Rejected) {
            $this->toast('Cannot refund a rejected return.', 'error');
            return;
        }

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();

        $record->update([
            'status'               => ReturnStatus::Refunded,
            'refund_amount'        => $this->refundAmount !== '' ? (float) $this->refundAmount : null,
            'admin_notes'          => $this->adminNotes ?: $record->admin_notes,
            'reviewed_by_admin_id' => $admin?->id,
            'reviewed_at'          => now(),
        ]);

        $this->showRefundModal = false;
        $this->hydrateReturn($record->fresh());
        $this->toast('Return marked as refunded.');
    }

    private function hydrateReturn(OrderReturn $record): void
    {
        $this->returnRecord = [
            'id'            => $record->id,
            'tenant_id'     => $record->tenant_id,
            'order_number'  => $record->order_number,
            'status'        => $record->status,
            'status_label'  => $record->status->label(),
            'status_color'  => $record->status->color(),
            'reason'        => $record->reason,
            'customer_notes'=> $record->customer_notes,
            'admin_notes'   => $record->admin_notes,
            'refund_amount' => $record->refund_amount,
            'reviewed_by'   => $record->reviewer?->name,
            'reviewed_at'   => $record->reviewed_at?->format('M d, Y H:i'),
            'created_at'    => $record->created_at?->format('M d, Y H:i'),
        ];
    }
}
