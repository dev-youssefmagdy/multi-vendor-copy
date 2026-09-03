<?php

namespace App\Livewire\TenantOwner;

use App\Models\TenantOwner;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SubscriptionsList extends Component
{
    public function getOwner(): TenantOwner
    {
        /** @var TenantOwner $owner */
        $owner = Auth::guard('tenant_owner')->user();
        return $owner;
    }

    #[Layout('layouts.owner')]
    public function render(): \Illuminate\View\View
    {
        $tenant = $this->getOwner()->selectedTenant()->with('package', 'domains')->first();

        tenancy()->initialize($tenant);
        $subscriptions = \App\Models\Tenant\Subscription::query()
            ->with('transaction')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.owner.subscriptions-list', [
            'tenant' => $tenant,
            'subscriptions' => $subscriptions,
        ]);
    }
}
