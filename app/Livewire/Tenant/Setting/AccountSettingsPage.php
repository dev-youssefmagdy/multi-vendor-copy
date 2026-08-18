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
    public string $description = '';
    public string $address = '';
    public string $password = '';

    public function mount(): void
    {
        $tenant = tenant();
        $admin = auth('tenant')->user();

        $this->adminName = (string) ($admin?->name ?? '');
        $this->adminEmail = (string) ($admin?->email ?? '');
        $rawPhone = (string) ($tenant->phone ?? '');
        $this->phone = str_contains($rawPhone, 'object') ? '' : $rawPhone;
        $this->shopName = (string) data_get($tenant, 'data.shop_name', $tenant->name ?? '');
        $this->description = (string) data_get($tenant, 'data.description', '');
        $this->address = (string) data_get($tenant, 'data.address', '');
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Account Settings',
            'badge' => 'Settings',
            'description' => 'Maintain the tenant account profile used by the vendor control panel.',
            'contentIntro' => 'This page updates the central tenant record while staying within the tenant panel shell.',
            'formAction' => 'save',
            'submitLabel' => 'Save Account',
            'fieldGroups' => [
                [
                    'title' => 'Profile',
                    'description' => 'Tenant admin credentials and storefront identity details.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Admin Name', 'model' => 'adminName'],
                        ['label' => 'Admin Email', 'model' => 'adminEmail', 'type' => 'email'],
                        ['label' => 'Store Phone', 'model' => 'phone'],
                        ['label' => 'Shop Name', 'model' => 'shopName'],
                        ['label' => 'New Admin Password', 'model' => 'password', 'type' => 'password', 'wrapperClass' => 'span-2'],
                    ],
                ],
                [
                    'title' => 'Store Details',
                    'description' => 'Details shown to customers on your storefront.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Store Description', 'model' => 'description', 'wrapperClass' => 'span-2'],
                        ['label' => 'Address', 'model' => 'address', 'wrapperClass' => 'span-2'],
                    ],
                ],
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
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $service->updateAccount([
            'admin_name' => $validated['adminName'],
            'admin_email' => $validated['adminEmail'],
            'phone' => $validated['phone'] ?? '',
            'shop_name' => $validated['shopName'],
            'description' => $validated['description'] ?? '',
            'address' => $validated['address'] ?? '',
            'password' => $validated['password'] ?? null,
        ]);

        $this->password = '';
        $this->toast('Account settings updated successfully.');
    }
}
