@props([
    'text' => '',
    'label' => 'More information',
    'placement' => 'top',
])

{{--
    Info-icon tooltip: <x-tenant::tooltip text="Explains the value" />.
    Opens on hover, keyboard focus, and tap (the trigger is a focusable button).
    Pass a slot to replace the default info icon.
--}}

@php $tipId = 'tip-'.\Illuminate\Support\Str::random(8); @endphp

<span {{ $attributes->merge(['class' => 't-tooltip t-tooltip-'.$placement]) }}>
    <button type="button" class="t-tooltip-trigger" aria-label="{{ $label }}" aria-describedby="{{ $tipId }}">
        @if($slot->isNotEmpty())
            {{ $slot }}
        @else
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9.75"/><path d="M12 16v-4.5M12 8.01V8" stroke-linecap="round"/></svg>
        @endif
    </button>
    <span class="t-tooltip-bubble" id="{{ $tipId }}" role="tooltip">{{ $text }}</span>
</span>
