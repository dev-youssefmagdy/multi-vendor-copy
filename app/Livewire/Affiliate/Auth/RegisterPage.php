<?php

namespace App\Livewire\Affiliate\Auth;

use App\Models\Affiliate;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class RegisterPage extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        if (Auth::guard('affiliate')->check()) {
            $this->redirectRoute('affiliate.dashboard');
        }
    }

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:affiliates,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Affiliate::query()->create([
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'password' => $validated['password'],
            'code' => Affiliate::generateUniqueCode(),
            'commission_type' => 'percentage',
            'commission_value' => 10.00,
            'status' => 'pending',
        ]);

        session()->flash('success', __('Your application has been submitted. We will notify you once approved.'));
        $this->redirectRoute('affiliate.login');
    }

    #[Layout('layouts.auth')]
    public function render(): \Illuminate\View\View
    {
        return view('livewire.affiliate.auth.register');
    }
}
