@php
  use App\Helpers\AdminNavigation;

  $sections = AdminNavigation::visibleSections();
  $currentRoute = request()->route()?->getName();
  $childDotClasses = ['dot-cyan', 'dot-violet', 'dot-green', 'dot-amber'];
  $supportUnreadCount = auth('admin')->user()?->hasAnyPermission(['support.tickets.view', 'support.tickets.manage'])
      ? \App\Models\SupportTicket::where('admin_has_unread', true)->count()
      : 0;
  $pendingEditRequestsCount = auth('admin')->user()?->hasPermission('catalog.product-edit-requests.manage')
      ? \App\Models\ProductEditRequest::where('status', 'pending')->count()
      : 0;
  $productRequestsUnreadCount = auth('admin')->user()?->hasAnyPermission(['catalog.product-requests.view', 'catalog.product-requests.manage'])
      ? \App\Models\ProductRequest::where('admin_has_unread', true)->count()
      : 0;
  $pendingTenantChangeRequestsCount = auth('admin')->user()?->hasPermission('plans.tenant-change-requests.manage')
      ? \App\Models\TenantChangeRequest::where('status', 'pending')->count()
      : 0;
@endphp

<aside id="sb">
  <div class="logo-row">
    <div class="logo-icon">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="white" stroke-width="2.2"
          stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    </div>
    <div>
      <div class="brand">NEXUS</div>
      <div class="brand-subtitle">ADMIN PANEL</div>
    </div>
    <button type="button" class="close-sb" data-action="close-mobile" aria-label="Close sidebar">
      <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <line x1="18" y1="6" x2="6" y2="18" />
        <line x1="6" y1="6" x2="18" y2="18" />
      </svg>
    </button>
  </div>

  <div class="sb-scroll">
    @foreach ($sections as $section)
    <div class="sb-lbl">{{ $section['label'] }}</div>

    @foreach ($section['items'] as $item)
    @if (($item['type'] ?? 'link') === 'link')
      <a href="{{ AdminNavigation::href($item) }}"
        class="ni {{ AdminNavigation::isActive($item, $currentRoute) ? 'act' : '' }}" data-action="set-active">
        @if (AdminNavigation::isActive($item, $currentRoute))
          <div class="act-bar"></div>
        @endif
        @include('layouts.app.icon', ['name' => $item['icon']])
        {{ $item['label'] }}
        @if (($item['route'] ?? null) === 'admin.support.index' && ($supportUnreadCount ?? 0) > 0)
          <span class="ni-badge">{{ $supportUnreadCount }}</span>
        @endif
      </a>
    @else
    @php($groupOpen = AdminNavigation::groupIsActive($item, $currentRoute))
    <div class="ng">
      <button type="button" class="ng-trigger {{ $groupOpen ? 'op' : '' }}" data-action="toggle-group">
        @include('layouts.app.icon', ['name' => $item['icon']])
        {{ $item['label'] }}
        <svg class="ng-arrow" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"
          stroke-width="2.5">
          <polyline points="6 9 12 15 18 9" />
        </svg>
      </button>
      <div class="ng-kids {{ $groupOpen ? 'op' : '' }}">
        @foreach ($item['children'] as $child)
          <a href="{{ AdminNavigation::href($child) }}" class="nk {{ $currentRoute === $child['route'] ? 'nk-act' : '' }}"
            data-action="set-sub-active">
            <span class="dot {{ $childDotClasses[$loop->index % count($childDotClasses)] }}"></span>
            {{ $child['label'] }}
            @if (($child['route'] ?? null) === 'admin.products.edit-requests' && $pendingEditRequestsCount > 0)
              <span class="ni-badge">{{ $pendingEditRequestsCount }}</span>
            @endif
            @if (($child['route'] ?? null) === 'admin.product-requests.index' && $productRequestsUnreadCount > 0)
              <span class="ni-badge">{{ $productRequestsUnreadCount }}</span>
            @endif
            @if (($child['route'] ?? null) === 'admin.plans.tenant-change-requests' && $pendingTenantChangeRequestsCount > 0)
              <span class="ni-badge">{{ $pendingTenantChangeRequestsCount }}</span>
            @endif
          </a>
        @endforeach
      </div>
    </div>
    @endif
    @endforeach

    @if (!$loop->last)
      <div class="sb-sep"></div>
    @endif
    @endforeach
  </div>

  <div class="sb-user">
    <div class="su-inner">
      <div class="user-avatar-wrap">
        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=admin&amp;backgroundColor=b6e3f4"
          class="avatar avatar-30" alt="" />
        <span class="status-indicator"></span>
      </div>
      <div class="user-meta">
        <div class="user-name">{{ auth()->guard('admin')->user()?->name ?? 'Admin' }}</div>
        <div class="user-role">{{ auth()->guard('admin')->user()?->role?->name ?? 'Super Admin' }}</div>
      </div>
      <svg class="icon-t3 shrink-0" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"
        stroke-width="2">
        <circle cx="12" cy="12" r="1" />
        <circle cx="19" cy="12" r="1" />
        <circle cx="5" cy="12" r="1" />
      </svg>
    </div>
  </div>
</aside>