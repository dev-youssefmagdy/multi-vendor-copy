<main class="auth-page">
  <section class="auth-card card">
    <div class="auth-copy">
      <span class="page-badge">{{ __('Affiliate Portal') }}</span>
      <h1 class="D page-title auth-title">{{ __('Become an Affiliate') }}</h1>
      <p class="page-copy auth-description">{{ __('Apply to join our affiliate program and start earning commissions on referrals.') }}</p>
      <div class="auth-note">{{ __('Applications are reviewed before your account is activated.') }}</div>
    </div>

    <div class="auth-panel">
      @if (session('success'))
        <div class="auth-note" style="margin-bottom: 1rem;">{{ session('success') }}</div>
      @endif

      <form wire:submit="register" class="auth-form">
        <div>
          <label class="field-label">{{ __('Name') }}</label>
          <input type="text" wire:model.defer="name" class="field-control {{ $errors->has('name') ? 'is-invalid' : '' }}" autocomplete="name">
          @error('name')<div class="field-error">{{ $message }}</div>@enderror
        </div>

        <div>
          <label class="field-label">{{ __('Email') }}</label>
          <input type="email" wire:model.defer="email" class="field-control {{ $errors->has('email') ? 'is-invalid' : '' }}" autocomplete="email">
          @error('email')<div class="field-error">{{ $message }}</div>@enderror
        </div>

        <div>
          <label class="field-label">{{ __('Password') }}</label>
          <input type="password" wire:model.defer="password" class="field-control {{ $errors->has('password') ? 'is-invalid' : '' }}" autocomplete="new-password">
          @error('password')<div class="field-error">{{ $message }}</div>@enderror
        </div>

        <div>
          <label class="field-label">{{ __('Confirm Password') }}</label>
          <input type="password" wire:model.defer="password_confirmation" class="field-control" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary auth-submit">{{ __('Submit Application') }}</button>
      </form>

      <p class="auth-note" style="margin-top: 1rem; text-align: center;">
        {{ __('Already have an account?') }}
        <a href="{{ route('affiliate.login') }}">{{ __('Sign in') }}</a>
      </p>
    </div>
  </section>
</main>
