<?php

namespace App\Services;

use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Models\AdminUser;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestMedia;
use App\Models\ReturnRequestNote;
use App\Models\Tenant;
use App\Repositories\OrderRepository;
use App\Services\Mail\TemplateMailService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use RuntimeException;

class ReturnRequestService
{
    /** @deprecated kept only as a last-resort fallback; use ReturnPolicyService for the real, configurable window. */
    public const RETURN_WINDOW_DAYS = ReturnPolicyService::DEFAULT_WINDOW_DAYS;

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly TenantNotificationService $tenantNotificationService,
        private readonly AdminNotificationService $adminNotificationService,
        private readonly TemplateMailService $mailService,
        private readonly ReturnPolicyService $returnPolicyService,
    ) {
    }

    /**
     * @param array{tenant_id:string, order_number:string, customer_id:int, product_id?:int|null, product_variant_id?:int|null, reason:string, description?:string|null} $data
     * @param UploadedFile[] $photoFiles
     * @param UploadedFile[] $videoFiles
     */
    public function create(array $data, array $photoFiles = [], array $videoFiles = []): ReturnRequest
    {
        $order = $this->orderRepository->find($data['tenant_id'], $data['order_number']);

        if (!$order) {
            throw new RuntimeException('Order not found.');
        }

        $windowDays = $this->getReturnWindowDays($data['tenant_id'], $data['product_id'] ?? null);

        if (!$this->isWithinReturnWindow($data['tenant_id'], $data['order_number'], $windowDays)) {
            throw new RuntimeException("This order is outside the {$windowDays}-day return window.");
        }

        if (empty($photoFiles)) {
            throw new RuntimeException('At least one photo is required as evidence.');
        }

        $returnRequest = ReturnRequest::create([
            'tenant_id' => $data['tenant_id'],
            'order_number' => $data['order_number'],
            'customer_id' => $data['customer_id'],
            'product_id' => $data['product_id'] ?? null,
            'product_variant_id' => $data['product_variant_id'] ?? null,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => ReturnStatus::Pending,
        ]);

        foreach ($photoFiles as $file) {
            $this->storeMedia($returnRequest, $file, 'photo');
        }

        foreach ($videoFiles as $file) {
            $this->storeMedia($returnRequest, $file, 'video');
        }

        $this->notify($returnRequest, 'Return request submitted', sprintf(
            'A new return request was submitted for order %s.',
            $returnRequest->order_number,
        ));

        return $returnRequest;
    }

    public function approve(ReturnRequest $returnRequest, ?int $adminId = null, ?string $note = null): ReturnRequest
    {
        $returnRequest->update([
            'status' => ReturnStatus::Approved,
            'reviewed_by_admin_id' => $adminId,
            'reviewed_at' => now(),
        ]);

        if ($note) {
            $this->addNote($returnRequest, $note, $adminId ? ReturnRequestNote::AUTHOR_ADMIN : ReturnRequestNote::AUTHOR_TENANT, $adminId, true);
        }

        $this->notify($returnRequest, 'Return request approved', 'Your return request has been approved.', true);

        return $returnRequest->fresh();
    }

    public function reject(ReturnRequest $returnRequest, string $reason, ?int $adminId = null): ReturnRequest
    {
        $returnRequest->update([
            'status' => ReturnStatus::Rejected,
            'reviewed_by_admin_id' => $adminId,
            'reviewed_at' => now(),
        ]);

        $this->addNote($returnRequest, $reason, $adminId ? ReturnRequestNote::AUTHOR_ADMIN : ReturnRequestNote::AUTHOR_TENANT, $adminId, true);

        $this->notify($returnRequest, 'Return request rejected', $reason, true);

        return $returnRequest->fresh();
    }

    public function requestMoreInfo(ReturnRequest $returnRequest, string $message, ?int $adminId = null): ReturnRequest
    {
        $returnRequest->update(['status' => ReturnStatus::AwaitingInfo]);

        $this->addNote($returnRequest, $message, $adminId ? ReturnRequestNote::AUTHOR_ADMIN : ReturnRequestNote::AUTHOR_TENANT, $adminId, true);

        $this->notify($returnRequest, 'More information requested', $message, true);

        return $returnRequest->fresh();
    }

    public function markItemReceived(ReturnRequest $returnRequest, ?int $adminId = null): ReturnRequest
    {
        $returnRequest->update(['status' => ReturnStatus::ItemReceived]);

        $this->notify($returnRequest, 'Returned item received', 'The returned item has been received.', true);

        return $returnRequest->fresh();
    }

    public function markAwaitingMerchantReview(ReturnRequest $returnRequest, ?int $adminId = null): ReturnRequest
    {
        $returnRequest->update([
            'status'               => ReturnStatus::AwaitingMerchantReview,
            'reviewed_by_admin_id' => $adminId ?? $returnRequest->reviewed_by_admin_id,
            'reviewed_at'          => $returnRequest->reviewed_at ?? now(),
        ]);

        $this->notify(
            $returnRequest,
            'Return request awaiting your review',
            'A return request for your store is awaiting your decision.',
            false,
        );

        return $returnRequest->fresh();
    }

    public function markRefunded(ReturnRequest $returnRequest, float $amount, ?int $adminId = null): ReturnRequest
    {
        $returnRequest->update([
            'status' => ReturnStatus::Refunded,
            'refund_amount' => $amount,
            'reviewed_by_admin_id' => $adminId ?? $returnRequest->reviewed_by_admin_id,
            'reviewed_at' => $returnRequest->reviewed_at ?? now(),
        ]);

        $this->notify($returnRequest, 'Refund issued', sprintf('A refund of %.2f has been issued.', $amount), true);

        return $returnRequest->fresh();
    }

    public function close(ReturnRequest $returnRequest): ReturnRequest
    {
        $returnRequest->update(['status' => ReturnStatus::Closed]);

        $this->notify($returnRequest, 'Return request closed', 'This return request has been closed.');

        return $returnRequest->fresh();
    }

    public function addNote(ReturnRequest $returnRequest, string $note, string $authorType, ?int $authorId, bool $customerVisible = false): ReturnRequestNote
    {
        return ReturnRequestNote::create([
            'return_request_id' => $returnRequest->id,
            'author_type' => $authorType,
            'author_id' => $authorId,
            'note' => $note,
            'customer_visible' => $customerVisible,
        ]);
    }

    /**
     * Determine eligibility for a fresh return request: order delivered within the window,
     * and no existing non-closed return request already covers this product/variant.
     */
    public function isWithinReturnWindow(string $tenantId, string $orderNumber, ?int $windowDays = null): bool
    {
        $deliveredAt = $this->deliveredAt($tenantId, $orderNumber);

        if (!$deliveredAt) {
            return false;
        }

        $windowDays ??= $this->getReturnWindowDays($tenantId);

        return $deliveredAt->copy()->addDays($windowDays)->isFuture();
    }

    /**
     * Resolve the configured return window, in days, for a given tenant/product: admin policy
     * for central-catalog products, tenant policy for the tenant's own products, falling back to
     * ReturnPolicyService::DEFAULT_WINDOW_DAYS if nothing is configured.
     */
    public function getReturnWindowDays(string $tenantId, ?int $productId = null): int
    {
        return $this->returnPolicyService->resolvePolicy($tenantId, $productId)['window_days'];
    }

    public function deliveredAt(string $tenantId, string $orderNumber): ?Carbon
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return null;
        }

        // Do not call tenancy()->end() here — mirrors TenantNotificationService's convention
        // of leaving the tenant context initialized so it doesn't break the calling context.
        tenancy()->initialize($tenant);

        $order = \App\Models\Tenant\Order::query()->where('uuid', $orderNumber)->first();

        if (!$order) {
            return null;
        }

        $activity = \App\Models\Tenant\OrderActivity::query()
            ->where('order_id', $order->id)
            ->where('status', 'delivered')
            ->latest('id')
            ->first();

        return $activity?->created_at;
    }

    protected function storeMedia(ReturnRequest $returnRequest, UploadedFile $file, string $type): ReturnRequestMedia
    {
        $path = $file->store('return-evidence', 'public');

        // Store the full tenant_asset() URL so ReturnRequestMedia::url() works
        // regardless of which tenant's storage the file was written to.
        return ReturnRequestMedia::create([
            'return_request_id' => $returnRequest->id,
            'file_path'         => tenant_asset($path),
            'type'              => $type,
        ]);
    }

    /**
     * Fan-out notification to tenant, admin, and (optionally) the customer via email for a status transition.
     */
    protected function notify(ReturnRequest $returnRequest, string $title, string $message, bool $emailCustomer = false): void
    {
        $tenant = Tenant::find($returnRequest->tenant_id);

        if ($tenant) {
            $this->tenantNotificationService->notify(
                $tenant,
                'return_request',
                $title,
                $message,
                ['return_request_id' => $returnRequest->id, 'order_number' => $returnRequest->order_number],
            );
        }

        $this->adminNotificationService->notify(
            'return_request',
            $title,
            sprintf('%s (Order %s, Tenant %s)', $message, $returnRequest->order_number, $tenant?->name ?? $returnRequest->tenant_id),
            ['return_request_id' => $returnRequest->id],
        );

        if ($emailCustomer && $tenant) {
            tenancy()->initialize($tenant);

            $order = \App\Models\Tenant\Order::query()->where('uuid', $returnRequest->order_number)->first();

            if ($order) {
                $this->mailService->sendReturnStatusUpdate($returnRequest, $order);
            }
        }
    }
}
