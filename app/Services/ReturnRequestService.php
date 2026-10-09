<?php

namespace App\Services;

use App\Enums\CancellationActor;
use App\Enums\InspectionResult;
use App\Enums\OrderStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundStatus;
use App\Enums\ReturnMethod;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use App\Exceptions\RefundException;
use App\Exceptions\ReturnActionException;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestMedia;
use App\Models\ReturnRequestNote;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderActivity;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product as TenantProduct;
use App\Models\Tenant\ProductVariant as TenantVariant;
use App\Services\Mail\TemplateMailService;
use App\Services\Orders\OrderPolicyService;
use App\Services\Refunds\RefundActor;
use App\Services\Refunds\RefundService;
use App\Services\Tenant\StockService;
use App\Support\Tenancy\RunsInTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Return & exchange workflow (RETURN_EXCHANGE_REFUND_PLAN.md B.5 / B.6 / B.9).
 *
 * Return requests live in the central DB, the order they belong to in the tenant DB. Every public
 * method works from the tenant context (storefront, vendor panel) AND from the central context
 * (admin pages): tenant work runs through inTenant(), which restores the caller's context.
 *
 * Every status change goes through transition(), which asserts ReturnStatus::canTransitionTo()
 * and claims the change with a conditional UPDATE (… WHERE status = from), then records the
 * reviewer, a customer-visible note, an order activity and the B.9 notification fan-out.
 * Rule violations throw ReturnActionException (user-safe message, rendered as a 422).
 */
class ReturnRequestService
{
    use RunsInTenant;

    /** @deprecated kept only as a last-resort fallback; use ReturnPolicyService for the real, configurable window. */
    public const RETURN_WINDOW_DAYS = ReturnPolicyService::DEFAULT_WINDOW_DAYS;

    /** Keys returned by availableActions(), in display order. */
    public const ACTIONS = [
        'approve',
        'reject',
        'request_info',
        'forward_to_merchant',
        'mark_received',
        'inspect',
        'issue_refund',
        'mark_exchange_shipped',
        'mark_exchange_completed',
        'convert_to_refund',
        'close',
        'withdraw',
        'reply',
    ];

    public function __construct(
        private readonly TenantNotificationService $tenantNotificationService,
        private readonly AdminNotificationService $adminNotificationService,
        private readonly TemplateMailService $mailService,
        private readonly ReturnPolicyService $returnPolicyService,
        private readonly OrderPolicyService $orderPolicy,
        private readonly StockService $stockService,
        private readonly RefundService $refundService,
    ) {}

    // ─── Creation ───────────────────────────────────────────────────────────

    /**
     * Customer return / exchange request (B.5 creation rules 1–9, B.6).
     *
     * @param array{
     *     tenant_id: string,
     *     order_number: string,
     *     customer_id: int,
     *     order_item_id?: int|null,
     *     quantity?: int|null,
     *     type?: string|ReturnType|null,
     *     return_method?: string|ReturnMethod|null,
     *     reason: string|ReturnReason,
     *     description?: string|null,
     *     customer_note?: string|null,
     *     replacement_product_variant_id?: int|null,
     *     product_id?: int|null,
     *     product_variant_id?: int|null,
     * } $data  product_id / product_variant_id are a legacy way to find the order item
     *          when order_item_id is missing.
     * @param  UploadedFile[]  $photoFiles
     * @param  UploadedFile[]  $videoFiles
     *
     * @throws ReturnActionException
     */
    public function create(array $data, array $photoFiles = [], array $videoFiles = []): ReturnRequest
    {
        $tenant = $this->tenantOrFail((string) $data['tenant_id']);

        // The order row is locked (tenant DB) while the rules are checked and the request is
        // inserted, so a double submit can't open two requests or return more units than exist.
        [$evaluation, $returnRequest] = $this->inTenant($tenant, function () use ($tenant, $data, $photoFiles, $videoFiles): array {
            return DB::transaction(function () use ($tenant, $data, $photoFiles, $videoFiles): array {
                Order::query()->where('uuid', (string) $data['order_number'])->lockForUpdate()->first();

                $evaluation = $this->evaluateCreation($tenant, $data, count($photoFiles), count($videoFiles));

                if ($evaluation['errors'] !== []) {
                    throw ReturnActionException::fromErrors($evaluation['errors'], $evaluation['code']);
                }

                /** @var OrderItem $item */
                $item = $evaluation['item'];
                $type = $evaluation['type'];

                $returnRequest = ReturnRequest::create([
                    'tenant_id' => (string) $data['tenant_id'],
                    'order_number' => (string) $data['order_number'],
                    'type' => $type,
                    'customer_id' => (int) $data['customer_id'],
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $evaluation['quantity'],
                    'return_method' => $evaluation['method'],
                    'reason' => $evaluation['reason'],
                    'description' => $this->nullableText($data['description'] ?? null),
                    'customer_note' => $this->nullableText($data['customer_note'] ?? null),
                    'replacement_product_variant_id' => $type === ReturnType::Exchange ? (int) $data['replacement_product_variant_id'] : null,
                    'replacement_quantity' => $type === ReturnType::Exchange ? $evaluation['quantity'] : null,
                    'status' => ReturnStatus::Pending,
                ]);

                return [$evaluation, $returnRequest];
            });
        });

        /** @var OrderItem $item */
        $item = $evaluation['item'];
        $type = $evaluation['type'];
        $quantity = $evaluation['quantity'];

        foreach ($photoFiles as $file) {
            $this->storeMedia($returnRequest, $file, 'photo');
        }

        foreach ($videoFiles as $file) {
            $this->storeMedia($returnRequest, $file, 'video');
        }

        $label = $evaluation['item_label'];
        $summary = sprintf('%d × %s', $quantity, $label);

        $this->addNote($returnRequest, $type === ReturnType::Exchange
            ? __('Your exchange request for :item has been submitted.', ['item' => $summary])
            : __('Your return request for :item has been submitted.', ['item' => $summary]),
            ReturnRequestNote::AUTHOR_SYSTEM, null, true);

        $this->orderActivity($tenant, $returnRequest, $type === ReturnType::Exchange ? 'Exchange requested' : 'Return requested', sprintf(
            '%s requested for %s (return #%d, reason: %s).',
            $type === ReturnType::Exchange ? 'Exchange' : 'Return',
            $summary,
            $returnRequest->id,
            $evaluation['reason']->label(),
        ));

        $this->notify(
            $returnRequest,
            'Return request submitted',
            sprintf('A new %s request was submitted for %s on order %s.', $type === ReturnType::Exchange ? 'exchange' : 'return', $summary, $returnRequest->order_number),
            true,
            __('We have received your request and will review it shortly.'),
        );

        return $returnRequest->fresh();
    }

