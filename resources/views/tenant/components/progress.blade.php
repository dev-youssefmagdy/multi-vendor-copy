@props(['value' => 0, 'max' => 100, 'color' => 'cyan', 'label' => null, 'showValue' => false])

@php
    $percent = $max > 0 ? min(100, max(0, ($value / $max) * 100)) : 0;
@endphp

<div class="t-progress">
    @if($label || $showValue)
        <div class="t-progress-head">
            @if($label)<span class="t-progress-label">{{ $label }}</span>@endif
            @if($showValue)<span class="t-progress-value">{{ round($percent) }}%</span>@endif
        </div>
    @endif
    <div class="t-progress-track">
        <div class="t-progress-bar t-progress-{{ $color }}" style="--p: {{ $percent }}%"></div>
    </div>
</div>
