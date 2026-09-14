@props(['align' => 'end', 'label' => null, 'icon' => null])

<div class="t-dropdown" data-tenant-dropdown data-align="{{ $align }}">
    <button type="button" class="t-dropdown-trigger" data-dropdown-trigger aria-haspopup="true" aria-expanded="false">
        @if($icon)
            {!! $icon !!}
        @else
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="5" r="1.3"/><circle cx="12" cy="12" r="1.3"/><circle cx="12" cy="19" r="1.3"/></svg>
        @endif
        @if($label)<span>{{ $label }}</span>@endif
    </button>
    <div class="t-dropdown-menu" data-dropdown-menu role="menu" hidden>
        {{ $slot }}
    </div>
</div>
