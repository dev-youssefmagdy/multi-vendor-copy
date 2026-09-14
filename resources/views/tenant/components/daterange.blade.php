@props([
    'label' => null,
    'help' => null,
    'required' => false,
    'id' => null,
    'wrapperClass' => '',
    'startName' => null,
    'endName' => null,
    'startValue' => null,
    'endValue' => null,
    'enableTime' => false,
])

@php
    use App\Support\Tenant\FieldName;

    $startDot = FieldName::dot($startName);
    $endDot = FieldName::dot($endName);
    $fieldId = $id ?? FieldName::id($startDot);
    $startResolved = old($startDot, $startValue);
    $endResolved = old($endDot, $endValue);
@endphp

<div class="t-field {{ $wrapperClass }}">
    @if($label)
        <label for="{{ $fieldId }}" class="field-label">{{ $label }} @if($required)<span class="t-req">*</span>@endif</label>
    @endif

    <div data-tenant-daterange data-start-name="{{ FieldName::html($startName) }}" data-end-name="{{ FieldName::html($endName) }}"
        @if($enableTime) data-enable-time="true" @endif>
        <input type="text" id="{{ $fieldId }}" class="field-control" autocomplete="off"
            value="{{ $startResolved && $endResolved ? "$startResolved to $endResolved" : '' }}">
        <input type="hidden" name="{{ FieldName::html($startName) }}" data-field="{{ $startDot }}" value="{{ $startResolved }}">
        <input type="hidden" name="{{ FieldName::html($endName) }}" data-field="{{ $endDot }}" value="{{ $endResolved }}">
    </div>

    @if($help)<p class="t-help">{{ $help }}</p>@endif
    <p class="field-error" data-error-for="{{ $startDot }}" role="alert" hidden></p>
    <p class="field-error" data-error-for="{{ $endDot }}" role="alert" hidden></p>
</div>