    /**
     * A return opened by staff on the customer's behalf (admin order page). It goes straight to the
     * merchant (AwaitingMerchantReview). Only the order-status and one-open-request rules apply:
     * staff may override the window and evidence rules.
     *
     * @param  array{tenant_id: string, order_number: string, description: string, order_item_id?: int|null, quantity?: int|null, reason?: string|ReturnReason|null, return_method?: string|ReturnMethod|null}  $data
     *
     * @throws ReturnActionException
     */
    public function createByStaff(array $data, CancellationActor|RefundActor $actor, ?int $actorId = null): ReturnRequest
    {
        $actor = $this->staffActor($actor, $actorId);
        $tenant = $this->tenantOrFail((string) $data['tenant_id']);
        $orderNumber = (string) $data['order_number'];

        $prepared = $this->inTenant($tenant, function () use ($tenant, $data, $orderNumber): array {
            $order = Order::query()->with('items')->where('uuid', $orderNumber)->first();

            if (! $order) {
                throw ReturnActionException::notFound();
            }

            $errors = array_filter([$this->orderStatusError($order)]);
            $item = filled($data['order_item_id'] ?? null) ? $order->items->firstWhere('id', (int) $data['order_item_id']) : null;

            if (filled($data['order_item_id'] ?? null) && ! $item) {
                $errors[] = __('The selected item does not belong to this order.');
            }

            if ($item) {
                $errors = array_merge($errors, $this->itemErrors($tenant, $order, $item, (int) ($data['quantity'] ?? 0) ?: null));
            } elseif ($this->openRequestsQuery((string) $tenant->getTenantKey(), $orderNumber)->exists()) {
                $errors[] = __('There is already an open return request for this order.');
            }

            if ($errors !== []) {
                throw ReturnActionException::fromErrors(array_values($errors), ReturnActionException::NOT_ELIGIBLE);
            }

            return [
                'order' => $order,
                'item' => $item,
                'quantity' => $item ? ((int) ($data['quantity'] ?? 0) ?: $this->remainingQuantity($order, $item, (string) $tenant->getTenantKey())) : 1,
            ];
        });

        $reason = $data['reason'] ?? ReturnReason::Other;
        $method = $data['return_method'] ?? null;

        $returnRequest = ReturnRequest::create(array_merge([
            'tenant_id' => (string) $tenant->getTenantKey(),
            'order_number' => $orderNumber,
            'type' => ReturnType::Return,
            'customer_id' => (int) ($prepared['order']->customer_id ?? 0),
            'order_item_id' => $prepared['item']?->id,
            'product_id' => $prepared['item']?->product_id,
            'product_variant_id' => $prepared['item']?->product_variant_id,
            'quantity' => $prepared['quantity'],
            'return_method' => $method instanceof ReturnMethod ? $method : ReturnMethod::tryFrom((string) $method),
            'reason' => $reason instanceof ReturnReason ? $reason : (ReturnReason::tryFrom((string) $reason) ?? ReturnReason::Other),
            'description' => $this->nullableText($data['description'] ?? null),
            'status' => ReturnStatus::AwaitingMerchantReview,
            'forwarded_at' => now(),
        ], $this->reviewerAttributes($actor)));

        $this->addNote($returnRequest, __('A return request was opened for your order by our support team.'), ReturnRequestNote::AUTHOR_SYSTEM, null, true);
        $this->orderActivity($tenant, $returnRequest, 'Return requested', sprintf('Return #%d opened by %s.', $returnRequest->id, $actor->type->label()));
        $this->notify(
            $returnRequest,
            'Return request awaiting your review',
            'A return request for your store is awaiting your decision.',
            true,
            __('A return request was opened for your order and is being reviewed by the store.'),
        );

        return $returnRequest->fresh();
    }

    /**
     * The B.5 / B.6 creation rules without creating anything — the storefront's inline pre-check
     * (ReturnRequestValidationService) and create() share this, so both enforce the same rules.
     *
     * @param  array<string, mixed>  $data  same keys as create()
     * @return list<string> error messages, empty when valid
     */
    public function creationErrors(array $data, int $photoCount = 0, int $videoCount = 0): array
    {
        $tenant = $this->findTenant((string) ($data['tenant_id'] ?? ''));

        if (! $tenant) {
            return [__('Order not found.')];
        }

        return $this->evaluateCreation($tenant, $data, $photoCount, $videoCount)['errors'];
    }

    // ─── Staff transitions ───────────────────────────────────────────────────

    /**
     * Approve the request (vendor or admin). Exchanges re-check and reserve the replacement stock.
     *
     * @throws ReturnActionException
     */
    public function approve(ReturnRequest $returnRequest, CancellationActor|RefundActor $actor, ?int $actorId = null, ?string $note = null): ReturnRequest
    {
        $actor = $this->staffActor($actor, $actorId);
        $this->assertCanTransition($returnRequest, ReturnStatus::Approved);
        $tenant = $this->tenantOrFail($returnRequest->tenant_id);

        $reserved = $this->reserveReplacement($returnRequest, $tenant);

        try {
            $this->transition($returnRequest, ReturnStatus::Approved, $actor);
        } catch (Throwable $e) {
            if ($reserved) {
                $this->releaseReplacement($returnRequest->refresh(), $tenant);
            }

            throw $e;
        }

        if (filled($note)) {
            $this->addNote($returnRequest, trim((string) $note), $this->authorType($actor), $actor->id, true);
        }

        $instructions = $this->returnInstructions($returnRequest);
        $this->addNote($returnRequest, __('Your return request has been approved.').' '.$instructions, ReturnRequestNote::AUTHOR_SYSTEM, null, true);
        $this->orderActivity($tenant, $returnRequest, 'Return approved', sprintf(
            'Return #%d approved by %s.%s',
            $returnRequest->id,
            $actor->type->label(),
            $returnRequest->isExchange() ? ' Replacement stock reserved.' : '',
        ));
        $this->notify($returnRequest, 'Return request approved', sprintf('Return request #%d was approved.', $returnRequest->id), true, $instructions);

        return $returnRequest->fresh();
    }

    /** @throws ReturnActionException */
    public function reject(ReturnRequest $returnRequest, string $reason, CancellationActor|RefundActor $actor, ?int $actorId = null): ReturnRequest
    {
        $actor = $this->staffActor($actor, $actorId);
        $reason = trim($reason);

        if ($reason === '') {
            throw ReturnActionException::fromErrors([__('A reason is required to reject a return request.')]);
        }

        if ($this->hasOpenRefund($returnRequest)) {
            throw ReturnActionException::notAllowed(__('Resolve the open refund for this return request first.'));
        }

        $tenant = $this->tenantOrFail($returnRequest->tenant_id);
        $this->transition($returnRequest, ReturnStatus::Rejected, $actor);
        $released = $this->releaseReplacement($returnRequest, $tenant);

        $this->addNote($returnRequest, $reason, $this->authorType($actor), $actor->id, true);
        $this->orderActivity($tenant, $returnRequest, 'Return rejected', sprintf(
            'Return #%d rejected by %s. Reason: %s%s',
            $returnRequest->id,
            $actor->type->label(),
            $reason,
            $released ? ' Reserved replacement stock released.' : '',
        ));
        $this->notify($returnRequest, 'Return request rejected', $reason, true, $reason);

        return $returnRequest->fresh();
    }

    /** @throws ReturnActionException */
    public function requestMoreInfo(ReturnRequest $returnRequest, string $message, CancellationActor|RefundActor $actor, ?int $actorId = null): ReturnRequest
    {
        $actor = $this->staffActor($actor, $actorId);
        $message = trim($message);

        if ($message === '') {
            throw ReturnActionException::fromErrors([__('Please tell the customer what information you need.')]);
        }

        $tenant = $this->tenantOrFail($returnRequest->tenant_id);
        $this->transition($returnRequest, ReturnStatus::AwaitingInfo, $actor);

        $this->addNote($returnRequest, $message, $this->authorType($actor), $actor->id, true);
        $this->orderActivity($tenant, $returnRequest, 'Return info requested', sprintf('More information requested for return #%d by %s.', $returnRequest->id, $actor->type->label()));
        $this->notify($returnRequest, 'More information requested', $message, true, $message);

        return $returnRequest->fresh();
    }

    /**
     * Platform admin forwards the request to the merchant. A later customer reply to an info
     * request goes back to the merchant (see customerReply()).
     *
     * @throws ReturnActionException
     */
    public function markAwaitingMerchantReview(ReturnRequest $returnRequest, CancellationActor|RefundActor $actor, ?int $actorId = null): ReturnRequest
    {
        $actor = $this->staffActor($actor, $actorId);

        if ($actor->type === CancellationActor::Vendor) {
            throw ReturnActionException::notAllowed(__('Only the platform team can forward a return request to the store.'));
        }

        $tenant = $this->tenantOrFail($returnRequest->tenant_id);
        $this->transition($returnRequest, ReturnStatus::AwaitingMerchantReview, $actor, ['forwarded_at' => now()]);

        $this->addNote($returnRequest, __('Your request has been passed to the store for review.'), ReturnRequestNote::AUTHOR_SYSTEM, null, true);
        $this->orderActivity($tenant, $returnRequest, 'Return forwarded to the store', sprintf('Return #%d forwarded to the store for review.', $returnRequest->id));
        $this->notify(
            $returnRequest,
            'Return request awaiting your review',
            'A return request for your store is awaiting your decision.',
            true,
            __('Your request has been passed to the store for review.'),
        );

        return $returnRequest->fresh();
    }

