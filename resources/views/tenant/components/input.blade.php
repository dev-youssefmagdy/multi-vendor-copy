@props([
    'name' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'help' => null,
    'id' => null,
    'wrapperClass' => '',
    'inline' => false,
    'error' => null,
    'type' => 'text',
    'placeholder' => null,
    'prefix' => null,
    'suffix' => null,
    'icon' => null,
    'min' => null,
    'max' => null,
    'step' => null,
    'maxlength' => null,
    'autocomplete' => null,
    'toggle' => false,
    'slugFrom' => null,
    'counter' => false,
])

@php
    use App\Support\Tenant\FieldName;

    $dot = $name ? FieldName::dot($name) : null;
    $htmlName = $name ? FieldName::html($name) : null;
    $fieldId = $id ?? ($dot ? FieldName::id($dot) : null);
    $resolved = old($dot, $value);
@endphp

@if($type === 'hidden')
    <input type="hidden" name="{{ $htmlName }}" id="{{ $fieldId }}" value="{{ $resolved }}" {{ $attributes }}>
@else
    <x-tenant::field :name="$name" :label="$label" :help="$help" :required="$required" :inline="$inline" :id="$fieldId" :wrapper-class="$wrapperClass">
        <div class="t-input-wrap {{ $prefix ? 'has-prefix' : '' }} {{ $suffix || $toggle ? 'has-suffix' : '' }} {{ $icon ? 'has-icon' : '' }}">
            @if($icon)<span class="t-input-icon">{!! $icon !!}</span>@endif
            @if($prefix)<span class="t-input-prefix">{{ $prefix }}</span>@endif
            <input
                type="{{ $type === 'password' && $toggle ? 'password' : $type }}"
                name="{{ $htmlName }}"
                id="{{ $fieldId }}"
                value="{{ $resolved }}"
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                @if($required) required @endif
                @if($disabled) disabled @endif
                @if($readonly) readonly @endif
                @if($min !== null) min="{{ $min }}" @endif
                @if($max !== null) max="{{ $max }}" @endif
                @if($step !== null) step="{{ $step }}" @endif
                @if($maxlength) maxlength="{{ $maxlength }}" @endif
                @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                @if($slugFrom) data-slug-from="{{ $slugFrom }}" @endif
                @if($counter && $maxlength) data-char-counter @endif
                {{ $attributes->merge(['class' => 'field-control '.($error ? 'is-invalid' : '')]) }}
            >
            @if($toggle)
                <button type="button" class="t-input-toggle" data-password-toggle tabindex="-1" aria-label="Show password">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </button>
            @elseif($suffix)
                <span class="t-input-suffix">{{ $suffix }}</span>
            @endif
        </div>
        @if($counter && $maxlength)
            <p class="t-counter" data-counter-for="{{ $fieldId }}">0/{{ $maxlength }}</p>
        @endif
    </x-tenant::field>
@endif
