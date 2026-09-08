<?php

namespace App\Http\Controllers\Tenant;

use App\Concerns\SanitizesPhoneNumber;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerAddress;
use App\Repositories\Tenant\TenantPanelRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerDetailController extends Controller
{
    use SanitizesPhoneNumber;

    public function show(Request $request, int $customerId): View
    {
        $customer = Customer::query()->findOrFail($customerId);
        $detail   = app(TenantPanelRepository::class)->customerDetail($customer);

        $rawPhone = (string) ($customer->phone ?? '');

        return view('tenant.customer.customer-detail', [
            'detail'     => $detail,
            'customer'   => $customer,
            'customerId' => $customerId,
            'phone'      => str_contains($rawPhone, 'object') ? '' : $rawPhone,
            'countries'  => Country::with('translations.language')->orderBy('name')
                ->get(['id', 'name'])->pluck('name', 'id'),
            'cities'     => $customer->country_id
                ? City::where('country_id', $customer->country_id)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'activeTab'  => $request->query('tab', 'profile'),
        ]);
    }

    public function updateProfile(Request $request, int $customerId): RedirectResponse
    {
        $customer = Customer::query()->findOrFail($customerId);

        $validated = $request->validate([
            'fullName'             => 'required|string|max:191',
            'email'                => 'required|email|max:191|unique:customers,email,' . $customerId,
            'phone'                => 'nullable|string|max:50',
            'address'              => 'nullable|string|max:500',
            'countryId'            => 'nullable|integer',
            'cityId'               => 'nullable|integer',
            'password'             => 'nullable|string|min:8|same:passwordConfirmation',
            'passwordConfirmation' => 'nullable|string',
        ]);

        $payload = [
            'full_name'  => $validated['fullName'],
            'email'      => $validated['email'],
            'phone'      => $this->sanitizePhone($validated['phone'] ?? null),
            'address'    => $validated['address'] ?? null,
            'country_id' => filled($validated['countryId'] ?? null) ? (int) $validated['countryId'] : null,
            'city_id'    => filled($validated['cityId'] ?? null) ? (int) $validated['cityId'] : null,
            'active'     => $request->boolean('active'),
        ];

        if (filled($validated['password'] ?? null)) {
            $payload['password'] = bcrypt($validated['password']);
        }

        $customer->update($payload);

        return redirect()
            ->route('tenant.customers.show', $customerId)
            ->with('status', 'Profile updated successfully.');
    }

    public function storeAddress(Request $request, int $customerId): RedirectResponse
    {
        Customer::query()->findOrFail($customerId);

        $validated = $request->validate([
            'addrLabel'     => 'nullable|string|max:191',
            'addrFullName'  => 'nullable|string|max:191',
            'addrEmail'     => 'nullable|email|max:191',
            'addrPhone'     => 'nullable|string|max:50',
            'addrLine1'     => 'required|string|max:500',
            'addrCity'      => 'nullable|string|max:191',
            'addrState'     => 'nullable|string|max:191',
            'addrCountry'   => 'nullable|string|max:191',
            'addrIsDefault' => 'boolean',
        ]);

        if ($request->boolean('addrIsDefault')) {
            CustomerAddress::query()->where('customer_id', $customerId)->update(['is_default' => false]);
        }

        CustomerAddress::query()->create([
            'customer_id'    => $customerId,
            'label'          => $validated['addrLabel'] ?: null,
            'full_name'      => $validated['addrFullName'] ?: null,
            'email'          => $validated['addrEmail'] ?: null,
            'phone'          => $this->sanitizePhone($validated['addrPhone'] ?? null),
            'address_line_1' => $validated['addrLine1'],
            'city'           => $validated['addrCity'] ?: null,
            'state'          => $validated['addrState'] ?: null,
            'country'        => $validated['addrCountry'] ?: null,
            'is_default'     => $request->boolean('addrIsDefault'),
        ]);

        return redirect()
            ->route('tenant.customers.show', [$customerId, 'tab' => 'addresses'])
            ->with('status', 'Address added.');
    }

    public function updateAddress(Request $request, int $customerId, int $addressId): RedirectResponse
    {
        $addr = CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->findOrFail($addressId);

        $validated = $request->validate([
            'addrLabel'     => 'nullable|string|max:191',
            'addrFullName'  => 'nullable|string|max:191',
            'addrEmail'     => 'nullable|email|max:191',
            'addrPhone'     => 'nullable|string|max:50',
            'addrLine1'     => 'required|string|max:500',
            'addrCity'      => 'nullable|string|max:191',
            'addrState'     => 'nullable|string|max:191',
            'addrCountry'   => 'nullable|string|max:191',
            'addrIsDefault' => 'boolean',
        ]);

        if ($request->boolean('addrIsDefault')) {
            CustomerAddress::query()->where('customer_id', $customerId)->update(['is_default' => false]);
        }

        $addr->update([
            'label'          => $validated['addrLabel'] ?: null,
            'full_name'      => $validated['addrFullName'] ?: null,
            'email'          => $validated['addrEmail'] ?: null,
            'phone'          => $this->sanitizePhone($validated['addrPhone'] ?? null),
            'address_line_1' => $validated['addrLine1'],
            'city'           => $validated['addrCity'] ?: null,
            'state'          => $validated['addrState'] ?: null,
            'country'        => $validated['addrCountry'] ?: null,
            'is_default'     => $request->boolean('addrIsDefault'),
        ]);

        return redirect()
            ->route('tenant.customers.show', [$customerId, 'tab' => 'addresses'])
            ->with('status', 'Address updated.');
    }

    public function destroyAddress(int $customerId, int $addressId): RedirectResponse
    {
        CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->findOrFail($addressId)
            ->delete();

        return redirect()
            ->route('tenant.customers.show', [$customerId, 'tab' => 'addresses'])
            ->with('status', 'Address deleted.');
    }

    public function citiesByCountry(int $countryId): JsonResponse
    {
        $cities = City::where('country_id', $countryId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($cities);
    }
}
