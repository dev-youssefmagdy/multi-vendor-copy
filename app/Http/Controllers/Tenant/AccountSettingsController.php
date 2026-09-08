<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function __construct(private readonly TenantPanelService $service) {}

    public function show(): View
    {
        $tenant = tenant();
        $admin = auth('tenant')->user();

        $rawPhone = (string) ($tenant->phone ?? '');

        return view('tenant.setting.account-settings', [
            'adminName' => (string) ($admin?->name ?? ''),
            'adminEmail' => (string) ($admin?->email ?? ''),
            'phone' => str_contains($rawPhone, 'object') ? '' : $rawPhone,
            'shopName' => (string) $tenant->shop_name ?? $tenant->name ?? '',
            'description' => (string) $tenant->description ?? '',
            'address' => (string) $tenant->address ?? '',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'adminName' => ['required', 'string', 'max:255'],
            'adminEmail' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'shopName' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $phone = preg_replace('/[^+\d\s\-()]+/', '', (string) ($validated['phone'] ?? ''));
        $phone = trim($phone ?? '');

        $this->service->updateAccount([
            'admin_name' => $validated['adminName'],
            'admin_email' => $validated['adminEmail'],
            'phone' => $phone,
            'shop_name' => $validated['shopName'],
            'description' => $validated['description'] ?? '',
            'address' => $validated['address'] ?? '',
            'password' => $validated['password'] ?? null,
        ]);

        if ($request->input('from') === 'onboarding') {
            return redirect()
                ->route('tenant.onboarding', ['tab' => 'setup'])
                ->with('status', 'Account settings updated successfully.')
                ->with('status_type', 'success');
        }

        return redirect()
            ->route('tenant.settings.account')
            ->with('status', 'Account settings updated successfully.')
            ->with('status_type', 'success');
    }
}
