@props([
    'name' => null,
    'label' => null,
    'required' => false,
    'help' => null,
    'id' => null,
    'wrapperClass' => '',
    'error' => null,
    'accept' => null,
    'current' => null,
    'currentName' => null,
    'removable' => false,
    'preview' => false,
])

@php
    use App\Support\Tenant\FieldName;

    $dot = FieldName::dot($name);
    $htmlName = FieldName::html($name);
    $fieldId = $id ?? FieldName::id($dot);
@endphp

<x-tenant::field :name="$name" :label="$label" :help="$help" :required="$required" :id="$fieldId" :wrapper-class="$wrapperClass">
    <div class="t-file-field" data-dimension-check>
        @if($current)
            <div class="t-file-current">
                @if($preview)<img src="{{ $current }}" alt="" class="t-file-current-preview">@endif
                <a href="{{ $current }}" target="_blank" class="t-file-current-name">{{ $currentName ?? basename($current) }}</a>
                @if($removable)
                    <label class="t-file-remove">
                        <input type="checkbox" name="remove_{{ $htmlName }}" value="1">
                        <span>Remove</span>
                    </label>
                @endif
            </div>
        @endif
        <input type="file" name="{{ $htmlName }}" id="{{ $fieldId }}"
            @if($accept) accept="{{ $accept }}" @endif
            @if($required && !$current) required @endif
            {{ $attributes->merge(['class' => 'field-control '.($error ? 'is-invalid' : '')]) }}>
        <p class="dimension-warning" hidden></p>
    </div>
</x-tenant::field>
