<?php

namespace App\Livewire\TenantOwner\Auth;

use App\Models\TenantOwner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

class LoginPage extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function mount(): void
    {
        if (Auth::guard('tenant_owner')->check()) {
            $owner = Auth::guard('tenant_owner')->user();
            $this->redirectRoute($owner->selected_tenant_id ? 'owner.dashboard' : 'owner.select-tenant');
        }
    }

    public function login(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $owner = TenantOwner::query()
            ->where('email', strtolower(trim($validated['email'])))
            ->first();

        if (!$owner || !Hash::check($validated['password'], $owner->password)) {
            $this->addError('email', __('Invalid email or password.'));
            return;
        }

        Auth::guard('tenant_owner')->login($owner, $validated['remember']);
        request()->session()->regenerate();

        $tenants = $owner->availableTenants();

        if ($tenants->count() <= 1) {
            $tenant = $tenants->first();

            if ($tenant && $owner->selected_tenant_id !== $tenant->id) {
                $owner->forceFill(['selected_tenant_id' => $tenant->id])->save();
            }

            $this->redirectRoute('owner.dashboard');
            return;
        }

        $this->redirectRoute('owner.select-tenant');
    }

    #[Layout('layouts.auth')]
    public function render(): \Illuminate\View\View
    {
        return view('livewire.auth.panel-login', [
            'panelTitle' => __('My Account'),
            'panelSubtitle' => __('Sign in to manage your stores, domains, and subscriptions.'),
            'submitLabel' => __('Sign In'),
            'brandLabel' => __('Tenant Portal'),
            'contextNote' => __('Use the email and password you registered with.'),
        ]);
    }
}
