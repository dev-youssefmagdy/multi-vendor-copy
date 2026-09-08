@props([
        'label' => 'Click to upload or drag and drop',
        'sublabel' => 'PNG, JPG, PDF up to 10MB',
        'multiple' => true,
        'model' => null,
        'default' => null,
    'removeAction' => null,
    'expectedWidth' => null,
    'expectedHeight' => null,
    'dimensionLabel' => null,
    ])

<div class="w-full relative group" data-dropzone
    @if($model) data-model="{{ $model }}" @endif
    @if($removeAction) data-remove-action="{{ $removeAction }}" @endif
    @if($expectedWidth && $expectedHeight) data-expect-w="{{ $expectedWidth }}" data-expect-h="{{ $expectedHeight }}" @endif>

    @if($expectedWidth && $expectedHeight)
        <p class="dimension-hint">
            Required size{{ $dimensionLabel ? " ($dimensionLabel)" : '' }}: <strong>{{ $expectedWidth }} × {{ $expectedHeight }}px</strong>
        </p>
    @endif
    <label class="relative flex flex-col items-center justify-center w-full {{ $multiple ? 'py-10' : 'py-4' }} px-4 border-2 border-dashed border-(--border2) rounded-xl bg-(--surface) hover:border-(--cyan) transition-all duration-300 cursor-pointer focus-within:ring-2 focus-within:ring-(--cyan) focus-within:ring-offset-2 overflow-hidden">

        <div class="absolute inset-0 bg-(--elevated) opacity-0 group-hover:opacity-40 transition-opacity duration-300"></div>

        @if($default)
            <div class="relative {{ $multiple ? 'w-12 h-12 mb-4' : 'w-8 h-8 mb-2' }} rounded-full bg-(--surface) border border-(--border2) flex items-center justify-center text-(--cyan) shadow-sm group-hover:scale-110 transition-transform duration-300 z-10">
                <img src="{{ $default }}" alt="Default Image" class="max-h-full max-w-full object-contain rounded-md shadow-sm">
            </div>
        @else
        <div class="relative {{ $multiple ? 'w-12 h-12 mb-4' : 'w-8 h-8 mb-2' }} rounded-full bg-(--surface) border border-(--border2) flex items-center justify-center text-(--cyan) shadow-sm group-hover:scale-110 transition-transform duration-300 z-10">
            <svg class="{{ $multiple ? 'w-6 h-6' : 'w-4 h-4' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
        </div>
        @endif

        <div class="relative text-center z-10">
            <h4 class="{{ $multiple ? 'text-[14px]' : 'text-[12px]' }} font-semibold text-(--t1) mb-1">{{ $label }}</h4>
            <p class="{{ $multiple ? 'text-[12px]' : 'text-[11px]' }} text-(--t3)">{{ $sublabel }}</p>
        </div>

        <input type="file" @if($multiple) multiple @endif {{ $attributes }} class="dropzone-input sr-only" wire:model="{{ $model }}">
    </label>

    <div class="dropzone-files mt-4 space-y-2 empty:hidden" data-dropzone-files></div>
</div>
