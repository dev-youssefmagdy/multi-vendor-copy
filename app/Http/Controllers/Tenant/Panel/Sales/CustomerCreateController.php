<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Sales;

use App\Concerns\SanitizesPhoneNumber;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Sales\StoreCustomerRequest;
use App\Models\City;
use App\Models\Country;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class CustomerCreateController extends PanelController
{
    use SanitizesPhoneNumber;

    public function __construct(
        private readonly TenantPanelService $service,
    ) {
    }

    public function create(): View
    {
        return view('tenant.pages.sales.customers.create', [
            'countries' => Country::with('translations.language')->orderBy('name')
                ->get(['id', 'name'])->pluck('name', 'id'),
        ]);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $customer = $this->service->saveCustomer([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $this->sanitizePhone($validated['phone'] ?? null),
            'address' => $validated['address'] ?? null,
            'country_id' => filled($validated['country_id'] ?? null) ? (int) $validated['country_id'] : null,
            'city_id' => filled($validated['city_id'] ?? null) ? (int) $validated['city_id'] : null,
            'password' => $validated['password'],
            'active' => $validated['active'] ?? false,
        ]);

        return $this->success(
            'Customer created successfully.',
            redirect: route('tenant.customers.show', $customer->id),
        );
    }

    public function validateStore(StoreCustomerRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }
}
