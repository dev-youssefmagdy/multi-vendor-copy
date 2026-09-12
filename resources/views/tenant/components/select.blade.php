@props([
    'name' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'help' => null,
    'id' => null,
    'wrapperClass' => '',
    'inline' => false,
    'error' => null,
    'options' => [],
    'placeholder' => null,
    'multiple' => false,
])

@php
    use App\Support\Tenant\FieldName;
    use App\Support\Tenant\Options;

    $dot = $name ? FieldName::dot($name) : null;
    $htmlName = $name ? FieldName::html($name) : null;
    $fieldId = $id ?? ($dot ? FieldName::id($dot) : null);
    $resolved = old($dot, $value);
    $selected = $multiple ? (array) ($resolved ?? []) : $resolved;
    $normalized = Options::normalize($options);
@endphp

<x-tenant::field :name="$name" :label="$label" :help="$help" :required="$required" :inline="$inline" :id="$fieldId" :wrapper-class="$wrapperClass">
    <select
        name="{{ $multiple ? $htmlName.'[]' : $htmlName }}"
        id="{{ $fieldId }}"
        @if($multiple) multiple @endif
        @if($required) required @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'field-control '.($error ? 'is-invalid' : '')]) }}
    >
        @if($placeholder && !$multiple)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($normalized as $option)
            <option value="{{ $option['value'] }}"
                @if($multiple ? in_array((string) $option['value'], array_map('strval', $selected), true) : (string) $option['value'] === (string) $selected) selected @endif
                @if($option['disabled'] ?? false) disabled @endif
            >{{ str_repeat('— ', $option['level'] ?? 0) }}{{ $option['label'] }}</option>
        @endforeach
    </select>
</x-tenant::field>
