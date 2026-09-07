<?php

namespace App\Livewire\Admin\Compliance;

use App\Helpers\TenantNavigation;
use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Tenant;

class ComplianceDetailPage extends AdminPage
{
    use InteractsWithAdminUi;

    public string $tenantId = '';
    public string $note = '';

    public function mount(string $tenant): void
    {
        $this->authorizePermission('compliance.tenants.view');
        $this->tenantId = $tenant;
        $this->note = (string) (Tenant::query()->find($tenant)?->compliance_admin_note ?? '');
    }

    protected function pageMeta(): array
    {
        $tenant = $this->tenant();

        return [
            'title' => $tenant?->name ?? 'Tenant Compliance',
            'badge' => 'Compliance Detail',
            'description' => 'Review compliance data, uploaded documents, and set the tenant\'s verification status.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.compliance.compliance-detail-page';
    }

    protected function pageData(): array
    {
        $tenant = $this->tenant();
        $complianceSettings = [];
        $completionPercent = 0;

        if ($tenant) {
            tenancy()->initialize($tenant);

            $complianceSettings = \App\Models\Tenant\Setting::query()
                ->where('group', 'compliance')
                ->pluck('value', 'name')
                ->all();

            foreach (['compliance_doc_additional_paths'] as $jsonField) {
                if (isset($complianceSettings[$jsonField])) {
                    $complianceSettings[$jsonField] = json_decode($complianceSettings[$jsonField], true) ?? [];
                }
            }

            $completionPercent = TenantNavigation::complianceCompletionPercent();

            tenancy()->end();
        }

        return array_merge(parent::pageData(), [
            'tenant' => $tenant,
            'complianceSettings' => $complianceSettings,
            'completionPercent' => $completionPercent,
        ]);
    }

    public function markVerified(): void
    {
        $this->authorizePermission('compliance.tenants.manage');
        $this->updateStatus('verified');
    }

    public function markNeedsAction(): void
    {
        $this->authorizePermission('compliance.tenants.manage');
        $this->updateStatus('needs_action');
    }

    protected function updateStatus(string $status): void
    {
        $tenant = $this->tenant();

        if (!$tenant) {
            return;
        }

        $tenant->update([
            'compliance_status' => $status,
            'compliance_admin_note' => trim($this->note) ?: null,
            'compliance_reviewed_by' => auth('admin')->user()?->name,
            'compliance_reviewed_at' => now(),
        ]);

        $this->toast('Compliance status updated to "' . str_replace('_', ' ', $status) . '".');
    }

    protected function tenant(): ?Tenant
    {
        return Tenant::query()->find($this->tenantId);
    }
}
