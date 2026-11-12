<?php

namespace App\Livewire\Tenant\Auth;

use App\Enums\TenantStatus;
use App\Enums\ActivationStatus;
use App\Models\Tenant\AdminUser;
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
        if (Auth::guard('tenant')->check()) {
            $this->redirectRoute('tenant.dashboard');
        }
    }

    public function login(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $tenant = tenant();
        /** @var AdminUser|null $admin */
        $admin = AdminUser::query()->with('role')->where('email', strtolower(trim((string) $validated['email'])))->first();

        if (!$tenant || !$admin || !Hash::check($validated['password'], (string) $admin->password)) {
            $this->addError('email', __('The provided tenant admin credentials are invalid for this domain.'));

            return;
        }

        if (!in_array($tenant->status, [TenantStatus::Active, TenantStatus::Onboarding], true)) {
            $this->addError('email', __('This tenant account is not allowed to access the vendor panel.'));

            return;
        }

        if ($admin->status !== ActivationStatus::Active) {
            $this->addError('email', __('This tenant admin account is inactive.'));

            return;
        }

        Auth::guard('tenant')->login($admin, $validated['remember']);
        $admin->forceFill(['last_login_at' => now()])->save();
        request()->session()->regenerate();

        $this->redirectIntended(route('tenant.dashboard'));
    }

    #[Layout('layouts.auth')]
    public function render()
    {
        $tenant = tenant();

        return view('livewire.auth.panel-login', [
            'panelTitle' => __(':store Vendor Panel', ['store' => $tenant?->data['shop_name'] ?? $tenant?->name ?? __('Tenant')]),
            'panelSubtitle' => __('Sign in to manage products, orders, storefront settings, and vendor operations for this tenant.'),
            'submitLabel' => __('Login to Vendor Panel'),
            'brandLabel' => __('Tenant Workspace'),
            'contextNote' => __('Use a tenant admin email and password created inside this tenant workspace.'),
            'showSocialLogin' => false,
            'googleLoginUrl' => null,
            'appleLoginUrl' => null,
        ]);
    }
}
