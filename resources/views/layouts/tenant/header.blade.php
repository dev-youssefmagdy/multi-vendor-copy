@php
$routeInfo = \App\Helpers\TenantNavigation::routeInfo(request()->route()?->getName());
$tenantUser = auth('tenant')->user();
$tenantRole = $tenantUser?->role?->name ?? 'Tenant Admin';

$activeSub = \App\Models\Tenant\Subscription::query()
    ->whereIn('status', [\App\Enums\Tenant\SubscriptionStatus::Active->value, \App\Enums\Tenant\SubscriptionStatus::Trial->value])
    ->latest('end_date')
    ->first();

$planName = null;
$planExpiry = null;
$planExpired = false;

if ($activeSub) {
    $package = \App\Models\Package::find($activeSub->package_id);
    $planName = $package?->name ?? 'Plan';
    $planExpiry = $activeSub->end_date;
    $planExpired = $planExpiry && $planExpiry->isPast();
}
@endphp

<header id="nav">
  <button type="button" class="ham" data-action="handle-ham" aria-label="Toggle sidebar">
    <svg class="icon-t2" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
      <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>

  <div class="bc xs-hide">
    <span class="text-t3">{{ $routeInfo['section'] }}</span>
    @if ($routeInfo['group'])
      <svg class="icon-t3" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      <span class="text-t3">{{ $routeInfo['group'] }}</span>
    @endif
    <svg class="icon-t3" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
    <span class="text-strong">{{ $routeInfo['label'] }}</span>
  </div>

  <div class="nav-spacer"></div>

  <div class="date-pill xs-hide">
    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    <span id="dt"></span>
  </div>

  @if ($planName)
  <a href="{{ route('tenant.finance.wallet') }}" class="plan-pill xs-hide {{ $planExpired ? 'plan-pill-expired' : '' }}" title="{{ $planExpired ? 'Subscription expired' : 'View subscriptions' }}">
    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
    <span class="plan-pill-name">{{ $planName }}</span>
    @if ($planExpiry)
      <span class="plan-pill-sep">·</span>
      <span class="plan-pill-date">{{ $planExpired ? 'Expired ' : 'Exp ' }}{{ $planExpiry->format('M d, Y') }}</span>
    @endif
  </a>
  @endif

  <x-tenant-notification-bell />

  <div class="tp" data-action="toggle-theme" title="Toggle theme">
    <div class="to ta" id="dOpt">Moon</div>
    <div class="to" id="lOpt">Sun</div>
  </div>

  <div class="av">
    <img src="https://api.dicebear.com/7.x/shapes/svg?seed={{ urlencode((string) ($tenantUser?->email ?? tenant('id'))) }}&backgroundColor=b6e3f4" class="avatar avatar-26" alt=""/>
    <div class="profile-meta xs-hide">
      <div class="profile-name">{{ $tenantUser?->name ?? 'Tenant Admin' }}</div>
      <div class="profile-role">{{ $tenantRole }}</div>
    </div>
    <form method="POST" action="{{ route('tenant.logout') }}" class="xs-hide">
      @csrf
      <button type="submit" class="btn btn-secondary btn-sm">Logout</button>
    </form>
  </div>
</header>
