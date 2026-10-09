<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Enums\RefundMethod;
use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use App\Exceptions\OrderActionException;
use App\Exceptions\RefundException;
use App\Models\AdminUser;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\Tenant;
use App\Models\Tenant\AdminUser as TenantAdminUser;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product as TenantProduct;
use App\Models\Tenant\ProductVariant as TenantVariant;
use App\Services\Orders\OrderCancellationPolicy;
use App\Services\Orders\OrderCancellationService;
use App\Services\Orders\OrderPolicyService;
use App\Services\Refunds\RefundActor;
use App\Services\Refunds\RefundService;
use App\Services\Tenant\StockService;
use App\Support\Tenancy\RunsInTenant;
use App\Support\Tenant\Refunds\RefundPresenter;
use Throwable;

/**
 * Central-admin side of the cancel / refund / return features (RETURN_EXCHANGE_REFUND_PLAN.md B.8
 * Admin). The admin pages run in the central context, the orders live in the tenant DBs: every
 * tenant read / write here goes through inTenant(), which restores the caller's context.
 */
class OrderAfterSalesService
{
    use RunsInTenant;

    public function __construct(
        private readonly OrderCancellationPolicy $policy,
        private readonly OrderCancellationService $cancellation,
        private readonly OrderPolicyService $policyService,
        private readonly RefundService $refunds,
        private readonly StockService $stock,
    ) {}

    /**
     * Cancellation + refunds context of one order for the admin order page.
     *
     * @return array<string, mixed>
     */
    public function orderContext(string $tenantId, string $orderNumber, bool $canManage): array
    {
        $tenant = $this->tenantOrFail($tenantId);

        $context = $this->inTenant($tenant, function () use ($tenant, $tenantId, $orderNumber, $canManage): array {
            $order = Order::query()->with('items')->where('uuid', $orderNumber)->first();

            if (! $order) {
                return [];
            }

            $paymentState = $order->paymentState();
            $refundable = $this->refunds->refundableAmount($order, $tenant);

            return [
                'status_value' => $order->status->value,
                'is_closed' => $order->status->isTerminal(),
                'can_cancel' => $canManage
                    && $this->policy->canCancel($order, CancellationActor::Admin, $this->policyService),
                'cancel_reasons' => CancellationReason::options(CancellationActor::Admin),
                'cancellation' => $order->isCancelled() ? [
                    'status' => $order->status->label(),
                    'reason' => $order->cancellation_reason?->label(),
                    'note' => $order->cancellation_note,
                    'cancelled_at' => $order->cancelled_at?->format('M d, Y H:i'),
                    'cancelled_by' => $order->cancelled_by_type?->label(),
                    'cancelled_by_type' => $order->cancelled_by_type,
                    'cancelled_by_id' => $order->cancelled_by_id,
                ] : null,
                'can_manage' => $canManage,
                'can_refund' => $canManage && $refundable > 0,
                'refundable_amount' => $refundable,
                'refunded_amount' => round((float) $order->refunded_amount, 2),
                'payment_state' => $paymentState->value,
                'payment_state_label' => $paymentState->label(),
                'payment_state_color' => $paymentState->color(),
                'refunds' => $this->refundRows(Refund::query()->forOrder($tenantId, $orderNumber)->latest('id')->get()),
            ];
        });

        if (is_array($context['cancellation'] ?? null)) {
            $cancellation = &$context['cancellation'];
            // Resolved outside the tenant context: the central admins table lives in the central DB.
            $cancellation['cancelled_by'] = $this->cancelledBy($tenant, $cancellation['cancelled_by_type'], $cancellation['cancelled_by_id']);
            unset($cancellation['cancelled_by_type'], $cancellation['cancelled_by_id']);
        }

        return $context;
    }

    /**
     * Cancel the order as the platform admin (admin reasons, policy re-checked under the lock).
     *
     * @throws OrderActionException with the policy message when the order can no longer be cancelled
     */
    public function cancel(string $tenantId, string $orderNumber, string $reason, ?string $note, ?int $adminId): Order
    {
        $tenant = $this->tenantOrFail($tenantId);
        $reason = CancellationReason::tryFrom($reason) ?? throw OrderActionException::invalidReason();

        return $this->inTenant($tenant, function () use ($orderNumber, $reason, $note, $adminId): Order {
            $order = Order::query()->where('uuid', $orderNumber)->first() ?? throw OrderActionException::notFound();

            return $this->cancellation->cancel($order, CancellationActor::Admin, $adminId, $reason, $note);
        });
    }

