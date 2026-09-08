<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Concerns\SanitizesPhoneNumber;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    use SanitizesPhoneNumber;

    /** GET /addresses */
    public function index(): JsonResponse
    {
        $addresses = CustomerAddress::where('customer_id', $this->customer()->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $addresses->map(fn(CustomerAddress $a) => $this->transform($a))->values()]);
    }

    /** POST /addresses */
    public function store(Request $request): JsonResponse
    {
        $customer = $this->customer();
        $validated = $this->validated($request);

        $data = array_merge($validated, [
            'customer_id' => $customer->id,
            'country' => Country::on('mysql')->find($validated['country_id'])?->name ?? '',
        ]);

        if (!empty($data['is_default'])) {
            CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
        } elseif (!CustomerAddress::where('customer_id', $customer->id)->exists()) {
            $data['is_default'] = true;
        }

        $address = CustomerAddress::create($data);

        return response()->json(['success' => true, 'address' => $this->transform($address)], 201);
    }

    /** PUT /addresses/{address} */
    public function update(Request $request, CustomerAddress $address): JsonResponse
    {
        $customer = $this->customer();
        if ((int) $address->customer_id !== (int) $customer->id) {
            return response()->json(['success' => false, 'message' => __('Address not found.')], 404);
        }

        $validated = $this->validated($request);
        $data = array_merge($validated, [
            'country' => Country::on('mysql')->find($validated['country_id'])?->name ?? '',
        ]);

        if (!empty($data['is_default'])) {
            CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json(['success' => true, 'address' => $this->transform($address->fresh())]);
    }

    /** DELETE /addresses/{address} */
    public function destroy(CustomerAddress $address): JsonResponse
    {
        $customer = $this->customer();
        if ((int) $address->customer_id !== (int) $customer->id) {
            return response()->json(['success' => false, 'message' => __('Address not found.')], 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            CustomerAddress::where('customer_id', $customer->id)->orderBy('id')->first()?->update(['is_default' => true]);
        }

        return response()->json(['success' => true]);
    }

    /** POST /addresses/{address}/default */
    public function setDefault(CustomerAddress $address): JsonResponse
    {
        $customer = $this->customer();
        if ((int) $address->customer_id !== (int) $customer->id) {
            return response()->json(['success' => false, 'message' => __('Address not found.')], 404);
        }

        CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json(['success' => true]);
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'full_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'address_line_1' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'country_id' => ['required', 'integer', Rule::exists('mysql.countries', 'id')],
            'is_default' => 'nullable|boolean',
        ]);

        $validated['phone'] = $this->sanitizePhone($validated['phone'] ?? '');
        $validated['label'] = $validated['label'] ?: null;
        $validated['state'] = $validated['state'] ?? null;
        $validated['is_default'] = (bool) ($validated['is_default'] ?? false);

        return $validated;
    }

    private function customer(): Customer
    {
        /** @var Customer $customer */
        $customer = Auth::guard('storefront')->user();

        return $customer;
    }

    private function transform(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'full_name' => $address->full_name,
            'phone' => $address->phone,
            'address_line_1' => $address->address_line_1,
            'city' => $address->city,
            'state' => $address->state,
            'country' => $address->country,
            'country_id' => $address->country_id,
            'is_default' => (bool) $address->is_default,
        ];
    }
}
