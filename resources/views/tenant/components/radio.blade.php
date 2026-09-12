@props([
    'name' => null,
    'value' => null,
    'checked' => false,
    'label' => null,
    'description' => null,
    'id' => null,
])

@php
    use App\Support\Tenant\FieldName;

    $htmlName = FieldName::html($name);
    $fieldId = $id ?? FieldName::id(FieldName::dot($name), (string) $value);
@endphp

<label class="toggle-field t-radio">
    <input type="radio" name="{{ $htmlName }}" id="{{ $fieldId }}" value="{{ $value }}" @if($checked) checked @endif {{ $attributes }}>
    <span>
        {{ $label ?? $slot }}
        @if($description)<span class="t-checkbox-desc">{{ $description }}</span>@endif
    </span>
</label>