    /**
     * A manual refund on a paid order (source = manual); sent to the original payment method right
     * away when the gateway can refund.
     *
     * @throws RefundException
     */
    public function manualRefund(string $tenantId, string $orderNumber, float $amount, string $reason, RefundActor $actor): Refund
    {
        $tenant = $this->tenantOrFail($tenantId);

        $order = $this->inTenant($tenant, fn () => Order::query()->with('items')->where('uuid', $orderNumber)->first())
            ?? throw RefundException::notFound();

        $refund = $this->refunds->create($order, RefundSource::Manual, $amount, $reason, $actor, null, [], $tenant);

        if ($refund->refund_method === RefundMethod::OriginalPayment) {
            $refund = $this->refunds->execute($refund, $actor);
        }

        return $refund;
    }

    /** Retry a failed refund / send a pending one to the gateway. */
    public function retryRefund(Refund $refund, RefundActor $actor): Refund
    {
        return $refund->status === RefundStatus::Pending
            ? $this->refunds->execute($refund, $actor)
            : $this->refunds->retry($refund, $actor);
    }

    /** @return array<string, mixed> */
    public function refundRow(Refund $refund): array
    {
        return RefundPresenter::panel($refund);
    }

    /**
     * @param  iterable<Refund>  $refunds
     * @return list<array<string, mixed>>
     */
    public function refundRows(iterable $refunds): array
    {
        $rows = [];

        foreach ($refunds as $refund) {
            $rows[] = RefundPresenter::panel($refund);
        }

        return $rows;
    }

    /**
     * Tenant-side facts of a return request for the admin return page: the returned item, the
     * replacement variant with its live stock, the vendor reviewer's name and (when a refund can
     * be issued) the calculated refund breakdown.
     *
     * @return array{item: string|null, replacement: array<string, mixed>|null, vendor_reviewer: string|null, refund_breakdown: array<string, mixed>|null}
     */
    public function returnContext(ReturnRequest $record, bool $withBreakdown): array
    {
        $tenant = $this->findTenant((string) $record->tenant_id);

        if (! $tenant) {
            return ['item' => null, 'replacement' => null, 'vendor_reviewer' => null, 'refund_breakdown' => null];
        }

        $context = $this->inTenant($tenant, function () use ($record): array {
            $item = $record->order_item_id ? OrderItem::query()->find((int) $record->order_item_id) : null;

            return [
                'item' => $item ? $this->itemLabel($item) : null,
                'replacement' => $this->replacement($record),
                'vendor_reviewer' => $record->reviewed_by_type === CancellationActor::Vendor && $record->reviewed_by_id
                    ? TenantAdminUser::query()->whereKey((int) $record->reviewed_by_id)->value('name')
                    : null,
            ];
        });

        $context['refund_breakdown'] = null;

        if ($withBreakdown) {
            try {
                $context['refund_breakdown'] = $this->refunds->calculateForReturn($record)->toArray();
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $context;
    }

    /** Who cancelled the order, e.g. "Support (Jane)" / "Store (Sam)" / "Customer". */
    private function cancelledBy(Tenant $tenant, ?CancellationActor $type, ?int $id): ?string
    {
        if (! $type) {
            return null;
        }

        $name = match (true) {
            ! $id => null,
            $type === CancellationActor::Admin => AdminUser::query()->whereKey($id)->value('name'),
            $type === CancellationActor::Vendor => $this->inTenant($tenant, fn () => TenantAdminUser::query()->whereKey($id)->value('name')),
            default => null,
        };

        return $name ? sprintf('%s (%s)', $type->label(), $name) : $type->label();
    }

    private function itemLabel(OrderItem $item): string
    {
        $product = $item->product_id ? TenantProduct::query()->withoutGlobalScope('centralVisible')->find($item->product_id) : null;
        $name = $product ? ($product->translationValue('name') ?: $product->slug) : __('Item');
        $variant = $item->product_variant_id ? TenantVariant::query()->find($item->product_variant_id) : null;

        return $variant?->display_label ? sprintf('%s (%s)', $name, $variant->display_label) : (string) $name;
    }

    /** @return array{label: string, quantity: int, stock: int|null, reserved: bool}|null */
    private function replacement(ReturnRequest $record): ?array
    {
        if (! $record->replacement_product_variant_id) {
            return null;
        }

        $variant = TenantVariant::query()->with('centralVariant')->find((int) $record->replacement_product_variant_id);
        $product = $variant ? TenantProduct::query()->withoutGlobalScope('centralVisible')->with('centralProduct')->find($variant->product_id) : null;

        return [
            'label' => $variant
                ? (string) ($variant->display_label ?: ($variant->sku ?: '#'.$variant->id))
                : '#'.$record->replacement_product_variant_id,
            'quantity' => (int) ($record->replacement_quantity ?: $record->quantity ?: 1),
            'stock' => $variant && $product ? $this->stock->availableStock($product, $variant) : 0,
            'reserved' => $record->replacement_reserved_at !== null,
        ];
    }

    private function tenantOrFail(string $tenantId): Tenant
    {
        return $this->findTenant($tenantId) ?? throw OrderActionException::notFound();
    }
}
