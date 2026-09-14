<?php

declare(strict_types=1);

// ROUTES (this controller replaces App\Http\Controllers\Tenant\AccountSettingsController;
// the kept GET/PUT `tenant.settings.account` / `tenant.settings.account.update` routes need
// their `use` import repointed to this namespace):
// Route::get('/account', [AccountSettingsController::class, 'show'])->name('account');
// Route::put('/account', [AccountSettingsController::class, 'update'])->name('account.update');
// Route::post('/account/validate', [AccountSettingsController::class, 'validateUpdate'])->name('account.validate');
// (all under the existing tenant.permission:settings.account.manage middleware)

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\UpdateAccountRequest;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class AccountSettingsController extends PanelController
{
    public function __construct(private readonly TenantPanelService $service)
    {
    }

    public function show(): View
    {
        $tenant = tenant();
        $admin = auth('tenant')->user();

        $rawPhone = (string) ($tenant->phone ?? '');

        return view('tenant.pages.settings.account.index', [
            'adminName' => (string) ($admin?->name ?? ''),
            'adminEmail' => (string) ($admin?->email ?? ''),
            'phone' => str_contains($rawPhone, 'object') ? '' : $rawPhone,
            'shopName' => (string) $tenant->shop_name ?? $tenant->name ?? '',
            'description' => (string) $tenant->description ?? '',
            'address' => (string) $tenant->address ?? '',
        ]);
    }

    public function update(UpdateAccountRequest $request): JsonResponse
    {
        $validated = $request->validated();

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

        $redirect = $request->input('from') === 'onboarding'
            ? route('tenant.onboarding', ['tab' => 'setup'])
            : route('tenant.settings.account');

        return $this->success('Account settings updated successfully.', [], $redirect);
    }

    public function validateUpdate(UpdateAccountRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }
}
