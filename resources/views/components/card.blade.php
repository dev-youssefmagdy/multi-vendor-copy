@props(['title' => null, 'subtitle' => null, 'glow' => null, 'actions' => null])

<div {{ $attributes->merge(['class' => 'card ' . ($glow ? 'card-glow-' . $glow : '')]) }}>
    @if($title || $actions)
        <div class="panel-head">
            <div>
                @if($title)<h3 class="panel-title">{{ $title }}</h3>@endif
                @if($subtitle)<p class="panel-copy">{{ $subtitle }}</p>@endif
            </div>
            @if($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div>
        {{ $slot }}
    </div>
</div>
