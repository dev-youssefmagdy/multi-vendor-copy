<?php

namespace App\Services\Admin;

use App\Enums\ActivationStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\PaymentLogStatus;
use App\Enums\OrderShippingStatus;
use App\Enums\OrderStatus;
use App\Models\Tenant;
use App\Models\Tenant\Currency as TenantCurrency;
use App\Models\Tenant\Order as TenantOrder;
use App\Models\Tenant\Subscription;
use App\Models\Tenant\Transaction as TenantTransaction;
use App\Support\OrderProfitCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class TenantAdminAggregateService
{
    protected ?Collection $ordersCache = null;

    protected ?Collection $walletsCache = null;

    protected ?Collection $transactionsCache = null;

    protected ?Collection $paymentLogsCache = null;

    public function orders(): Collection
    {
        if ($this->ordersCache !== null) {
            return $this->ordersCache;
        }

        return $this->ordersCache = $this->collectAcrossTenants(function (Tenant $tenant) {
            return TenantOrder::query()
                ->with([
                    'customer',
                    'items.product.translations.language',
                    'items.product.files',
                    'items.variant.product.translations.language',
                    'items.variant.product.files',
                    'items.variant.centralVariant.options.translations.language',
                    'items.variant.centralVariant.files',
                    'paymentGateway',
                    'activities',
                ])
                ->get()
                ->map(fn(TenantOrder $order) => $this->mapOrder($tenant, $order));
        });
    }

    public function paymentLogs(): Collection
    {
        if ($this->paymentLogsCache !== null) {
            return $this->paymentLogsCache;
        }

        return $this->paymentLogsCache = $this->orders()
            ->map(fn(object $order) => (object) [
                'tenant' => $order->tenant,
                'tenant_id' => $order->tenant_id,
                'store_name' => $order->store_name,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'gateway' => $order->payment_method ?: $order->payment_gateway ?: 'Unknown gateway',
                'amount' => (float) $order->total_amount,
                'status' => $order->payment_status === OrderPaymentStatus::Paid
                    ? PaymentLogStatus::Paid
                    : (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true)
                        ? PaymentLogStatus::Failed
                        : PaymentLogStatus::Pending),
                'reference' => $order->payment_reference ?: $order->order_number,
                'paid_at' => $order->paid_at,
                'created_at' => $order->created_at,
                'meta' => $order->payment_details,
                'order' => $order,
            ])
            ->values();
    }

    public function wallets(): Collection
    {
        if ($this->walletsCache !== null) {
            return $this->walletsCache;
        }

        return $this->walletsCache = $this->collectAcrossTenants(function (Tenant $tenant) {
            $transactions = TenantTransaction::query()->with('model')->get();
            $orders = TenantOrder::query()->with('items')->get();
            $subscriptions = Subscription::query()->with('transaction')->latest()->get();

            $credits = (float) $transactions->where('type', 'credit')->sum('amount');
            $debits = (float) $transactions->where('type', 'debit')->sum('amount');
            $grossSales = (float) $orders->sum(fn(TenantOrder $order) => $order->grand_total);
            $paidOrders = $orders->filter(fn(TenantOrder $order) => $order->paid);
            $collectedSales = (float) $paidOrders->sum(fn(TenantOrder $order) => $order->grand_total);
            $ownerProfit = (float) $paidOrders->sum(fn(TenantOrder $order) => OrderProfitCalculator::effectiveOwnerProfitForOrder($order));
            $vendorNetBalance = (float) $paidOrders->sum(fn(TenantOrder $order) => max(0, OrderProfitCalculator::effectiveTenantProfitForOrder($order)));
            $pendingBalance = (float) $orders
                ->where('paid', false)
                ->filter(fn(TenantOrder $order) => !in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true))
                ->sum(fn(TenantOrder $order) => max(0, OrderProfitCalculator::effectiveTenantProfitForOrder($order)));

            $currency = TenantCurrency::query()->where('is_default', true)->value('code')
                ?? TenantCurrency::query()->orderByDesc('is_active')->value('code')
                ?? 'USD';

            return [
                (object) [
                    'tenant' => $tenant,
                    'tenant_id' => $tenant->getTenantKey(),
                    'store_name' => $this->tenantStoreName($tenant),
                    'currency' => $currency,
                    'balance' => round($vendorNetBalance + ($credits - $debits), 2),
                    'pending_balance' => round($pendingBalance, 2),
                    'gross_sales' => round($grossSales, 2),
                    'collected_sales' => round($collectedSales, 2),
                    'owner_profit' => round($ownerProfit, 2),
                    'active_subscriptions' => $subscriptions->filter(fn(Subscription $subscription) => $this->humanizeEnum($subscription->status) === 'Active')->count(),
                    'subscription_revenue' => round((float) $subscriptions->sum('price'), 2),
                    'transactions_count' => $transactions->count(),
                    'subscriptions' => $subscriptions->map(fn(Subscription $subscription) => [
                        'id' => $subscription->id,
                        'price' => (float) $subscription->price,
                        'status' => $this->humanizeEnum($subscription->status),
                        'term' => $this->humanizeEnum($subscription->term),
                        'gateway' => $this->humanizeEnum($subscription->payment_gateway_type),
                        'transaction_reference' => $subscription->transaction?->uuid,
                        'start_date' => $subscription->start_date,
                        'end_date' => $subscription->end_date,
                    ])->all(),
                    'recent_transactions' => $transactions->sortByDesc(fn(TenantTransaction $transaction) => $transaction->created_at?->getTimestamp() ?? 0)
                        ->take(5)
                        ->map(fn(TenantTransaction $transaction) => [
                            'reference' => $transaction->uuid,
                            'direction' => $this->humanizeEnum($transaction->type),
                            'amount' => (float) $transaction->amount,
                            'admin_profit' => (float) ($transaction->admin_profit ?? 0),
                            'description' => $transaction->description,
                            'created_at' => $transaction->created_at,
                        ])
                        ->values()
                        ->all(),
                    'recent_orders' => $orders->sortByDesc(fn(TenantOrder $order) => $order->created_at?->getTimestamp() ?? 0)
                        ->take(5)
                        ->map(fn(TenantOrder $order) => [
                            'order_number' => $order->uuid,
                            'customer_name' => $order->shipping_address['full_name'] ?? $order->customer?->full_name ?? 'Guest',
                            'total_amount' => (float) $order->grand_total,
                            'status' => $this->humanizeEnum($order->status),
                            'paid' => $order->paid,
                            'created_at' => $order->created_at,
                        ])
                        ->values()
                        ->all(),
                    'status' => $tenant->status === \App\Enums\TenantStatus::Active ? ActivationStatus::Active->value : ActivationStatus::Inactive->value,
                    'updated_at' => $transactions->max('created_at') ?? $subscriptions->max('updated_at') ?? $orders->max('updated_at') ?? $tenant->updated_at,
                ],
            ];
        });
    }

    public function transactions(): Collection
    {
        if ($this->transactionsCache !== null) {
            return $this->transactionsCache;
        }

        return $this->transactionsCache = $this->collectAcrossTenants(function (Tenant $tenant) {
            $wallet = (object) ['tenant' => $tenant];
            $subscriptions = Subscription::query()->with('transaction')->get()->keyBy('transaction_id');

            return TenantTransaction::query()
                ->with('model')
                ->get()
                ->map(function (TenantTransaction $transaction) use ($tenant, $wallet, $subscriptions) {
                    $subscription = $transaction->model instanceof Subscription
                        ? $transaction->model
                        : $subscriptions->get($transaction->id);

                    return (object) [
                        'tenant' => $tenant,
                        'tenant_id' => $tenant->getTenantKey(),
                        'store_name' => $this->tenantStoreName($tenant),
                        'reference' => $transaction->uuid,
                        'wallet' => $wallet,
                        'direction' => $transaction->type,
                        'amount' => (float) $transaction->amount,
                        'admin_profit' => (float) ($transaction->admin_profit ?? 0),
                        'description' => $transaction->description,
                        'model_type' => class_basename((string) $transaction->getAttribute('model_type')) ?: 'Ledger',
                        'model_label' => $subscription
                            ? sprintf('%s %s subscription', $this->humanizeEnum($subscription->term), strtolower($this->humanizeEnum($subscription->status)))
                            : ($transaction->description ?: 'Wallet ledger entry'),
                        'subscription' => $subscription ? [
                            'id' => $subscription->id,
                            'price' => (float) $subscription->price,
                            'status' => $this->humanizeEnum($subscription->status),
                            'term' => $this->humanizeEnum($subscription->term),
                        ] : null,
                        'created_at' => $transaction->created_at,
                    ];
                });
        });
    }

    protected function collectAcrossTenants(callable $callback): Collection
    {
        $items = collect();
        $tenants = Tenant::query()->orderBy('id')->get();

        foreach ($tenants as $tenantRecord) {
            /** @var Tenant $tenant */
            $tenant = $tenantRecord;
            $initialized = false;

            try {
                tenancy()->initialize($tenant);
                $initialized = true;

                $items = $items->concat(collect($callback($tenant)));
            } catch (Throwable) {
                continue;
            } finally {
                if ($initialized) {
                    tenancy()->end();
                }
            }
        }

        return $items->values();
    }

    protected function mapOrder(Tenant $tenant, TenantOrder $order): object
    {
        $shippingAddress = is_array($order->shipping_address) ? $order->shipping_address : [];
        $paymentDetails = is_array($order->payment_details) ? $order->payment_details : [];
        $financials = $this->orderFinancials($order);
        $paymentGateway = $order->paymentGateway?->name ?? $order->payment_method;

        return (object) [
            'id' => $order->id,
            'tenant' => $tenant,
            'tenant_id' => $tenant->getTenantKey(),
            'store_name' => $this->tenantStoreName($tenant),
            'order_number' => $order->uuid,
            'customer_name' => $shippingAddress['full_name'] ?? $shippingAddress['name'] ?? $order->customer?->full_name,
            'customer_email' => $shippingAddress['email'] ?? $order->customer?->email,
            'customer_phone' => $shippingAddress['phone'] ?? $order->customer?->phone,
            'payment_status' => $this->mapPaymentStatus($order),
            'status' => $order->status,
            'shipping_status' => $this->mapShippingStatus($order->status),
            'subtotal' => $financials['subtotal'],
            'items_discount' => $financials['items_discount'],
            'discount_percentage' => (float) ($order->discount_percentage ?? 0),
            'discount_amount' => $financials['discount'],
            'items_tax' => $financials['items_tax'],
            'tax_percentage' => (float) ($order->tax_percentage ?? 0),
            'tax_amount' => $financials['tax'],
            'shipping_charge' => $financials['shipping_total'],
            'owner_profit' => $financials['owner_profit'],
            'vendor_cost' => $order->vendor_cost,
            'vendor_net_total' => $financials['vendor_net_total'],
            'tenant_own_central' => $financials['tenant_own_central'],
            'central_own_tenant' => $financials['central_own_tenant'],
            'remaining_owner_profit' => $financials['remaining_owner_profit'],
            'remaining_tenant_profit' => $financials['remaining_tenant_profit'],
            'total_amount' => $financials['grand_total'],
            'payment_method' => $order->payment_method,
            'payment_gateway' => $paymentGateway,
            'vendor_gateway_id' => $order->vendor_gateway_id,
            'payment_reference' => $paymentDetails['transaction_id'] ?? data_get($paymentDetails, 'raw.transaction_id') ?? data_get($paymentDetails, 'raw.id'),
            'placed_at' => $order->created_at,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'paid_at' => $order->paid ? $order->updated_at : null,
            'tracking_number' => $order->tracking_number,
            'shipping_address' => $shippingAddress,
            'payment_details' => $paymentDetails,
            'items_count' => $order->items->count(),
            'items' => $order->items->map(function ($item) {
                $variantOptions = $item->variant?->centralVariant?->options?->map(function ($option) {
                    return method_exists($option, 'translationValue') ? ($option->translationValue('name') ?? 'Option') : ($option->name ?? 'Option');
                })->filter()->implode(' / ');

                // Resolve item image: prefer variant thumbnail, fall back to product primary image
                $imageUrl = $item->variant?->thumbnail_url ?? $item->product?->primary_image_url;

                return [
                    'name' => $this->orderItemName($item),
                    'variant' => $item->variant?->title ?: $variantOptions,
                    'image_url' => $imageUrl,
                    'qty' => (int) $item->qty,
                    'price' => (float) $item->price,
                    'discount' => (float) $item->discount,
                    'tax' => (float) $item->tax,
                    'shipping' => (float) $item->shipping_fee,
                    'line_total' => round((float) $item->sub_total - (float) $item->discount + (float) $item->tax + (float) $item->shipping_fee, 2),
                ];
            })->values()->all(),
            'activities' => $order->activities->map(fn($activity) => [
                'title' => $activity->title,
                'description' => $activity->description,
                'created_at' => $activity->created_at,
            ])->values()->all(),
        ];
    }

    protected function mapPaymentStatus(TenantOrder $order): OrderPaymentStatus
    {
        if (!$order->paid) {
            return in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true)
                ? OrderPaymentStatus::Failed
                : OrderPaymentStatus::Unpaid;
        }

        // For central-gateway orders (customer paid through central platform),
        // admin owes the tenant their share. Show Pending Payment until released.
        // $isCentralGateway = $order->paymentGateway !== null && !$order->paymentGateway->use_own;
        $isCentralGateway = $order->vendor_gateway_id === null || $order->vendor_gateway_id === 'central';

        if ($isCentralGateway && !$order->payout_released) {
            return OrderPaymentStatus::PendingPayment;
        }

        // For own-gateway orders (tenant collected payment directly), the tenant
        // owes central their product/shipping cost. Show Pending Payment until settled.
        if (!$isCentralGateway && (float) ($order->vendor_cost ?? 0) > 0 && $order->vendor_settled_at === null) {
            return OrderPaymentStatus::PendingPayment;
        }

        return OrderPaymentStatus::Paid;
    }

    protected function orderFinancials(TenantOrder $order): array
    {
        return [
            'subtotal' => (float) $order->subtotal,
            'items_discount' => (float) $order->items_discount,
            'discount' => (float) $order->discount_amount,
            'items_tax' => (float) $order->items_tax,
            'tax' => (float) $order->tax_amount,
            'shipping_total' => (float) $order->resolved_shipping_charge,
            'grand_total' => (float) $order->grand_total,
            'owner_profit' => OrderProfitCalculator::effectiveOwnerProfitForOrder($order),
            'vendor_net_total' => round(OrderProfitCalculator::effectiveTenantProfitForOrder($order), 2),
            'tenant_own_central' => round(OrderProfitCalculator::tenantOwnCentralForOrder($order), 2),
            'central_own_tenant' => round(OrderProfitCalculator::centralOwnTenantForOrder($order), 2),
            'remaining_owner_profit' => round(OrderProfitCalculator::remainingTenantOwnCentralForOrder($order), 2),
            'remaining_tenant_profit' => round(OrderProfitCalculator::remainingCentralOwnTenantForOrder($order), 2),
        ];
    }

    protected function tenantStoreName(Tenant $tenant): string
    {
        return $tenant->data['shop_name'] ?? $tenant->name ?? 'Unknown store';
    }

    protected function orderItemName($item): string
    {
        $product = $item->product ?? $item->variant?->product;

        if ($product && method_exists($product, 'translationValue')) {
            return $product->translationValue('name') ?? $product->slug ?? 'Item #' . $item->id;
        }

        return $product?->name ?? $product?->slug ?? 'Item #' . $item->id;
    }

    protected function humanizeEnum(mixed $value): string
    {
        if (is_object($value) && method_exists($value, 'label')) {
            return $value->label();
        }

        return Str::of((string) $value)->replace('_', ' ')->headline()->toString();
    }

    protected function mapShippingStatus(OrderStatus $status): OrderShippingStatus
    {
        return match ($status) {
            OrderStatus::Pending => OrderShippingStatus::Pending,
            OrderStatus::Processing => OrderShippingStatus::InDelivery,
            OrderStatus::Shipped => OrderShippingStatus::Shipped,
            OrderStatus::Delivered, OrderStatus::Completed => OrderShippingStatus::Delivered,
            OrderStatus::Cancelled, OrderStatus::Rejected => OrderShippingStatus::Cancelled,
        };
    }
}
