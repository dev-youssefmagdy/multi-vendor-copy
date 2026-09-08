<?php

namespace App\Livewire\Admin\Auth;

use App\Enums\ActivationStatus;
use App\Models\AdminUser;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
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
        if (Auth::guard('admin')->check()) {
            $this->redirectRoute('admin.dashboard');
        }
    }

    public function login(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        /** @var AdminUser|null $admin */
        $admin = AdminUser::query()->where('email', strtolower(trim((string) $validated['email'])))->first();

        if (!$admin || !Hash::check($validated['password'], (string) $admin->password)) {
            $this->addError('email', 'The provided credentials are invalid.');

            return;
        }

        if ($admin->status !== ActivationStatus::Active) {
            $this->addError('email', 'This admin account is currently inactive.');

            return;
        }

        if (!$admin instanceof AuthenticatableContract) {
            $this->addError('email', 'This admin account cannot be authenticated.');

            return;
        }

        Auth::guard('admin')->login($admin, $validated['remember']);
        $admin->forceFill(['last_login_at' => now()])->save();

        request()->session()->regenerate();

        $this->redirectIntended(route('admin.dashboard'));
    }

    #[Layout('layouts.auth')]
    public function render()
    {
        return view('livewire.auth.panel-login', [
            'panelTitle' => 'Central Admin',
            'panelSubtitle' => 'Sign in to manage the marketplace control plane, catalog governance, and platform operations.',
            'submitLabel' => 'Login to Central',
            'brandLabel' => 'Central Control',
            'contextNote' => 'Use your central admin email and password.',
            'showSocialLogin' => false,
            'googleLoginUrl' => null,
            'appleLoginUrl' => null,
        ]);
    }
}
