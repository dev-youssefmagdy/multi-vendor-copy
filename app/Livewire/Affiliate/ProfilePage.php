<?php

namespace App\Livewire\Affiliate;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ProfilePage extends Component
{
    public string $name = '';
    public string $paypal_email = '';
    public string $bank_name = '';
    public string $bank_account = '';
    public string $bank_iban = '';

    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $affiliate = Auth::guard('affiliate')->user();

        $this->name = $affiliate->name;
        $this->paypal_email = (string) $affiliate->paypal_email;
        $this->bank_name = (string) $affiliate->bank_name;
        $this->bank_account = (string) $affiliate->bank_account;
        $this->bank_iban = (string) $affiliate->bank_iban;
    }

    public function updateProfile(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'paypal_email' => ['nullable', 'email'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'bank_iban' => ['nullable', 'string', 'max:100'],
        ]);

        Auth::guard('affiliate')->user()->update($validated);

        session()->flash('success', __('Profile updated successfully.'));
    }

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $affiliate = Auth::guard('affiliate')->user();

        if (!Hash::check($validated['current_password'], $affiliate->password)) {
            $this->addError('current_password', __('Current password is incorrect.'));
            return;
        }

        $affiliate->update(['password' => $validated['new_password']]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('success', __('Password updated successfully.'));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.affiliate.profile')
            ->layout('layouts.affiliate');
    }
}
