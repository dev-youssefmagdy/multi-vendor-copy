<?php

declare(strict_types=1);

namespace App\Services\Refunds;

use App\Enums\CancellationActor;
use App\Enums\OrderStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Exceptions\RefundException;
use App\Models\AdminUser as CentralAdminUser;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestNote;
use App\Models\Tenant;
use App\Models\Tenant\AdminUser as TenantAdminUser;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\PaymentGateway\Exceptions\PaymentException;
use App\PaymentGateway\PaymentManager;
use App\Services\AdminNotificationService;
use App\Services\Mail\TemplateMailService;
use App\Services\ReturnPolicyService;
use App\Services\ReturnRequestService;
use App\Services\TenantNotificationService;
use App\Support\Tenancy\RunsInTenant;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Refund engine (RETURN_EXCHANGE_REFUND_PLAN.md B.4 / B.9).
 *
 * Refund rows live in the central DB; the order they refund lives in the tenant DB. Every
 * public method works from the tenant context AND from the central context: tenant work runs
 * through inTenant(), which (like $tenant->run()) restores the previous context — also when
 * an exception is thrown.
 *
 * Lifecycle:  pending ──execute──> processing ──> completed
 *                │                       └──────> failed ──retry──> processing …
 *                ├──markCompletedManually──> completed   (also from failed)
 *                └──reject──> rejected                   (also from failed)
 *
 * Status changes are claimed with a conditional UPDATE (… WHERE status IN (…)), so two
 * concurrent executions of the same refund can never both reach the gateway. The
 * no-double-refund guard runs under a row lock on the tenant order.
 *
 * Notifications and emails are sent after the surrounding DB transaction commits.
 */
class RefundService
{
    use RunsInTenant;

    /** Payment method / gateway codes that never go through a gateway refund. */
    private const OFFLINE_METHODS = ['cod', 'cash_on_delivery', 'cash', 'bank_transfer', 'manual'];

    public function __construct(
        private readonly RefundCalculator $calculator,
        private readonly TemplateMailService $mailService,
        private readonly TenantNotificationService $tenantNotifier,
        private readonly AdminNotificationService $adminNotifier,
        private readonly ReturnPolicyService $returnPolicyService,
    ) {}

    // ─── Creation ───────────────────────────────────────────────────────────

    /**
     * Create a pending refund for an order, guarded against refunding more than the order's
     * grand total (pending + processing + completed refunds count). A refund created by staff
     * (vendor / admin) is approved by them at creation.
     *
     * @param  array{items_amount?: float, shipping_amount?: float, return_fee?: float, return_request_id?: int|null, refund_method?: RefundMethod|string|null, notes?: string|null, meta?: array<string, mixed>}  $attributes
     *
     * @throws RefundException
     */
    public function create(
        Order $order,
        RefundSource $source,
        float $amount,
        string $reason,
        CancellationActor|RefundActor $requestedBy,
        ?int $requestedById = null,
        array $attributes = [],
        ?Tenant $tenant = null,
    ): Refund {
        $tenant = $this->tenantFor($tenant);
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw RefundException::invalidAmount();
        }

        $refund = $this->inTenant($tenant, function () use ($tenant, $order, $source, $amount, $reason, $requestedBy, $requestedById, $attributes): Refund {
            $actor = $this->resolveActorName(RefundActor::from($requestedBy, $requestedById));

            return DB::transaction(function () use ($tenant, $order, $source, $amount, $reason, $actor, $attributes): Refund {
                $locked = $this->lockOrder((string) $order->uuid);
                $this->assertRefundable($locked);

                $remaining = $this->remainingFor($locked, (string) $tenant->getTenantKey());

                if ($amount > $remaining) {
                    throw RefundException::exceedsRefundable($remaining);
                }

                [$method, $gateway, $transactionId] = $this->resolveRefundMethod($locked, $attributes['refund_method'] ?? null);

                $refund = new Refund([
                    'tenant_id' => (string) $tenant->getTenantKey(),
                    'order_number' => (string) $locked->uuid,
                    'return_request_id' => $attributes['return_request_id'] ?? null,
                    'source' => $source,
                    'reason' => Str::limit(trim($reason) !== '' ? trim($reason) : $source->label(), 250, ''),
                    'currency' => 'USD', // payments are always charged in USD (PaymentController::charge)
                    'items_amount' => round((float) ($attributes['items_amount'] ?? $amount), 2),
                    'shipping_amount' => round((float) ($attributes['shipping_amount'] ?? 0), 2),
                    'return_fee' => round((float) ($attributes['return_fee'] ?? 0), 2),
                    'amount' => $amount,
                    'payment_method' => $locked->payment_method,
                    'gateway' => $gateway,
                    'original_transaction_id' => $transactionId,
                    'refund_method' => $method,
                    'status' => RefundStatus::Pending,
                    'requested_by_type' => $actor->type,
                    'requested_by_id' => $actor->id,
                    'notes' => $attributes['notes'] ?? null,
                    'meta' => ($attributes['meta'] ?? []) ?: null,
                ]);
                $refund->reference = Refund::generateReference();

                if ($actor->isStaff()) {
                    $refund->fill($this->approvalAttributes($actor));
                }

                $this->appendActivity($locked, 'Refund requested', sprintf(
                    'Refund %s of %s requested (%s, %s).',
                    $refund->reference,
                    $this->money($amount, $refund->currency),
                    $source->label(),
                    $method->label(),
                ));

                // Central write last, so a failure above rolls the tenant side back cleanly.
                $refund->save();

                return $refund;
            });
        });

