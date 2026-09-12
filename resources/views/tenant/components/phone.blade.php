@props([
    'name' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'help' => null,
    'id' => null,
    'wrapperClass' => '',
    'error' => null,
    'initialCountry' => 'sa',
    'preferred' => [],
])

@php
    use App\Support\Tenant\FieldName;

    $dot = FieldName::dot($name);
    $htmlName = FieldName::html($name);
    $fieldId = $id ?? FieldName::id($dot);
    $resolved = old($dot, $value);
@endphp

<x-tenant::field :name="$name" :label="$label" :help="$help" :required="$required" :id="$fieldId" :wrapper-class="$wrapperClass">
    <div data-tenant-phone data-initial-country="{{ $initialCountry }}" data-preferred="{{ implode(',', $preferred) }}">
        <input type="tel" class="field-control t-phone-visible" autocomplete="tel">
        <input type="hidden" name="{{ $htmlName }}" id="{{ $fieldId }}" value="{{ $resolved }}" data-phone-e164
            class="{{ $error ? 'is-invalid' : '' }}">
    </div>
</x-tenant::field>
