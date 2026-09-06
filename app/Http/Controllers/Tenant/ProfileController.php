<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Concerns\SanitizesPhoneNumber;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    use SanitizesPhoneNumber;

    /** GET /profile */
    public function show(): JsonResponse
    {
        return response()->json(['customer' => $this->transform($this->customer())]);
    }

    /** PUT /profile */
    public function update(Request $request): JsonResponse
    {
        $customer = $this->customer();

        $validated = $request->validate([
            'full_name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:30',
            'email' => ['required', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($customer->id)],
            'language' => 'nullable|string|max:10',
        ]);

        $validated['phone'] = $this->sanitizePhone($validated['phone'] ?? '');

        $customer->update($validated);

        return response()->json(['success' => true, 'customer' => $this->transform($customer->fresh())]);
    }

    /** POST /profile/password */
    public function updatePassword(Request $request): JsonResponse
    {
        $customer = $this->customer();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($validated['current_password'], $customer->password)) {
            return response()->json(['success' => false, 'message' => __('Current password is incorrect.')], 422);
        }

        $customer->update(['password' => Hash::make($validated['password'])]);

        return response()->json(['success' => true]);
    }

    /** POST /logout */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('storefront')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true]);
    }

    private function customer(): Customer
    {
        /** @var Customer $customer */
        $customer = Auth::guard('storefront')->user();

        return $customer;
    }

    private function transform(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'full_name' => $customer->full_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'language' => $customer->language,
            'avatar' => $customer->avatar,
        ];
    }
}
