<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Enums\OrderStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundStatus;
use App\Exceptions\OrderActionException;
use App\Models\Refund;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use App\Services\AdminNotificationService;
use App\Services\Mail\TemplateMailService;
use App\Services\Refunds\RefundService;
use App\Services\Tenant\StockService;
use App\Services\TenantNotificationService;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Cancels tenant orders (RETURN_EXCHANGE_REFUND_PLAN.md B.3.3). Must run in the order's tenant
 * context (the API / storefront / panel already are; central callers wrap it in $tenant->run()).
 *
 *  1. Lock the order row and re-evaluate OrderCancellationPolicy inside the lock (no double submit).
 *  2. Status Cancelled + cancelled_at / reason / note / cancelled_by_*.
 *  3. Order activity "Order cancelled".
 *  4. Give back the stock when it was deducted (StockService::restoreForOrder(), idempotent).
 *  5. After commit: a cancellation refund for collected payments; executed right away when the
 *     tenant policy auto_refund_on_cancel is on and the refund can go to the original gateway.
 *  6. After commit: vendor + admin notifications and the customer TenantOrderCancelled email.
 *
 * Refund / notification failures never undo a cancellation: they are logged (a failed refund
 * stays `failed` for the vendor / admin to retry or settle manually).
 */
class OrderCancellationService
{
    public const NOTE_MAX_LENGTH = 1000;

    public function __construct(
        private readonly OrderCancellationPolicy $policy,
        private readonly OrderPolicyService $policyService,
        private readonly StockService $stockService,
        private readonly RefundService $refundService,
        private readonly TemplateMailService $mailService,
        private readonly TenantNotificationService $tenantNotifier,
        private readonly AdminNotificationService $adminNotifier,
    ) {}

