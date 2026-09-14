@props(['title' => null, 'subtitle' => null, 'glow' => null, 'padding' => null])

<div {{ $attributes->merge(['class' => 'card ' . ($glow ? 'card-glow-' . $glow : '') . ($padding === 'none' ? ' p-0' : ($padding === 'sm' ? ' p-sm' : ''))]) }}>
    @if($title || isset($actions))
        <div class="panel-head">
            <div>
                @if($title)<h3 class="panel-title">{{ $title }}</h3>@endif
                @if($subtitle)<p class="panel-copy">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div>{{ $slot }}</div>

    @isset($footer)
        <div class="t-card-footer">{{ $footer }}</div>
    @endisset
</div>
