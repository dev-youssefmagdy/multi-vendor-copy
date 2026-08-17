<main class="auth-page">
  <section class="auth-card card">
    <div class="auth-copy">
      <span class="page-badge">{{ __('Tenant Portal') }}</span>
      <h1 class="D page-title auth-title">{{ __('Select a Store') }}</h1>
      <p class="page-copy auth-description">{{ __('Your account is linked to more than one store. Choose which one you want to manage.') }}</p>
    </div>

    <div class="auth-form" style="display:flex;flex-direction:column;gap:10px;">
      @foreach($tenants as $tenant)
        <button type="button" wire:click="selectTenant('{{ $tenant->id }}')" wire:loading.attr="disabled"
                class="btn btn-secondary" style="display:flex;align-items:center;justify-content:space-between;width:100%;text-align:left;padding:12px 16px;">
          <span>
            <span style="display:block;font-weight:600;">{{ $tenant->name }}</span>
            <span style="display:block;font-size:12px;color:var(--t3);">{{ $tenant->domains->first()?->domain ?? $tenant->slug }}</span>
          </span>
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      @endforeach
    </div>

    <form method="POST" action="{{ route('owner.logout') }}" style="margin-top:14px;">
      @csrf
      <button type="submit" class="btn btn-secondary btn-sm">{{ __('Logout') }}</button>
    </form>
  </section>
</main>
