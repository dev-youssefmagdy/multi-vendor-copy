<?php

namespace App\Livewire\TenantOwner;

use App\Models\TenantOwner;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TenantSwitcher extends Component
{
    public function getOwner(): TenantOwner
    {
        /** @var TenantOwner $owner */
        $owner = Auth::guard('tenant_owner')->user();
        return $owner;
    }

    public function switchTenant(string $tenantId): void
    {
        $owner = $this->getOwner();

        $tenant = $owner->availableTenants()->firstWhere('id', $tenantId);

        if (!$tenant) {
            abort(403);
        }

        $owner->forceFill(['selected_tenant_id' => $tenant->id])->save();

        $this->redirectRoute('owner.dashboard');
    }

    public function render(): \Illuminate\View\View
    {
        $owner = $this->getOwner();

        return view('livewire.owner.tenant-switcher', [
            'tenants' => $owner->availableTenants(),
            'selectedTenantId' => $owner->selected_tenant_id,
        ]);
    }
}
