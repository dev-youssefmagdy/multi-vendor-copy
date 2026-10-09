<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Sales;

use App\Enums\RefundStatus;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Sales\CompleteRefundRequest;
use App\Http\Requests\Tenant\Panel\Sales\RejectRefundRequest;
use App\Models\Refund;
use App\Models\Tenant\AdminUser;
use App\Services\Refunds\RefundActor;
use App\Services\Refunds\RefundService;
use App\Support\Tenant\Refunds\RefundPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Vendor actions on a single refund (RETURN_EXCHANGE_REFUND_PLAN.md B.4 / B.8). Refunds live in
 * the central DB; every action is guarded to the current tenant. RefundException (wrong state,
 * manual-only, …) renders itself as a 422 with a user-safe message.
 */
final class RefundController extends PanelController
{
    public function __construct(
        private readonly RefundService $refunds,
    ) {}

    /** Send a pending refund to the gateway, or a failed one again. */
    public function retry(int $id): JsonResponse
    {
        $refund = $this->findGuarded($id);

        $refund = $refund->status === RefundStatus::Pending
            ? $this->refunds->execute($refund, $this->actor())
            : $this->refunds->retry($refund, $this->actor());

        return $refund->status === RefundStatus::Completed
            ? $this->success(__('Refund completed.'), ['refund' => RefundPresenter::panel($refund)])
            : $this->failure((string) ($refund->failure_reason ?: __('The refund could not be processed.')), 422, [], null, 'warning');
    }

    public function validateComplete(CompleteRefundRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    /** The money was returned outside the system (bank transfer, cash, …). */
    public function complete(CompleteRefundRequest $request, int $id): JsonResponse
    {
        $refund = $this->refunds->markCompletedManually(
            $this->findGuarded($id),
            $this->actor(),
            null,
            $request->validated('reference'),
        );

        return $this->success(__('Refund marked as completed.'), ['refund' => RefundPresenter::panel($refund)]);
    }

    public function validateReject(RejectRefundRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function reject(RejectRefundRequest $request, int $id): JsonResponse
    {
        $refund = $this->refunds->reject($this->findGuarded($id), $this->actor(), (string) $request->validated('reason'));

        return $this->success(__('Refund rejected.'), ['refund' => RefundPresenter::panel($refund)]);
    }

    private function findGuarded(int $id): Refund
    {
        $refund = Refund::query()->findOrFail($id);

        if ((string) $refund->tenant_id !== (string) tenant()->id) {
            abort(403);
        }

        return $refund;
    }

    private function actor(): RefundActor
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();

        return RefundActor::vendor($admin?->id, $admin?->name);
    }
}
