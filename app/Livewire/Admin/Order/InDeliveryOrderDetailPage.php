<?php

namespace App\Livewire\Admin\Order;

use App\Enums\OrderShippingStatus;
use App\Enums\OrderStatus;
use App\Enums\ReturnStatus;
use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\AdminUser;
use App\Models\OrderAttachment;
use App\Models\OrderReturn;
use App\Repositories\OrderRepository;
use App\Services\Admin\OrderFulfillmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class InDeliveryOrderDetailPage extends AdminPage
{
    use InteractsWithAdminUi;
    use WithFileUploads;

    public string $tenantId = '';
    public string $orderNumber = '';
    public array $order = [];
    public array $orderAttachments = [];
    public string $selectedShippingStatus = '';
    public string $selectedOrderStatus = '';
    public string $trackingNumber = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $attachmentFile = null;

    public bool   $showReturnModal = false;
    public string $returnReason    = '';
    public string $returnNotes     = '';

    public function mount(string $tenantId, string $orderNumber): void
    {
        $this->authorizeAnyPermission(['shipping.delivery.view', 'shipping.delivery.manage']);
        $this->tenantId = $tenantId;
        $this->orderNumber = $orderNumber;

        $record = app(OrderRepository::class)->find($tenantId, $orderNumber);
        abort_if(!$record, 404);

        $this->order = app(OrderRepository::class)->orderDetail($record);
        $this->selectedShippingStatus = (string) ($this->order['shipping_status_value'] ?? OrderShippingStatus::Pending->value);
        $this->selectedOrderStatus = (string) ($this->order['status_value'] ?? OrderStatus::Pending->value);
        $this->trackingNumber = (string) ($this->order['tracking_number'] ?? '');
        $this->loadOrderAttachments();
    }

    protected function pageMeta(): array
    {
        return [
            'title' => $this->order['uuid'] ?? 'Delivery Order',
            'badge' => 'Shipping Queue',
            'description' => 'Manage shipping status, upload attachments, and review full order details for this delivery.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.order.in-delivery-order-detail-page';
    }

    protected function pageData(): array
    {
        $orderStatusOptions = collect(OrderStatus::cases())
            ->mapWithKeys(fn(OrderStatus $s) => [$s->value => $s->label()])
            ->all();

        $shippingStatusOptions = [
            OrderShippingStatus::Pending->value => OrderShippingStatus::Pending->label(),
            OrderShippingStatus::InDelivery->value => OrderShippingStatus::InDelivery->label(),
            OrderShippingStatus::Shipped->value => OrderShippingStatus::Shipped->label(),
            OrderShippingStatus::Delivered->value => OrderShippingStatus::Delivered->label(),
        ];

        return array_merge(parent::pageData(), [
            'order' => $this->order,
            'orderAttachments' => $this->orderAttachments,
            'shippingStatusOptions' => $shippingStatusOptions,
            'orderStatusOptions' => $orderStatusOptions,
        ]);
    }

    public function updateShippingStatus(OrderFulfillmentService $service): void
    {
        $shippingStatus = OrderShippingStatus::tryFrom($this->selectedShippingStatus);

        if (!$shippingStatus) {
            $this->toast('Select a valid shipping status.', 'error');
            return;
        }

        try {
            $service->updateShippingStatus($this->tenantId, $this->orderNumber, $shippingStatus);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            $this->toast($e->getMessage(), 'error');
            return;
        }

        $record = app(OrderRepository::class)->find($this->tenantId, $this->orderNumber);
        if ($record) {
            $this->order = app(OrderRepository::class)->orderDetail($record);
            $this->selectedShippingStatus = (string) ($this->order['shipping_status_value'] ?? $shippingStatus->value);
        }

        $this->toast('Shipping status updated successfully.');
    }

    public function updateOrderStatus(OrderFulfillmentService $service): void
    {
        $orderStatus = OrderStatus::tryFrom($this->selectedOrderStatus);

        if (!$orderStatus) {
            $this->toast('Select a valid order status.', 'error');
            return;
        }

        try {
            $service->updateOrderStatus($this->tenantId, $this->orderNumber, $orderStatus);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            $this->toast($e->getMessage(), 'error');
            return;
        }

        $record = app(OrderRepository::class)->find($this->tenantId, $this->orderNumber);
        if ($record) {
            $this->order = app(OrderRepository::class)->orderDetail($record);
            $this->selectedOrderStatus = (string) ($this->order['status_value'] ?? $orderStatus->value);
        }

        $this->toast('Order status updated successfully.');
    }

    public function updateTrackingNumber(OrderFulfillmentService $service): void
    {
        $this->validate([
            'trackingNumber' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $service->updateTrackingNumber($this->tenantId, $this->orderNumber, $this->trackingNumber);
        } catch (\RuntimeException $e) {
            $this->toast($e->getMessage(), 'error');
            return;
        }

        $record = app(OrderRepository::class)->find($this->tenantId, $this->orderNumber);
        if ($record) {
            $this->order = app(OrderRepository::class)->orderDetail($record);
            $this->trackingNumber = (string) ($this->order['tracking_number'] ?? '');
        }

        $this->toast('Tracking number updated successfully.');
    }

    public function loadOrderAttachments(): void
    {
        if (!$this->tenantId || !$this->orderNumber) {
            $this->orderAttachments = [];
            return;
        }

        $this->orderAttachments = OrderAttachment::query()
            ->where('tenant_id', $this->tenantId)
            ->where('order_number', $this->orderNumber)
            ->with('uploader')
            ->latest()
            ->get()
            ->map(fn(OrderAttachment $att) => [
                'id' => $att->id,
                'original_name' => $att->original_name,
                'mime_type' => $att->mime_type,
                'extension' => $att->extension,
                'size' => $att->formatted_size,
                'url' => $att->url,
                'is_image' => $att->isImage(),
                'uploaded_by' => $att->uploader?->name ?? 'Admin',
                'created_at' => $att->created_at?->format('M d, Y H:i'),
            ])
            ->all();
    }

    public function uploadAttachment(): void
    {
        $this->validate([
            'attachmentFile' => 'required|file|max:5120|mimes:jpg,jpeg,png,gif,webp,pdf,xlsx,xls,csv,doc,docx,mp4,mov,avi,mkv,webm,wmv,flv',
        ]);

        $file = $this->attachmentFile;
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $name = Str::uuid()->toString() . '.' . $ext;
        $path = $file->storeAs('order-attachments/' . $this->tenantId . '/' . $this->orderNumber, $name, 'public');

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();

        OrderAttachment::create([
            'tenant_id' => $this->tenantId,
            'order_number' => $this->orderNumber,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'extension' => $ext,
            'size_bytes' => is_file($file->getRealPath()) ? (int) filesize($file->getRealPath()) : 0,
            'uploaded_by_admin_id' => $admin?->id,
        ]);

        $this->attachmentFile = null;
        $this->loadOrderAttachments();
        $this->toast('File uploaded successfully.');
    }

    public function deleteAttachment(int $id): void
    {
        $attachment = OrderAttachment::query()
            ->where('id', $id)
            ->where('tenant_id', $this->tenantId)
            ->first();

        if (!$attachment) {
            $this->toast('Attachment not found.', 'error');
            return;
        }

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();
        $this->loadOrderAttachments();
        $this->toast('Attachment deleted.');
    }

    public function createReturn(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage', 'shipping.delivery.manage']);
        $this->validate([
            'returnReason' => ['required', 'string', 'min:10', 'max:1000'],
            'returnNotes'  => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = OrderReturn::where('tenant_id', $this->tenantId)
            ->where('order_number', $this->orderNumber)
            ->whereIn('status', [ReturnStatus::Pending->value, ReturnStatus::Approved->value])
            ->exists();

        if ($existing) {
            $this->toast('An active return request already exists for this order.', 'error');
            return;
        }

        $returnRecord = OrderReturn::create([
            'tenant_id'      => $this->tenantId,
            'order_number'   => $this->orderNumber,
            'status'         => ReturnStatus::Pending,
            'reason'         => $this->returnReason,
            'customer_notes' => $this->returnNotes ?: null,
        ]);

        $this->showReturnModal = false;
        $this->returnReason    = '';
        $this->returnNotes     = '';
        $this->toast('Return request created successfully.');
        $this->redirect(route('admin.orders.returns.show', $returnRecord->id));
    }
}
