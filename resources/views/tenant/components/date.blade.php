@props([
    'name' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'help' => null,
    'id' => null,
    'wrapperClass' => '',
    'error' => null,
    'min' => null,
    'max' => null,
    'enableTime' => false,
    'format' => null,
    'altFormat' => 'M d, Y',
])

@php
    use App\Support\Tenant\FieldName;

    $dot = FieldName::dot($name);
    $htmlName = FieldName::html($name);
    $fieldId = $id ?? FieldName::id($dot);
    $resolved = old($dot, $value);
    $resolvedFormat = $format ?? ($enableTime ? 'Y-m-d H:i' : 'Y-m-d');
@endphp

<x-tenant::field :name="$name" :label="$label" :help="$help" :required="$required" :id="$fieldId" :wrapper-class="$wrapperClass">
    <input type="text" name="{{ $htmlName }}" id="{{ $fieldId }}" value="{{ $resolved }}"
        data-tenant-flatpickr
        data-format="{{ $resolvedFormat }}"
        data-alt-format="{{ $altFormat }}"
        @if($enableTime) data-enable-time="true" @endif
        @if($min) data-min="{{ $min }}" @endif
        @if($max) data-max="{{ $max }}" @endif
        @if($required) required @endif
        @if($disabled) disabled @endif
        autocomplete="off"
        {{ $attributes->merge(['class' => 'field-control '.($error ? 'is-invalid' : '')]) }}>
</x-tenant::field>
