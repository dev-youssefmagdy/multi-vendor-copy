<?php

namespace App\Livewire\TenantOwner\Auth;

use App\Models\Tenant;
use App\Models\TenantOwner;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SelectTenantPage extends Component
{
    public function mount(): void
    {
        if (!Auth::guard('tenant_owner')->check()) {
            $this->redirectRoute('owner.login');
            return;
        }

        if ($this->getOwner()->availableTenants()->count() <= 1) {
            $this->redirectRoute('owner.dashboard');
        }
    }

    public function getOwner(): TenantOwner
    {
        /** @var TenantOwner $owner */
        $owner = Auth::guard('tenant_owner')->user();
        return $owner;
    }

    public function selectTenant(string $tenantId): void
    {
        $owner = $this->getOwner();

        $tenant = $owner->availableTenants()->firstWhere('id', $tenantId);

        if (!$tenant) {
            abort(403);
        }

        $owner->forceFill(['selected_tenant_id' => $tenant->id])->save();

        $this->redirectRoute('owner.dashboard');
    }

    #[Layout('layouts.auth')]
    public function render(): \Illuminate\View\View
    {
        return view('livewire.owner.select-tenant', [
            'tenants' => $this->getOwner()->availableTenants()->load('domains'),
        ]);
    }
}
