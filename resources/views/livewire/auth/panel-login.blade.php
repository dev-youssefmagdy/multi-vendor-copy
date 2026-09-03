<main class="auth-page">
  <section class="auth-card card">
    <div class="auth-copy">
      <span class="page-badge">{{ $brandLabel }}</span>
      <h1 class="D page-title auth-title">{{ $panelTitle }}</h1>
      <p class="page-copy auth-description">{{ $panelSubtitle }}</p>
      <div class="auth-note">{{ $contextNote }}</div>
    </div>

    <form wire:submit="login" class="auth-form">
      <div>
        <label class="field-label">Email</label>
        <input type="email" wire:model.defer="email" class="field-control {{ $errors->has('email') ? 'is-invalid' : '' }}" autocomplete="email">
        @error('email')<div class="field-error">{{ $message }}</div>@enderror
      </div>

      <div>
        <label class="field-label">Password</label>
        <input type="password" wire:model.defer="password" class="field-control {{ $errors->has('password') ? 'is-invalid' : '' }}" autocomplete="current-password">
        @error('password')<div class="field-error">{{ $message }}</div>@enderror
      </div>

      <label class="toggle-field">
        <input type="checkbox" wire:model.defer="remember">
        <span>Keep me signed in on this device</span>
      </label>

      <button type="submit" class="btn btn-primary auth-submit">{{ $submitLabel }}</button>
    </form>
  </section>
</main>
