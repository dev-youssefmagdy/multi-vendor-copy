@props([
    'name' => null,
    'label' => null,
    'required' => false,
    'help' => null,
    'id' => null,
    'wrapperClass' => '',
    'error' => null,
    'current' => null,
    'removable' => false,
    'expectedWidth' => null,
    'expectedHeight' => null,
    'dimensionLabel' => null,
    'aspect' => null,
    'maxKb' => null,
])

@php
    use App\Support\Tenant\FieldName;

    $dot = FieldName::dot($name);
    $htmlName = FieldName::html($name);
    $fieldId = $id ?? FieldName::id($dot);
@endphp

<x-tenant::field :name="$name" :label="$label" :help="$help" :required="$required" :id="$fieldId" :wrapper-class="$wrapperClass">
    <div class="t-image-upload" data-image-upload
        @if($expectedWidth && $expectedHeight) data-expect-w="{{ $expectedWidth }}" data-expect-h="{{ $expectedHeight }}" @endif
        @if($maxKb) data-max-kb="{{ $maxKb }}" @endif>
        @if($expectedWidth && $expectedHeight)
            <p class="dimension-hint">
                {{ __('Required size:dimensionLabel:', ['dimensionLabel' => $dimensionLabel ? " ($dimensionLabel)" : '']) }} <strong>{{ __(':expectedWidth × :expectedHeightpx', ['expectedWidth' => $expectedWidth, 'expectedHeight' => $expectedHeight]) }}</strong>
            </p>
        @endif
        <div class="t-image-upload-tile" @if($aspect) style="aspect-ratio: {{ $aspect }}" @endif>
            <img src="{{ $current }}" alt="" class="t-image-upload-preview" @unless($current) hidden @endunless>
            <label class="t-image-upload-label">
                <span>{{ $current ? __('Replace') : __('Upload image') }}</span>
                <input type="file" name="{{ $htmlName }}" id="{{ $fieldId }}" accept="image/*"
                    {{ $attributes->merge(['class' => $error ? 'is-invalid' : '']) }}>
            </label>
        </div>
        @if($removable && $current)
            <label class="t-file-remove">
                <input type="checkbox" name="remove_{{ $htmlName }}" value="1">
                <span>{{ __('Remove image') }}</span>
            </label>
        @endif
        <p class="dimension-warning" hidden></p>
    </div>
</x-tenant::field>
