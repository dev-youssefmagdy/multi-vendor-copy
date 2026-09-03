@php
use App\Helpers\AdminNavigation;

$routeInfo = AdminNavigation::routeInfo(request()->route()?->getName());
$adminUser = auth('admin')->user();
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

  <div class="tp" data-action="toggle-theme" title="Toggle theme">
    <div class="to ta" id="dOpt">Moon</div>
    <div class="to" id="lOpt">Sun</div>
  </div>


  <div class="av">
    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=admin&amp;backgroundColor=b6e3f4" class="avatar avatar-26" alt=""/>
    <div class="profile-meta xs-hide">
      <div class="profile-name">{{ $adminUser?->name ?? 'Admin' }}</div>
      <div class="profile-role">{{ $adminUser?->role?->name ?? 'Central Admin' }}</div>
    </div>
    <form method="POST" action="{{ route('admin.logout') }}" class="xs-hide">
      @csrf
      <button type="submit" class="btn btn-secondary btn-sm">Logout</button>
    </form>
  </div>
</header>