        $this->afterCommit($tenant, fn () => $this->notifyCreated($refund));

        return $refund->refresh();
    }

    /**
     * Refund what is left on a cancelled order (grand total − refunds already counted), fee 0,
     * source = cancellation. Returns null when nothing is left to refund. Executing it
     * (immediately or later) is up to the caller — see execute().
     *
     * @throws RefundException when the order is unpaid or already refunded
     */
    public function requestForCancellation(
        Order $order,
        CancellationActor|RefundActor $actor,
        ?int $actorId = null,
        ?Tenant $tenant = null,
    ): ?Refund {
        $tenant = $this->tenantFor($tenant);

        $prepared = $this->inTenant($tenant, function () use ($order, $tenant): ?array {
            $fresh = $this->findOrder((string) $order->uuid);
            $this->assertRefundable($fresh);

            $remaining = $this->remainingFor($fresh, (string) $tenant->getTenantKey());

            if ($remaining <= 0) {
                return null;
            }

            $calculated = $this->calculator->forCancellation($fresh);
            $amount = min($remaining, $calculated->amount);
            $shipping = min($calculated->shippingAmount, $amount);

            $reason = $fresh->cancellation_reason?->label() ?? 'Order cancelled';

            if (filled($fresh->cancellation_note)) {
                $reason .= ': '.$fresh->cancellation_note;
            }

            return [
                'order' => $fresh,
                'amount' => $amount,
                'reason' => $reason,
                'attributes' => [
                    'items_amount' => round($amount - $shipping, 2),
                    'shipping_amount' => $shipping,
                    'return_fee' => 0.0,
                    'meta' => ['calculated' => $calculated->toArray()],
                ],
            ];
        });

        if ($prepared === null || $prepared['amount'] <= 0) {
            return null;
        }

        return $this->create(
            $prepared['order'],
            RefundSource::Cancellation,
            $prepared['amount'],
            $prepared['reason'],
            $actor,
            $actorId,
            $prepared['attributes'],
            $tenant,
        );
    }

    /**
     * Refund for a return request (source = return, linked to the request). $amount may be
     * lowered by the reviewer (partial refund after inspection) but never exceed the
     * calculated maximum (see calculateForReturn()). Only one pending/processing/completed
     * refund may exist per return request.
     *
     * @throws RefundException
     */
    public function requestForReturn(
        ReturnRequest $returnRequest,
        float $amount,
        CancellationActor|RefundActor $actor,
        ?int $actorId = null,
    ): Refund {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw RefundException::invalidAmount();
        }

        if (Refund::query()->where('return_request_id', $returnRequest->id)->reserving()->exists()) {
            throw RefundException::alreadyRequested();
        }

        $tenant = $this->tenantForId((string) $returnRequest->tenant_id);
        [$order, $calculated] = $this->inTenant($tenant, fn () => $this->calculateReturn($returnRequest, null));

        if ($amount > $calculated->max) {
            throw RefundException::exceedsCalculated($calculated->max);
        }

        // Lowered by the reviewer: keep shipping and fee, reduce the goods value.
        $shipping = $calculated->shippingAmount;
        $fee = $calculated->returnFee;
        $items = round($amount - $shipping + $fee, 2);

        if ($items < 0) {
            $items = 0.0;
            $shipping = round($amount + $fee, 2);
        }

        return $this->create(
            $order,
            RefundSource::Return,
            $amount,
            ($returnRequest->reason ?? ReturnReason::Other)->label(),
            $actor,
            $actorId,
            [
                'return_request_id' => $returnRequest->id,
                'items_amount' => $items,
                'shipping_amount' => $shipping,
                'return_fee' => $fee,
                'meta' => [
                    'calculated' => $calculated->toArray(),
                    'adjusted_by_reviewer' => $amount < $calculated->amount,
                    'quantity' => (int) ($returnRequest->quantity ?: 1),
                ],
            ],
            $tenant,
        );
    }

    /**
     * The calculated refund for a return request (policy fee, seller-fault waiver, shipping
     * when the whole order comes back, capped at what's left on the order). Use `max` to
     * prefill the reviewer's amount.
     */
    public function calculateForReturn(ReturnRequest $returnRequest, ?bool $wholeOrder = null): RefundBreakdown
    {
        $tenant = $this->tenantForId((string) $returnRequest->tenant_id);

        return $this->inTenant($tenant, fn () => $this->calculateReturn($returnRequest, $wholeOrder)[1]);
    }

    /**
     * Estimated refund for a return the customer has not submitted yet (storefront preview):
     * the same calculation as calculateForReturn(), for an unsaved request of $quantity units
     * of $item. Nothing is stored.
     */
    public function estimateForReturn(Order $order, OrderItem $item, int $quantity, ReturnReason $reason, ?Tenant $tenant = null): RefundBreakdown
    {
        $tenant = $this->tenantFor($tenant);

        $draft = new ReturnRequest([
            'tenant_id' => (string) $tenant->getTenantKey(),
            'order_number' => (string) $order->uuid,
            'order_item_id' => $item->id,
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => max(1, min($quantity, (int) $item->qty)),
            'reason' => $reason,
        ]);

        return $this->inTenant($tenant, fn () => $this->calculateReturn($draft, null)[1]);
    }

    /**
     * What can still be refunded on an order right now: grand total minus completed refunds
     * and minus pending/processing refunds (0 when no payment was collected or the order is
     * already refunded).
     */
    public function refundableAmount(Order $order, ?Tenant $tenant = null): float
    {
        $tenant = $this->tenantFor($tenant);

        return $this->inTenant($tenant, function () use ($order, $tenant): float {
            $fresh = $this->findOrder((string) $order->uuid);

            if (! $fresh->isPaymentCollected() || $fresh->status === OrderStatus::Refunded) {
                return 0.0;
            }

            return $this->remainingFor($fresh, (string) $tenant->getTenantKey());
        });
    }

    // ─── Execution ──────────────────────────────────────────────────────────

    /**
     * Send a pending refund to the original payment gateway. The actor (default: system,
     * e.g. auto-refund on cancel) becomes the approver when nobody approved it yet.
     * Gateway errors never throw: the refund ends up `failed` with a safe reason.
     *
     * @throws RefundException when the refund isn't pending or must be completed manually
     */
    public function execute(Refund $refund, CancellationActor|RefundActor|null $actor = null, ?int $actorId = null): Refund
    {
        return $this->runGateway($refund, $actor ? RefundActor::from($actor, $actorId) : RefundActor::system(), [RefundStatus::Pending], 'executed');
    }

    /**
     * Send a failed refund to the gateway again.
     *
     * @throws RefundException when the refund isn't failed or must be completed manually
     */
    public function retry(Refund $refund, CancellationActor|RefundActor $actor, ?int $actorId = null): Refund
    {
        return $this->runGateway($refund, RefundActor::from($actor, $actorId), [RefundStatus::Failed], 'retried');
    }

    /**
     * The money was returned outside the system (bank transfer, cash, …): mark a pending or
     * failed refund completed with refund_method = manual and an optional external reference
     * (meta.manual_reference).
     *
     * @throws RefundException
     */
    public function markCompletedManually(
        Refund $refund,
        CancellationActor|RefundActor $actor,
        ?int $actorId = null,
        ?string $reference = null,
    ): Refund {
        $tenant = $this->tenantForId((string) $refund->tenant_id);
        $actor = $this->inTenant($tenant, fn () => $this->resolveActorName(RefundActor::from($actor, $actorId)));

        $this->assertFailedRefundStillFits($refund, $tenant);

        if (! $this->claim($refund, [RefundStatus::Pending, RefundStatus::Failed], RefundStatus::Processing, $actor)) {
            throw RefundException::invalidState('completed');
        }

        $refund->refresh();

        return $this->complete($refund, [
            'refund_method' => RefundMethod::Manual,
            'meta' => array_merge($refund->meta ?? [], array_filter([
                'manual_reference' => filled($reference) ? Str::limit(trim((string) $reference), 190, '') : null,
                'completed_by' => $actor->toArray(),
            ], fn ($value) => $value !== null)),
        ], 'Completed manually');
    }

    /**
     * Refuse a pending or failed refund. The reason is stored in failure_reason (shown to
     * the customer) and meta.rejection.
     *
     * @throws RefundException
     */
    public function reject(Refund $refund, CancellationActor|RefundActor $actor, string $reason, ?int $actorId = null): Refund
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new RefundException(__('A reason is required to reject a refund.'));
        }

        $tenant = $this->tenantForId((string) $refund->tenant_id);
        $actor = $this->inTenant($tenant, fn () => $this->resolveActorName(RefundActor::from($actor, $actorId)));

        if (! $this->claim($refund, [RefundStatus::Pending, RefundStatus::Failed], RefundStatus::Rejected)) {
            throw RefundException::invalidState('rejected');
        }

        $refund->refresh();
        $refund->forceFill([
            'failure_reason' => $reason,
            'meta' => array_merge($refund->meta ?? [], [
                'rejection' => $actor->toArray() + ['reason' => $reason, 'at' => now()->toIso8601String()],
            ]),
        ])->save();

        $this->inTenant($tenant, function () use ($refund, $reason): void {
            $order = $this->findOrder((string) $refund->order_number, false);

            if ($order) {
                $this->appendActivity($order, 'Refund rejected', sprintf('Refund %s was rejected. Reason: %s', $refund->reference, $reason));
            }
        });

        return $refund->refresh();
    }

    // ─── Internals: gateway / completion / failure ───────────────────────────

    /** @param list<RefundStatus> $from */
    private function runGateway(Refund $refund, RefundActor $actor, array $from, string $action): Refund
    {
        $refund->refresh();

        if ($refund->refund_method === RefundMethod::Manual) {
            throw RefundException::manualOnly();
        }

        $tenant = $this->tenantForId((string) $refund->tenant_id);
        $actor = $this->inTenant($tenant, fn () => $this->resolveActorName($actor));

        $this->assertFailedRefundStillFits($refund, $tenant);

        if (! $this->claim($refund, $from, RefundStatus::Processing, $actor)) {
            throw RefundException::invalidState($action);
        }

        $refund->refresh();

        // Gateway credentials are tenant-specific, so resolve and call the gateway in the tenant.
        $outcome = $this->inTenant($tenant, fn (): array => $this->callGateway($refund));

        if ($outcome['success']) {
            return $this->complete($refund, [
                'gateway_refund_id' => $outcome['gateway_refund_id'],
                'meta' => $this->withAttempt($refund, 'completed', $outcome['raw'] ?? null),
            ], 'Refunded to the original payment method');
        }

        return $this->fail($refund, $outcome['reason'], $outcome['code']);
    }

    /** @return array{success: bool, gateway_refund_id?: string|null, raw?: string|null, reason?: string, code?: string} */
    private function callGateway(Refund $refund): array
    {
        $context = [
            'order_id' => $refund->order_number,
            'refund_reference' => $refund->reference,
            'tenant_id' => $refund->tenant_id,
        ];

        if (! filled($refund->gateway) || ! filled($refund->original_transaction_id)) {
            return [
                'success' => false,
                'code' => 'missing_transaction',
                'reason' => __('The original payment reference is missing, so the gateway cannot refund it. Please refund the customer manually and mark the refund as completed.'),
            ];
        }

        try {
            $manager = app(PaymentManager::class);
            $manager->forget($refund->gateway);

            $result = $manager->gateway($refund->gateway)->refund(
                (string) $refund->original_transaction_id,
                (float) $refund->amount,
                $refund->currency ?: 'USD',
                $context,
            );
        } catch (PaymentException $e) {
            $this->logGatewayError($refund, $e->getMessage(), $e);

            if ($e->isNotSupported()) {
                return [
                    'success' => false,
                    'code' => 'not_supported',
                    'reason' => __('This payment method does not support automatic refunds. Please refund the customer manually and mark the refund as completed.'),
                ];
            }

            return [
                'success' => false,
                'code' => $e->getCode() === PaymentException::GATEWAY_NOT_FOUND ? 'gateway_unavailable' : 'gateway_error',
                'reason' => $e->getCode() === PaymentException::GATEWAY_NOT_FOUND
                    ? __('The payment gateway used for this order is no longer configured. Please refund the customer manually and mark the refund as completed.')
                    : __('The refund could not be processed because of a payment gateway error. Please retry or complete it manually.'),
            ];
        } catch (Throwable $e) {
            $this->logGatewayError($refund, $e->getMessage(), $e);

            return [
                'success' => false,
                'code' => 'gateway_error',
                'reason' => __('The refund could not be processed because of a payment gateway error. Please retry or complete it manually.'),
            ];
        }

        if (! $result->success) {
            $this->logGatewayError($refund, (string) ($result->errorMessage ?? 'unknown gateway failure'));

            return [
                'success' => false,
                'code' => 'declined',
                'reason' => __('The payment gateway declined the refund. Please retry or complete it manually.'),
            ];
        }

        return [
            'success' => true,
            'gateway_refund_id' => $result->transactionId,
            'raw' => $result->rawResponse,
        ];
    }

    /**
     * Finalise a claimed (processing) refund: mark it completed and propagate it to the order
     * (refunded_amount, refunded_at, status Refunded unless the order is cancelled, activity),
     * then the linked return request, then notifications after commit.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function complete(Refund $refund, array $attributes, string $how): Refund
    {
        $tenant = $this->tenantForId((string) $refund->tenant_id);

        $this->inTenant($tenant, function () use ($refund, $attributes, $how): void {
            DB::transaction(function () use ($refund, $attributes, $how): void {
                $order = $this->lockOrder((string) $refund->order_number);

                $refundedAmount = round((float) $order->refunded_amount + (float) $refund->amount, 2);
                $updates = ['refunded_amount' => $refundedAmount];

                // A retained return fee is money the store keeps by policy: once refunds + retained fees
                // cover the order, nothing is left to give back, so the order counts as fully refunded.
                $retainedFees = (float) Refund::query()
                    ->forOrder((string) $refund->tenant_id, (string) $order->uuid)
                    ->where('status', RefundStatus::Completed->value)
                    ->whereKeyNot($refund->getKey())
                    ->sum('return_fee') + (float) $refund->return_fee;

                if ($refundedAmount >= $order->grand_total
                    || ($retainedFees > 0 && round($refundedAmount + $retainedFees, 2) >= $order->grand_total)) {
                    $updates['refunded_at'] = $order->refunded_at ?? now();

                    // A cancelled (or rejected) order stays cancelled; its payment state shows refunded.
                    if (! $order->isCancelled()) {
                        $updates['status'] = OrderStatus::Refunded;
                    }
                }

                $order->update($updates);

                $this->appendActivity($order, 'Refund completed', sprintf(
                    'Refund completed — %s (%s). %s.',
                    $this->money((float) $refund->amount, $refund->currency),
                    $refund->reference,
                    $how,
                ));

                $refund->forceFill(array_merge($attributes, [
                    'status' => RefundStatus::Completed,
                    'processed_at' => now(),
                    'failure_reason' => null,
                ]))->save();
            });
        });

        $this->onReturnRefundCompleted($refund);

        $this->afterCommit($tenant, fn () => $this->notifyCompleted($refund));

        return $refund->refresh();
    }

    private function fail(Refund $refund, string $reason, string $code): Refund
    {
        $tenant = $this->tenantForId((string) $refund->tenant_id);

        $refund->forceFill([
            'status' => RefundStatus::Failed,
            'failure_reason' => $reason,
            'processed_at' => now(),
            'meta' => $this->withAttempt($refund, 'failed', null, $code),
        ])->save();

        $this->inTenant($tenant, function () use ($refund): void {
            $order = $this->findOrder((string) $refund->order_number, false);

            if ($order) {
                $this->appendActivity($order, 'Refund failed', sprintf(
                    'Refund %s of %s could not be processed: %s',
                    $refund->reference,
                    $this->money((float) $refund->amount, $refund->currency),
                    $refund->failure_reason,
                ));
            }
        });

        $this->afterCommit($tenant, fn () => $this->notifyFailed($refund));

        return $refund->refresh();
    }

    /**
     * Phase 4 hook: a refund linked to a return request completed → move the request to
     * Refunded (when the state machine allows it), record the refunded total and leave a
     * customer-visible system note.
     */
    protected function onReturnRefundCompleted(Refund $refund): void
    {
        if (! $refund->return_request_id) {
            return;
        }

        $returnRequest = ReturnRequest::query()->find($refund->return_request_id);

        if (! $returnRequest) {
            return;
        }

        $completedTotal = (float) Refund::query()
            ->where('return_request_id', $returnRequest->id)
            ->where('status', RefundStatus::Completed->value)
            ->sum('amount');

        $updates = ['refund_amount' => round($completedTotal, 2)];
        $moved = $returnRequest->status instanceof ReturnStatus && $returnRequest->status->canTransitionTo(ReturnStatus::Refunded);

        if ($moved) {
            $updates['status'] = ReturnStatus::Refunded;
        }

        $returnRequest->update($updates);

        ReturnRequestNote::create([
            'return_request_id' => $returnRequest->id,
            'author_type' => ReturnRequestNote::AUTHOR_SYSTEM,
            'author_id' => null,
            'note' => __('Refund :reference of :amount has been completed.', [
                'reference' => $refund->reference,
                'amount' => $this->money((float) $refund->amount, $refund->currency),
            ]),
            'customer_visible' => true,
        ]);

        if ($moved) {
            // Order activity + return notifications / customer email for the Refunded transition.
            // Resolved lazily: ReturnRequestService depends on this service.
            app(ReturnRequestService::class)->recordRefunded($returnRequest->refresh(), $refund);
        }
    }

    // ─── Internals: guards & lookups ─────────────────────────────────────────

    /**
     * Atomically move the refund from one of $from to $to; records the approver when the
     * refund has none yet. Returns false when the refund is no longer in one of $from.
     *
     * @param  list<RefundStatus>  $from
     */
    /**
     * A failed refund no longer reserves its amount, so a replacement refund may have been issued
     * since (e.g. a second "issue refund" on the same return). Retrying / completing the old one
     * must not push the order past what can still be refunded.
     *
     * @throws RefundException
     */
    private function assertFailedRefundStillFits(Refund $refund, Tenant $tenant): void
    {
        $refund->refresh();

        if ($refund->status !== RefundStatus::Failed) {
            return;
        }

        $remaining = $this->inTenant($tenant, function () use ($refund, $tenant): ?float {
            $order = $this->findOrder((string) $refund->order_number, false);

            return $order ? $this->remainingFor($order, (string) $tenant->getTenantKey()) : null;
        });

        if ($remaining !== null && round((float) $refund->amount, 2) > $remaining) {
            throw RefundException::exceedsRefundable($remaining);
        }
    }

    private function claim(Refund $refund, array $from, RefundStatus $to, ?RefundActor $approver = null): bool
    {
        $values = ['status' => $to->value, 'updated_at' => now()];

        $claimed = Refund::query()
            ->whereKey($refund->getKey())
            ->whereIn('status', array_map(fn (RefundStatus $status) => $status->value, $from))
            ->update($values) === 1;

        if ($claimed && $approver) {
            Refund::query()
                ->whereKey($refund->getKey())
                ->whereNull('approved_at')
                ->update(array_map(
                    fn ($value) => $value instanceof CancellationActor ? $value->value : $value,
                    $this->approvalAttributes($approver),
                ));
        }

        return $claimed;
    }

    /** @return array{approved_by_type: CancellationActor, approved_by_id: int|null, approved_by_name: string|null, approved_at: Carbon} */
    private function approvalAttributes(RefundActor $actor): array
    {
        return [
            'approved_by_type' => $actor->type,
            'approved_by_id' => $actor->id,
            'approved_by_name' => $actor->name !== null ? Str::limit($actor->name, 190, '') : null,
            'approved_at' => now(),
        ];
    }

    /**
     * The customer's money must have been collected: a `paid` order, or a cash-on-delivery order
     * that was delivered (the courier collected the cash) — see Order::isPaymentCollected().
     *
     * @throws RefundException
     */
    private function assertRefundable(Order $order): void
    {
        if (! $order->isPaymentCollected()) {
            throw RefundException::notPaid();
        }

        if ($order->status === OrderStatus::Refunded) {
            throw RefundException::orderRefunded();
        }
    }

    /**
     * grand_total − (completed + pending + processing refunds). Completed refunds are taken
     * from the larger of the refund rows and orders.refunded_amount, so neither source can be
     * bypassed.
     */
    private function remainingFor(Order $order, string $tenantId): float
    {
        $refunds = Refund::query()->forOrder($tenantId, (string) $order->uuid);

        $completed = (float) (clone $refunds)->where('status', RefundStatus::Completed->value)->sum('amount');
        $open = (float) (clone $refunds)
            ->whereIn('status', [RefundStatus::Pending->value, RefundStatus::Processing->value])
            ->sum('amount');

        $reserved = max($completed, (float) $order->refunded_amount) + $open;

        return max(0.0, round($order->grand_total - $reserved, 2));
    }

    /** @return array{0: RefundMethod, 1: string|null, 2: string|null} [method, gateway, original transaction id] */
    private function resolveRefundMethod(Order $order, RefundMethod|string|null $forced): array
    {
        $details = is_array($order->payment_details) ? $order->payment_details : [];
        $gateway = $details['gateway'] ?? $order->payment_method;
        $gateway = filled($gateway) ? (string) $gateway : null;
        $transactionId = $details['transaction_id'] ?? null;
        $transactionId = filled($transactionId) ? (string) $transactionId : null;

        $forced = is_string($forced) ? RefundMethod::tryFrom($forced) : $forced;

        // Cash collected by the courier (COD, not flagged paid) can only be returned by hand.
        $useGateway = $forced !== RefundMethod::Manual
            && $order->paid
            && $gateway !== null
            && $transactionId !== null
            && ! in_array(strtolower($gateway), self::OFFLINE_METHODS, true)
            && ! in_array(strtolower((string) $order->payment_method), self::OFFLINE_METHODS, true)
            && app(PaymentManager::class)->supportsRefunds($gateway);

        return [$useGateway ? RefundMethod::OriginalPayment : RefundMethod::Manual, $gateway, $transactionId];
    }

    /** @return array{0: Order, 1: RefundBreakdown} */
    private function calculateReturn(ReturnRequest $returnRequest, ?bool $wholeOrder): array
    {
        $order = $this->findOrder((string) $returnRequest->order_number);
        $item = $this->returnItem($order, $returnRequest);
        $quantity = max(1, min((int) ($returnRequest->quantity ?: 1), (int) $item->qty));

        $fee = (float) ($this->returnPolicyService->resolveProductPolicy(
            (string) $returnRequest->tenant_id,
            $item->product_id ? (int) $item->product_id : null,
        )['fee'] ?? 0);

        $wholeOrder ??= $this->returnCoversWholeOrder($order, $returnRequest);

        return [$order, $this->calculator->forReturn(
            $order,
            $item,
            $quantity,
            $returnRequest->reason ?? ReturnReason::Other,
            $fee,
            $wholeOrder,
        )];
    }

    private function returnItem(Order $order, ReturnRequest $returnRequest): OrderItem
    {
        $items = $order->items;

        $item = ($returnRequest->order_item_id ? $items->firstWhere('id', (int) $returnRequest->order_item_id) : null)
            ?? ($returnRequest->product_variant_id ? $items->firstWhere('product_variant_id', (int) $returnRequest->product_variant_id) : null)
            ?? ($returnRequest->product_id ? $items->firstWhere('product_id', (int) $returnRequest->product_id) : null)
            ?? ($items->count() === 1 ? $items->first() : null);

        if (! $item instanceof OrderItem) {
            throw new RefundException(__('The returned item could not be found on the order.'), RefundException::NOT_FOUND);
        }

        return $item;
    }

    /**
     * Whether every unit of every order line is covered by return requests that hold quantity
     * (this one included). Null (let the calculator infer) for legacy requests without item links.
     */
    private function returnCoversWholeOrder(Order $order, ReturnRequest $returnRequest): ?bool
    {
        if (! $returnRequest->order_item_id) {
            return null;
        }

        $held = ReturnRequest::query()
            ->where('tenant_id', $returnRequest->tenant_id)
            ->where('order_number', $returnRequest->order_number)
            ->whereNotNull('order_item_id')
            ->where(fn ($query) => $query
                ->whereIn('status', array_map(fn (ReturnStatus $status) => $status->value, ReturnStatus::quantityHoldingStatuses()))
                ->orWhere('id', $returnRequest->id))
            ->get(['id', 'order_item_id', 'quantity'])
            ->groupBy('order_item_id')
            ->map(fn ($rows) => (int) $rows->sum('quantity'));

        // An unsaved request (estimateForReturn() preview) isn't in the query above yet.
        if (! $returnRequest->exists) {
            $itemId = (int) $returnRequest->order_item_id;
            $held[$itemId] = (int) ($held[$itemId] ?? 0) + (int) $returnRequest->quantity;
        }

        return $order->items->every(fn (OrderItem $item) => ($held[$item->id] ?? 0) >= (int) $item->qty);
    }

    private function lockOrder(string $uuid): Order
    {
        $order = Order::query()->where('uuid', $uuid)->lockForUpdate()->first();

        if (! $order) {
            throw RefundException::notFound();
        }

        return $order->load('items');
    }

    /** @return ($required is true ? Order : Order|null) */
    private function findOrder(string $uuid, bool $required = true): ?Order
    {
        $order = Order::query()->with('items')->where('uuid', $uuid)->first();

        if (! $order && $required) {
            throw RefundException::notFound();
        }

        return $order;
    }

    /** Fill in the actor's display name from its id (must run inside the tenant context). */
    private function resolveActorName(RefundActor $actor): RefundActor
    {
        if (filled($actor->name)) {
            return $actor;
        }

        $name = match ($actor->type) {
            CancellationActor::System => 'System',
            CancellationActor::Admin => $actor->id
                ? CentralAdminUser::on(config('tenancy.database.central_connection'))->whereKey($actor->id)->value('name')
                : null,
            CancellationActor::Vendor => $actor->id ? TenantAdminUser::query()->whereKey($actor->id)->value('name') : null,
            CancellationActor::Customer => $actor->id ? Customer::query()->whereKey($actor->id)->value('full_name') : null,
        };

        return $actor->withName($name);
    }

    private function appendActivity(Order $order, string $title, string $description): void
    {
        $order->activities()->create([
            'status' => $order->status?->value,
            'title' => $title,
            'description' => $description,
        ]);
    }

    /** @return array<string, mixed> */
    private function withAttempt(Refund $refund, string $result, ?string $raw = null, ?string $code = null): array
    {
        $meta = $refund->meta ?? [];
        $meta['attempts'] ??= [];
        $meta['attempts'][] = array_filter([
            'at' => now()->toIso8601String(),
            'result' => $result,
            'code' => $code,
        ]);

        if ($raw !== null) {
            $meta['gateway_response'] = Str::limit($raw, 4000, '');
        }

        return $meta;
    }

    private function logGatewayError(Refund $refund, string $message, ?Throwable $exception = null): void
    {
        Log::warning('Refund gateway call failed', [
            'refund_id' => $refund->id,
            'reference' => $refund->reference,
            'tenant_id' => $refund->tenant_id,
            'order_number' => $refund->order_number,
            'gateway' => $refund->gateway,
            'error' => $message,
            'exception' => $exception ? $exception::class : null,
        ]);
    }

    private function money(float $amount, ?string $currency = null): string
    {
        return sprintf('%s %s', $currency ?: 'USD', number_format($amount, 2));
    }

    // ─── Internals: tenancy & notifications ───────────────────────────────────

    private function tenantFor(?Tenant $tenant): Tenant
    {
        $tenant ??= tenant();

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('RefundService needs a tenant: pass one or call it inside a tenant context.');
        }

        return $tenant;
    }

    private function tenantForId(string $tenantId): Tenant
    {
        $current = tenant();

        if ($current instanceof Tenant && (string) $current->getTenantKey() === $tenantId) {
            return $current;
        }

        $tenant = Tenant::query()->find($tenantId);

        if (! $tenant instanceof Tenant) {
            throw RefundException::notFound();
        }

        return $tenant;
    }

    /** Run side effects (mail / notifications) after the surrounding transaction commits. */
    private function afterCommit(Tenant $tenant, Closure $callback): void
    {
        DB::afterCommit(function () use ($tenant, $callback): void {
            try {
                $this->inTenant($tenant, $callback);
            } catch (Throwable $e) {
                Log::warning('Refund notification failed', ['tenant_id' => $tenant->getTenantKey(), 'error' => $e->getMessage()]);
            }
        });
    }

    private function notifyCreated(Refund $refund): void
    {
        $message = sprintf(
            'Refund %s of %s was requested for order #%s (%s, %s).',
            $refund->reference,
            $this->money((float) $refund->amount, $refund->currency),
            $refund->order_number,
            $refund->source->label(),
            $refund->refund_method->label(),
        );

        $this->notifyStaff($refund, 'Refund requested', $message);
    }

    private function notifyCompleted(Refund $refund): void
    {
        $order = $this->findOrder((string) $refund->order_number, false);

        if ($order) {
            $this->mailService->sendTenantRefundProcessed($order, $refund);
        }

        $this->notifyStaff($refund, 'Refund completed', sprintf(
            'Refund %s of %s for order #%s has been completed (%s).',
            $refund->reference,
            $this->money((float) $refund->amount, $refund->currency),
            $refund->order_number,
            $refund->refund_method->label(),
        ));
    }

    private function notifyFailed(Refund $refund): void
    {
        $this->notifyStaff($refund, 'Refund failed — action needed', sprintf(
            'Refund %s of %s for order #%s failed: %s Retry it or complete it manually.',
            $refund->reference,
            $this->money((float) $refund->amount, $refund->currency),
            $refund->order_number,
            $refund->failure_reason,
        ));
    }

    /** Vendor (tenant notification) + platform admin (admin notification). Runs in the tenant context. */
    private function notifyStaff(Refund $refund, string $title, string $message): void
    {
        $data = [
            'refund_id' => $refund->id,
            'reference' => $refund->reference,
            'order_number' => $refund->order_number,
            'status' => $refund->status->value,
        ];

        $tenant = tenant();

        if ($tenant instanceof Tenant) {
            $this->tenantNotifier->notify($tenant, 'refund', $title, $message, $data);
        }

        $this->adminNotifier->notify(
            'refund',
            $title,
            sprintf('%s (Tenant %s)', $message, $refund->tenant_id),
            $data + ['tenant_id' => $refund->tenant_id],
        );
    }
}
