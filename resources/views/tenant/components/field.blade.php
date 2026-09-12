@props([
    'name' => null,
    'label' => null,
    'help' => null,
    'required' => false,
    'inline' => false,
    'id' => null,
    'wrapperClass' => '',
])

@php
    use App\Support\Tenant\FieldName;

    $dot = $name ? FieldName::dot($name) : null;
    $fieldId = $id ?? ($dot ? FieldName::id($dot) : null);
@endphp

<div class="t-field {{ $inline ? 't-field--inline' : '' }} {{ $wrapperClass }}" @if($dot) data-field="{{ $dot }}" @endif>
    @if($label)
        <label for="{{ $fieldId }}" class="field-label">
            {{ $label }}
            @if($required)<span class="t-req">*</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if($help)
        <p class="t-help">{{ $help }}</p>
    @endif

    @if($dot)
        <p class="field-error" data-error-for="{{ $dot }}" role="alert" hidden></p>
    @endif
</div>
