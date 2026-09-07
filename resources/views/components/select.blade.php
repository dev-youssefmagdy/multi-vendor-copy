@props(['disabled' => false, 'placeholder' => 'Select an option', 'searchable' => false, 'tree' => false, 'error' => false])

<div class="relative w-full {{ $error ? 'is-invalid' : '' }}" data-enhanced-select data-placeholder="{{ $placeholder }}"
    @if($searchable) data-searchable="true" @endif @if($tree) data-tree="true" @endif>
    <select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' => 'enhanced-select-native w-full bg-(--input) border border-(--border) rounded-[8px] px-[11px] py-[7px] text-[13px] text-(--t1) outline-none focus:border-(--cyan) transition-colors appearance-none cursor-pointer'
]) !!}>
        {{ $slot }}
    </select>
    <button type="button" class="enhanced-select-trigger" aria-haspopup="listbox" aria-expanded="false" wire:ignore>
        <span class="enhanced-select-value">{{ $placeholder }}</span>
        <span class="enhanced-select-chevron" aria-hidden="true">
            <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </span>
    </button>
    <div class="enhanced-select-panel" hidden wire:ignore>
        @if($searchable)
            <div class="enhanced-select-search-wrap">
                <input type="text" class="enhanced-select-search" placeholder="Search…" autocomplete="off">
            </div>
        @endif
        <div class="enhanced-select-options"></div>
    </div>
</div>
