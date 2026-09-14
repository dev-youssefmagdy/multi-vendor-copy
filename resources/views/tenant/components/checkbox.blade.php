@props([
    'name' => null,
    'label' => null,
    'description' => null,
    'checked' => false,
    'value' => 1,
    'disabled' => false,
    'inGroup' => false,
    'id' => null,
    'wrapperClass' => '',
])

@php
    use App\Support\Tenant\FieldName;

    $dot = $name ? FieldName::dot($name) : null;
    $htmlName = $name ? FieldName::html($name) : null;
    $fieldId = $id ?? ($dot ? FieldName::id($dot) : null);
    $resolved = old($dot, $checked);
@endphp

<div class="t-checkbox-wrap {{ $wrapperClass }}" @if($dot && !$inGroup) data-field="{{ $dot }}" @endif>
    @if(!$inGroup && $name)
        <input type="hidden" name="{{ $htmlName }}" value="0">
    @endif
    <label class="toggle-field">
        <input type="checkbox"
            @if($name) name="{{ $htmlName }}" @endif
            id="{{ $fieldId }}"
            value="{{ $value }}"
            @if($resolved) checked @endif
            @if($disabled) disabled @endif
            {{ $attributes }}
        >
        <span>
            {{ $label ?? $slot }}
            @if($description)<span class="t-checkbox-desc">{{ $description }}</span>@endif
        </span>
    </label>
    @if($dot && !$inGroup)
        <p class="field-error" data-error-for="{{ $dot }}" role="alert" hidden></p>
    @endif
</div>
