@php
    $sections = \App\Helpers\TenantNavigation::visibleSections();
    $currentRoute = request()->route()?->getName();
    $childDotClasses = ['dot-cyan', 'dot-violet', 'dot-green', 'dot-amber'];
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
            <div class="brand-subtitle">VENDOR PANEL</div>
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
        @if (($item['type'] ?? 'link') === 'external')
            <a href="{{ \App\Helpers\TenantNavigation::href($item) }}" class="ni" target="_blank" rel="noopener noreferrer">
                @include('layouts.tenant.icon', ['name' => $item['icon']])
                {{ $item['label'] }}
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                    style="margin-left:auto;opacity:.5;flex-shrink:0;">
                    <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3" />
                </svg>
            </a>
        @elseif (($item['type'] ?? 'link') === 'link')
            <a href="{{ \App\Helpers\TenantNavigation::href($item) }}"
                class="ni {{ \App\Helpers\TenantNavigation::isActive($item, $currentRoute) ? 'act' : '' }}"
                data-action="set-active">
                @if (\App\Helpers\TenantNavigation::isActive($item, $currentRoute))
                    <div class="act-bar"></div>
                @endif
                @include('layouts.tenant.icon', ['name' => $item['icon']])
                {{ $item['label'] }}
                @if (($item['route'] ?? null) === 'tenant.onboarding')
                    @php($progress = \App\Helpers\TenantNavigation::onboardingSetupProgress())
                    <span class="ni-badge {{ $progress['done'] === $progress['total'] ? 'ni-badge-done' : '' }}">{{ $progress['done'] }}/{{ $progress['total'] }}</span>
                @endif
            </a>
        @else
        @php($groupOpen = \App\Helpers\TenantNavigation::groupIsActive($item, $currentRoute))
        <div class="ng">
            <button type="button" class="ng-trigger {{ $groupOpen ? 'op' : '' }}" data-action="toggle-group">
                @include('layouts.tenant.icon', ['name' => $item['icon']])
                {{ $item['label'] }}
                <svg class="ng-arrow" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>
            <div class="ng-kids {{ $groupOpen ? 'op' : '' }}">
                @foreach ($item['children'] as $child)
                    <a href="{{ \App\Helpers\TenantNavigation::href($child) }}"
                        class="nk {{ \App\Helpers\TenantNavigation::isActive($child, $currentRoute) ? 'nk-act' : '' }}"
                        data-action="set-sub-active">
                        <span class="dot {{ $childDotClasses[$loop->index % count($childDotClasses)] }}"></span>
                        {{ $child['label'] }}
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
                <img src="https://api.dicebear.com/7.x/shapes/svg?seed={{ urlencode((string) tenant('id')) }}&backgroundColor=b6e3f4"
                    class="avatar avatar-30" alt="" />
                <span class="status-indicator"></span>
            </div>
            <div class="user-meta">
                <div class="user-name">{{ tenant('name') ?? 'Tenant Owner' }}</div>
                <div class="user-role">Vendor Workspace</div>
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