    /**
     * @param  int|null  $actorId  the actor's id in its own guard (customer / tenant admin / central admin)
     *
     * @throws OrderActionException when the actor may not cancel the order (policy message) or the
     *                              reason / note are invalid for the actor
     */
    public function cancel(
        Order $order,
        CancellationActor $actor,
        ?int $actorId,
        CancellationReason $reason,
        ?string $note = null,
    ): Order {
        $tenant = $this->currentTenant();
        $note = $this->normalizeNote($note);

        if (! $reason->isAllowedFor($actor)) {
            throw OrderActionException::invalidReason();
        }

        if ($reason->requiresNote() && $note === null) {
            throw OrderActionException::noteRequired();
        }

        $policy = $this->policyService->all($tenant);

        /** @var Order $cancelled */
        $cancelled = DB::transaction(function () use ($order, $actor, $actorId, $reason, $note, $policy): Order {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            if (! $locked) {
                throw OrderActionException::notFound();
            }

            $decision = $this->policy->evaluate($locked, $actor, $policy);

            if (! $decision->allowed) {
                throw OrderActionException::fromDecision($decision);
            }

            $locked->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'cancellation_note' => $note,
                'cancelled_by_type' => $actor,
                'cancelled_by_id' => $actorId,
            ]);

            $this->appendActivity($locked, 'Order cancelled', trim(sprintf(
                'Cancelled by %s. Reason: %s.%s',
                $actor->label(),
                $reason->label(),
                $note !== null ? ' '.$note : '',
            )));

            // Last step inside the transaction: the central stock moves commit on their own, so
            // nothing after them may roll the tenant side back.
            if ($locked->needsStockRestore()) {
                $this->stockService->restoreForOrder($locked);
            }

            return $locked;
        });

        $this->afterCommit(function () use ($cancelled, $actor, $actorId, $tenant, $policy): void {
            $this->refundCancelledOrder($cancelled, $actor, $actorId, $tenant, (bool) $policy[OrderPolicyService::AUTO_REFUND_ON_CANCEL]);
            $this->notifyCancelled($cancelled->fresh() ?? $cancelled, $tenant);
        });

        return $order->refresh();
    }

    /**
     * A payment confirmation (gateway success callback / webhook) arrived for an order that was
     * already cancelled: record the payment (paid + payment_details), keep the status, move no
     * stock, and refund it straight away (cancellation refund, executed now when the gateway can
     * refund; otherwise it stays pending for a manual refund).
     *
     * Returns false (and does nothing) when the order is not cancelled — the caller then runs the
     * normal payment confirmation.
     *
     * @param  array<string, mixed>  $paymentDetails
     */
    public function recordPaymentAfterCancellation(Order $order, array $paymentDetails): bool
    {
        $tenant = $this->currentTenant();

        // Atomic claim instead of a locked transaction: a query-builder update records the payment
        // only while the order is still cancelled and unpaid (so a duplicate callback is a no-op),
        // and fires no model events — no marketplace invoice is issued for a void order. (The paid
        // observer switches to the central context, which would drop an open tenant transaction.)
        $claimed = Order::query()
            ->whereKey($order->getKey())
            ->where('paid', false)
            ->whereIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value])
            ->update([
                'paid' => true,
                'payment_details' => json_encode($paymentDetails),
                'updated_at' => now(),
            ]) === 1;

        if (! $claimed) {
            return false;
        }

        $recorded = Order::query()->with('items')->findOrFail($order->getKey());

        $this->appendActivity(
            $recorded,
            'Payment received after cancellation — refund initiated',
            sprintf(
                'A payment (%s) arrived for this order after it was cancelled. The order stays cancelled and the payment is being refunded.',
                $paymentDetails['transaction_id'] ?? '-',
            ),
        );

        $this->afterCommit(function () use ($recorded, $tenant): void {
            $this->refundCancelledOrder($recorded, CancellationActor::System, null, $tenant, autoExecute: true);

            $this->notifyStaff(
                $tenant,
                'Payment received after cancellation',
                sprintf('A payment arrived for the cancelled order #%s. A refund was initiated automatically.', $recorded->uuid),
                ['order_number' => $recorded->uuid],
            );
        });

        $order->refresh();

        return true;
    }

    // ─── Internals ──────────────────────────────────────────────────────────

    /** Step 5: refund collected money; never throws. */
    private function refundCancelledOrder(Order $order, CancellationActor $actor, ?int $actorId, Tenant $tenant, bool $autoExecute): ?Refund
    {
        try {
            $fresh = Order::query()->with('items')->find($order->getKey());

            if (! $fresh || ! $fresh->isPaymentCollected()) {
                return null;
            }

            $refund = $this->refundService->requestForCancellation($fresh, $actor, $actorId, $tenant);

            if ($refund
                && $autoExecute
                && $refund->status === RefundStatus::Pending
                && $refund->refund_method === RefundMethod::OriginalPayment) {
                $refund = $this->refundService->execute($refund, CancellationActor::System);
            }

            return $refund;
        } catch (Throwable $e) {
            Log::warning('Cancellation refund could not be created', [
                'tenant_id' => $tenant->getTenantKey(),
                'order_number' => $order->uuid,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** Step 6: vendor + admin notifications, customer email. Never throws. */
    private function notifyCancelled(Order $order, Tenant $tenant): void
    {
        $actor = $order->cancelled_by_type instanceof CancellationActor ? $order->cancelled_by_type : CancellationActor::System;
        $reason = $order->cancellation_reason instanceof CancellationReason ? $order->cancellation_reason->label() : '-';

        $this->notifyStaff(
            $tenant,
            'Order cancelled',
            sprintf('Order #%s was cancelled by %s. Reason: %s.', $order->uuid, Str::lower($actor->label()), $reason),
            [
                'order_number' => $order->uuid,
                'reason' => $order->cancellation_reason?->value,
                'cancelled_by' => $actor->value,
            ],
        );

        try {
            $this->mailService->sendTenantOrderCancelled($order);
        } catch (Throwable $e) {
            Log::warning('Order cancellation email failed', ['order_number' => $order->uuid, 'error' => $e->getMessage()]);
        }
    }

    /** @param array<string, mixed> $data */
    private function notifyStaff(Tenant $tenant, string $title, string $message, array $data): void
    {
        try {
            $this->tenantNotifier->notify($tenant, 'order', $title, $message, $data);

            $this->adminNotifier->notify(
                'order',
                $title,
                sprintf('%s (Tenant %s)', $message, $tenant->getTenantKey()),
                $data + ['tenant_id' => $tenant->getTenantKey()],
            );
        } catch (Throwable $e) {
            Log::warning('Order notification failed', ['tenant_id' => $tenant->getTenantKey(), 'title' => $title, 'error' => $e->getMessage()]);
        }
    }

    private function appendActivity(Order $order, string $title, string $description): void
    {
        $order->activities()->create([
            'status' => $order->status?->value,
            'title' => $title,
            'description' => $description,
        ]);
    }

    private function normalizeNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : Str::limit($note, self::NOTE_MAX_LENGTH, '');
    }

    private function currentTenant(): Tenant
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('OrderCancellationService must run inside the order\'s tenant context.');
        }

        return $tenant;
    }

    /** Run side effects once the surrounding transaction (if any) commits, in this tenant. */
    private function afterCommit(Closure $callback): void
    {
        DB::afterCommit($callback);
    }
}
