@props([
    'tabs' => [],
    'active' => null,
    'mode' => 'local',
])

@php
    $keys = array_keys($tabs);
    $activeKey = $active ?? $keys[0] ?? null;
@endphp

<div class="t-tabs" data-tenant-tabs data-mode="{{ $mode }}">
    <div class="t-tabs-header" role="tablist">
        @foreach($tabs as $key => $def)
            @php
                $label = is_array($def) ? ($def['label'] ?? $key) : $def;
                $badge = is_array($def) ? ($def['badge'] ?? null) : null;
                $icon = is_array($def) ? ($def['icon'] ?? null) : null;
                $href = is_array($def) ? ($def['href'] ?? null) : null;
            @endphp
            @if($mode === 'link' && $href)
                <a href="{{ $href }}" class="t-tab {{ $key === $activeKey ? 'is-active' : '' }}" data-tab-key="{{ $key }}">
                    @if($icon){!! $icon !!}@endif {{ $label }}
                    @if($badge)<span class="t-tab-badge">{{ $badge }}</span>@endif
                </a>
            @else
                <button type="button" class="t-tab {{ $key === $activeKey ? 'is-active' : '' }}" role="tab" data-tab-key="{{ $key }}">
                    @if($icon){!! $icon !!}@endif {{ $label }}
                    @if($badge)<span class="t-tab-badge">{{ $badge }}</span>@endif
                </button>
            @endif
        @endforeach
    </div>

    <div class="t-tabs-body">
        {{ $slot }}
    </div>
</div>
