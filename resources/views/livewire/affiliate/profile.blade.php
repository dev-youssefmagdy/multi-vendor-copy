<main>
    <div class="page-head">
        <div>
            <h1 class="D page-title">{{ __('Profile') }}</h1>
            <p class="page-copy">{{ __('Manage your account and payout details.') }}</p>
        </div>
    </div>

    @if (session('success'))
        <div class="auth-note section-gap">{{ session('success') }}</div>
    @endif

    <div class="g-r2 section-gap">
        <div class="card">
            <h3 class="D section-title">{{ __('Account & Payout Details') }}</h3>
            <p class="section-copy">{{ __('Used to identify you and send commission payouts.') }}</p>

            <form wire:submit="updateProfile" class="auth-form" style="margin-top:16px;">
                <div>
                    <label class="field-label">{{ __('Name') }}</label>
                    <input type="text" wire:model.defer="name" class="field-control {{ $errors->has('name') ? 'is-invalid' : '' }}">
                    @error('name')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="field-label">{{ __('PayPal Email') }}</label>
                    <input type="email" wire:model.defer="paypal_email" class="field-control {{ $errors->has('paypal_email') ? 'is-invalid' : '' }}">
                    @error('paypal_email')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="field-label">{{ __('Bank Name') }}</label>
                    <input type="text" wire:model.defer="bank_name" class="field-control">
                </div>

                <div>
                    <label class="field-label">{{ __('Bank Account') }}</label>
                    <input type="text" wire:model.defer="bank_account" class="field-control">
                </div>

                <div>
                    <label class="field-label">{{ __('IBAN') }}</label>
                    <input type="text" wire:model.defer="bank_iban" class="field-control">
                </div>

                <button type="submit" class="btn btn-primary auth-submit">{{ __('Save Changes') }}</button>
            </form>
        </div>

        <div class="card">
            <h3 class="D section-title">{{ __('Change Password') }}</h3>
            <p class="section-copy">{{ __('Update the password used to sign in.') }}</p>

            <form wire:submit="updatePassword" class="auth-form" style="margin-top:16px;">
                <div>
                    <label class="field-label">{{ __('Current Password') }}</label>
                    <input type="password" wire:model.defer="current_password" class="field-control {{ $errors->has('current_password') ? 'is-invalid' : '' }}">
                    @error('current_password')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="field-label">{{ __('New Password') }}</label>
                    <input type="password" wire:model.defer="new_password" class="field-control {{ $errors->has('new_password') ? 'is-invalid' : '' }}">
                    @error('new_password')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="field-label">{{ __('Confirm New Password') }}</label>
                    <input type="password" wire:model.defer="new_password_confirmation" class="field-control">
                </div>

                <button type="submit" class="btn btn-primary auth-submit">{{ __('Update Password') }}</button>
            </form>
        </div>
    </div>
</main>
