@props([
    'name' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'help' => null,
    'id' => null,
    'wrapperClass' => '',
    'error' => null,
    'allowTransparent' => false,
    'swatches' => [],
])

@php
    use App\Support\Tenant\FieldName;

    $dot = FieldName::dot($name);
    $htmlName = FieldName::html($name);
    $fieldId = $id ?? FieldName::id($dot);
    $resolved = old($dot, $value) ?? '#000000';
@endphp

<x-tenant::field :name="$name" :label="$label" :help="$help" :required="$required" :id="$fieldId" :wrapper-class="$wrapperClass">
    <div class="t-color-field" data-tenant-color @if($allowTransparent) data-allow-transparent="true" @endif>
        <input type="color" class="t-color-swatch" value="{{ $resolved === 'transparent' ? '#ffffff' : $resolved }}" data-color-picker>
        <input type="text" name="{{ $htmlName }}" id="{{ $fieldId }}" value="{{ $resolved }}" data-color-text
            {{ $attributes->merge(['class' => 'field-control '.($error ? 'is-invalid' : '')]) }}>
        @if($allowTransparent)
            <label class="toggle-field t-color-transparent">
                <input type="checkbox" data-color-transparent @if($resolved === 'transparent') checked @endif>
                <span>Transparent</span>
            </label>
        @endif
        @if($swatches)
            <div class="t-color-swatches">
                @foreach($swatches as $swatch)
                    <button type="button" class="t-color-swatch-btn" data-swatch="{{ $swatch }}" style="--sw: {{ $swatch }}"></button>
                @endforeach
            </div>
        @endif
    </div>
</x-tenant::field>
