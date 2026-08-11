@props(['name', 'title' => 'Modal', 'maxWidth' => 'md', 'open' => false, 'closeAction' => null])

@php
    $maxWidthClass = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        '4xl' => 'sm:max-w-4xl',
        '5xl' => 'sm:max-w-5xl',
    ][$maxWidth] ?? 'sm:max-w-md';
@endphp

@php
    $wireModel = $attributes->wire('model');
    $showExpression = $wireModel->value();
    $wrapperAttributes = $attributes->exceptProps(['wire:model', 'wire:model.live', 'wire:model.defer']);
@endphp

<div @if ($showExpression) wire:show="{{ $showExpression }}" x-cloak @elseif ($open) data-modal-open="true" @endif
    class="modal-shell fixed inset-0 z-50 overflow-y-auto" @if (!$showExpression && !$open) hidden @endif>
    <div class="modal-backdrop fixed inset-0 bg-black/60 backdrop-blur-[3px]" @if ($closeAction)
    wire:click="{{ $closeAction }}" @endif></div>

    <div class="flex min-h-full items-center justify-center px-4 py-6 sm:px-0">
    <div {{ $wrapperAttributes->merge(['class' => "card w-full {$maxWidthClass} relative z-10 p-0 modal-card"]) }}>
        <div class="p-5 border-b flex justify-between items-center modal-header-shell">
            <h3 class="panel-title modal-title">{{ $title }}</h3>
            <button type="button" @if ($closeAction) wire:click="{{ $closeAction }}" @endif
                class="modal-close-btn transition-colors">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <div class="p-5 modal-body-shell">
            {{ $slot }}
        </div>
    </div>
    </div>
</div>