@props([
    'name' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'help' => null,
    'id' => null,
    'wrapperClass' => '',
    'error' => null,
])

@php
    use App\Support\Tenant\FieldName;

    $dot = FieldName::dot($name);
    $htmlName = FieldName::html($name);
    $fieldId = $id ?? FieldName::id($dot);
    $resolved = old($dot, $value);
@endphp

<x-tenant::field :name="$name" :label="$label" :help="$help" :required="$required" :id="$fieldId" :wrapper-class="$wrapperClass">
    <input type="text" name="{{ $htmlName }}" id="{{ $fieldId }}" value="{{ $resolved }}"
        data-tenant-flatpickr data-no-calendar="true" data-format="H:i" autocomplete="off"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'field-control '.($error ? 'is-invalid' : '')]) }}>
</x-tenant::field>
