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
    'rows' => 4,
    'maxlength' => null,
    'counter' => false,
    'autosize' => false,
    'placeholder' => null,
])

@php
    use App\Support\Tenant\FieldName;

    $dot = $name ? FieldName::dot($name) : null;
    $htmlName = $name ? FieldName::html($name) : null;
    $fieldId = $id ?? ($dot ? FieldName::id($dot) : null);
    $resolved = old($dot, $value);
@endphp

<x-tenant::field :name="$name" :label="$label" :help="$help" :required="$required" :inline="$inline" :id="$fieldId" :wrapper-class="$wrapperClass">
    <textarea
        name="{{ $htmlName }}"
        id="{{ $fieldId }}"
        rows="{{ $rows }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        @if($maxlength) maxlength="{{ $maxlength }}" @endif
        @if($autosize) data-autosize @endif
        @if($counter && $maxlength) data-char-counter @endif
        {{ $attributes->merge(['class' => 'field-control '.($error ? 'is-invalid' : '')]) }}
    >{{ $resolved }}</textarea>
    @if($counter && $maxlength)
        <p class="t-counter" data-counter-for="{{ $fieldId }}">0/{{ $maxlength }}</p>
    @endif
</x-tenant::field>
