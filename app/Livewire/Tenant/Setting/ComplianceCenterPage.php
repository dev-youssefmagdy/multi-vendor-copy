<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\ContentPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Services\Tenant\TenantPanelService;

class ComplianceCenterPage extends ContentPage
{
    use InteractsWithTenantUi;

    protected function pageView(): string
    {
        return 'livewire.tenant.setting.compliance-center';
    }

    public string $ownerName = '';
    public string $ownerIdNumber = '';
    public string $registrationNumber = '';
    public string $bankName = '';
    public string $bankAccountNumber = '';
    public string $bankIban = '';

    public function mount(): void
    {
        $tenant = tenant();

        $this->ownerName = (string) data_get($tenant, 'data.compliance_owner_name', '');
        $this->ownerIdNumber = (string) data_get($tenant, 'data.compliance_owner_id_number', '');
        $this->registrationNumber = (string) data_get($tenant, 'data.compliance_registration_number', '');
        $this->bankName = (string) data_get($tenant, 'data.compliance_bank_name', '');
        $this->bankAccountNumber = (string) data_get($tenant, 'data.compliance_bank_account_number', '');
        $this->bankIban = (string) data_get($tenant, 'data.compliance_bank_iban', '');
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Compliance Center',
            'badge' => 'Compliance',
            'description' => 'Owner details, business registration, and bank information required to keep your store in good standing.',
        ];
    }

    public function save(TenantPanelService $service): void
    {
        $validated = $this->validate([
            'ownerName' => ['required', 'string', 'max:255'],
            'ownerIdNumber' => ['required', 'string', 'max:100'],
            'registrationNumber' => ['required', 'string', 'max:100'],
            'bankName' => ['required', 'string', 'max:255'],
            'bankAccountNumber' => ['required', 'string', 'max:100'],
            'bankIban' => ['nullable', 'string', 'max:100'],
        ]);

        $service->updateCompliance([
            'owner_name' => $validated['ownerName'],
            'owner_id_number' => $validated['ownerIdNumber'],
            'registration_number' => $validated['registrationNumber'],
            'bank_name' => $validated['bankName'],
            'bank_account_number' => $validated['bankAccountNumber'],
            'bank_iban' => $validated['bankIban'] ?? '',
        ]);

        $this->toast('Compliance information updated successfully.');
    }
}