    /** @throws ReturnActionException */
    public function markItemReceived(ReturnRequest $returnRequest, CancellationActor|RefundActor $actor, ?int $actorId = null): ReturnRequest
    {
        $actor = $this->staffActor($actor, $actorId);
        $tenant = $this->tenantOrFail($returnRequest->tenant_id);
        $this->transition($returnRequest, ReturnStatus::ItemReceived, $actor, ['received_at' => now()]);

        $message = __('We have received your returned item and will inspect it shortly.');
        $this->addNote($returnRequest, $message, ReturnRequestNote::AUTHOR_SYSTEM, null, true);
        $this->orderActivity($tenant, $returnRequest, 'Returned item received', sprintf('Returned item for return #%d received.', $returnRequest->id));
        $this->notify($returnRequest, 'Returned item received', 'The returned item has been received.', true, $message);

        return $returnRequest->fresh();
    }

    /**
     * Record the inspection. Passed / partial + restock puts the returned units back into stock
     * once (restocked_at). $restock defaults to the tenant's restock_returned_items policy.
     *
     * @throws ReturnActionException
     */
    public function inspect(
        ReturnRequest $returnRequest,
        InspectionResult|string $result,
        ?string $notes,
        ?bool $restock,
        CancellationActor|RefundActor $actor,
        ?int $actorId = null,
    ): ReturnRequest {
        $actor = $this->staffActor($actor, $actorId);
        $result = $result instanceof InspectionResult ? $result : InspectionResult::tryFrom($result);

        if (! $result) {
            throw ReturnActionException::fromErrors([__('Please choose an inspection result.')]);
        }

        $tenant = $this->tenantOrFail($returnRequest->tenant_id);
        $notes = $this->nullableText($notes);

        $this->transition($returnRequest, ReturnStatus::Inspected, $actor, [
            'inspection_result' => $result,
            'inspection_notes' => $notes,
            'inspected_at' => now(),
        ]);

        $restock ??= $this->orderPolicy->restockReturnedItems($tenant);
        $stockNote = 'Not restocked.';

        if ($result->allowsRestock() && $restock) {
            $stockNote = match ($this->restockReturnedItem($returnRequest, $tenant)) {
                'restocked' => sprintf('Restocked %d unit(s).', (int) $returnRequest->quantity),
                'already' => 'Already restocked.',
                default => 'Not restocked: the order\'s stock was never deducted (e.g. cash on delivery) or was already restored, so inventory was left unchanged.',
            };
        }

        $this->addNote($returnRequest, $result->customerLabel(), ReturnRequestNote::AUTHOR_SYSTEM, null, true);
        $this->addNote($returnRequest, trim(sprintf('Inspection: %s. %s %s', $result->label(), $stockNote, $notes ?? '')), $this->authorType($actor), $actor->id, false);
        $this->orderActivity($tenant, $returnRequest, 'Return inspected', sprintf('Return #%d inspected: %s. %s', $returnRequest->id, $result->label(), $stockNote));
        $this->notify($returnRequest, 'Returned item inspected', sprintf('Inspection result for return #%d: %s.', $returnRequest->id, $result->label()), true, $result->customerLabel());

        return $returnRequest->fresh();
    }

    /**
     * Refund an inspected return (type return): creates a linked Refund through RefundService
     * (amount ≤ the calculated maximum; defaults to it) and sends it to the original payment
     * method right away when possible. The request moves to Refunded when the refund completes
     * (RefundService → recordRefunded()); a manual / failed refund leaves it Inspected.
     *
     * @throws ReturnActionException
     * @throws RefundException
     */
    public function issueRefund(ReturnRequest $returnRequest, ?float $amount, CancellationActor|RefundActor $actor, ?int $actorId = null): Refund
    {
        $actor = $this->staffActor($actor, $actorId);

        if ($returnRequest->isExchange()) {
            throw ReturnActionException::notAllowed(__('Convert this exchange to a refund first.'));
        }

        if ($returnRequest->status !== ReturnStatus::Inspected) {
            throw ReturnActionException::invalidTransition($returnRequest->status, ReturnStatus::Refunded);
        }

        $amount ??= $this->refundService->calculateForReturn($returnRequest)->max;
        $refund = $this->refundService->requestForReturn($returnRequest, $amount, $actor);

        $this->addNote($returnRequest, __('A refund of :amount (:reference) has been issued.', [
            'amount' => $this->money((float) $refund->amount, $refund->currency),
            'reference' => $refund->reference,
        ]), ReturnRequestNote::AUTHOR_SYSTEM, null, true);

        if ($refund->refund_method === RefundMethod::OriginalPayment) {
            $refund = $this->refundService->execute($refund, $actor);
        }

        if ($refund->status !== RefundStatus::Completed) {
            $this->notify($returnRequest, 'Refund issued for return', sprintf(
                'Refund %s of %s for return #%d is %s.',
                $refund->reference,
                $this->money((float) $refund->amount, $refund->currency),
                $returnRequest->id,
                Str::lower($refund->status->label()),
            ));
        }

        return $refund->refresh();
    }

    /** @throws ReturnActionException */
    public function markExchangeShipped(ReturnRequest $returnRequest, string $trackingNumber, CancellationActor|RefundActor $actor, ?int $actorId = null): ReturnRequest
    {
        $actor = $this->staffActor($actor, $actorId);
        $trackingNumber = trim($trackingNumber);

        if (! $returnRequest->isExchange()) {
            throw ReturnActionException::notAllowed(__('Only exchange requests can ship a replacement.'));
        }

        if ($trackingNumber === '') {
            throw ReturnActionException::fromErrors([__('Please enter the tracking number of the replacement.')]);
        }

        if ($returnRequest->inspection_result === InspectionResult::Failed) {
            throw ReturnActionException::notAllowed(__('The returned item failed inspection, so no replacement can be shipped.'));
        }

        $this->assertCanTransition($returnRequest, ReturnStatus::ExchangeShipped);
        $tenant = $this->tenantOrFail($returnRequest->tenant_id);

        // Approved before reservations existed (or released meanwhile): reserve now.
        $this->reserveReplacement($returnRequest, $tenant);

        $this->transition($returnRequest, ReturnStatus::ExchangeShipped, $actor, [
            'exchange_tracking_number' => Str::limit($trackingNumber, 190, ''),
            'exchange_shipped_at' => now(),
        ]);

        $message = __('Your replacement has been shipped. Tracking number: :tracking', ['tracking' => $returnRequest->exchange_tracking_number]);
        $this->addNote($returnRequest, $message, ReturnRequestNote::AUTHOR_SYSTEM, null, true);
        $this->orderActivity($tenant, $returnRequest, 'Exchange shipped', sprintf('Replacement for return #%d shipped (tracking %s).', $returnRequest->id, $returnRequest->exchange_tracking_number));
        $this->notify($returnRequest, 'Exchange shipped', sprintf('The replacement for return #%d was shipped (tracking %s).', $returnRequest->id, $returnRequest->exchange_tracking_number), true, $message);

        return $returnRequest->fresh();
    }

    /** @throws ReturnActionException */
    public function markExchangeCompleted(ReturnRequest $returnRequest, CancellationActor|RefundActor $actor, ?int $actorId = null): ReturnRequest
    {
        $actor = $this->staffActor($actor, $actorId);
        $tenant = $this->tenantOrFail($returnRequest->tenant_id);
        $this->transition($returnRequest, ReturnStatus::Exchanged, $actor, ['exchange_completed_at' => now()]);

        $message = __('Your exchange has been completed.');
        $this->addNote($returnRequest, $message, ReturnRequestNote::AUTHOR_SYSTEM, null, true);
        $this->orderActivity($tenant, $returnRequest, 'Exchange completed', sprintf('Exchange for return #%d completed.', $returnRequest->id));
        $this->notify($returnRequest, 'Exchange completed', sprintf('The exchange for return #%d was completed.', $returnRequest->id), true, $message);

        return $returnRequest->fresh();
    }

