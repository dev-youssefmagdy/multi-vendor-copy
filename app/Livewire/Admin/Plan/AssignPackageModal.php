<?php

namespace App\Livewire\Admin\Plan;

use App\Enums\PaymentLogStatus;
use App\Models\Package;
use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Services\Mail\TemplateMailService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AssignPackageModal extends Component
{
    public string $tenantId = '';
    public bool $showModal = false;

    public ?int $assignPackageId = null;
    public string $assignGateway = 'manual';
    public ?string $assignAmount = null;
    public string $assignStatus = 'paid';
    public ?string $assignReference = null;
    public ?string $assignPaidAt = null;

    public function mount(string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function open(): void
    {
        $this->reset(['assignPackageId', 'assignAmount', 'assignReference', 'assignPaidAt']);
        $this->assignGateway = 'manual';
        $this->assignStatus  = 'paid';
        $this->showModal     = true;
    }

    public function close(): void
    {
        $this->showModal = false;
    }

    public function updatedAssignPackageId(?int $value): void
    {
        if ($value) {
            $package = Package::find($value);
            $this->assignAmount = $package ? (string) $package->price : null;
        }
    }

    public function save(): void
    {
        $this->validate([
            'assignPackageId' => ['required', 'integer', Rule::exists('packages', 'id')],
            'assignGateway'   => ['required', 'string', 'max:50'],
            'assignAmount'    => ['nullable', 'numeric', 'min:0'],
            'assignStatus'    => ['required', Rule::enum(PaymentLogStatus::class)],
            'assignReference' => ['nullable', 'string', 'max:255'],
            'assignPaidAt'    => ['nullable', 'date'],
        ]);

        $tenant  = Tenant::findOrFail($this->tenantId);
        $package = Package::findOrFail($this->assignPackageId);

        $tenant->update(['package_id' => $this->assignPackageId]);

        $log = PaymentLog::create([
            'tenant_id'  => $this->tenantId,
            'package_id' => $this->assignPackageId,
            'gateway'    => $this->assignGateway,
            'amount'     => $this->assignAmount ?? $package->price,
            'status'     => $this->assignStatus,
            'reference'  => $this->assignReference ?: null,
            'paid_at'    => $this->assignStatus === 'paid'
                ? ($this->assignPaidAt ? \Carbon\Carbon::parse($this->assignPaidAt) : now())
                : null,
        ]);

        $mailer = app(TemplateMailService::class);
        if ($this->assignStatus === 'paid') {
            $mailer->sendAdminSubscriptionActivated($tenant, $package, $log);
            $mailer->sendTenantSubscriptionActivated($tenant, $package, $log);
        } elseif ($this->assignStatus === 'failed') {
            $mailer->sendAdminSubscriptionCancelled($tenant, $package, $log);
        }

        $this->showModal = false;
        session()->flash('status', 'Package assigned and payment recorded.');
        $this->dispatch('package-assigned');
    }

    public function render()
    {
        return view('livewire.admin.plan.assign-package-modal', [
            'packages'       => Package::query()->with('translations.language')->get(),
            'currentPackage' => Tenant::find($this->tenantId)?->package,
        ]);
    }
}
