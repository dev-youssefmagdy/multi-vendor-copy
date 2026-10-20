<?php

namespace App\Livewire\Affiliate\Auth;

use App\Models\Affiliate;
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
        if (Auth::guard('affiliate')->check()) {
            $this->redirectRoute('affiliate.dashboard');
        }
    }

    public function login(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $affiliate = Affiliate::query()
            ->where('email', strtolower(trim($validated['email'])))
            ->first();

        if (!$affiliate || !Hash::check($validated['password'], $affiliate->password)) {
            $this->addError('email', __('Invalid email or password.'));
            return;
        }

        if (!$affiliate->isActive()) {
            $this->addError('email', __('Your affiliate account is pending approval.'));
            return;
        }

        Auth::guard('affiliate')->login($affiliate, $validated['remember']);
        request()->session()->regenerate();

        $this->redirectRoute('affiliate.dashboard');
    }

    #[Layout('layouts.auth')]
    public function render(): \Illuminate\View\View
    {
        return view('livewire.auth.panel-login', [
            'panelTitle' => __('Affiliate Portal'),
            'panelSubtitle' => __('Sign in to track your referrals, conversions, and payouts.'),
            'submitLabel' => __('Sign In'),
            'brandLabel' => __('Affiliate Portal'),
            'contextNote' => __('Use the email and password you registered with.'),
            'showSocialLogin' => false,
            'registerUrl' => route('affiliate.register'),
        ]);
    }
}
