<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Models\Tenant\Customer;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class AuthPage extends Component
{
    use HasStorefrontLayout;

    public string $tab = 'login';   // 'login' | 'register'

    // Login
    public string $loginEmail = '';
    public string $loginPassword = '';

    // Register
    public string $regName = '';
    public string $regEmail = '';
    public string $regPhone = '';
    public string $regPassword = '';
    public string $regConfirm = '';

    public function mount(): void
    {
        if (Auth::guard('storefront')->check()) {
            $this->redirect(route('tenant.storefront.profile'));
        }
    }

    public function updatedTab(): void
    {
        // dispatch event to re-run intltel on on the frontend when switching tabs
        $this->dispatch('storefront-auth-tab-changed', ['tab' => $this->tab]);
    }

    public function login(): void
    {
        $data = $this->validate([
            'loginEmail' => 'required|email',
            'loginPassword' => 'required|min:6',
        ]);

        if (
            !Auth::guard('storefront')->attempt([
                'email' => $data['loginEmail'],
                'password' => $data['loginPassword'],
            ])
        ) {
            $this->addError('loginEmail', __('Invalid email or password.'));
            return;
        }

        /** @var \App\Models\Tenant\Customer $customer */
        $customer = Auth::guard('storefront')->user();

        if ($customer->language) {
            session([
                'storefront_language'        => $customer->language,
                'storefront_language_manual' => true,
            ]);
        }

        if (request()->has('redirect')) {
            $this->redirect(request()->query('redirect'));
            return;
        }

        $this->redirect(session()->pull('url.intended', route('tenant.storefront.profile')));
    }

    public function register(): void
    {
        $this->validate([
            'regName' => 'required|string|max:120',
            'regEmail' => 'required|email|unique:customers,email',
            'regPhone' => 'nullable|string|max:30',
            'regPassword' => 'required|min:8|same:regConfirm',
        ]);

        $customer = Customer::create([
            'full_name' => $this->regName,
            'email'     => $this->regEmail,
            'phone'     => $this->regPhone,
            'password'  => Hash::make($this->regPassword),
            'active'    => true,
            'language'  => session('storefront_language'),
        ]);

        Auth::guard('storefront')->login($customer);
        $this->redirect(route('tenant.storefront.profile'));
    }

    public function render()
    {
        $data = array_merge($this->sharedData(), [
            'tab' => $this->tab,
        ]);

        $storeName = app(StorefrontRepository::class)->storeName();

        return view($this->pageView('auth'), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Sign In') . " — {$storeName}" : __('Sign In'),
                'metaDescription' => '',
            ]);
    }
}
