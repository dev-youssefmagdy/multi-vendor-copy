<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Sales;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Customer;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

final class CustomersController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $stats = $this->repo->customerStats();

        return view('tenant.pages.sales.customers.index', [
            'stats' => Metric::cards([
                ['label' => __('Customers'), 'value' => $stats['total'], 'format' => 'number', 'caption' => __('Tenant customer records.'), 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => __('Active'), 'value' => $stats['active'], 'format' => 'number', 'caption' => __('Customers currently allowed to order.'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('Buyers'), 'value' => $stats['buyers'], 'format' => 'number', 'caption' => __('Profiles that have already converted into orders.'), 'dot' => 'dot-violet'],
                ['label' => __('Avg Lifetime'), 'value' => $stats['avg_lifetime'], 'format' => 'currency', 'caption' => __('Average customer lifetime spend for ordering customers.'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ]),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('customer', __('Customer'))->orderable(false),
                TableColumn::make('contact', __('Contact'))->orderable(false),
                TableColumn::make('orders', __('Orders'))->orderable(false),
                TableColumn::make('lifetime_value', __('Lifetime Value'))->orderable(false),
                TableColumn::make('last_order', __('Last Order'))->orderable(false),
                TableColumn::make('status', __('Status'))->orderable(false),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status']);

        return DataTables::eloquent($this->repo->queryCustomers($filters))
            ->addIndexColumn()
            ->editColumn('customer', fn(Customer $customer) => view('tenant.pages.sales.customers._cols.customer', ['customer' => $customer])->render())
            ->editColumn('contact', fn(Customer $customer) => view('tenant.pages.sales.customers._cols.contact', ['customer' => $customer])->render())
            ->editColumn('orders', fn(Customer $customer) => view('tenant.pages.sales.customers._cols.orders', ['customer' => $customer])->render())
            ->editColumn('lifetime_value', fn(Customer $customer) => view('tenant.pages.sales.customers._cols.lifetime-value', ['customer' => $customer])->render())
            ->editColumn('last_order', fn(Customer $customer) => e(optional($customer->orders->sortByDesc('created_at')->first()?->created_at)->format('M d, Y') ?: 'N/A'))
            ->editColumn('status', fn(Customer $customer) => view('tenant::components.status-badge', ['status' => $customer->active ? 'active' : 'inactive'])->render())
            ->addColumn('actions', fn(Customer $customer) => view('tenant.pages.sales.customers._cols.actions', ['customer' => $customer])->render())
            ->rawColumns(['customer', 'contact', 'orders', 'lifetime_value', 'status', 'actions'])
            ->toJson();
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request, ['search', 'status']);

        $rows = $this->repo->exportCustomers($filters)->map(fn(Customer $customer) => [
            $customer->id,
            $customer->full_name,
            $customer->email,
            $customer->phone ?: '',
            $customer->address ?: '',
            $customer->orders_count,
            $customer->paid_orders_count,
            $customer->active ? __('Active') : __('Inactive'),
            $customer->created_at?->format('Y-m-d') ?? '',
        ]);

        return $this->streamCsv(
            'customers-' . now()->format('Y-m-d') . '.csv',
            ['ID', __('Name'), __('Email'), __('Phone'), __('Address'), __('Orders'), __('Paid Orders'), __('Status'), __('Registered At')],
            $rows,
        );
    }

    public function destroy(int $customerId): JsonResponse
    {
        $this->service->deleteModel(Customer::query()->findOrFail($customerId));

        return $this->success(__('Customer deleted successfully.'));
    }
}