    /**
     * The replacement can't be fulfilled: turn the exchange into a plain return (refund path) and
     * release the reserved replacement stock. Allowed while the request is open and nothing was
     * shipped yet; the status is unchanged.
     *
     * @throws ReturnActionException
     */
    public function convertToRefund(ReturnRequest $returnRequest, CancellationActor|RefundActor $actor, ?int $actorId = null): ReturnRequest
    {
        $actor = $this->staffActor($actor, $actorId);

        if (! $returnRequest->isExchange()) {
            throw ReturnActionException::notAllowed(__('Only exchange requests can be converted to a refund.'));
        }

        if ($returnRequest->exchange_shipped_at !== null || ! $returnRequest->status?->isOpen() || $returnRequest->status === ReturnStatus::ExchangeShipped) {
            throw ReturnActionException::notAllowed(__('This exchange can no longer be converted to a refund.'));
        }

        $tenant = $this->tenantOrFail($returnRequest->tenant_id);
        $released = $this->releaseReplacement($returnRequest, $tenant);

        $converted = ReturnRequest::query()
            ->whereKey($returnRequest->getKey())
            ->where('type', ReturnType::Exchange->value)
            ->whereNull('exchange_shipped_at')
            ->update(array_merge(
                ['type' => ReturnType::Return->value, 'updated_at' => now()],
                $this->serialize($this->reviewerAttributes($actor)),
            )) === 1;

        if (! $converted) {
            throw ReturnActionException::notAllowed(__('This exchange can no longer be converted to a refund.'));
        }

        $returnRequest->refresh();

        $message = __('Your exchange has been changed to a refund.');
        $this->addNote($returnRequest, $message, ReturnRequestNote::AUTHOR_SYSTEM, null, true);
        $this->orderActivity($tenant, $returnRequest, 'Exchange converted to refund', sprintf(
            'Exchange #%d converted to a refund by %s.%s',
            $returnRequest->id,
            $actor->type->label(),
            $released ? ' Reserved replacement stock released.' : '',
        ));
        $this->notify($returnRequest, 'Exchange converted to refund', sprintf('Exchange #%d was converted to a refund.', $returnRequest->id), true, $message);

        return $returnRequest->fresh();
    }

    /** Archive a finished request (Refunded / Exchanged / Rejected / Withdrawn → Closed). */
    public function close(ReturnRequest $returnRequest, CancellationActor|RefundActor $actor, ?int $actorId = null): ReturnRequest
    {
        $actor = $this->staffActor($actor, $actorId);
        $tenant = $this->tenantOrFail($returnRequest->tenant_id);
        $this->transition($returnRequest, ReturnStatus::Closed, $actor);
        $this->releaseReplacement($returnRequest, $tenant);

        $this->addNote($returnRequest, __('This return request has been closed.'), ReturnRequestNote::AUTHOR_SYSTEM, null, true);
        $this->orderActivity($tenant, $returnRequest, 'Return closed', sprintf('Return #%d closed.', $returnRequest->id));
        $this->notify($returnRequest, 'Return request closed', 'This return request has been closed.');

        return $returnRequest->fresh();
    }

    /**
     * @deprecated Legacy one-click "mark refunded" of the current panel / admin UI: walks the
     * state machine (Approved → ItemReceived → Inspected(passed)) and then issueRefund().
     * The new UI calls the individual steps.
     *
     * @throws ReturnActionException
     * @throws RefundException
     */
    public function markRefunded(ReturnRequest $returnRequest, float $amount, CancellationActor|RefundActor $actor, ?int $actorId = null): Refund
    {
        $actor = $this->staffActor($actor, $actorId);

        if ($returnRequest->status === ReturnStatus::Approved) {
            $returnRequest = $this->markItemReceived($returnRequest, $actor);
        }

        if ($returnRequest->status === ReturnStatus::ItemReceived) {
            $returnRequest = $this->inspect($returnRequest, InspectionResult::Passed, null, null, $actor);
        }

        if ($returnRequest->isExchange()) {
            $returnRequest = $this->convertToRefund($returnRequest, $actor);
        }

        return $this->issueRefund($returnRequest, $amount, $actor);
    }

    // ─── Customer actions ────────────────────────────────────────────────────

    /** The customer withdraws the request (Pending / AwaitingInfo / AwaitingMerchantReview only). */
    public function cancelByCustomer(ReturnRequest $returnRequest, int $customerId, ?string $reason = null): ReturnRequest
    {
        $this->assertOwnedBy($returnRequest, $customerId);

        if (! $returnRequest->status?->canBeWithdrawn()) {
            throw ReturnActionException::notAllowed(__('This return request can no longer be withdrawn.'));
        }

        $tenant = $this->tenantOrFail($returnRequest->tenant_id);
        $this->transition($returnRequest, ReturnStatus::Cancelled, RefundActor::customer($customerId), ['cancelled_at' => now()]);
        $this->releaseReplacement($returnRequest, $tenant);

        $reason = $this->nullableText($reason);
        $this->addNote($returnRequest, $reason ?? __('You withdrew this return request.'), ReturnRequestNote::AUTHOR_CUSTOMER, $customerId, true);
        $this->orderActivity($tenant, $returnRequest, 'Return withdrawn', sprintf('Return #%d withdrawn by the customer.', $returnRequest->id));
        $this->notify($returnRequest, 'Return request withdrawn', sprintf('The customer withdrew return request #%d.', $returnRequest->id), true, __('You withdrew this return request.'));

        return $returnRequest->fresh();
    }

    /**
     * The customer answers an info request: AwaitingInfo → Pending, or back to
     * AwaitingMerchantReview when the request had been forwarded to the merchant.
     *
     * @param  UploadedFile[]  $photoFiles
     * @param  UploadedFile[]  $videoFiles
     */
    public function customerReply(ReturnRequest $returnRequest, int $customerId, string $message, array $photoFiles = [], array $videoFiles = []): ReturnRequest
    {
        $this->assertOwnedBy($returnRequest, $customerId);
        $message = trim($message);

        if ($returnRequest->status !== ReturnStatus::AwaitingInfo) {
            throw ReturnActionException::notAllowed(__('No info is currently requested.'));
        }

        if ($message === '') {
            throw ReturnActionException::fromErrors([__('Please write a reply.')]);
        }

        $tenant = $this->tenantOrFail($returnRequest->tenant_id);
        $target = $returnRequest->forwarded_at !== null ? ReturnStatus::AwaitingMerchantReview : ReturnStatus::Pending;
        $this->transition($returnRequest, $target, RefundActor::customer($customerId));

        $this->addNote($returnRequest, $message, ReturnRequestNote::AUTHOR_CUSTOMER, $customerId, true);

        foreach ($photoFiles as $file) {
            $this->storeMedia($returnRequest, $file, 'photo');
        }

        foreach ($videoFiles as $file) {
            $this->storeMedia($returnRequest, $file, 'video');
        }

        $this->orderActivity($tenant, $returnRequest, 'Return info provided', sprintf('The customer replied to the info request on return #%d.', $returnRequest->id));
        $this->notify($returnRequest, 'Customer replied to a return request', sprintf('The customer replied to return request #%d: %s', $returnRequest->id, Str::limit($message, 200)));

        return $returnRequest->fresh();
    }

    // ─── Hooks ───────────────────────────────────────────────────────────────

    /**
     * Called by RefundService when a refund linked to this request completed and moved it to
     * Refunded: order activity + notifications / customer email (the note is added there).
     */
    public function recordRefunded(ReturnRequest $returnRequest, Refund $refund): void
    {
        $tenant = $this->findTenant((string) $returnRequest->tenant_id);

        if (! $tenant) {
            return;
        }

        $amount = $this->money((float) $refund->amount, $refund->currency);
        $this->orderActivity($tenant, $returnRequest, 'Return refunded', sprintf('Return #%d refunded — %s (%s).', $returnRequest->id, $amount, $refund->reference));
        $this->notify(
            $returnRequest,
            'Return refunded',
            sprintf('Return #%d was refunded — %s (%s).', $returnRequest->id, $amount, $refund->reference),
            true,
            __('Your refund of :amount (:reference) has been completed.', ['amount' => $amount, 'reference' => $refund->reference]),
        );
    }

    // ─── Queries for the UIs ─────────────────────────────────────────────────

