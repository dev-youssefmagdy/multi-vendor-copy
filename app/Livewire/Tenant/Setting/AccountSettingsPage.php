<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\ContentPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Services\Tenant\TenantPanelService;

class AccountSettingsPage extends ContentPage
{
    use InteractsWithTenantUi;

    protected function pageView(): string
    {
        return 'livewire.tenant.setting.account-settings';
    }

    public string $adminName = '';
    public string $adminEmail = '';
    public string $phone = '';
    public string $shopName = '';
    // public string $address = '';
    public string $password = '';

    public function mount(): void
    {
        $tenant = tenant();
        $admin = auth('tenant')->user();

        $this->adminName = (string) ($admin?->name ?? '');
        $this->adminEmail = (string) ($admin?->email ?? '');
        $this->phone = (string) ($tenant->phone ?? '');
        $this->shopName = (string) data_get($tenant, 'data.shop_name', $tenant->name ?? '');
        // $this->address = (string) data_get($tenant, 'data.address', '');
    }

    protected function pageMeta(): array
    {
        return [
            'title' => __('Account Settings'),
            'badge' => __('Settings'),
            'description' => __('Maintain the tenant account profile used by the vendor control panel.'),
            'contentIntro' => __('This page updates the central tenant record while staying within the tenant panel shell.'),
            'formAction' => 'save',
            'submitLabel' => __('Save Account'),
            'fieldGroups' => [
                [
                    'title' => __('Profile'),
                    'description' => __('Tenant admin credentials and storefront identity details.'),
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => __('Admin Name'), 'model' => 'adminName'],
                        ['label' => __('Admin Email'), 'model' => 'adminEmail', 'type' => 'email'],
                        ['label' => __('Store Phone'), 'model' => 'phone'],
                        ['label' => __('Shop Name'), 'model' => 'shopName'],
                        // ['label' => 'Address', 'model' => 'address', 'wrapperClass' => 'span-2'],
                        ['label' => __('New Admin Password'), 'model' => 'password', 'type' => 'password', 'wrapperClass' => 'span-2'],
                    ],
                ]
            ],
        ];
    }

    public function save(TenantPanelService $service): void
    {
        $validated = $this->validate([
            'adminName' => ['required', 'string', 'max:255'],
            'adminEmail' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'shopName' => ['required', 'string', 'max:255'],
            // 'address' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $service->updateAccount([
            'admin_name' => $validated['adminName'],
            'admin_email' => $validated['adminEmail'],
            'phone' => $validated['phone'] ?? '',
            'shop_name' => $validated['shopName'],
            // 'address' => $validated['address'] ?? '',
            'password' => $validated['password'] ?? null,
        ]);

        $this->password = '';
        $this->toast(__('Account settings updated successfully.'));
    }
}
