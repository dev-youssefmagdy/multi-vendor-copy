<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Sales;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Enums\OrderShippingStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use App\Exceptions\Tenant\PanelActionException;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Sales\CancelOrderRequest;
use App\Http\Requests\Tenant\Panel\Sales\StoreManualRefundRequest;
use App\Http\Requests\Tenant\Panel\Sales\UpdateShippingStatusRequest;
use App\Models\Refund;
use App\Models\Tenant\AdminUser;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Orders\OrderCancellationPolicy;
use App\Services\Orders\OrderCancellationService;
use App\Services\Orders\OrderPolicyService;
use App\Services\Refunds\RefundActor;
use App\Services\Refunds\RefundService;
use App\Services\Tenant\OrderLifecycleService;
use App\Services\Tenant\VendorPurchaseService;
use App\Support\OrderProfitCalculator;
use App\Support\Tenant\Metric;
use App\Support\Tenant\Payments\InlineGatewayPresenter;
use App\Support\Tenant\Refunds\RefundPresenter;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Yajra\DataTables\Facades\DataTables;

final class OrdersController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly VendorPurchaseService $purchaseService,
        private readonly InlineGatewayPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $stats = $this->repo->orderStats();

        return view('tenant.pages.sales.orders.index', [
            'stats' => Metric::cards([
                ['label' => 'Orders', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Orders in this tenant database.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Paid', 'value' => $stats['paid'], 'format' => 'number', 'caption' => 'Orders with successful payment capture.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Processing', 'value' => $stats['processing'], 'format' => 'number', 'caption' => 'Orders currently moving through fulfillment.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Collected', 'value' => $stats['collected'], 'format' => 'currency', 'caption' => 'Paid order value collected by the tenant.', 'dot' => 'dot-violet'],
            ]),
            'statusOptions' => collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status) => [$status->value => $status->label()])->all(),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('uuid', 'Order'),
                TableColumn::make('customer', 'Customer')->orderable(false),
                TableColumn::make('value', 'Value')->name('grand_total'),
                TableColumn::make('commission', 'Commission')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::make('gateway', 'Gateway')->orderable(false)->searchable(false),
                TableColumn::make('placed_at', 'Placed At')->name('created_at'),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status']);

        return DataTables::eloquent($this->repo->queryOrders($filters))
            ->addIndexColumn()
            ->editColumn('uuid', fn (Order $order) => view('tenant.pages.sales.orders._cols.order', ['order' => $order])->render())
            ->editColumn('customer', fn (Order $order) => view('tenant.pages.sales.orders._cols.customer', ['order' => $order])->render())
            ->editColumn('value', fn (Order $order) => view('tenant.pages.sales.orders._cols.value', ['order' => $order])->render())
            ->editColumn('commission', fn (Order $order) => view('tenant.pages.sales.orders._cols.commission', ['order' => $order])->render())
            ->editColumn('status', fn (Order $order) => view('tenant::components.status-badge', ['status' => $order->status])->render())
            ->editColumn('gateway', fn (Order $order) => view('tenant.pages.sales.orders._cols.gateway', ['order' => $order])->render())
            ->editColumn('placed_at', fn (Order $order) => $order->created_at ? strtoupper($order->created_at->format('D')).$order->created_at->format(', d M,Y') : null)
            ->addColumn('actions', fn (Order $order) => view('tenant.pages.sales.orders._cols.actions', ['order' => $order])->render())
            ->orderColumn('value', 'grand_total $1')
            ->orderColumn('placed_at', 'created_at $1')
            ->rawColumns(['uuid', 'customer', 'value', 'commission', 'status', 'gateway', 'actions'])
            ->toJson();
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request, ['search', 'status']);

        $headers = ['Order UUID', 'Customer', 'Customer Email', 'Items', 'Grand Total', 'Discount %', 'Shipping', 'Vendor Commission', 'Status', 'Payment Method', 'Paid', 'Placed At'];

        $rows = $this->repo->exportOrders($filters)->map(fn (Order $order) => [
            $order->uuid,
            $order->customer?->full_name ?? 'Guest',
            $order->customer?->email ?? '',
            $order->items_count,
            number_format((float) $order->grand_total, 2),
            number_format((float) $order->discount_percentage, 2),
            number_format((float) $order->resolved_shipping_charge, 2),
            number_format(OrderProfitCalculator::effectiveTenantProfitForOrder($order), 2),
            $order->status->label(),
            str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()->toString(),
            $order->paid ? 'Paid' : 'Unpaid',
            $order->created_at?->format('Y-m-d H:i') ?? '',
        ]);

        return $this->streamCsv('orders-'.now()->format('Y-m-d').'.csv', $headers, $rows);
    }

    public function show(int $orderId): View
    {
        $order = Order::query()->findOrFail($orderId);

        return view('tenant.pages.sales.orders.show', [
            'orderId' => $orderId,
            'order' => $this->repo->orderDetail($order),
            'shippingStatuses' => OrderShippingStatus::cases(),
            'settlement' => $this->settlementContext($order),
            'afterSales' => $this->afterSalesContext($order),
        ]);
    }

    /**
     * Cancellation + refunds context for the order detail page (RETURN_EXCHANGE_REFUND_PLAN.md B.8
     * Vendor). Actions are only offered when the policy and the user's permissions allow them; the
     * endpoints enforce the same rules.
     *
     * @return array<string, mixed>
     */
    private function afterSalesContext(Order $order): array
    {
        $user = auth('tenant')->user();
        $canManageOrders = $user instanceof AdminUser && $user->hasPermission('sales.orders.manage');
        $canManageRefunds = $user instanceof AdminUser && $user->hasPermission('sales.returns.manage');
        $paymentState = $order->paymentState();

        return [
            'can_cancel' => $canManageOrders
                && app(OrderCancellationPolicy::class)->canCancel($order, CancellationActor::Vendor, app(OrderPolicyService::class)),
            'cancel_reasons' => CancellationReason::options(CancellationActor::Vendor),
            'cancellation' => $order->isCancelled() ? [
                'status' => $order->status->label(),
                'reason' => $order->cancellation_reason?->label(),
                'note' => $order->cancellation_note,
                'cancelled_at' => $order->cancelled_at?->format('M d, Y H:i'),
                'cancelled_by' => $order->cancelled_by_type?->label(),
            ] : null,
            'can_manage_refunds' => $canManageRefunds,
            'refundable_amount' => app(RefundService::class)->refundableAmount($order),
            'refunded_amount' => round((float) $order->refunded_amount, 2),
            'payment_state' => $paymentState->value,
            'payment_state_label' => $paymentState->label(),
            'payment_state_color' => $paymentState->color(),
            'refunds' => $order->refundsQuery()->latest('id')->get()->map(fn (Refund $refund) => RefundPresenter::panel($refund))->values()->all(),
        ];
    }

    /**
     * Pay-central context for the order detail page — mirrors Finance > Vendor Purchases > Pay Central.
     * Null when the order owes nothing, is already settled, or the user cannot settle purchases.
     */
    private function settlementContext(Order $order): ?array
    {
        $user = auth('tenant')->user();

        if (
            (float) $order->vendor_cost <= 0
            || $order->vendor_settled
            || ! $user instanceof AdminUser
            || ! $user->hasPermission('finance.vendor-purchases.view')
        ) {
            return null;
        }

        return [
            'breakdown' => $this->purchaseService->previewBreakdown($order, null),
            'presented' => $this->presenter->present(collect($this->repo->centralGatewaysForPayment())),
        ];
    }

    public function updateShippingStatus(UpdateShippingStatusRequest $request, int $orderId): JsonResponse
    {
        $order = Order::query()->findOrFail($orderId);
        $detail = $this->repo->orderDetail($order);

        if (! ($detail['can_update_shipping'] ?? false)) {
            throw new PanelActionException('Shipping status can only be updated for your own products orders.', 403);
        }

        $status = OrderShippingStatus::tryFrom($request->validated('shipping_status'));

        if (! $status) {
            return $this->failure('Invalid shipping status selected.', 422);
        }

        try {
            $updated = app(OrderLifecycleService::class)->updateShippingStatus($order, $status, CancellationActor::Vendor, $this->adminId());
        } catch (InvalidArgumentException $e) {
            return $this->failure($e->getMessage(), 422);
        }

        $freshDetail = $this->repo->orderDetail($updated);

        return $this->success('Shipping status updated successfully.', [
            'shipping_status' => $freshDetail['shipping_status'],
            'shipping_status_value' => $freshDetail['shipping_status_value'],
            'status' => $freshDetail['status'],
            'status_value' => $freshDetail['status_value'],
            'panel' => view('tenant.pages.sales.orders._partials.shipping-status', [
                'order' => $freshDetail,
                'orderId' => $orderId,
                'shippingStatuses' => OrderShippingStatus::cases(),
            ])->render(),
        ]);
    }

    public function validateUpdateShippingStatus(UpdateShippingStatusRequest $request, int $orderId): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function validateCancel(CancelOrderRequest $request, int $orderId): JsonResponse
    {
        return $this->validFormResponse();
    }

    /**
     * Vendor cancels the order (staff reason + note). Policy, stock restore, cancellation refund
     * and notifications are handled by OrderCancellationService; a blocked cancellation renders
     * as a 422 with the policy message (OrderActionException).
     */
    public function cancel(CancelOrderRequest $request, int $orderId, OrderCancellationService $service): JsonResponse
    {
        $order = $service->cancel(
            Order::query()->findOrFail($orderId),
            CancellationActor::Vendor,
            $this->adminId(),
            $request->cancellationReason(),
            $request->cancellationNote(),
        );

        return $this->success(__('Order cancelled successfully.'), $this->orderRefundState($order->load('items')));
    }

    public function validateStoreRefund(StoreManualRefundRequest $request, int $orderId): JsonResponse
    {
        return $this->validFormResponse();
    }

    /**
     * Manual (goodwill / remaining) refund on a paid order. Approved by the vendor at creation and
     * sent to the original gateway right away when it supports refunds; otherwise it stays
     * pending until it is marked completed manually.
     */
    public function storeRefund(StoreManualRefundRequest $request, int $orderId, RefundService $refunds): JsonResponse
    {
        $order = Order::query()->with('items')->findOrFail($orderId);
        $admin = auth('tenant')->user();
        $actor = RefundActor::vendor($admin?->id, $admin?->name);

        $refund = $refunds->create(
            $order,
            RefundSource::Manual,
            (float) $request->validated('amount'),
            (string) $request->validated('reason'),
            $actor,
        );

        if ($refund->refund_method === RefundMethod::OriginalPayment) {
            $refund = $refunds->execute($refund, $actor);
        }

        $message = match ($refund->status) {
            RefundStatus::Completed => __('Refund completed.'),
            RefundStatus::Failed => __('The refund was created but the payment gateway could not process it. Retry it or complete it manually.'),
            default => __('Refund created. Mark it as completed once the money has been returned to the customer.'),
        };

        return $this->success($message, array_merge(
            ['refund' => RefundPresenter::panel($refund)],
            $this->orderRefundState($order->refresh()->load('items')),
        ));
    }

    private function adminId(): ?int
    {
        $id = auth('tenant')->id();

        return $id !== null ? (int) $id : null;
    }

    /** @return array<string, mixed> */
    private function orderRefundState(Order $order): array
    {
        return [
            'status' => $order->status->label(),
            'status_value' => $order->status->value,
            'payment_state' => $order->paymentState()->value,
            'payment_state_label' => $order->paymentState()->label(),
            'refunded_amount' => round((float) $order->refunded_amount, 2),
            'refundable_amount' => app(RefundService::class)->refundableAmount($order),
            'refunds' => $order->refundsQuery()->latest('id')->get()->map(fn (Refund $refund) => RefundPresenter::panel($refund))->values()->all(),
        ];
    }
}
