@props(['type' => 'info', 'title' => null, 'dismissible' => false])

<div class="t-alert t-alert-{{ $type }}" role="alert" data-tenant-alert>
    <div class="t-alert-body">
        @if($title)<p class="t-alert-title">{{ $title }}</p>@endif
        <div class="t-alert-copy">{{ $slot }}</div>
    </div>
    @if($dismissible)
        <button type="button" class="t-alert-dismiss" data-alert-dismiss aria-label="Dismiss">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    @endif
</div>