    /**
     * Which actions the given actor may take right now (keys from self::ACTIONS), accounting for
     * the status, the type (return / exchange), the inspection result and open refunds.
     * Pass $hasOpenRefund to skip the refunds query (e.g. for an unsaved model).
     *
     * @param  CancellationActor|string  $actorType  customer | vendor (alias: tenant) | admin
     * @return list<string>
     */
    public function availableActions(ReturnRequest $returnRequest, CancellationActor|string $actorType, ?bool $hasOpenRefund = null): array
    {
        $actor = $actorType instanceof CancellationActor
            ? $actorType
            : CancellationActor::tryFrom($actorType === 'tenant' ? 'vendor' : $actorType);
        $status = $returnRequest->status;

        if (! $actor || ! $status instanceof ReturnStatus) {
            return [];
        }

        if ($actor === CancellationActor::Customer) {
            return array_values(array_filter([
                $status->canBeWithdrawn() ? 'withdraw' : null,
                $status === ReturnStatus::AwaitingInfo ? 'reply' : null,
            ]));
        }

        if (! $actor->isStaff()) {
            return [];
        }

        $hasOpenRefund ??= $this->hasOpenRefund($returnRequest);
        $exchange = $returnRequest->isExchange();
        $notShipped = $returnRequest->exchange_shipped_at === null;
        $can = fn (ReturnStatus $target): bool => $status->canTransitionTo($target);

        $allowed = [
            'approve' => $can(ReturnStatus::Approved),
            'reject' => $can(ReturnStatus::Rejected) && ! $hasOpenRefund,
            'request_info' => $can(ReturnStatus::AwaitingInfo),
            'forward_to_merchant' => $actor === CancellationActor::Admin && $can(ReturnStatus::AwaitingMerchantReview),
            'mark_received' => $can(ReturnStatus::ItemReceived),
            'inspect' => $can(ReturnStatus::Inspected),
            'issue_refund' => $status === ReturnStatus::Inspected && ! $exchange && ! $hasOpenRefund,
            'mark_exchange_shipped' => $status === ReturnStatus::Inspected && $exchange && $notShipped
                && $returnRequest->inspection_result !== InspectionResult::Failed,
            'mark_exchange_completed' => $can(ReturnStatus::Exchanged),
            'convert_to_refund' => $exchange && $notShipped && $status->isOpen() && $status !== ReturnStatus::ExchangeShipped,
            'close' => $can(ReturnStatus::Closed),
        ];

        return array_keys(array_filter($allowed));
    }

    /**
     * Units of an order line that can still be returned: qty − units held by requests that are
     * not rejected / withdrawn (ReturnStatus::quantityHoldingStatuses()).
     */
    public function remainingQuantity(Order $order, OrderItem $item, ?string $tenantId = null): int
    {
        $tenantId ??= (string) tenant('id');

        $holding = array_filter(ReturnStatus::quantityHoldingStatuses(), fn (ReturnStatus $status) => $status !== ReturnStatus::Closed);

        // A Closed request only keeps holding units when it ended refunded or exchanged — not when
        // it was closed after a rejection / withdrawal.
        $held = (int) $this->itemRequestsQuery($tenantId, (string) $order->uuid, $item)
            ->where(fn (Builder $query) => $query
                ->whereIn('status', $this->statusValues($holding))
                ->orWhere(fn (Builder $closed) => $closed
                    ->where('status', ReturnStatus::Closed->value)
                    ->where(fn (Builder $outcome) => $outcome
                        ->whereNotNull('exchange_completed_at')
                        ->orWhere('refund_amount', '>', 0))))
            ->sum('quantity');

        return max(0, (int) $item->qty - $held);
    }

    /**
     * Every line of the order with what the return form needs to know about it. Pass
     * $withExchangeOptions = false to skip the replacement lookup (e.g. on the order page).
     *
     * @return list<array{order_item_id: int, item: OrderItem, quantity: int, remaining: int, has_open_request: bool, returnable: bool, errors: list<string>, exchange_options: list<array{id: int, label: string, price: float, stock: int|null}>}>
     */
    public function eligibleItems(Order $order, ?string $tenantId = null, bool $withExchangeOptions = true): array
    {
        $tenantId ??= (string) tenant('id');
        $tenant = $this->tenantOrFail($tenantId);

        return $this->inTenant($tenant, function () use ($tenant, $order, $tenantId, $withExchangeOptions): array {
            $order->loadMissing('items');
            $orderError = $this->orderStatusError($order);
            $exchangeEnabled = $withExchangeOptions && $this->orderPolicy->exchangeEnabled($tenant);
            $rows = [];

            foreach ($order->items as $item) {
                $remaining = $this->remainingQuantity($order, $item, $tenantId);
                $hasOpen = $this->itemRequestsQuery($tenantId, (string) $order->uuid, $item)
                    ->whereIn('status', $this->statusValues(ReturnStatus::openStatuses()))
                    ->exists();

                $errors = array_values(array_filter(array_merge(
                    [$orderError],
                    $this->policyErrors($tenantId, (string) $order->uuid, $item->product_id ? (int) $item->product_id : null),
                    [$hasOpen ? __('There is already an open return request for this item.') : null],
                    [$remaining < 1 ? __('All units of this item have already been returned.') : null],
                )));

                $rows[] = [
                    'order_item_id' => (int) $item->id,
                    'item' => $item,
                    'quantity' => (int) $item->qty,
                    'remaining' => $remaining,
                    'has_open_request' => $hasOpen,
                    'returnable' => $errors === [],
                    'errors' => $errors,
                    'exchange_options' => $exchangeEnabled ? $this->exchangeOptions($item, $tenantId) : [],
                ];
            }

            return $rows;
        });
    }

    /**
     * Replacement options for an exchange (B.6): the other active variants of the same product
     * with the same unit price and at least one unit in stock.
     *
     * @return list<array{id: int, label: string, price: float, stock: int|null}>
     */
    public function exchangeOptions(OrderItem $item, ?string $tenantId = null): array
    {
        $tenant = $this->tenantOrFail($tenantId ?? (string) tenant('id'));

        return $this->inTenant($tenant, function () use ($item): array {
            if (! $item->product_variant_id || ! $item->product_id) {
                return [];
            }

            $product = $this->findProduct((int) $item->product_id);
            $order = $item->order()->first();

            if (! $product || ! $order) {
                return [];
            }

            $options = [];

            foreach ($product->variants as $variant) {
                if ((int) $variant->id === (int) $item->product_variant_id || ! $variant->active) {
                    continue;
                }

                $variant->setRelation('product', $product);
                $stock = $this->stockService->availableStock($product, $variant);

                if (($stock !== null && $stock < 1) || ! $this->hasPriceParity($order, $item, $product, $variant)) {
                    continue;
                }

                $options[] = [
                    'id' => (int) $variant->id,
                    'label' => $this->variantLabel($variant),
                    'price' => $this->unitPrice($order, $product, $variant),
                    'stock' => $stock,
                ];
            }

            return $options;
        });
    }

    // ─── Legacy helpers (unchanged API) ──────────────────────────────────────

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

