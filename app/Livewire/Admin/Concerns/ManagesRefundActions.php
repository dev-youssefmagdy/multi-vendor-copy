<?php

namespace App\Livewire\Admin\Concerns;

use App\Enums\RefundStatus;
use App\Exceptions\OrderActionException;
use App\Exceptions\RefundException;
use App\Models\Refund;
use App\Services\Admin\OrderAfterSalesService;
use App\Services\Refunds\RefundActor;
use App\Services\Refunds\RefundService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Refund row actions of the admin order / return pages (retry, mark completed, reject) — the same
 * actions the vendor panel offers, performed as the central admin. Needs InteractsWithAdminUi and
 * AuthorizesAdminPermissions (AdminPage) on the component.
 */
trait ManagesRefundActions
{
    public ?int $completeRefundId = null;

    public string $completeReference = '';

    public ?int $rejectRefundId = null;

    public string $rejectRefundReason = '';

    /** The refunds this page may act on (never trust a posted id alone). */
    abstract protected function refundsScope(): Builder;

    /** Re-read whatever the page shows after a refund changed. */
    abstract protected function afterRefundAction(): void;

    public function retryRefund(int $refundId, OrderAfterSalesService $service): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);
        $refund = $this->findRefund($refundId);
        $result = null;

        if (! $this->attemptRefundAction(function () use ($service, $refund, &$result): void {
            $result = $service->retryRefund($refund, $this->refundActor());
        })) {
            $this->afterRefundAction();

            return;
        }

        $this->afterRefundAction();
        $this->toastForRefund($result);
    }

    public function openCompleteRefund(int $refundId): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);

        $this->findRefund($refundId);
        $this->resetValidation();
        $this->completeReference = '';
        $this->completeRefundId = $refundId;
    }

    public function closeCompleteRefund(): void
    {
        $this->completeRefundId = null;
        $this->resetValidation();
    }

    public function completeRefund(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);
        $this->validate(['completeReference' => ['nullable', 'string', 'max:190']]);

        $refund = $this->findRefund((int) $this->completeRefundId);

        if (! $this->attemptRefundAction(fn () => app(RefundService::class)->markCompletedManually($refund, $this->refundActor(), null, $this->completeReference ?: null))) {
            $this->closeCompleteRefund();
            $this->afterRefundAction();

            return;
        }

        $this->closeCompleteRefund();
        $this->afterRefundAction();
        $this->toast(__('Refund marked as completed.'));
    }

    public function openRejectRefund(int $refundId): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);

        $this->findRefund($refundId);
        $this->resetValidation();
        $this->rejectRefundReason = '';
        $this->rejectRefundId = $refundId;
    }

    public function closeRejectRefund(): void
    {
        $this->rejectRefundId = null;
        $this->resetValidation();
    }

    public function rejectRefund(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);
        $this->validate(['rejectRefundReason' => ['required', 'string', 'max:1000']], [
            'rejectRefundReason.required' => __('A reason is required to reject a refund.'),
        ]);

        $refund = $this->findRefund((int) $this->rejectRefundId);

        if (! $this->attemptRefundAction(fn () => app(RefundService::class)->reject($refund, $this->refundActor(), $this->rejectRefundReason))) {
            $this->closeRejectRefund();
            $this->afterRefundAction();

            return;
        }

        $this->closeRejectRefund();
        $this->afterRefundAction();
        $this->toast(__('Refund rejected.'));
    }

    private function findRefund(int $refundId): Refund
    {
        return $this->refundsScope()->findOrFail($refundId);
    }

    protected function refundActor(): RefundActor
    {
        $admin = $this->adminUser();

        return RefundActor::admin($admin?->id, $admin?->name);
    }

    /** Run a refund action; rule violations become an error toast. */
    protected function attemptRefundAction(callable $action): bool
    {
        try {
            $action();
        } catch (RefundException|OrderActionException $e) {
            $this->toast($e->getMessage(), 'error');

            return false;
        }

        return true;
    }

    protected function toastForRefund(?Refund $refund): void
    {
        match ($refund?->status) {
            RefundStatus::Completed => $this->toast(__('Refund completed.')),
            RefundStatus::Failed => $this->toast((string) ($refund->failure_reason ?: __('The refund could not be processed.')), 'error'),
            default => $this->toast(__('Refund created. Mark it as completed once the money has been returned to the customer.'), 'warning'),
        };
    }
}
