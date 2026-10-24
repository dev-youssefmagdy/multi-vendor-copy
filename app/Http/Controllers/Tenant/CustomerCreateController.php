<?php

namespace App\Http\Controllers\Tenant;

use App\Concerns\SanitizesPhoneNumber;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerCreateController extends Controller
{
    use SanitizesPhoneNumber;

    public function create(): View
    {
        return view('tenant.customer.customer-create', [
            'countries' => Country::with('translations.language')->orderBy('name')
                ->get(['id', 'name'])->pluck('name', 'id'),
            'cities'    => old('countryId')
                ? City::where('country_id', old('countryId'))->orderBy('name')->pluck('name', 'id')
                : collect(),
        ]);
    }

    public function store(Request $request, TenantPanelService $service): RedirectResponse
    {
        $validated = $request->validate([
            'fullName'             => 'required|string|max:191',
            'email'                => 'required|email|max:191|unique:customers,email',
            'phone'                => 'nullable|string|max:50',
            'address'              => 'nullable|string|max:500',
            'countryId'            => 'nullable|integer',
            'cityId'               => 'nullable|integer',
            'password'             => 'required|string|min:6|same:passwordConfirmation',
            'passwordConfirmation' => 'nullable|string',
        ]);

        $customer = $service->saveCustomer([
            'full_name'  => $validated['fullName'],
            'email'      => $validated['email'],
            'phone'      => $this->sanitizePhone($validated['phone'] ?? null),
            'address'    => $validated['address'] ?? null,
            'country_id' => filled($validated['countryId'] ?? null) ? (int) $validated['countryId'] : null,
            'city_id'    => filled($validated['cityId'] ?? null) ? (int) $validated['cityId'] : null,
            'password'   => $validated['password'],
            'active'     => $request->boolean('active'),
        ]);

        return redirect()
            ->route('tenant.customers.show', $customer->id)
            ->with('status', 'Customer created successfully.');
    }
}
