@props([
    'name' => null,
    'label' => 'Click to upload or drag and drop',
    'sublabel' => 'PNG, JPG, PDF up to 10MB',
    'multiple' => true,
    'accept' => null,
    'maxFiles' => null,
    'maxKb' => null,
    'existing' => [],
    'removeName' => null,
    'orderName' => null,
    'sortable' => false,
    'expectedWidth' => null,
    'expectedHeight' => null,
    'help' => null,
    'id' => null,
    'wrapperClass' => '',
    'error' => null,
])

@php
    use App\Support\Tenant\FieldName;

    $dot = FieldName::dot($name);
    $htmlName = FieldName::html($name);
    $fieldId = $id ?? FieldName::id($dot);
    $removeFieldName = $removeName ?? $name.'_remove';
    $orderFieldName = $orderName ?? $name.'_order';
@endphp

<x-tenant::field :name="$name" :label="null" :help="$help" :id="$fieldId" :wrapper-class="$wrapperClass">
    <div class="t-dropzone" data-tenant-dropzone
        data-name="{{ $htmlName }}"
        @if($multiple) data-multiple="true" @endif
        @if($maxFiles) data-max-files="{{ $maxFiles }}" @endif
        @if($maxKb) data-max-kb="{{ $maxKb }}" @endif
        data-remove-name="{{ $removeFieldName }}"
        data-order-name="{{ $orderFieldName }}"
        @if($sortable) data-sortable="true" @endif
        @if($expectedWidth && $expectedHeight) data-expect-w="{{ $expectedWidth }}" data-expect-h="{{ $expectedHeight }}" @endif
    >
        <label class="t-dropzone-drop {{ $multiple ? 'is-multiple' : '' }}">
            <div class="t-dropzone-icon">
                <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>
            <div class="t-dropzone-text">
                <h4>{{ $label }}</h4>
                <p>{{ $sublabel }}</p>
            </div>
            <input type="file" class="t-dropzone-input" @if($multiple) multiple @endif @if($accept) accept="{{ $accept }}" @endif
                {{ $attributes->merge(['class' => $error ? 'is-invalid' : '']) }}>
        </label>

        <div class="t-dropzone-files" data-dropzone-files></div>

        @foreach($existing as $item)
            <input type="hidden" class="t-dropzone-existing" data-existing-id="{{ $item['id'] }}" data-existing-url="{{ $item['url'] }}" data-existing-name="{{ $item['name'] ?? '' }}" data-existing-type="{{ $item['type'] ?? 'image' }}">
        @endforeach

        <input type="hidden" name="{{ $orderFieldName }}" data-order-input value="{{ collect($existing)->pluck('id')->implode(',') }}">
    </div>
</x-tenant::field>
