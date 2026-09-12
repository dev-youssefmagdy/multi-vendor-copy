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
    'allowClear' => null,
    'tags' => false,
    'ajaxUrl' => null,
    'minInput' => null,
    'selected' => [],
    'tree' => false,
    'template' => 'default',
    'maxSelection' => null,
    'closeOnSelect' => null,
    'dependsOn' => null,
])

@php
    use App\Support\Tenant\FieldName;
    use App\Support\Tenant\Options;

    $dot = $name ? FieldName::dot($name) : null;
    $htmlName = $name ? FieldName::html($name) : null;
    $fieldId = $id ?? ($dot ? FieldName::id($dot) : null);
    $resolved = old($dot, $value);
    $selectedValues = $multiple ? (array) ($resolved ?? []) : $resolved;
    $normalized = $ajaxUrl ? [] : Options::normalize($options);
    $clear = $allowClear ?? !$required;
    $minInputResolved = $minInput ?? ($ajaxUrl ? 2 : 0);
@endphp

<x-tenant::field :name="$name" :label="$label" :help="$help" :required="$required" :inline="$inline" :id="$fieldId" :wrapper-class="$wrapperClass">
    <select
        name="{{ $multiple ? $htmlName.'[]' : $htmlName }}"
        id="{{ $fieldId }}"
        data-tenant-select2
        @if($ajaxUrl) data-ajax-url="{{ $ajaxUrl }}" @endif
        @if($dependsOn) data-depends-on="{{ $dependsOn }}" @endif
        @if($tree) data-tree="true" @endif
        data-template="{{ $template }}"
        @if($multiple) multiple @endif
        @if($tags) data-tags="true" @endif
        @if($maxSelection) data-max-selection="{{ $maxSelection }}" @endif
        data-min-input="{{ $minInputResolved }}"
        data-allow-clear="{{ $clear ? '1' : '0' }}"
        @if($closeOnSelect !== null) data-close-on-select="{{ $closeOnSelect ? '1' : '0' }}" @endif
        @if($placeholder) data-placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'field-control '.($error ? 'is-invalid' : '')]) }}
    >
        @if($placeholder && !$multiple)
            <option value=""></option>
        @endif
        @if($ajaxUrl)
            @foreach((array) $selected as $optId => $optText)
                <option value="{{ $optId }}" selected>{{ $optText }}</option>
            @endforeach
        @else
            @foreach($normalized as $option)
                <option value="{{ $option['value'] }}"
                    data-level="{{ $option['level'] ?? 0 }}"
                    @if(!empty($option['image'])) data-image="{{ $option['image'] }}" @endif
                    @if($multiple ? in_array((string) $option['value'], array_map('strval', $selectedValues), true) : (string) $option['value'] === (string) $selectedValues) selected @endif
                    @if($option['disabled'] ?? false) disabled @endif
                >{{ $option['label'] }}</option>
            @endforeach
        @endif
    </select>
</x-tenant::field>
