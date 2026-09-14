<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Sales;

use App\Concerns\SanitizesPhoneNumber;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Sales\SaveCustomerAddressRequest;
use App\Http\Requests\Tenant\Panel\Sales\UpdateCustomerProfileRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerAddress;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use App\Support\Tenant\Select2Response;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class CustomerDetailController extends PanelController
{
    use SanitizesPhoneNumber;

    public function __construct(
        private readonly TenantPanelRepository $repo,
    ) {
    }

    public function show(Request $request, int $customerId): View
    {
        $customer = Customer::query()->findOrFail($customerId);
        $detail = $this->repo->customerDetail($customer);

        $rawPhone = (string) ($customer->phone ?? '');

        return view('tenant.pages.sales.customers.show', [
            'detail' => $detail,
            'customer' => $customer,
            'customerId' => $customerId,
            'stats' => Metric::cards([
                ['label' => 'Total Orders', 'value' => $detail['orderCount'], 'format' => 'number', 'caption' => $detail['paidCount'] . ' paid', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Total Spent', 'value' => $detail['totalSpent'], 'format' => 'currency', 'caption' => '$' . number_format((float) $detail['paidSpent'], 2) . ' collected', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Avg Order', 'value' => $detail['avgOrder'], 'format' => 'currency', 'caption' => 'Avg per order placed', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Last Order', 'value' => $detail['lastOrderAt'] ? $detail['lastOrderAt']->format('M d, Y') : 'Never', 'caption' => $customer->active ? 'Active' : 'Inactive', 'dot' => $customer->active ? 'dot-green' : 'dot-amber'],
            ]),
            'phone' => str_contains($rawPhone, 'object') ? '' : $rawPhone,
            'countries' => Country::with('translations.language')->orderBy('name')
                ->get(['id', 'name'])->pluck('name', 'id'),
            'cities' => $customer->country_id
                ? City::where('country_id', $customer->country_id)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'activeTab' => $request->query('tab', 'profile'),
            'paymentsColumns' => [
                TableColumn::index(),
                TableColumn::make('uuid', 'Order')->orderable(false),
                TableColumn::make('gateway', 'Gateway')->orderable(false),
                TableColumn::make('grand_total', 'Amount')->orderable(false),
                TableColumn::make('paid', 'Status')->orderable(false),
                TableColumn::make('created_at', 'Date'),
            ],
        ]);
    }

    public function updateProfile(UpdateCustomerProfileRequest $request, int $customerId): JsonResponse
    {
        $customer = Customer::query()->findOrFail($customerId);
        $validated = $request->validated();

        $payload = [
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $this->sanitizePhone($validated['phone'] ?? null),
            'address' => $validated['address'] ?? null,
            'country_id' => filled($validated['country_id'] ?? null) ? (int) $validated['country_id'] : null,
            'city_id' => filled($validated['city_id'] ?? null) ? (int) $validated['city_id'] : null,
            'active' => $validated['active'] ?? false,
        ];

        if (filled($validated['password'] ?? null)) {
            $payload['password'] = bcrypt($validated['password']);
        }

        $customer->update($payload);

        return $this->success('Customer profile updated.');
    }

    public function validateProfile(UpdateCustomerProfileRequest $request, int $customerId): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function toggleActive(int $customerId): JsonResponse
    {
        $customer = Customer::query()->findOrFail($customerId);
        $customer->update(['active' => !$customer->active]);

        return $this->success('Customer status updated successfully.');
    }

    public function paymentsData(Request $request, int $customerId): JsonResponse
    {
        Customer::query()->findOrFail($customerId);

        $filters = $this->filters($request, ['search']);

        $query = Order::query()
            ->with(['paymentGateway'])
            ->where('customer_id', $customerId)
            ->latest();

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('uuid', fn (Order $order) => '<a href="' . route('tenant.orders.show', $order->id) . '" class="entity-title" style="color:var(--accent,#6366f1);">#' . e($order->uuid ?: (string) $order->id) . '</a>')
            ->editColumn('gateway', fn (Order $order) => e($order->paymentGateway?->name ?? $order->payment_method ?? '-'))
            ->editColumn('grand_total', fn (Order $order) => '$' . number_format((float) $order->grand_total, 2))
            ->editColumn('paid', fn (Order $order) => view('tenant::components.status-badge', ['status' => $order->paid ? 'paid' : 'pending'])->render())
            ->editColumn('created_at', fn (Order $order) => $order->created_at?->format('M d, Y'))
            ->rawColumns(['uuid', 'paid'])
            ->toJson();
    }

    public function showAddress(int $customerId, int $addressId): JsonResponse
    {
        $address = CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->findOrFail($addressId);

        return response()->json([
            'data' => [
                'label' => $address->label,
                'full_name' => $address->full_name,
                'email' => $address->email,
                'phone' => $address->phone,
                'address_line_1' => $address->address_line_1,
                'city' => $address->city,
                'state' => $address->state,
                'country' => $address->country,
                'is_default' => (bool) $address->is_default,
            ],
        ]);
    }

    public function storeAddress(SaveCustomerAddressRequest $request, int $customerId): JsonResponse
    {
        Customer::query()->findOrFail($customerId);

        $validated = $request->validated();

        if ($validated['is_default'] ?? false) {
            CustomerAddress::query()->where('customer_id', $customerId)->update(['is_default' => false]);
        }

        CustomerAddress::query()->create([
            'customer_id' => $customerId,
            'label' => $validated['label'] ?: null,
            'full_name' => $validated['full_name'] ?: null,
            'email' => $validated['email'] ?: null,
            'phone' => $this->sanitizePhone($validated['phone'] ?? null),
            'address_line_1' => $validated['address_line_1'],
            'city' => $validated['city'] ?: null,
            'state' => $validated['state'] ?: null,
            'country' => $validated['country'] ?: null,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return $this->success('Address added.');
    }

    public function updateAddress(SaveCustomerAddressRequest $request, int $customerId, int $addressId): JsonResponse
    {
        $address = CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->findOrFail($addressId);

        $validated = $request->validated();

        if ($validated['is_default'] ?? false) {
            CustomerAddress::query()->where('customer_id', $customerId)->update(['is_default' => false]);
        }

        $address->update([
            'label' => $validated['label'] ?: null,
            'full_name' => $validated['full_name'] ?: null,
            'email' => $validated['email'] ?: null,
            'phone' => $this->sanitizePhone($validated['phone'] ?? null),
            'address_line_1' => $validated['address_line_1'],
            'city' => $validated['city'] ?: null,
            'state' => $validated['state'] ?: null,
            'country' => $validated['country'] ?: null,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return $this->success('Address updated.');
    }

    public function validateAddress(SaveCustomerAddressRequest $request, int $customerId): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function destroyAddress(int $customerId, int $addressId): JsonResponse
    {
        CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->findOrFail($addressId)
            ->delete();

        return $this->success('Address deleted.');
    }

    public function citiesByCountry(Request $request, ?int $countryId = null): JsonResponse
    {
        $countryId = $countryId ?: (int) $request->query('depends_on', 0);

        if ($request->query('format') === 'select2') {
            $cities = City::where('country_id', $countryId)->orderBy('name')->paginate(50, ['id', 'name']);

            return Select2Response::paginate($cities, fn (City $city) => ['id' => $city->id, 'text' => $city->name]);
        }

        $cities = City::where('country_id', $countryId)->orderBy('name')->get(['id', 'name']);

        return response()->json($cities);
    }
}
