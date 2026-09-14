@props([
    'name' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'help' => null,
    'id' => null,
    'wrapperClass' => '',
    'error' => null,
    'height' => 400,
    'toolbar' => 'full',
    'placeholder' => null,
    'dir' => null,
])

@php
    use App\Support\Tenant\FieldName;

    $dot = FieldName::dot($name);
    $htmlName = FieldName::html($name);
    $fieldId = $id ?? FieldName::id($dot);
    $resolved = old($dot, $value);
@endphp

<x-tenant::field :name="$name" :label="$label" :help="$help" :required="$required" :id="$fieldId" :wrapper-class="$wrapperClass">
    <textarea
        name="{{ $htmlName }}"
        id="{{ $fieldId }}"
        data-tenant-editor
        data-height="{{ $height }}"
        data-toolbar="{{ $toolbar }}"
        @if($placeholder) data-placeholder="{{ $placeholder }}" @endif
        @if($dir) dir="{{ $dir }}" @endif
        class="{{ $error ? 'is-invalid' : '' }}"
        {{ $attributes }}
    >{{ $resolved }}</textarea>
</x-tenant::field>