    /** The order was delivered within the return window (null window = the tenant/admin default). */
    public function isWithinReturnWindow(string $tenantId, string $orderNumber, ?int $windowDays = null): bool
    {
        $deliveredAt = $this->deliveredAt($tenantId, $orderNumber);

        if (! $deliveredAt) {
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
        return $this->returnPolicyService->resolveProductPolicy($tenantId, $productId)['window_days'];
    }

    /** When the order was delivered (its latest "delivered" activity). Restores the caller's tenancy context. */
    public function deliveredAt(string $tenantId, string $orderNumber): ?Carbon
    {
        $tenant = $this->findTenant($tenantId);

        if (! $tenant) {
            return null;
        }

        return $this->inTenant($tenant, function () use ($orderNumber): ?Carbon {
            $orderId = Order::query()->where('uuid', $orderNumber)->value('id');

            if (! $orderId) {
                return null;
            }

            return OrderActivity::query()
                ->where('order_id', $orderId)
                ->where('status', 'delivered')
                ->latest('id')
                ->first()
                ?->created_at;
        });
    }

    // ─── Internals: creation rules ───────────────────────────────────────────

    /**
     * Evaluate B.5 rules 1–8 (+ B.6). Shared by create() and creationErrors().
     *
     * @param  array<string, mixed>  $data
     * @return array{errors: list<string>, code: string, item: OrderItem|null, item_label: string, quantity: int, type: ReturnType, method: ReturnMethod|null, reason: ReturnReason|null}
     */
    private function evaluateCreation(Tenant $tenant, array $data, int $photoCount, int $videoCount): array
    {
        return $this->inTenant($tenant, function () use ($tenant, $data, $photoCount, $videoCount): array {
            $tenantId = (string) $tenant->getTenantKey();
            $result = [
                'errors' => [],
                'code' => ReturnActionException::VALIDATION,
                'item' => null,
                'item_label' => '',
                'quantity' => 0,
                'type' => ReturnType::Return,
                'method' => null,
                'reason' => null,
            ];

            // 1. The order belongs to the customer and was delivered.
            $order = Order::query()->with('items')->where('uuid', (string) ($data['order_number'] ?? ''))->first();
            $customerId = (int) ($data['customer_id'] ?? 0);

            if (! $order || ($customerId > 0 && (int) $order->customer_id !== $customerId)) {
                $result['errors'][] = __('Order not found.');
                $result['code'] = ReturnActionException::NOT_FOUND;

                return $result;
            }

            $errors = [];

            if ($orderError = $this->orderStatusError($order)) {
                $errors[] = $orderError;
                $result['code'] = ReturnActionException::NOT_ELIGIBLE;
            }

            // 3. The order item belongs to the order (legacy: matched by product / variant).
            $item = $this->resolveItem($order, $data);
            $quantity = (int) ($data['quantity'] ?? 0);

            if (! $item) {
                $errors[] = __('The selected item does not belong to this order.');
            } else {
                $quantity = $quantity > 0 ? $quantity : $this->remainingQuantity($order, $item, $tenantId);

                // 2. Window + returnable product.
                $policyErrors = $this->policyErrors($tenantId, (string) $order->uuid, $item->product_id ? (int) $item->product_id : null);

                if ($policyErrors !== []) {
                    $result['code'] = ReturnActionException::NOT_ELIGIBLE;
                }

                // 3 + 4. Quantity within what's left and one open request per item.
                $errors = array_merge($errors, $policyErrors, $this->itemErrors($tenant, $order, $item, $quantity));
            }

            // 5 + 6. Reason, description and evidence.
            $reason = $this->resolveEnum(ReturnReason::class, $data['reason'] ?? null);
            $errors = array_merge($errors, $this->reasonErrors($tenantId, $item, $reason, $data['description'] ?? null, $photoCount, $videoCount));

            // 7. Return method + customer note.
            $method = $this->resolveEnum(ReturnMethod::class, $data['return_method'] ?? null);

            if (! $method) {
                $errors[] = __('Please choose how you will return the item.');
            }

            if (mb_strlen((string) ($data['customer_note'] ?? '')) > 1000) {
                $errors[] = __('Additional notes must not exceed 1000 characters.');
            }

            // 8. Exchange (B.6).
            $type = $this->resolveEnum(ReturnType::class, $data['type'] ?? null) ?? ReturnType::Return;

            if (filled($data['type'] ?? null) && ! $this->resolveEnum(ReturnType::class, $data['type'])) {
                $errors[] = __('Please choose a valid resolution.');
            }

            if ($type === ReturnType::Exchange && $item) {
                $exchangeErrors = $this->exchangeErrors($tenant, $order, $item, (int) ($data['replacement_product_variant_id'] ?? 0) ?: null, max(1, $quantity));

                if ($exchangeErrors !== [] && $errors === []) {
                    $result['code'] = ReturnActionException::OUT_OF_STOCK;
                }

                $errors = array_merge($errors, $exchangeErrors);
            }

            $result['errors'] = array_values(array_unique($errors));
            $result['item'] = $item;
            $result['item_label'] = $item ? $this->itemLabel($item) : '';
            $result['quantity'] = $quantity;
            $result['type'] = $type;
            $result['method'] = $method;
            $result['reason'] = $reason;

            if ($result['errors'] === []) {
                $result['code'] = ReturnActionException::VALIDATION;
            }

            return $result;
        });
    }

    /** Rule 1: only Delivered / Completed orders can be returned. */
    private function orderStatusError(Order $order): ?string
    {
        $status = $order->status;

        if ($status instanceof OrderStatus && $status->isDelivered()) {
            return null;
        }

        if ($status instanceof OrderStatus && ($status->isCancelled() || $status === OrderStatus::Refunded)) {
            return __('This order is not eligible for a return.');
        }

        return __('You can request a return once the order is delivered.');
    }

    /**
     * Rule 2: the product is returnable and the order is within the policy window.
     *
     * @return list<string>
     */
    private function policyErrors(string $tenantId, string $orderNumber, ?int $productId): array
    {
        $policy = $this->returnPolicyService->resolveProductPolicy($tenantId, $productId);
        $errors = [];

        if ((isset($policy['is_returnable']) && ! $policy['is_returnable'])
            || ($productId && in_array($productId, $policy['non_returnable_ids'] ?? [], true))) {
            $errors[] = __('This product is not eligible for return.');
        }

        if (! $this->isWithinReturnWindow($tenantId, $orderNumber, (int) $policy['window_days'])) {
            $errors[] = __('This order is outside the :days-day return window.', ['days' => $policy['window_days']]);
        }

        return $errors;
    }

    /**
     * Rules 3 + 4: 1 ≤ quantity ≤ remaining, and no other open request for the same item.
     *
     * @return list<string>
     */
    private function itemErrors(Tenant $tenant, Order $order, OrderItem $item, ?int $quantity): array
    {
        $tenantId = (string) $tenant->getTenantKey();
        $errors = [];

        if ($this->itemRequestsQuery($tenantId, (string) $order->uuid, $item)
            ->whereIn('status', $this->statusValues(ReturnStatus::openStatuses()))
            ->exists()) {
            $errors[] = __('There is already an open return request for this item.');
        }

        $remaining = $this->remainingQuantity($order, $item, $tenantId);

        if ($remaining < 1) {
            $errors[] = __('All units of this item have already been returned.');
        } elseif ($quantity !== null && ($quantity < 1 || $quantity > $remaining)) {
            $errors[] = __('You can return between 1 and :max unit(s) of this item.', ['max' => $remaining]);
        }

        return $errors;
    }

    /**
     * Rules 5 + 6: reason, description (seller fault: min 10, always max 2000), photos when the
     * reason needs evidence, video per the (unchanged) policy.
     *
     * @return list<string>
     */
    private function reasonErrors(string $tenantId, ?OrderItem $item, ?ReturnReason $reason, mixed $description, int $photoCount, int $videoCount): array
    {
        $errors = [];
        $description = trim((string) ($description ?? ''));

        if (! $reason) {
            $errors[] = __('Please select a return reason.');
        }

        if ($reason?->requiresDescription() && mb_strlen($description) < 10) {
            $errors[] = __('Please describe the problem (at least 10 characters).');
        }

        if (mb_strlen($description) > 2000) {
            $errors[] = __('Description must not exceed 2000 characters.');
        }

        if ($reason?->requiresPhotos() && $photoCount < 1) {
            $errors[] = __('At least one photo is required as evidence.');
        }

        if ($reason) {
            $policy = $this->returnPolicyService->resolveProductPolicy($tenantId, $item?->product_id ? (int) $item->product_id : null);

            if ($this->reasonRequiresVideo($reason, $policy) && $videoCount < 1) {
                $errors[] = __('A video is required as evidence for this return reason.');
            }
        }

        return $errors;
    }

    /**
     * B.6: exchanges enabled, a different active variant of the same product, same unit price,
     * enough stock. Runs inside the tenant context.
     *
     * @return list<string>
     */
    private function exchangeErrors(Tenant $tenant, Order $order, OrderItem $item, ?int $replacementId, int $quantity): array
    {
        if (! $this->orderPolicy->exchangeEnabled($tenant)) {
            return [__('Exchanges are not available for this store.')];
        }

        if (! $item->product_variant_id) {
            return [__('This item cannot be exchanged.')];
        }

        if (! $replacementId) {
            return [__('Please choose the replacement option.')];
        }

        $variant = TenantVariant::query()->with('centralVariant')->find($replacementId);
        $product = $variant ? $this->findProduct((int) $variant->product_id) : null;

        if (! $variant || ! $product
            || (int) $variant->product_id !== (int) $item->product_id
            || (int) $variant->id === (int) $item->product_variant_id
            || ! $variant->active) {
            return [__('Please choose another available option of the same product.')];
        }

        $variant->setRelation('product', $product);

        if (! $this->hasPriceParity($order, $item, $product, $variant)) {
            return [__('This option has a different price — please return the item and place a new order.')];
        }

        $stockError = $this->stockError($product, $variant, $quantity);

        return $stockError ? [$stockError] : [];
    }

    /** "Only N left of {variant}" when fewer than $quantity units are available (ChecksCartStock rules). */
    private function stockError(TenantProduct $product, TenantVariant $variant, int $quantity): ?string
    {
        $stock = $this->stockService->availableStock($product, $variant);

        if ($stock === null || $stock >= $quantity) {
            return null;
        }

        return __('Only :count left of :name.', ['count' => max(0, $stock), 'name' => $this->variantLabel($variant)]);
    }

    /**
     * The replacement's current unit price equals the price paid for the original line — or,
     * when prices changed since the order, the original variant's current price.
     */
    private function hasPriceParity(Order $order, OrderItem $item, TenantProduct $product, TenantVariant $replacement): bool
    {
        $replacementPrice = $this->unitPrice($order, $product, $replacement);

        if (abs($replacementPrice - (float) $item->price) < 0.005) {
            return true;
        }

        $original = $product->variants->firstWhere('id', (int) $item->product_variant_id);

        return $original instanceof TenantVariant
            && abs($replacementPrice - $this->unitPrice($order, $product, $original)) < 0.005;
    }

    /** The effective unit price the storefront would charge now, for the order's shipping country. */
    private function unitPrice(Order $order, TenantProduct $product, TenantVariant $variant): float
    {
        $countryId = (int) (($order->shipping_address ?? [])['country_id'] ?? 0) ?: null;

        return round((float) $product->storefrontPricing($variant, $countryId)['current_price'], 2);
    }

    private function reasonRequiresVideo(ReturnReason $reason, array $policy): bool
    {
        if (! empty($policy['video_required'])) {
            return true;
        }

        if (! empty($policy['video_required_reasons'])) {
            return in_array($reason->value, $policy['video_required_reasons'], true);
        }

        return $reason->requiresVideo();
    }

    /** @param array<string, mixed> $data */
    private function resolveItem(Order $order, array $data): ?OrderItem
    {
        $items = $order->items;

        if (filled($data['order_item_id'] ?? null)) {
            return $items->firstWhere('id', (int) $data['order_item_id']);
        }

        if (filled($data['product_variant_id'] ?? null)) {
            return $items->firstWhere('product_variant_id', (int) $data['product_variant_id']);
        }

        if (filled($data['product_id'] ?? null)) {
            return $items->firstWhere('product_id', (int) $data['product_id']);
        }

        return $items->count() === 1 ? $items->first() : null;
    }

    /** Requests on the same order line (legacy rows without order_item_id match by product / variant). */
    private function itemRequestsQuery(string $tenantId, string $orderNumber, OrderItem $item): Builder
    {
        return ReturnRequest::query()
            ->where('tenant_id', $tenantId)
            ->where('order_number', $orderNumber)
            ->where(fn (Builder $query) => $query
                ->where('order_item_id', $item->id)
                ->orWhere(fn (Builder $legacy) => $legacy
                    ->whereNull('order_item_id')
                    ->where('product_id', $item->product_id)
                    ->when($item->product_variant_id, fn (Builder $q) => $q->where('product_variant_id', $item->product_variant_id))));
    }

    private function openRequestsQuery(string $tenantId, string $orderNumber): Builder
    {
        return ReturnRequest::query()
            ->where('tenant_id', $tenantId)
            ->where('order_number', $orderNumber)
            ->whereIn('status', $this->statusValues(ReturnStatus::openStatuses()));
    }

    // ─── Internals: state machine ────────────────────────────────────────────

    /** @throws ReturnActionException */
    private function assertCanTransition(ReturnRequest $returnRequest, ReturnStatus $to): void
    {
        $from = $returnRequest->status;

        if (! $from instanceof ReturnStatus || ! $from->canTransitionTo($to)) {
            throw ReturnActionException::invalidTransition($from instanceof ReturnStatus ? $from : null, $to);
        }
    }

    /**
     * Assert and claim the status change (… WHERE status = current), recording the staff reviewer.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ReturnActionException
     */
    private function transition(ReturnRequest $returnRequest, ReturnStatus $to, RefundActor $actor, array $attributes = []): ReturnRequest
    {
        $this->assertCanTransition($returnRequest, $to);
        $from = $returnRequest->status;

        $values = array_merge($attributes, ['status' => $to, 'updated_at' => now()]);

        if ($actor->isStaff()) {
            $values = array_merge($values, $this->reviewerAttributes($actor));
        }

        $claimed = ReturnRequest::query()
            ->whereKey($returnRequest->getKey())
            ->where('status', $from->value)
            ->update($this->serialize($values)) === 1;

        if (! $claimed) {
            $returnRequest->refresh();

            throw ReturnActionException::invalidTransition($returnRequest->status, $to);
        }

        return $returnRequest->refresh();
    }

    /** @return array<string, mixed> */
    private function reviewerAttributes(RefundActor $actor): array
    {
        $attributes = [
            'reviewed_by_type' => $actor->type,
            'reviewed_by_id' => $actor->id,
            'reviewed_at' => now(),
        ];

        if ($actor->type === CancellationActor::Admin) {
            $attributes['reviewed_by_admin_id'] = $actor->id;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function serialize(array $values): array
    {
        return array_map(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value, $values);
    }

    /** @throws ReturnActionException when a customer tries a staff action */
    private function staffActor(CancellationActor|RefundActor $actor, ?int $actorId): RefundActor
    {
        $actor = RefundActor::from($actor, $actorId);

        if ($actor->type === CancellationActor::Customer) {
            throw ReturnActionException::notAllowed(__('Only the store or the support team can do this.'));
        }

        return $actor;
    }

    private function assertOwnedBy(ReturnRequest $returnRequest, int $customerId): void
    {
        if ((int) $returnRequest->customer_id !== $customerId) {
            throw ReturnActionException::notAllowed(__('Return request not found.'));
        }
    }

    private function hasOpenRefund(ReturnRequest $returnRequest): bool
    {
        if (! $returnRequest->exists) {
            return false;
        }

        $open = array_filter(RefundStatus::cases(), fn (RefundStatus $status) => $status->isOpen());

        return Refund::query()
            ->where('return_request_id', $returnRequest->id)
            ->whereIn('status', $this->statusValues($open))
            ->exists();
    }

    // ─── Internals: stock ────────────────────────────────────────────────────

    /**
     * Re-check and reserve the exchange replacement (once — replacement_reserved_at).
     * Returns true when this call made the reservation.
     *
     * @throws ReturnActionException when the replacement is unavailable
     */
    private function reserveReplacement(ReturnRequest $returnRequest, Tenant $tenant): bool
    {
        if (! $returnRequest->isExchange() || $returnRequest->replacement_reserved_at !== null) {
            return false;
        }

        $quantity = $this->replacementQuantity($returnRequest);

        $reserved = $this->inTenant($tenant, function () use ($returnRequest, $quantity): bool {
            $variant = TenantVariant::query()->with('centralVariant')->find((int) $returnRequest->replacement_product_variant_id);
            $product = $variant ? $this->findProduct((int) $variant->product_id) : null;

            if (! $variant || ! $product || ! $variant->active) {
                throw ReturnActionException::fromErrors([__('The replacement option is no longer available.')], ReturnActionException::OUT_OF_STOCK);
            }

            $variant->setRelation('product', $product);

            if ($error = $this->stockError($product, $variant, $quantity)) {
                throw ReturnActionException::fromErrors([$error], ReturnActionException::OUT_OF_STOCK);
            }

            $claimed = ReturnRequest::query()
                ->whereKey($returnRequest->getKey())
                ->whereNull('replacement_reserved_at')
                ->update(['replacement_reserved_at' => now()]) === 1;

            if (! $claimed) {
                return false;
            }

            try {
                $this->stockService->reserveVariant($variant, $quantity);
            } catch (Throwable $e) {
                ReturnRequest::query()->whereKey($returnRequest->getKey())->update(['replacement_reserved_at' => null]);

                throw $e;
            }

            return true;
        });

        $returnRequest->refresh();

        return $reserved;
    }

    /** Give a reserved, not yet shipped replacement back to stock (idempotent). */
    private function releaseReplacement(ReturnRequest $returnRequest, Tenant $tenant): bool
    {
        if ($returnRequest->replacement_reserved_at === null
            || $returnRequest->exchange_shipped_at !== null
            || ! $returnRequest->replacement_product_variant_id) {
            return false;
        }

        $claimed = ReturnRequest::query()
            ->whereKey($returnRequest->getKey())
            ->whereNotNull('replacement_reserved_at')
            ->whereNull('exchange_shipped_at')
            ->update(['replacement_reserved_at' => null]) === 1;

        if (! $claimed) {
            return false;
        }

        $quantity = $this->replacementQuantity($returnRequest);

        $this->inTenant($tenant, function () use ($returnRequest, $quantity): void {
            $variant = TenantVariant::query()->find((int) $returnRequest->replacement_product_variant_id);

            if ($variant) {
                $this->stockService->releaseVariant($variant, $quantity);
            }
        });

        $returnRequest->refresh();

        return true;
    }

    /**
     * Put the returned units back into stock, once.
     *
     * @return 'restocked'|'already'|'skipped'
     */
    private function restockReturnedItem(ReturnRequest $returnRequest, Tenant $tenant): string
    {
        $claimed = ReturnRequest::query()
            ->whereKey($returnRequest->getKey())
            ->whereNull('restocked_at')
            ->update(['restocked_at' => now()]) === 1;

        if (! $claimed) {
            return 'already';
        }

        try {
            $done = $this->inTenant($tenant, function () use ($returnRequest): bool {
                $order = Order::query()->with('items')->where('uuid', $returnRequest->order_number)->first();
                $item = $order ? $this->resolveItem($order, $returnRequest->only(['order_item_id', 'product_variant_id', 'product_id'])) : null;

                if (! $item) {
                    return false;
                }

                // An exchange keeps the sale alive (the replacement unit), so sold_count stays.
                return $this->stockService->restockItem($item, max(1, (int) $returnRequest->quantity), ! $returnRequest->isExchange());
            });
        } catch (Throwable $e) {
            ReturnRequest::query()->whereKey($returnRequest->getKey())->update(['restocked_at' => null]);

            throw $e;
        }

        if (! $done) {
            ReturnRequest::query()->whereKey($returnRequest->getKey())->update(['restocked_at' => null]);
        }

        $returnRequest->refresh();

        return $done ? 'restocked' : 'skipped';
    }

    private function replacementQuantity(ReturnRequest $returnRequest): int
    {
        return max(1, (int) ($returnRequest->replacement_quantity ?: $returnRequest->quantity ?: 1));
    }

    // ─── Internals: lookups & labels ─────────────────────────────────────────

    private function findProduct(int $productId): ?TenantProduct
    {
        return TenantProduct::query()
            ->withoutGlobalScope('centralVisible')
            ->with(['variants.centralVariant', 'centralProduct'])
            ->find($productId);
    }

    private function itemLabel(OrderItem $item): string
    {
        $product = $item->product_id ? TenantProduct::query()->withoutGlobalScope('centralVisible')->find($item->product_id) : null;
        $name = $product ? ($product->translationValue('name') ?: $product->slug) : __('Item');
        $variant = $item->product_variant_id ? TenantVariant::query()->find($item->product_variant_id) : null;
        $variantLabel = $variant?->display_label;

        return $variantLabel ? sprintf('%s (%s)', $name, $variantLabel) : (string) $name;
    }

    private function variantLabel(TenantVariant $variant): string
    {
        return (string) ($variant->display_label ?: ($variant->translationValue('title') ?: ($variant->sku ?: '#'.$variant->id)));
    }

    private function returnInstructions(ReturnRequest $returnRequest): string
    {
        return match ($returnRequest->return_method) {
            ReturnMethod::CourierPickup => __('A courier will contact you to collect the item.'),
            ReturnMethod::DropOff => __('Please drop the item off at the store.'),
            ReturnMethod::ShipBack => __('Please ship the item back to the store.'),
            default => __('Please follow the store\'s instructions to return the item.'),
        };
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return T|null
     */
    private function resolveEnum(string $enum, mixed $value): ?\BackedEnum
    {
        if ($value instanceof $enum) {
            return $value;
        }

        return is_string($value) || is_int($value) ? $enum::tryFrom((string) $value) : null;
    }

    /**
     * @param  iterable<\BackedEnum>  $statuses
     * @return list<string>
     */
    private function statusValues(iterable $statuses): array
    {
        $values = [];

        foreach ($statuses as $status) {
            $values[] = (string) $status->value;
        }

        return $values;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function money(float $amount, ?string $currency = null): string
    {
        return sprintf('%s %s', $currency ?: 'USD', number_format($amount, 2));
    }

    private function authorType(RefundActor $actor): string
    {
        return match ($actor->type) {
            CancellationActor::Admin => ReturnRequestNote::AUTHOR_ADMIN,
            CancellationActor::Vendor => ReturnRequestNote::AUTHOR_TENANT,
            CancellationActor::Customer => ReturnRequestNote::AUTHOR_CUSTOMER,
            CancellationActor::System => ReturnRequestNote::AUTHOR_SYSTEM,
        };
    }

    /** @throws ReturnActionException */
    private function tenantOrFail(string $tenantId): Tenant
    {
        return $this->findTenant($tenantId) ?? throw ReturnActionException::notFound();
    }

    // ─── Internals: side effects ─────────────────────────────────────────────

    protected function storeMedia(ReturnRequest $returnRequest, UploadedFile $file, string $type): ReturnRequestMedia
    {
        $path = $file->store('return-evidence', 'public');

        // Store the full tenant_asset() URL so ReturnRequestMedia::url() works
        // regardless of which tenant's storage the file was written to.
        return ReturnRequestMedia::create([
            'return_request_id' => $returnRequest->id,
            'file_path' => tenant_asset($path),
            'type' => $type,
        ]);
    }

    /** Order activity in the tenant DB. Never breaks the workflow. */
    private function orderActivity(Tenant $tenant, ReturnRequest $returnRequest, string $title, string $description): void
    {
        try {
            $this->inTenant($tenant, function () use ($returnRequest, $title, $description): void {
                $order = Order::query()->where('uuid', $returnRequest->order_number)->first();

                $order?->activities()->create([
                    'status' => $order->status?->value,
                    'title' => $title,
                    'description' => $description,
                ]);
            });
        } catch (Throwable $e) {
            Log::warning('Return request activity failed', ['return_request_id' => $returnRequest->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * B.9 fan-out: tenant + admin notification, and (optionally) the customer email
     * (TenantReturnUpdate) with $customerMessage. Restores the caller's tenancy context and
     * never breaks the workflow.
     */
    protected function notify(ReturnRequest $returnRequest, string $title, string $message, bool $emailCustomer = false, ?string $customerMessage = null): void
    {
        $tenant = $this->findTenant((string) $returnRequest->tenant_id);
        $data = ['return_request_id' => $returnRequest->id, 'order_number' => $returnRequest->order_number, 'status' => $returnRequest->status?->value];

        try {
            if ($tenant) {
                $this->inTenant($tenant, fn () => $this->tenantNotificationService->notify($tenant, 'return', $title, $message, $data));
            }

            $this->adminNotificationService->notify(
                'return',
                $title,
                sprintf('%s (Order %s, Tenant %s)', $message, $returnRequest->order_number, $tenant?->name ?? $returnRequest->tenant_id),
                $data + ['tenant_id' => $returnRequest->tenant_id],
            );

            if ($emailCustomer && $tenant) {
                $this->inTenant($tenant, function () use ($returnRequest, $customerMessage): void {
                    $order = Order::query()->where('uuid', $returnRequest->order_number)->first();

                    if ($order) {
                        $this->mailService->sendReturnStatusUpdate($returnRequest, $order, $customerMessage);
                    }
                });
            }
        } catch (Throwable $e) {
            Log::warning('Return request notification failed', ['return_request_id' => $returnRequest->id, 'error' => $e->getMessage()]);
        }
    }
}
