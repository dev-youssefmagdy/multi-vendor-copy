<?php

namespace App\Repositories\Tenant;

use App\Enums\OrderStatus;
use App\Enums\PackageTerm;
use App\Enums\Tenant\SubscriptionStatus;
use App\Enums\WalletTransactionDirection;
use App\Models\Category as CentralCategory;
use App\Models\Product as CentralProduct;
use App\Models\Tenant\AdminRole;
use App\Models\Tenant\AdminUser;
use App\Models\Tenant\Banner;
use App\Models\Tenant\Category;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmailTemplate;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Language;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Page;
use App\Models\Tenant\PaymentGateway;
use App\PaymentGateway\PaymentManager;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Setting;
use App\Models\Tenant\SocialLink;
use App\Models\Tenant\Subscriber;
use App\Models\Tenant\Subscription;
use App\Models\Tenant\Theme;
use App\Models\Tenant\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ManualPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TenantPanelRepository
{
    public $orders;
    protected $customers;
    protected $subscriptions;
    private ?array $profitabilityRowsCache = null;

    function __construct()
    {
        $this->orders = Order::query()->with('items.product')->get();
    }

    protected function customers(): Collection
    {
        return $this->customers ??= Customer::query()->withCount('orders')->get();
    }

    protected function subscriptions(): Collection
    {
        return $this->subscriptions ??= Subscription::query()->get();
    }

    public function dashboardStats(): array
    {
        $orders = $this->orders;
        $customers = $this->customers();
        $subscriptions = $this->subscriptions();

        $grossSales = $orders->sum(fn(Order $order) => $this->orderFinancials($order)['grand_total']);
        $collectedSales = $orders
            ->filter(fn(Order $order) => $order->paid)
            ->sum(fn(Order $order) => $this->orderFinancials($order)['grand_total']);
        $outstandingSales = $orders
            ->filter(fn(Order $order) => !$order->paid && !in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true))
            ->sum(fn(Order $order) => $this->orderFinancials($order)['grand_total']);

        $paidOrders = $orders->filter(fn(Order $order) => $order->paid);
        $centralProductsProfit = $paidOrders
            ->filter(fn(Order $order) => $this->orderIsCentralType($order))
            ->sum(fn(Order $order) => $this->orderFinancials($order)['vendor_net_total']);
        $ownProductsProfit = $paidOrders
            ->filter(fn(Order $order) => !$this->orderIsCentralType($order))
            ->sum(fn(Order $order) => $this->orderFinancials($order)['vendor_net_total']);

        return [
            'revenue' => round($collectedSales, 2),
            'gross_sales' => round($grossSales, 2),
            'outstanding' => round($outstandingSales, 2),
            'balance' => round($this->tenantBalance(), 2),
            'orders' => $orders->count(),
            'paid_orders' => $orders->filter(fn(Order $order) => $order->paid)->count(),
            'pending_orders' => $orders->filter(fn(Order $order) => in_array($order->status, [OrderStatus::Pending, OrderStatus::Processing], true))->count(),
            'customers' => $customers->count(),
            'active_customers' => $customers->filter(fn(Customer $customer) => $customer->active)->count(),
            'repeat_buyers' => $customers->filter(fn(Customer $customer) => (int) $customer->orders_count > 1)->count(),
            'products' => Product::query()->count(),
            'active_products' => Product::query()->where('active', true)->count(),
            'subscribers' => Subscriber::query()->count(),
            'subscriptions' => $subscriptions->count(),
            'active_subscriptions' => $subscriptions->filter(fn(Subscription $subscription) => $subscription->status === SubscriptionStatus::Active)->count(),
            'shipping' => round($orders->sum(fn(Order $order) => $this->orderFinancials($order)['shipping_total']), 2),
            'avg_order' => $orders->isNotEmpty() ? round($grossSales / $orders->count(), 2) : 0.0,
            'vendor_net' => round($centralProductsProfit + $ownProductsProfit, 2),
            'central_products_profit' => round($centralProductsProfit, 2),
            'own_products_profit' => round($ownProductsProfit, 2),
        ];
    }

    public function dashboardSeries(): array
    {
        $orders = $this->orders;
        $customers = $this->customers();
        $subscriptions = $this->subscriptions();
        $customerOrderCounts = $customers->keyBy('id')->map(fn(Customer $customer) => (int) $customer->orders_count);

        return $this->rollingMonths()->map(function (Carbon $date) use ($orders, $customers, $subscriptions, $customerOrderCounts) {
            $monthOrders = $orders->filter(fn(Order $order) => $this->isSameMonth($order->created_at, $date));
            $monthPaidOrders = $monthOrders->filter(fn(Order $order) => $order->paid);
            $fulfilledOrders = $monthOrders->filter(fn(Order $order) => in_array($order->status, [OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Completed], true));
            $monthCustomerIds = $monthOrders->pluck('customer_id')->filter()->unique();

            return [
                'label' => $date->format('M'),
                'gross' => round($monthOrders->sum(fn(Order $order) => $this->orderFinancials($order)['grand_total']), 2),
                'revenue' => round($monthPaidOrders->sum(fn(Order $order) => $this->orderFinancials($order)['grand_total']), 2),
                'orders' => $monthOrders->count(),
                'paid_orders' => $monthPaidOrders->count(),
                'fulfilled_orders' => $fulfilledOrders->count(),
                'shipping' => round($monthOrders->sum(fn(Order $order) => $this->orderFinancials($order)['shipping_total']), 2),
                'customers' => $customers->filter(fn(Customer $customer) => $this->isSameMonth($customer->created_at, $date))->count(),
                'repeat_buyers' => $monthCustomerIds->filter(fn($customerId) => (int) ($customerOrderCounts[$customerId] ?? 0) > 1)->count(),
                'subscriptions' => round($subscriptions->filter(fn(Subscription $subscription) => $this->isSameMonth($subscription->created_at, $date))->sum('price'), 2),
            ];
        })->all();
    }

    public function latestOrders(int $limit = 5)
    {
        return Order::query()
            ->with(['customer', 'items', 'paymentGateway'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function paginateProducts(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return Product::query()
            ->with(['translations.language', 'categories.translations.language', 'variants', 'files', 'badges'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where('slug', 'like', "%{$search}%")
                    ->orWhereHas('translations', fn(Builder $translationQuery) => $translationQuery->where('value', 'like', "%{$search}%"));
            })
            ->when(($filters['status'] ?? '') !== '', fn($query) => $query->where('active', $filters['status'] === 'active'))
            ->when(in_array($filters['stock'] ?? '', ['in', 'partial', 'out'], true), function ($query) use ($filters) {
                $this->applyProductStockFilter($query, $filters['stock']);
            })
            ->orderBy('order_number')
            ->paginate($perPage);
    }

    /**
     * @param 'in'|'partial'|'out' $stock
     */
    protected function applyProductStockFilter(Builder $query, string $stock): void
    {
        match ($stock) {
            'out' => $query->where(function (Builder $q) {
                $q->where(function (Builder $noVariants) {
                    $noVariants->whereDoesntHave('variants')->where('manage_stock', true)->where('stock', '<=', 0);
                })->orWhere(function (Builder $hasVariants) {
                    $hasVariants->whereHas('variants')->whereDoesntHave('variants', fn($v) => $v->where('stock', '>', 0));
                });
            }),
            'in' => $query->where(function (Builder $q) {
                $q->where(function (Builder $noVariants) {
                    $noVariants->whereDoesntHave('variants')->where(function (Builder $nv) {
                        $nv->where('manage_stock', false)->orWhere('stock', '>', 0);
                    });
                })->orWhere(function (Builder $hasVariants) {
                    $hasVariants->whereHas('variants')->whereDoesntHave('variants', fn($v) => $v->where('stock', '<=', 0));
                });
            }),
            'partial' => $query
                ->whereHas('variants', fn($v) => $v->where('stock', '<=', 0))
                ->whereHas('variants', fn($v) => $v->where('stock', '>', 0)),
            default => null,
        };
    }

    public function productStats(): array
    {
        return [
            'total' => Product::query()->count(),
            'active' => Product::query()->where('active', true)->count(),
            'featured' => Product::query()->where('featured', true)->count(),
        ];
    }

    public function centralProductSnapshot(?int $centralProductId): ?array
    {
        if (!$centralProductId) {
            return null;
        }

        return $this->centralProductSnapshots([$centralProductId])[$centralProductId] ?? null;
    }

    public function centralProductSnapshots(array $centralProductIds): array
    {
        $ids = collect($centralProductIds)
            ->filter(fn($id) => filled($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $products = tenancy()->central(fn() => CentralProduct::query()
            ->with([
                'translations.language',
                'categories.translations.language',
                'files',
                'variants.options.translations.language',
            ])
            ->whereIn('id', $ids)
            ->get());

        return $products->mapWithKeys(fn(CentralProduct $product) => [$product->id => $this->mapCentralProductSnapshot($product)])->all();
    }

    protected function mapCentralProductSnapshot(CentralProduct $product): array
    {
        $currentPrice = (float) ($product->sale_price ?: $product->base_price);

        return [
            'id' => $product->id,
            'name' => $product->translationValue('name') ?? $product->sku ?? 'Product #' . $product->id,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'status' => $product->status?->label() ?? (string) $product->status,
            'factory' => $product->factory,
            'stock' => $product->stock,
            'base_price' => (float) $product->base_price,
            'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
            'current_price' => $currentPrice,
            'cost_price' => $product->cost_price !== null ? (float) $product->cost_price : null,
            'delivery_scope' => $product->delivery_scope?->label() ?? (string) $product->delivery_scope,
            'summary' => $product->translationValue('summary') ?? '',
            'description' => $product->translationValue('description') ?? '',
            'image_url' => $product->primary_image_url,
            'categories' => $product->categories
                ->map(fn($category) => $category->translationValue('name') ?? $category->slug)
                ->filter()
                ->values()
                ->all(),
            'variants' => $product->variants->load('files')
                ->sortBy('position')
                ->values()
                ->map(function ($variant) use ($product) {
                    $options = $variant->options
                        ->map(fn($option) => $option->translationValue('name') ?? 'Option #' . $option->id)
                        ->filter()
                        ->values()
                        ->all();

                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'title' => $variant->title ?: (implode(' / ', $options) ?: 'Variant #' . $variant->id),
                        'options' => $options,
                        'price' => (float) ($variant->price ?? $product->sale_price ?? $product->base_price),
                        'weight_grams' => $variant->weight_grams,
                        'status' => $variant->status?->label() ?? (string) $variant->status,
                        'image_url' => $variant->files->first()?->full_path,
                    ];
                })
                ->all(),
        ];
    }

    public function centralCategorySnapshot(?int $centralCategoryId): ?array
    {
        if (!$centralCategoryId) {
            return null;
        }

        return $this->centralCategorySnapshots([$centralCategoryId])[$centralCategoryId] ?? null;
    }

    public function centralCategorySnapshots(array $centralCategoryIds): array
    {
        $ids = collect($centralCategoryIds)
            ->filter(fn($id) => filled($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $categories = tenancy()->central(fn() => CentralCategory::query()
            ->with([
                'translations.language',
                'parent.translations.language',
                'files',
            ])
            ->whereIn('id', $ids)
            ->get());

        return $categories
            ->mapWithKeys(fn(CentralCategory $category) => [$category->id => $this->mapCentralCategorySnapshot($category)])
            ->all();
    }

    protected function mapCentralCategorySnapshot(CentralCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->translationValue('name') ?? $category->slug ?? 'Category #' . $category->id,
            'slug' => $category->slug,
            'status' => $category->status?->label() ?? (string) $category->status,
            'parent_name' => $category->parent?->translationValue('name') ?? $category->parent?->slug,
            'image_url' => $category->thumb_url,
            'featured' => (bool) $category->is_featured,
        ];
    }

    public function paginateCategories(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return Category::query()
            ->with(['translations.language', 'parent.translations.language', 'products'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->whereHas('translations', fn(Builder $translationQuery) => $translationQuery->where('value', 'like', "%{$search}%"));
            })
            ->when(($filters['status'] ?? '') !== '', fn($query) => $query->where('active', $filters['status'] === 'active'))
            ->when(!empty($filters['mine'] ?? false), fn($query) => $query->whereNull('central_category_id'))
            ->orderBy('order_number')
            ->paginate($perPage);
    }

    public function categoryStats(): array
    {
        return [
            'total' => Category::query()->count(),
            'active' => Category::query()->where('active', true)->count(),
            'featured' => Category::query()->where('featured', true)->count(),
        ];
    }

    public function paginateOrders(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->buildOrdersQuery($filters)->paginate($perPage);
    }

    public function exportOrders(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        return $this->buildOrdersQuery($filters)->get();
    }

    protected function buildOrdersQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return Order::query()
            ->with(['customer', 'items.product', 'items.variant.product', 'items.variant.centralVariant', 'paymentGateway'])
            ->withCount('items')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery
                        ->where('uuid', 'like', "%{$search}%")
                        ->orWhere('payment_method', 'like', "%{$search}%")
                        ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                            $customerQuery
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))
            ->when(($filters['paid'] ?? '') !== '', fn($query) => $query->where('paid', $filters['paid'] === 'paid'))
            ->when(filled($filters['gateway'] ?? null), fn($query) => $query->where('payment_method', $filters['gateway']))
            ->latest();
    }

    public function orderStats(): array
    {
        $orders = $this->orders;
        $grossSales = $orders->sum(fn(Order $order) => $this->orderFinancials($order)['grand_total']);
        $collectedSales = $orders
            ->filter(fn(Order $order) => $order->paid)
            ->sum(fn(Order $order) => $this->orderFinancials($order)['grand_total']);
        $outstandingSales = $orders
            ->filter(fn(Order $order) => !$order->paid && !in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true))
            ->sum(fn(Order $order) => $this->orderFinancials($order)['grand_total']);

        return [
            'total' => $orders->count(),
            'paid' => $orders->filter(fn(Order $order) => $order->paid)->count(),
            'pending' => $orders->filter(fn(Order $order) => $order->status === OrderStatus::Pending)->count(),
            'processing' => $orders->filter(fn(Order $order) => $order->status === OrderStatus::Processing)->count(),
            'fulfilled' => $orders->filter(fn(Order $order) => in_array($order->status, [OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Completed], true))->count(),
            'gross' => round($grossSales, 2),
            'collected' => round($collectedSales, 2),
            'outstanding' => round($outstandingSales, 2),
            'avg_order' => $orders->isNotEmpty() ? round($grossSales / $orders->count(), 2) : 0.0,
        ];
    }

    public function paginateCustomers(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->buildCustomersQuery($filters)->paginate($perPage);
    }

    public function exportCustomers(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        return $this->buildCustomersQuery($filters)->get();
    }

    protected function buildCustomersQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return Customer::query()
            ->withCount('orders')
            ->withMax('orders', 'created_at')
            ->withCount(['orders as paid_orders_count' => fn($query) => $query->where('paid', true)])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(($filters['status'] ?? '') !== '', fn($query) => $query->where('active', $filters['status'] === 'active'))
            ->latest();
    }

    public function customerStats(): array
    {
        $customers = Customer::query()->withCount('orders')->get();
        $lifetimeRows = collect($this->customerLifetimeRows());

        return [
            'total' => $customers->count(),
            'active' => $customers->filter(fn(Customer $customer) => $customer->active)->count(),
            'buyers' => $customers->filter(fn(Customer $customer) => (int) $customer->orders_count > 0)->count(),
            'repeat' => $customers->filter(fn(Customer $customer) => (int) $customer->orders_count > 1)->count(),
            'avg_lifetime' => $lifetimeRows->isNotEmpty() ? round((float) $lifetimeRows->avg('total'), 2) : 0.0,
        ];
    }

    public function customerDetail(Customer $customer): array
    {
        $customer->loadMissing([
            'orders.items',
            'orders.paymentGateway',
            'orders.activities',
            'addresses',
        ]);

        $orders = $customer->orders->sortByDesc('created_at');

        $totalSpent = round($orders->sum(fn(Order $o) => $this->orderFinancials($o)['grand_total']), 2);
        $paidSpent = round($orders->filter(fn(Order $o) => $o->paid)->sum(fn(Order $o) => $this->orderFinancials($o)['grand_total']), 2);
        $orderCount = $orders->count();
        $paidCount = $orders->filter(fn(Order $o) => $o->paid)->count();
        $avgOrder = $orderCount > 0 ? round($totalSpent / $orderCount, 2) : 0.0;
        $lastOrderAt = $orders->first()?->created_at;

        return [
            'customer' => $customer,
            'totalSpent' => $totalSpent,
            'paidSpent' => $paidSpent,
            'orderCount' => $orderCount,
            'paidCount' => $paidCount,
            'avgOrder' => $avgOrder,
            'lastOrderAt' => $lastOrderAt,
            'orders' => $orders->map(fn(Order $o) => [
                'id' => $o->id,
                'uuid' => $o->uuid,
                'status' => $o->status,
                'paid' => $o->paid,
                'created_at' => $o->created_at,
                'gateway' => $o->paymentGateway?->name ?? $o->payment_method,
                'financials' => $this->orderFinancials($o),
                'items' => $o->items->map(fn(OrderItem $item) => [
                    'name' => $item->product?->translationValue('name') ?? $item->product?->slug ?? '—',
                    'variant' => $item->variant?->display_label,
                    'qty' => $item->qty,
                    'price' => $item->sub_total,
                ])->all(),
                'shipping_address' => $o->shipping_address,
            ])->values()->all(),
            'addresses' => $customer->addresses,
        ];
    }

    public function paginateTransactions(int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        return Transaction::query()->with('model')->latest()->paginate($perPage, ['*'], $pageName);
    }

    public function walletStats(): array
    {
        $subscriptions = Subscription::query()->get();
        $credits = (float) Transaction::query()->where('type', WalletTransactionDirection::Credit->value)->sum('amount');
        $debits = (float) Transaction::query()->where('type', WalletTransactionDirection::Debit->value)->sum('amount');

        return [
            'credits' => round($credits, 2),
            'debits' => round($debits, 2),
            'balance' => round($credits - $debits, 2),
            'subscriptions' => $subscriptions->count(),
            'active_subscriptions' => $subscriptions->filter(fn(Subscription $subscription) => $subscription->status === SubscriptionStatus::Active)->count(),
            'mrr' => round($subscriptions->filter(fn(Subscription $subscription) => $subscription->status === SubscriptionStatus::Active)->sum(fn(Subscription $subscription) => $this->normalizedMonthlyValue($subscription)), 2),
            'revenue' => round((float) $subscriptions->sum('price'), 2),
            'transactions' => Transaction::query()->count(),
        ];
    }

    public function paginateSubscriptions(int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        return Subscription::query()->with('transaction')->latest()->paginate($perPage, ['*'], $pageName);
    }

    public function billingStats(): array
    {
        $orders = $this->orders;
        $grossSales = $orders->sum(fn(Order $order) => $this->orderFinancials($order)['grand_total']);
        $collectedSales = $orders
            ->filter(fn(Order $order) => $order->paid)
            ->sum(fn(Order $order) => $this->orderFinancials($order)['grand_total']);
        $outstandingSales = $orders
            ->filter(fn(Order $order) => !$order->paid && !in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true))
            ->sum(fn(Order $order) => $this->orderFinancials($order)['grand_total']);

        return [
            'orders' => $orders->count(),
            'paid' => $orders->filter(fn(Order $order) => $order->paid)->count(),
            'unpaid' => $orders->filter(fn(Order $order) => !$order->paid)->count(),
            'revenue' => round($grossSales, 2),
            'collected' => round($collectedSales, 2),
            'outstanding' => round($outstandingSales, 2),
            'gateways' => Order::query()->whereNotNull('payment_method')->distinct()->count('payment_method'),
        ];
    }

    public function themes()
    {
        return Theme::query()->with('countries')->orderBy('name')->get();
    }

    public function paginatePages(int $perPage = 10): LengthAwarePaginator
    {
        return Page::query()->with('translations.language')->latest()->paginate($perPage);
    }

    public function pageStats(): array
    {
        return [
            'total' => Page::query()->count(),
            'active' => Page::query()->where('active', true)->count(),
            'draft' => Page::query()->where('active', false)->count(),
        ];
    }

    public function paginateCoupons(int $perPage = 10): LengthAwarePaginator
    {
        return Coupon::query()->with('translations.language')->latest()->paginate($perPage);
    }

    public function couponStats(): array
    {
        return [
            'total' => Coupon::query()->count(),
            'active' => Coupon::query()->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', now());
            })->count(),
            'scheduled' => Coupon::query()->whereNotNull('start_date')->where('start_date', '>', now())->count(),
        ];
    }

    public function paginateFlashSales(?int $countryId = null, int $perPage = 10): LengthAwarePaginator
    {
        return FlashSale::query()
            ->with(['product.translations.language', 'products.translations.language', 'files'])
            ->where('country_id', $countryId)
            ->latest()
            ->paginate($perPage);
    }

    public function flashSaleStats(?int $countryId = null): array
    {
        $row = DB::table('flash_sales')
            ->where('country_id', $countryId)
            ->selectRaw('COUNT(*) as total, SUM(active = 1) as active_count, AVG(discount_percentage) as avg_discount')
            ->first();

        $productCount = DB::table('flash_sale_product')
            ->join('flash_sales', 'flash_sales.id', '=', 'flash_sale_product.flash_sale_id')
            ->where('flash_sales.country_id', $countryId)
            ->distinct('flash_sale_product.product_id')
            ->count('flash_sale_product.product_id');

        return [
            'total' => (int) ($row->total ?? 0),
            'active' => (int) ($row->active_count ?? 0),
            'avg_discount' => (float) ($row->avg_discount ?? 0),
            'products' => (int) $productCount,
        ];
    }

    public function paginateSubscribers(int $perPage = 10): LengthAwarePaginator
    {
        return Subscriber::query()->latest()->paginate($perPage);
    }

    public function exportSubscribers(): \Illuminate\Database\Eloquent\Collection
    {
        return Subscriber::query()->latest()->get();
    }

    public function subscriberStats(): array
    {
        return [
            'total' => Subscriber::query()->count(),
            'new_this_month' => Subscriber::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'new_this_week' => Subscriber::query()->where('created_at', '>=', now()->startOfWeek())->count(),
        ];
    }

    public function currencies()
    {
        return Currency::query()->orderByDesc('is_default')->orderBy('code')->get();
    }

    public function languages()
    {
        return Language::query()->orderBy('sort_order')->orderByDesc('is_default')->get();
    }

    public function paymentGateways()
    {
        return PaymentGateway::query()->where('hide', false)->orderByDesc('is_active')->orderBy('name')->get();
    }

    /**
     * Payment Readiness Widget data: gateway connectivity + international
     * sales / Apple Pay / Google Pay / target-currency / target-country
     * coverage of the tenant's currently active gateways.
     */
    public function paymentReadiness(): array
    {
        $manager = app(PaymentManager::class);

        $activeGateways = PaymentGateway::query()
            ->where('is_active', true)
            ->where('hide', false)
            ->get();

        $connected = $activeGateways->isNotEmpty();

        $metas = $activeGateways->map(fn(PaymentGateway $gateway) => $manager->meta($gateway->code));

        $merchantCountries = $metas->pluck('merchant_countries')->flatten()->unique();
        $customerCountries = $metas->pluck('customer_countries')->flatten()->map(fn($c) => strtoupper($c))->unique();
        $currencies = $metas->pluck('currencies')->flatten()->map(fn($c) => strtoupper($c))->unique();
        $paymentMethods = $metas->pluck('payment_methods')->flatten()->unique();

        $targetCurrencyCodes = Currency::query()
            ->where('is_active', true)
            ->pluck('code')
            ->map(fn($c) => strtoupper($c))
            ->unique();

        $targetCountryCodes = collect();
        if (tenant()) {
            $targetCountryCodes = tenant()->tenantCountries()
                ->where('is_active', true)
                ->with('country')
                ->get()
                ->pluck('country.iso2')
                ->filter()
                ->map(fn($c) => strtoupper($c))
                ->unique();
        }

        $supportsInternational = $merchantCountries->count() > 1;
        $supportsApplePay = $paymentMethods->contains('apple_pay');
        $supportsGooglePay = $paymentMethods->contains('google_pay');
        $supportsTargetCurrencies = $targetCurrencyCodes->isNotEmpty()
            && $targetCurrencyCodes->diff($currencies)->isEmpty();
        $canReceiveFromTargetCountries = $targetCountryCodes->isNotEmpty()
            && $targetCountryCodes->diff($customerCountries)->isEmpty();

        return [
            [
                'label' => 'Gateway connected',
                'ready' => $connected,
                'caption' => $connected
                    ? $activeGateways->count() . ' active gateway(s)'
                    : 'Connect a payment gateway to start accepting orders.',
            ],
            [
                'label' => 'Supports international sales',
                'ready' => $supportsInternational,
                'caption' => $supportsInternational
                    ? 'Your connected gateways can onboard from multiple countries.'
                    : 'Connect a gateway with broader merchant country coverage.',
            ],
            [
                'label' => 'Supports Apple Pay',
                'ready' => $supportsApplePay,
                'caption' => $supportsApplePay ? 'Available at checkout.' : 'No connected gateway offers Apple Pay.',
            ],
            [
                'label' => 'Supports Google Pay',
                'ready' => $supportsGooglePay,
                'caption' => $supportsGooglePay ? 'Available at checkout.' : 'No connected gateway offers Google Pay.',
            ],
            [
                'label' => 'Supports your target currencies',
                'ready' => $supportsTargetCurrencies,
                'caption' => $targetCurrencyCodes->isEmpty()
                    ? 'No active currencies configured yet.'
                    : ($supportsTargetCurrencies ? 'All your active currencies are supported.' : 'Some of your active currencies are not supported by connected gateways.'),
            ],
            [
                'label' => 'Can receive from your target countries',
                'ready' => $canReceiveFromTargetCountries,
                'caption' => $targetCountryCodes->isEmpty()
                    ? 'No target countries configured yet.'
                    : ($canReceiveFromTargetCountries ? 'All your target countries are covered.' : 'Some of your target countries are not covered by connected gateways.'),
            ],
        ];
    }

    public function paginateAdmins(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return AdminUser::query()
            ->with('role')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when(($filters['status'] ?? '') !== '', fn($query) => $query->where('status', $filters['status']))
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function adminStats(): array
    {
        return [
            'total' => AdminUser::query()->count(),
            'active' => AdminUser::query()->where('status', 'active')->count(),
            'roles' => AdminRole::query()->count(),
        ];
    }

    public function roleOptions(): array
    {
        return AdminRole::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function paginateAdminRoles(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return AdminRole::query()
            ->withCount('admins')
            ->when(filled($filters['search'] ?? null), fn($query) => $query->where('name', 'like', '%' . trim((string) $filters['search']) . '%'))
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function adminRoleStats(): array
    {
        return [
            'total' => AdminRole::query()->count(),
            'permissions' => (int) AdminRole::query()->sum('permissions_count'),
            'assigned' => AdminUser::query()->whereNotNull('role_id')->count(),
        ];
    }

    public function availableAdminPermissions(): array
    {
        return AdminRole::availablePermissions();
    }

    public function emailSettings(): array
    {
        return Setting::query()
            ->where('group', 'mail')
            ->pluck('value', 'name')
            ->all();
    }

    public function paginateEmailTemplates(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return EmailTemplate::query()
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when(($filters['status'] ?? '') !== '', fn($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function emailTemplateStats(): array
    {
        return [
            'total' => EmailTemplate::query()->count(),
            'active' => EmailTemplate::query()->where('is_active', true)->count(),
        ];
    }

    public function regionalSettings(): array
    {
        return Setting::query()->whereIn('name', ['default_currency', 'default_language'])->pluck('value', 'name')->all();
    }

    public function activeLanguages()
    {
        return Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get();
    }

    /**
     * Fetch central variation groups + options (read-only, via CentralConnection).
     */
    public function variationGroups()
    {
        return \App\Models\Variation::query()
            ->with(['translations.language', 'options.translations.language'])
            ->get();
    }

    public function productOptions(): array
    {
        $defaultLangId = $this->defaultLanguageId();

        return DB::table('products as p')
            ->leftJoin('translations as t', function ($join) use ($defaultLangId) {
                $join->on('t.translatable_id', '=', 'p.id')
                    ->where('t.translatable_type', '=', Product::class)
                    ->where('t.field', '=', 'name')
                    ->where('t.language_id', '=', $defaultLangId);
            })
            ->orderBy('p.id')
            ->select('p.id', DB::raw('COALESCE(t.value, p.slug, CONCAT("Product #", p.id)) as name'))
            ->pluck('name', 'p.id')
            ->all();
    }

    public function categoryOptions(): array
    {
        $defaultLangId = $this->defaultLanguageId();

        return DB::table('categories as c')
            ->leftJoin('translations as t', function ($join) use ($defaultLangId) {
                $join->on('t.translatable_id', '=', 'c.id')
                    ->where('t.translatable_type', '=', Category::class)
                    ->where('t.field', '=', 'name')
                    ->where('t.language_id', '=', $defaultLangId);
            })
            ->orderBy('c.id')
            ->select('c.id', DB::raw('COALESCE(t.value, CONCAT("Category #", c.id)) as name'))
            ->pluck('name', 'c.id')
            ->all();
    }

    /**
     * Return a flat depth-aware list of categories suitable for tree-style
     * <x-select tree> rendering.
     *
     * @return list<array{id:int,name:string,depth:int}>
     */
    public function categoryTreeOptions(): array
    {
        $defaultLangId = $this->defaultLanguageId();

        $rows = DB::table('categories as c')
            ->leftJoin('translations as t', function ($join) use ($defaultLangId) {
                $join->on('t.translatable_id', '=', 'c.id')
                    ->where('t.translatable_type', '=', Category::class)
                    ->where('t.field', '=', 'name')
                    ->where('t.language_id', '=', $defaultLangId);
            })
            ->orderBy('c.parent_id')
            ->orderBy('c.id')
            ->select('c.id', 'c.parent_id', DB::raw('COALESCE(t.value, CONCAT("Category #", c.id)) as name'))
            ->get();

        $byParent = [];
        foreach ($rows as $row) {
            $byParent[$row->parent_id ?? 0][] = $row;
        }

        $flat = [];
        $walk = function ($parentId, int $depth) use (&$walk, &$flat, $byParent) {
            foreach ($byParent[$parentId] ?? [] as $row) {
                $flat[] = ['id' => (int) $row->id, 'name' => (string) $row->name, 'depth' => $depth];
                $walk((int) $row->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $flat;
    }

    /**
     * Paginated product search for async dropdowns.
     * Returns up to $perPage items plus a has_more flag.
     */
    public function searchProducts(string $search = '', int $page = 1, int $perPage = 15): array
    {
        $defaultLangId = $this->defaultLanguageId();

        $query = DB::table('products as p')
            ->leftJoin('translations as t', function ($join) use ($defaultLangId) {
                $join->on('t.translatable_id', '=', 'p.id')
                    ->where('t.translatable_type', '=', Product::class)
                    ->where('t.field', '=', 'name')
                    ->where('t.language_id', '=', $defaultLangId);
            })
            ->select('p.id', DB::raw('COALESCE(t.value, p.slug, CONCAT("Product #", p.id)) as name'));

        if (filled($search)) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('t.value', 'like', $like)
                    ->orWhere('p.slug', 'like', $like);
            });
        }

        $total = (clone $query)->count();
        $items = $query
            ->orderBy(DB::raw('COALESCE(t.value, p.slug)'))
            ->forPage($page, $perPage)
            ->get()
            ->mapWithKeys(fn($row) => [(int) $row->id => $row->name])
            ->all();

        return [
            'items' => $items,
            'has_more' => ($page * $perPage) < $total,
        ];
    }

    /**
     * Fetch display names for a specific set of product IDs.
     */
    public function productNamesForIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $defaultLangId = $this->defaultLanguageId();

        return DB::table('products as p')
            ->leftJoin('translations as t', function ($join) use ($defaultLangId) {
                $join->on('t.translatable_id', '=', 'p.id')
                    ->where('t.translatable_type', '=', Product::class)
                    ->where('t.field', '=', 'name')
                    ->where('t.language_id', '=', $defaultLangId);
            })
            ->whereIn('p.id', $ids)
            ->select('p.id', DB::raw('COALESCE(t.value, p.slug, CONCAT("Product #", p.id)) as name'))
            ->pluck('name', 'p.id')
            ->mapWithKeys(fn($name, $id) => [(int) $id => $name])
            ->all();
    }

    /**
     * Return the primary thumbnail URL for each product ID.
     * Falls back to the platform default product image when no file is stored.
     *
     * @param  int[]  $ids
     * @return array<int, string>
     */
    public function productImagesForIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        // Eager-load only the one file we need per product.
        $products = \App\Models\Tenant\Product::query()
            ->with(['files' => fn($q) => $q->whereIn('key', ['primary_medium', 'primary_original'])->orderByDesc('key')])
            ->with('centralProduct')
            ->whereIn('id', $ids)
            ->get(['id', 'central_product_id']);

        return $products
            ->mapWithKeys(fn($product) => [(int) $product->id => ($product->primary_image_url ?? asset('elora/assets/images/product-default.png'))])
            ->all();
    }

    protected function defaultLanguageId(): int
    {
        return (int) (DB::table('languages')->where('is_default', true)->value('id') ?? 0);
    }

    public function customerLifetimeRows(): array
    {
        return Customer::query()->with(['orders' => fn($query) => $query->with('items')])->get()->map(function (Customer $customer) {
            $orders = $customer->orders;
            $count = $orders->count();
            $total = $orders->sum(fn(Order $order) => $this->orderFinancials($order)['grand_total']);
            $paidTotal = $orders->filter(fn(Order $order) => $order->paid)->sum(fn(Order $order) => $this->orderFinancials($order)['grand_total']);

            return [
                'id' => $customer->id,
                'name' => $customer->full_name,
                'email' => $customer->email,
                'orders' => $count,
                'paid_orders' => $orders->filter(fn(Order $order) => $order->paid)->count(),
                'total' => round($total, 2),
                'paid_total' => round($paidTotal, 2),
                'average' => $count > 0 ? round($total / $count, 2) : 0.0,
                'last_order' => $orders->sortByDesc('created_at')->first()?->created_at,
            ];
        })->filter(fn(array $row) => $row['orders'] > 0)->sortByDesc('total')->values()->all();
    }

    public function paymentMethodOptions(): array
    {
        return Order::query()
            ->whereNotNull('payment_method')
            ->where('payment_method', '!=', '')
            ->distinct()
            ->orderBy('payment_method')
            ->pluck('payment_method')
            ->mapWithKeys(fn(string $method) => [$method => str($method)->replace(['_', '-'], ' ')->headline()->toString()])
            ->all();
    }

    /**
     * Return the top N products by profitability using SQL aggregation.
     * Avoids loading thousands of ProductVariant / Translation models just for a dashboard snippet.
     */
    protected function topProductsForDashboard(int $limit = 6): array
    {
        // Variant-level aggregation
        $variantRows = DB::table('order_items as oi')
            ->join('product_variants as pv', 'pv.id', '=', 'oi.product_variant_id')
            ->whereNotNull('oi.product_variant_id')
            ->selectRaw('
                pv.id,
                pv.product_id,
                pv.real_price,
                pv.default_sell_price as sell_price,
                pv.active,
                pv.margin_percentage,
                SUM(oi.qty) as sold_qty,
                COUNT(DISTINCT oi.order_id) as orders_count,
                SUM(oi.sub_total - oi.discount + oi.tax + oi.shipping_fee) as revenue,
                MAX(oi.created_at) as last_sale
            ')
            ->groupBy('pv.id', 'pv.product_id', 'pv.real_price', 'pv.default_sell_price', 'pv.active', 'pv.margin_percentage')
            ->get();

        // Product-only aggregation (no variant)
        $productRows = DB::table('order_items as oi')
            ->whereNull('oi.product_variant_id')
            ->whereNotNull('oi.product_id')
            ->selectRaw('
                oi.product_id,
                SUM(oi.qty) as sold_qty,
                COUNT(DISTINCT oi.order_id) as orders_count,
                SUM(oi.sub_total - oi.discount + oi.tax + oi.shipping_fee) as revenue,
                MAX(oi.created_at) as last_sale
            ')
            ->groupBy('oi.product_id')
            ->get();

        // Collect product IDs we need names for
        $productIds = $variantRows->pluck('product_id')
            ->merge($productRows->pluck('product_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $products = Product::query()
            ->with('translations.language')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $rows = collect();

        foreach ($variantRows as $v) {
            /** @var Product|null $product */
            $product = $products->get($v->product_id);
            $soldQty = (int) $v->sold_qty;
            $revenue = (float) $v->revenue;
            $cost = round((float) $v->real_price * $soldQty, 2);
            $profit = round($revenue - $cost, 2);

            $rows->push([
                'id' => $v->id,
                'name' => $product?->translationValue('name') ?? $product?->slug ?? ('Variant #' . $v->id),
                'real_price' => (float) $v->real_price,
                'sell_price' => (float) $v->sell_price,
                'sold_qty' => $soldQty,
                'orders' => (int) $v->orders_count,
                'revenue' => round($revenue, 2),
                'cost' => $cost,
                'profit' => $profit,
                'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : (float) $v->margin_percentage,
                'status' => $v->active ? 'Active' : 'Inactive',
                'active' => (bool) $v->active,
                'last_sale' => $v->last_sale,
            ]);
        }

        foreach ($productRows as $p) {
            /** @var Product|null $product */
            $product = $products->get($p->product_id);
            $soldQty = (int) $p->sold_qty;
            $revenue = (float) $p->revenue;

            $rows->push([
                'id' => 'product-' . $p->product_id,
                'name' => $product?->translationValue('name') ?? $product?->slug ?? ('Product #' . $p->product_id),
                'real_price' => 0.0,
                'sell_price' => 0.0,
                'sold_qty' => $soldQty,
                'orders' => (int) $p->orders_count,
                'revenue' => round($revenue, 2),
                'cost' => 0.0,
                'profit' => round($revenue, 2),
                'margin' => $revenue > 0 ? 100.0 : 0.0,
                'status' => $product?->active ? 'Active' : 'Inactive',
                'active' => (bool) $product?->active,
                'last_sale' => $p->last_sale,
            ]);
        }

        return $rows
            ->sortByDesc(fn(array $row) => ($row['profit'] * 1000000) + ($row['revenue'] * 1000) + ($row['sold_qty'] * 10) + $row['margin'])
            ->take($limit)
            ->values()
            ->all();
    }

    public function dashboardOverview(): array
    {
        $stats = $this->dashboardStats();
        $series = collect($this->dashboardSeries());
        $statusRows = $this->orderStatusBreakdown();
        $topCustomers = collect($this->customerLifetimeRows())->take(6)->values()->all();
        $topProducts = $this->topProductsForDashboard(6);

        $radarMetrics = [
            'Paid Rate' => $stats['orders'] > 0 ? round(($stats['paid_orders'] / $stats['orders']) * 100, 2) : 0.0,
            'Repeat Buyers' => $stats['customers'] > 0 ? round(($stats['repeat_buyers'] / $stats['customers']) * 100, 2) : 0.0,
            'Catalog Health' => $stats['products'] > 0 ? round(($stats['active_products'] / $stats['products']) * 100, 2) : 0.0,
            'Subscription Health' => $stats['subscriptions'] > 0 ? round(($stats['active_subscriptions'] / $stats['subscriptions']) * 100, 2) : 0.0,
            'Shipping Mix' => $stats['gross_sales'] > 0 ? round(($stats['shipping'] / $stats['gross_sales']) * 100, 2) : 0.0,
            'Balance Cover' => $stats['revenue'] > 0 ? max(0.0, min(100.0, round(($stats['balance'] / $stats['revenue']) * 100, 2))) : 0.0,
        ];

        return [
            'cards' => [
                ['label' => 'Collected Sales', 'value' => $stats['revenue'], 'format' => 'currency', 'caption' => 'Paid order value captured in this tenant.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Gross Sales', 'value' => $stats['gross_sales'], 'format' => 'currency', 'caption' => 'All order value including unpaid orders.', 'dot' => 'dot-violet', 'glow' => 'card-glow-violet'],
                ['label' => 'Tenant Balance', 'value' => $stats['balance'], 'format' => 'currency', 'caption' => 'Subscription transaction balance for the tenant.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Outstanding', 'value' => $stats['outstanding'], 'format' => 'currency', 'caption' => 'Open unpaid order value still pending collection.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Orders', 'value' => $stats['orders'], 'format' => 'number', 'caption' => 'Orders recorded in the tenant storefront.', 'dot' => 'dot-cyan'],
                ['label' => 'Customers', 'value' => $stats['customers'], 'format' => 'number', 'caption' => 'Customers with tenant-scoped profiles.', 'dot' => 'dot-green'],
                ['label' => 'Active Subs', 'value' => $stats['active_subscriptions'], 'format' => 'number', 'caption' => 'Currently active tenant subscription records.', 'dot' => 'dot-amber'],
                ['label' => 'Average Order', 'value' => $stats['avg_order'], 'format' => 'currency', 'caption' => 'Average gross value per order.', 'dot' => 'dot-violet'],
                ['label' => 'My Net Profit from Platform Products', 'value' => $stats['central_products_profit'], 'format' => 'currency', 'caption' => 'Your net profit share from orders of admin/central catalog products (not the admin\'s cut).', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'My Net Profit from Own Products', 'value' => $stats['own_products_profit'], 'format' => 'currency', 'caption' => 'Net profit from orders of your own listed products.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'My Total Net Profit', 'value' => $stats['vendor_net'], 'format' => 'currency', 'caption' => 'Sum of your net profit from platform products and your own products.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
            ],
            'status_rows' => $statusRows,
            'top_customers' => $topCustomers,
            'top_products' => $topProducts,
            'latest_orders' => $this->latestOrders(8),
            'payment_readiness' => $this->paymentReadiness(),
            'chart_payload' => [
                'revenueLabels' => $series->pluck('label')->values()->all(),
                'revenueDatasets' => [
                    ['label' => 'Gross sales', 'data' => $series->pluck('gross')->values()->all(), 'color' => 'cyan', 'fill' => true, 'format' => 'currency'],
                    ['label' => 'Collected sales', 'data' => $series->pluck('revenue')->values()->all(), 'color' => 'violet', 'fill' => true, 'format' => 'currency'],
                ],
                'donutLabels' => collect($statusRows)->pluck('label')->values()->all(),
                'donutData' => collect($statusRows)->pluck('count')->values()->all(),
                'barLabels' => $series->pluck('label')->values()->all(),
                'barDatasets' => [
                    ['label' => 'Shipping revenue', 'data' => $series->pluck('shipping')->values()->all(), 'color' => 'green', 'format' => 'currency'],
                ],
                'lineLabels' => $series->pluck('label')->values()->all(),
                'lineDatasets' => [
                    ['label' => 'New customers', 'data' => $series->pluck('customers')->values()->all(), 'color' => 'amber', 'format' => 'number'],
                    ['label' => 'Repeat buyers', 'data' => $series->pluck('repeat_buyers')->values()->all(), 'color' => 'cyan', 'format' => 'number', 'dashed' => true],
                ],
                'radarLabels' => array_keys($radarMetrics),
                'radarDatasets' => [
                    ['label' => 'Current', 'data' => array_values($radarMetrics), 'color' => 'cyan'],
                    ['label' => 'Target', 'data' => array_fill(0, count($radarMetrics), 75), 'color' => 'violet'],
                ],
            ],
        ];
    }

    public function orderAnalyticsOverview(): array
    {
        $stats = $this->orderStats();
        $series = collect($this->dashboardSeries());
        $statusRows = $this->orderStatusBreakdown();

        return [
            'cards' => [
                ['label' => 'Gross Sales', 'value' => $stats['gross'], 'format' => 'currency', 'caption' => 'Total order value before payment collection.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Collected', 'value' => $stats['collected'], 'format' => 'currency', 'caption' => 'Paid orders already captured.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Outstanding', 'value' => $stats['outstanding'], 'format' => 'currency', 'caption' => 'Unpaid open order value still pending.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Average Order', 'value' => $stats['avg_order'], 'format' => 'currency', 'caption' => 'Average gross order value across the tenant.', 'dot' => 'dot-violet'],
            ],
            'status_rows' => $statusRows,
            'monthly_rows' => $series->map(fn(array $row) => [
                'label' => $row['label'],
                'orders' => $row['orders'],
                'paid_orders' => $row['paid_orders'],
                'gross' => $row['gross'],
                'collected' => $row['revenue'],
                'average' => $row['orders'] > 0 ? round($row['gross'] / $row['orders'], 2) : 0.0,
            ])->all(),
            'chart_payload' => [
                'revenueLabels' => $series->pluck('label')->values()->all(),
                'revenueDatasets' => [
                    ['label' => 'Gross sales', 'data' => $series->pluck('gross')->values()->all(), 'color' => 'cyan', 'fill' => true, 'format' => 'currency'],
                    ['label' => 'Collected', 'data' => $series->pluck('revenue')->values()->all(), 'color' => 'violet', 'fill' => true, 'format' => 'currency'],
                ],
                'donutLabels' => collect($statusRows)->pluck('label')->values()->all(),
                'donutData' => collect($statusRows)->pluck('count')->values()->all(),
                'barLabels' => $series->pluck('label')->values()->all(),
                'barDatasets' => [
                    ['label' => 'Orders', 'data' => $series->pluck('orders')->values()->all(), 'color' => 'green', 'format' => 'number'],
                    ['label' => 'Paid orders', 'data' => $series->pluck('paid_orders')->values()->all(), 'color' => 'amber', 'format' => 'number'],
                ],
                'lineLabels' => $series->pluck('label')->values()->all(),
                'lineDatasets' => [
                    ['label' => 'New customers', 'data' => $series->pluck('customers')->values()->all(), 'color' => 'violet', 'format' => 'number'],
                    ['label' => 'Repeat buyers', 'data' => $series->pluck('repeat_buyers')->values()->all(), 'color' => 'cyan', 'format' => 'number', 'dashed' => true],
                ],
            ],
        ];
    }

    public function customerLifetimeOverview(): array
    {
        $rows = collect($this->customerLifetimeRows());
        $stats = $this->customerStats();
        $series = collect($this->dashboardSeries());
        $topRows = $rows->take(8)->values();

        return [
            'cards' => [
                ['label' => 'Customers', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Total customer records synced for this tenant.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Buyers', 'value' => $stats['buyers'], 'format' => 'number', 'caption' => 'Customers who have already placed at least one order.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Repeat Buyers', 'value' => $stats['repeat'], 'format' => 'number', 'caption' => 'Customers with more than one historical order.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Average Lifetime', 'value' => $stats['avg_lifetime'], 'format' => 'currency', 'caption' => 'Average lifetime spend among customers with orders.', 'dot' => 'dot-violet'],
            ],
            'rows' => $rows->all(),
            'chart_payload' => [
                'barLabels' => $topRows->pluck('name')->values()->all(),
                'barDatasets' => [
                    ['label' => 'Lifetime spend', 'data' => $topRows->pluck('total')->values()->all(), 'color' => 'cyan', 'format' => 'currency'],
                ],
                'lineLabels' => $series->pluck('label')->values()->all(),
                'lineDatasets' => [
                    ['label' => 'New customers', 'data' => $series->pluck('customers')->values()->all(), 'color' => 'violet', 'format' => 'number'],
                    ['label' => 'Repeat buyers', 'data' => $series->pluck('repeat_buyers')->values()->all(), 'color' => 'green', 'format' => 'number', 'dashed' => true],
                ],
                'donutLabels' => ['Active', 'Inactive', 'One-time', 'Repeat'],
                'donutData' => [$stats['active'], max(0, $stats['total'] - $stats['active']), max(0, $stats['buyers'] - $stats['repeat']), $stats['repeat']],
            ],
        ];
    }

    public function shippingAnalyticsOverview(): array
    {
        $orders = $this->orders;
        $series = collect($this->dashboardSeries());
        $fulfilledOrders = $orders->filter(fn(Order $order) => in_array($order->status, [OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Completed], true));
        $shippingRevenue = $orders->sum(fn(Order $order) => $this->orderFinancials($order)['shipping_total']);
        $weight = OrderItem::query()->get()->sum(fn(OrderItem $item) => (float) $item->weight * (int) $item->qty);
        $statusRows = $this->shippingStatusBreakdown($orders);

        return [
            'cards' => [
                ['label' => 'Shipping Revenue', 'value' => round($shippingRevenue, 2), 'format' => 'currency', 'caption' => 'Shipping collected across all tenant orders.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Avg Shipping', 'value' => $orders->isNotEmpty() ? round($shippingRevenue / $orders->count(), 2) : 0.0, 'format' => 'currency', 'caption' => 'Average shipping captured per order.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Fulfilled Orders', 'value' => $fulfilledOrders->count(), 'format' => 'number', 'caption' => 'Orders already shipped, delivered, or completed.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Ship Weight', 'value' => round($weight, 2), 'format' => 'number', 'suffix' => 'kg', 'caption' => 'Combined recorded shipping weight across order items.', 'dot' => 'dot-violet'],
            ],
            'status_rows' => $statusRows,
            'monthly_rows' => $series->map(fn(array $row) => [
                'label' => $row['label'],
                'orders' => $row['orders'],
                'fulfilled' => $row['fulfilled_orders'],
                'shipping' => $row['shipping'],
                'average_shipping' => $row['orders'] > 0 ? round($row['shipping'] / $row['orders'], 2) : 0.0,
            ])->all(),
            'chart_payload' => [
                'barLabels' => $series->pluck('label')->values()->all(),
                'barDatasets' => [
                    ['label' => 'Shipping revenue', 'data' => $series->pluck('shipping')->values()->all(), 'color' => 'green', 'format' => 'currency'],
                ],
                'lineLabels' => $series->pluck('label')->values()->all(),
                'lineDatasets' => [
                    ['label' => 'Orders', 'data' => $series->pluck('orders')->values()->all(), 'color' => 'violet', 'format' => 'number'],
                    ['label' => 'Fulfilled orders', 'data' => $series->pluck('fulfilled_orders')->values()->all(), 'color' => 'cyan', 'format' => 'number', 'dashed' => true],
                ],
                'donutLabels' => collect($statusRows)->pluck('label')->values()->all(),
                'donutData' => collect($statusRows)->pluck('count')->values()->all(),
            ],
        ];
    }

    public function productProfitabilityRows(?int $limit = 20): array
    {
        $defaultLangId = $this->defaultLanguageId();

        // All variants (including those with zero sales) left-joined to order_items
        $variantRows = DB::table('product_variants as pv')
            ->leftJoin('order_items as oi', 'oi.product_variant_id', '=', 'pv.id')
            ->leftJoin('products as p', 'p.id', '=', 'pv.product_id')
            ->leftJoin('translations as t', function ($join) use ($defaultLangId) {
                $join->on('t.translatable_id', '=', 'p.id')
                    ->where('t.translatable_type', '=', Product::class)
                    ->where('t.field', '=', 'name')
                    ->where('t.language_id', '=', $defaultLangId);
            })
            ->selectRaw('
                pv.id,
                pv.product_id,
                pv.real_price,
                pv.default_sell_price as sell_price,
                pv.margin_percentage,
                pv.active,
                COALESCE(t.value, p.slug, CONCAT("Variant #", pv.id)) as name,
                COALESCE(SUM(oi.qty), 0) as sold_qty,
                COUNT(DISTINCT oi.order_id) as orders_count,
                COALESCE(SUM(oi.sub_total - oi.discount + oi.tax + oi.shipping_fee), 0) as revenue,
                MAX(oi.created_at) as last_sale
            ')
            ->groupBy('pv.id', 'pv.product_id', 'pv.real_price', 'pv.default_sell_price', 'pv.margin_percentage', 'pv.active', 'name')
            ->get();

        // Order items linked directly to a product (no variant)
        $productOnlyRows = DB::table('order_items as oi')
            ->leftJoin('products as p', 'p.id', '=', 'oi.product_id')
            ->leftJoin('translations as t', function ($join) use ($defaultLangId) {
                $join->on('t.translatable_id', '=', 'oi.product_id')
                    ->where('t.translatable_type', '=', Product::class)
                    ->where('t.field', '=', 'name')
                    ->where('t.language_id', '=', $defaultLangId);
            })
            ->whereNull('oi.product_variant_id')
            ->whereNotNull('oi.product_id')
            ->selectRaw('
                oi.product_id,
                p.active,
                COALESCE(t.value, p.slug, CONCAT("Product #", oi.product_id)) as name,
                SUM(oi.qty) as sold_qty,
                COUNT(DISTINCT oi.order_id) as orders_count,
                SUM(oi.sub_total - oi.discount + oi.tax + oi.shipping_fee) as revenue,
                MAX(oi.created_at) as last_sale
            ')
            ->groupBy('oi.product_id', 'p.active', 'name')
            ->get();

        $rows = collect();

        foreach ($variantRows as $v) {
            $soldQty = (int) $v->sold_qty;
            $revenue = (float) $v->revenue;
            $cost = round((float) $v->real_price * $soldQty, 2);
            $profit = round($revenue - $cost, 2);

            $rows->push([
                'id' => $v->id,
                'name' => $v->name,
                'real_price' => (float) $v->real_price,
                'sell_price' => (float) $v->sell_price,
                'sold_qty' => $soldQty,
                'orders' => (int) $v->orders_count,
                'revenue' => round($revenue, 2),
                'cost' => $cost,
                'profit' => $profit,
                'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : (float) $v->margin_percentage,
                'status' => $v->active ? 'Active' : 'Inactive',
                'active' => (bool) $v->active,
                'last_sale' => $v->last_sale,
            ]);
        }

        foreach ($productOnlyRows as $p) {
            $soldQty = (int) $p->sold_qty;
            $revenue = (float) $p->revenue;

            $rows->push([
                'id' => 'product-' . $p->product_id,
                'name' => $p->name,
                'real_price' => 0.0,
                'sell_price' => 0.0,
                'sold_qty' => $soldQty,
                'orders' => (int) $p->orders_count,
                'revenue' => round($revenue, 2),
                'cost' => 0.0,
                'profit' => round($revenue, 2),
                'margin' => $revenue > 0 ? 100.0 : 0.0,
                'status' => $p->active ? 'Active' : 'Inactive',
                'active' => (bool) $p->active,
                'last_sale' => $p->last_sale,
            ]);
        }

        $rows = $rows
            ->sortByDesc(fn(array $row) => ($row['profit'] * 1000000) + ($row['revenue'] * 1000) + ($row['sold_qty'] * 10) + $row['margin'])
            ->values();

        if ($limit !== null) {
            return $rows->take($limit)->all();
        }

        return $rows->all();
    }

    private function allProfitabilityRows(): array
    {
        return $this->profitabilityRowsCache ??= $this->productProfitabilityRows(null);
    }

    public function paginateProductProfitabilityRows(int $perPage = 20, string $pageName = 'profitability'): ManualPaginator
    {
        $all = $this->allProfitabilityRows();
        $currentPage = ManualPaginator::resolveCurrentPage($pageName);
        $items = array_slice($all, ($currentPage - 1) * $perPage, $perPage);

        return new ManualPaginator($items, count($all), $perPage, $currentPage, [
            'pageName' => $pageName,
        ]);
    }

    public function productProfitabilityOverview(): array
    {
        $rows = collect($this->allProfitabilityRows());
        $series = collect($this->monthlyProductProfitSeries());
        $topRows = $rows->take(8)->values();

        $marginBands = [
            'Loss / Flat' => $rows->filter(fn(array $row) => $row['margin'] <= 0)->count(),
            '1-15%' => $rows->filter(fn(array $row) => $row['margin'] > 0 && $row['margin'] <= 15)->count(),
            '15-35%' => $rows->filter(fn(array $row) => $row['margin'] > 15 && $row['margin'] <= 35)->count(),
            '35%+' => $rows->filter(fn(array $row) => $row['margin'] > 35)->count(),
        ];

        return [
            'cards' => [
                ['label' => 'Tracked Items', 'value' => $rows->count(), 'format' => 'number', 'caption' => 'Products or variants tracked for profitability.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Sold Units', 'value' => $rows->sum('sold_qty'), 'format' => 'number', 'caption' => 'Units sold across tracked variants and products.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Revenue', 'value' => round((float) $rows->sum('revenue'), 2), 'format' => 'currency', 'caption' => 'Revenue attributable to product-level sales.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Gross Profit', 'value' => round((float) $rows->sum('profit'), 2), 'format' => 'currency', 'caption' => 'Revenue minus recorded item costs.', 'dot' => 'dot-violet'],
            ],
            'chart_payload' => [
                'barLabels' => $topRows->pluck('name')->values()->all(),
                'barDatasets' => [
                    ['label' => 'Gross profit', 'data' => $topRows->pluck('profit')->values()->all(), 'color' => 'cyan', 'format' => 'currency'],
                ],
                'lineLabels' => $series->pluck('label')->values()->all(),
                'lineDatasets' => [
                    ['label' => 'Revenue', 'data' => $series->pluck('revenue')->values()->all(), 'color' => 'violet', 'format' => 'currency'],
                    ['label' => 'Profit', 'data' => $series->pluck('profit')->values()->all(), 'color' => 'green', 'format' => 'currency', 'dashed' => true],
                ],
                'donutLabels' => array_keys($marginBands),
                'donutData' => array_values($marginBands),
            ],
        ];
    }

    public function orderDetail(Order $order): array
    {
        $order->loadMissing(['customer', 'items.product', 'items.variant.product', 'items.variant.centralVariant', 'paymentGateway', 'activities']);
        $financials = $this->orderFinancials($order);

        // An order is "own products only" when every item belongs to a vendor-created product
        $isOwnProductsOnly = $order->items->isNotEmpty() && $order->items->every(
            fn($item) => $item->product && ($item->product->is_own_product || $item->product->is_tenant_owned)
        );

        $shippingStatus = $this->deriveShippingStatus($order);

        return [
            'uuid' => $order->uuid,
            'status' => $order->status->label(),
            'status_value' => $order->status->value,
            'shipping_status' => $shippingStatus?->label(),
            'shipping_status_value' => $shippingStatus?->value ?? '',
            'can_update_shipping' => $isOwnProductsOnly,
            'paid' => $order->paid,
            'payment_method' => $order->payment_method,
            'payment_gateway' => filled($order->payment_method)
                ? str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()->toString()
                : ($order->paymentGateway?->name ?? '-'),
            'created_at' => $order->created_at,
            'customer' => [
                'name' => $order->customer?->full_name ?? 'Guest',
                'email' => $order->customer?->email,
                'phone' => $order->customer?->phone,
            ],
            'financials' => $financials,
            'gateway_source' => ($order->paymentGateway?->use_own === false) ? 'central' : 'vendor',
            'carrier' => $order->shipping_address['carrier'] ?? null,
            'shipping_address' => $order->shipping_address ?? [],
            'payment_details' => $order->payment_details ?? [],
            'items' => $order->items->map(function (OrderItem $item) {
                $lineTotal = round((float) $item->sub_total - (float) $item->discount + (float) $item->tax + (float) $item->shipping_fee, 2);

                return [
                    'name' => $item->product?->translationValue('name')
                        ?? $item->product?->slug
                        ?? $item->variant?->product?->translationValue('name')
                        ?? $item->variant?->product?->slug
                        ?? ('Item #' . $item->id),
                    'variant' => $item->variant?->display_label ?? ($item->variant?->id ? 'Variant #' . $item->variant->id : null),
                    'qty' => (int) $item->qty,
                    'price' => (float) $item->price,
                    'sub_total' => (float) $item->sub_total,
                    'discount' => (float) $item->discount,
                    'tax' => (float) $item->tax,
                    'shipping' => (float) $item->shipping_fee,
                    'line_total' => $lineTotal,
                ];
            })->all(),
            'activities' => $order->activities->map(fn($activity) => [
                'title' => $activity->title,
                'description' => $activity->description,
                'created_at' => $activity->created_at,
            ])->all(),
        ];
    }

    public function walletOverview(): array
    {
        $stats = $this->walletStats();

        return [
            'cards' => [
                ['label' => 'Tenant Balance', 'value' => $stats['balance'], 'format' => 'currency', 'caption' => 'Credits minus debits from subscription transaction records.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Subscription Revenue', 'value' => $stats['revenue'], 'format' => 'currency', 'caption' => 'Total stored price across tenant subscriptions.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Active Subs', 'value' => $stats['active_subscriptions'], 'format' => 'number', 'caption' => 'Subscriptions currently in active status.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Transactions', 'value' => $stats['transactions'], 'format' => 'number', 'caption' => 'Subscription transaction records stored for this tenant.', 'dot' => 'dot-violet'],
            ],
        ];
    }

    protected function deriveShippingStatus(Order $order): ?\App\Enums\OrderShippingStatus
    {
        return match ($order->status) {
            \App\Enums\OrderStatus::Pending => \App\Enums\OrderShippingStatus::Pending,
            \App\Enums\OrderStatus::Processing => \App\Enums\OrderShippingStatus::InDelivery,
            \App\Enums\OrderStatus::Shipped => \App\Enums\OrderShippingStatus::Shipped,
            \App\Enums\OrderStatus::Delivered => \App\Enums\OrderShippingStatus::Delivered,
            \App\Enums\OrderStatus::Cancelled => \App\Enums\OrderShippingStatus::Cancelled,
            default => null,
        };
    }

    /**
     * Orders are homogeneous per checkout (see CheckoutPage::submit, which splits a mixed
     * cart into a separate own-products order and central-products order), so classifying
     * by the order's first item is sufficient.
     */
    protected function orderIsCentralType(Order $order): bool
    {
        $items = $order->relationLoaded('items') ? $order->items : $order->items()->with('product')->get();
        $product = $items->first()?->product;

        return !($product && ($product->is_own_product || $product->is_tenant_owned));
    }

    protected function orderFinancials(Order $order): array
    {
        $items = $order->relationLoaded('items') ? $order->items : $order->items()->get();
        $subtotal = round((float) $items->sum('sub_total'), 2);
        $itemsDiscount = round((float) $items->sum('discount'), 2);
        $itemsTax = round((float) $items->sum('tax'), 2);
        $itemsShipping = round((float) $items->sum('shipping_fee'), 2);
        $orderDiscount = round($subtotal * ((float) ($order->discount_percentage ?? 0) / 100), 2);
        $orderTax = round($subtotal * ((float) ($order->tax_percentage ?? 0) / 100), 2);
        $shippingTotal = round((float) ($order->shipping_charge ?: $itemsShipping), 2);
        $grandTotal = round($subtotal - $itemsDiscount - $orderDiscount + $itemsTax + $orderTax + $shippingTotal, 2);
        $vendorNet = round(\App\Support\OrderProfitCalculator::effectiveTenantProfit(
            $order->vendor_gateway_id,
            $order->vendor_cost,
            $order->owner_profit,
            $grandTotal
        ), 2);

        return [
            'subtotal' => $subtotal,
            'items_discount' => $itemsDiscount,
            'discount' => $orderDiscount,
            'items_tax' => $itemsTax,
            'tax' => $orderTax,
            'shipping_total' => $shippingTotal,
            'grand_total' => $grandTotal,
            'owner_profit' => round(\App\Support\OrderProfitCalculator::effectiveOwnerProfitForOrder($order), 2),
            'vendor_cost' => round((float) ($order->vendor_cost ?? 0), 2),
            'vendor_net_total' => $vendorNet,
            'tenant_own_central' => round(\App\Support\OrderProfitCalculator::tenantOwnCentralForOrder($order), 2),
            'central_own_tenant' => round(\App\Support\OrderProfitCalculator::centralOwnTenantForOrder($order), 2),
            'remaining_owner_profit' => round(\App\Support\OrderProfitCalculator::remainingTenantOwnCentralForOrder($order), 2),
            'remaining_tenant_profit' => round(\App\Support\OrderProfitCalculator::remainingCentralOwnTenantForOrder($order), 2),
        ];
    }

    protected function rollingMonths(int $months = 12): Collection
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        return collect(range(0, $months - 1))->map(fn(int $offset) => $start->copy()->addMonths($offset));
    }

    protected function isSameMonth($value, Carbon $month): bool
    {
        return $value instanceof Carbon && $value->format('Y-m') === $month->format('Y-m');
    }

    protected function tenantBalance(): float
    {
        $credits = (float) Transaction::query()->where('type', WalletTransactionDirection::Credit->value)->sum('amount');
        $debits = (float) Transaction::query()->where('type', WalletTransactionDirection::Debit->value)->sum('amount');

        return round($credits - $debits, 2);
    }

    protected function normalizedMonthlyValue(Subscription $subscription): float
    {
        return match ($subscription->term) {
            PackageTerm::Monthly => (float) $subscription->price,
            PackageTerm::Quarterly => round((float) $subscription->price / 3, 2),
            PackageTerm::Yearly => round((float) $subscription->price / 12, 2),
            PackageTerm::Lifetime => 0.0,
        };
    }

    protected function orderStatusBreakdown(?Collection $orders = null): array
    {
        $orders ??= Order::query()->get();

        return collect(OrderStatus::cases())
            ->map(fn(OrderStatus $status) => [
                'label' => $status->label(),
                'count' => $orders->filter(fn(Order $order) => $order->status === $status)->count(),
            ])
            ->filter(fn(array $row) => $row['count'] > 0)
            ->values()
            ->all();
    }

    protected function shippingStatusBreakdown(Collection $orders): array
    {
        return [
            ['label' => 'Pending', 'count' => $orders->filter(fn(Order $order) => $order->status === OrderStatus::Pending)->count()],
            ['label' => 'Processing', 'count' => $orders->filter(fn(Order $order) => $order->status === OrderStatus::Processing)->count()],
            ['label' => 'Shipped', 'count' => $orders->filter(fn(Order $order) => $order->status === OrderStatus::Shipped)->count()],
            ['label' => 'Delivered', 'count' => $orders->filter(fn(Order $order) => in_array($order->status, [OrderStatus::Delivered, OrderStatus::Completed], true))->count()],
            ['label' => 'Cancelled', 'count' => $orders->filter(fn(Order $order) => in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true))->count()],
        ];
    }

    protected function monthlyProductProfitSeries(int $months = 12): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1)->toDateTimeString();

        $dbRows = DB::table('order_items as oi')
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'oi.product_variant_id')
            ->where('oi.created_at', '>=', $start)
            ->selectRaw("
                DATE_FORMAT(oi.created_at, '%Y-%m') as month_key,
                SUM(oi.sub_total - oi.discount + oi.tax + oi.shipping_fee) as revenue,
                SUM(COALESCE(pv.real_price, 0) * oi.qty) as cost
            ")
            ->groupBy('month_key')
            ->get()
            ->keyBy('month_key');

        return $this->rollingMonths($months)->map(function (Carbon $month) use ($dbRows) {
            $row = $dbRows->get($month->format('Y-m'));
            $revenue = (float) ($row?->revenue ?? 0);
            $cost = (float) ($row?->cost ?? 0);

            return [
                'label' => $month->format('M'),
                'revenue' => round($revenue, 2),
                'profit' => round($revenue - $cost, 2),
            ];
        })->all();
    }

    public function banners()
    {
        return Banner::query()->with('translations.language')->orderBy('serial_number')->get();
    }

    public function socialLinks()
    {
        return SocialLink::query()->orderBy('serial_number')->get();
    }


    public function appearanceSettings(): array
    {
        return Setting::query()
            ->whereIn('name', [
                'store_name',
                'footer_text',
                'footer_copyright',
                'logo_mode',
                'logo_text_ar',
                'logo_text_en',
                'logo_color',
                'logo_bg_color',
                'logo_shape',
                'logo_font_ar',
                'logo_font_en',
                'logo_path_ar',
                'logo_path_en',
                'promo_banner_title',
                'promo_banner_subtitle',
                'promo_banner_link',
                'promo_banner_cta_text',
                'promo_banner_image_url',
            ])
            ->get()
            ->pluck('value', 'name')
            ->all();
    }

    public function trackingSettings(): array
    {
        return Setting::query()
            ->whereIn('name', [
                'tracking_fb_pixel_id',
                'tracking_tiktok_pixel_id',
                'tracking_snapchat_pixel_id',
                'tracking_ga_measurement_id',
            ])
            ->get()
            ->pluck('value', 'name')
            ->all();
    }

    // ─── Vendor Purchases ───────────────────────────────────────────────────

    /**
     * Paginate orders that have a vendor cost (i.e. products sourced from central).
     */
    public function paginateVendorPurchases(array $filters, int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->buildVendorPurchasesQuery($filters)->paginate($perPage);
    }

    public function exportVendorPurchases(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        return $this->buildVendorPurchasesQuery($filters)->get();
    }

    protected function buildVendorPurchasesQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return Order::query()
            ->with(['customer', 'items.product', 'items.variant'])
            ->withCount('items')
            ->where('vendor_cost', '>', 0)
            ->when(filled($filters['settled'] ?? null), function ($query) use ($filters) {
                if ($filters['settled'] === 'settled') {
                    $query->whereNotNull('vendor_settled_at');
                } else {
                    $query->whereNull('vendor_settled_at');
                }
            })
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('uuid', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn($cq) => $cq->where('full_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->latest();
    }

    /**
     * Summary stats for the vendor purchases page.
     */
    public function vendorPurchaseStats(): array
    {
        $orders = Order::query()->where('vendor_cost', '>', 0)->get();

        $unsettled = $orders->whereNull('vendor_settled_at');
        $settled = $orders->whereNotNull('vendor_settled_at');

        return [
            'total' => $orders->count(),
            'unsettled_count' => $unsettled->count(),
            'settled_count' => $settled->count(),
            'total_owed' => round($unsettled->sum(fn(Order $o) => (float) $o->vendor_cost + (float) $o->shipping_charge), 2),
            'total_settled' => round($settled->sum(fn(Order $o) => (float) $o->vendor_total_with_fee), 2),
        ];
    }

    /**
     * Active central payment gateways available for vendor-to-central payments.
     *
     * Returns a keyed array of [id => 'Name  (fee info)'] for select inputs.
     * Only surfaces `vendor_payments` type gateways.
     */
    public function centralGatewayOptions(): array
    {
        return tenancy()->central(function () {
            return \App\Models\PaymentGateway::query()
                ->where('status', \App\Enums\ActivationStatus::Active->value)
                ->where('type', \App\Enums\PaymentGatewayType::VendorPayments->value)
                ->orderBy('name')
                ->get()
                ->mapWithKeys(function (\App\Models\PaymentGateway $gw) {
                    $pct = (float) ($gw->transaction_fee_percentage ?? 0);
                    $fixed = (float) ($gw->transaction_fee_fixed ?? 0);
                    $feeLabel = '';
                    if ($pct > 0 || $fixed > 0) {
                        $parts = [];
                        if ($fixed > 0)
                            $parts[] = '$' . number_format($fixed, 2);
                        if ($pct > 0)
                            $parts[] = number_format($pct, 4) . '%';
                        $feeLabel = ' (' . implode(' + ', $parts) . ')';
                    }
                    return [$gw->id => $gw->name . $feeLabel];
                })
                ->all();
        });
    }

    /**
     * Load a central payment gateway by id (crossing tenancy boundary).
     */
    public function findCentralGateway(int $id): ?\App\Models\PaymentGateway
    {
        return tenancy()->central(fn() => \App\Models\PaymentGateway::query()->find($id));
    }

    /**
     * Full central gateway list for the vendor settlement payment modal.
     * Includes code, credentials and mode needed for inline-card gateways.
     *
     * Only surfaces `vendor_payments` type gateways — i.e. gateways configured
     * for vendor-to-central payments (settlements, subscriptions, manufacturing).
     *
     * @return array<int, array{id:int,code:string,name:string,mode:string,creds:array,fee_pct:float,fee_fixed:float}>
     */
    public function centralGatewaysForPayment(): array
    {
        return tenancy()->central(function () {
            return \App\Models\PaymentGateway::query()
                ->where('status', \App\Enums\ActivationStatus::Active->value)
                ->where('type', \App\Enums\PaymentGatewayType::VendorPayments->value)
                ->orderBy('name')
                ->get()
                ->map(function (\App\Models\PaymentGateway $gw) {
                    return [
                        'id' => $gw->id,
                        'code' => $gw->code,
                        'name' => $gw->name,
                        'mode' => 'live',
                        'creds' => (array) ($gw->credentials ?? []),
                        'fee_pct' => (float) ($gw->transaction_fee_percentage ?? 0),
                        'fee_fixed' => (float) ($gw->transaction_fee_fixed ?? 0),
                    ];
                })
                ->values()
                ->all();
        });
    }
}
