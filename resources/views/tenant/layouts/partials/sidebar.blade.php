@php
    $childDotClasses = ['dot-cyan', 'dot-violet', 'dot-green', 'dot-amber'];
@endphp

<aside id="sb">
    <div class="logo-row">
        <img src="{{ asset('tenant-panel/logo.svg') }}" alt="NOGRGR" class="sb-logo" width="152" height="40">
        <button type="button" class="close-sb" data-action="close-mobile" aria-label="Close sidebar">
            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>

    <div class="sb-scroll">
        @foreach($shell['sections'] as $section)
        <div class="sb-lbl">{{ $section['label'] }}</div>

        @foreach($section['items'] as $item)
        @if(($item['type'] ?? 'link') === 'external')
            <a href="{{ \App\Helpers\TenantNavigation::href($item) }}" class="ni" target="_blank" rel="noopener noreferrer" title="{{ $item['label'] }}">
                <x-tenant::nav-icon :item="$item" />
                <span class="ni-label">{{ $item['label'] }}</span>
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                    style="margin-left:auto;opacity:.5;flex-shrink:0;">
                    <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3" />
                </svg>
            </a>
        @elseif(($item['type'] ?? 'link') === 'link')
            <a href="{{ \App\Helpers\TenantNavigation::href($item) }}"
                class="ni {{ \App\Helpers\TenantNavigation::isActive($item, $shell['currentRoute']) ? 'act' : '' }}"
                data-action="set-active" title="{{ $item['label'] }}">
                <x-tenant::nav-icon :item="$item" />
                <span class="ni-label">{{ $item['label'] }}</span>
                @if(($item['route'] ?? null) === 'tenant.onboarding')
                    <span class="ni-badge {{ $shell['onboardingProgress']['done'] === $shell['onboardingProgress']['total'] ? 'ni-badge-done' : '' }}">{{ $shell['onboardingProgress']['done'] }}/{{ $shell['onboardingProgress']['total'] }}</span>
                @endif
                @if(($item['route'] ?? null) === 'tenant.support.index' && ($shell['supportUnread'] ?? 0) > 0)
                    <span class="ni-badge">{{ $shell['supportUnread'] }}</span>
                @endif
            </a>
        @else
        @php($groupOpen = \App\Helpers\TenantNavigation::groupIsActive($item, $shell['currentRoute']))
        <div class="ng">
            <button type="button" class="ng-trigger {{ $groupOpen ? 'op' : '' }}" data-action="toggle-group" title="{{ $item['label'] }}">
                <x-tenant::nav-icon :item="$item" />
                <span class="ni-label">{{ $item['label'] }}</span>
                <svg class="ng-arrow" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>
            <div class="ng-kids {{ $groupOpen ? 'op' : '' }}">
                @foreach($item['children'] as $child)
                    <a href="{{ \App\Helpers\TenantNavigation::href($child) }}"
                        class="nk {{ \App\Helpers\TenantNavigation::isActive($child, $shell['currentRoute']) ? 'nk-act' : '' }}"
                        data-action="set-sub-active">
                        <span class="dot {{ $childDotClasses[$loop->index % count($childDotClasses)] }}"></span>
                        <span class="ni-label">{{ $child['label'] }}</span>
                        @if(!empty($child['badge']))
                            <span class="ni-badge">{{ $child['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
        @endif
        @endforeach

        @if(!$loop->last)
            <div class="sb-sep"></div>
        @endif
        @endforeach
    </div>

    @auth('tenant')
        @include('tenant.layouts.partials.setup-progress')
    @endauth

    <div class="sb-user">
        <div class="su-inner">
            <x-tenant::avatar :name="$shell['tenantName'] ?? 'Tenant Owner'" :seed="$shell['tenantId']" size="48" />
            <div class="user-meta">
                <div class="user-name">{{ $shell['tenantName'] ?? 'Tenant Owner' }}</div>
                <div class="user-role">Vendor Workspace</div>
            </div>
            <button type="button" class="su-logout" aria-label="Logout" title="Logout"
                data-action-url="{{ route('tenant.logout') }}" data-action-method="POST" data-success="redirect">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 3.09A9.75 9.75 0 1 0 14 20.9"/>
                    <path d="M21 12H11m10 0c0-.7-2-2.01-2.5-2.5M21 12c0 .7-2 2.01-2.5 2.5"/>
                </svg>
            </button>
        </div>
    </div>
</aside>
