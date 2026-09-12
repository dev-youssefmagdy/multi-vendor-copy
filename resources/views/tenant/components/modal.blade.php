@props([
    'id' => null,
    'title' => null,
    'size' => 'md',
    'static' => false,
    'description' => null,
])

@php
    $maxWidthClass = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        'full' => 'sm:max-w-full',
    ][$size] ?? 'sm:max-w-md';
@endphp

<div id="{{ $id }}" class="modal-shell t-modal fixed inset-0 z-50 overflow-y-auto" data-tenant-modal
    @if($static) data-static="1" @endif
    role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title"
    hidden>
    <div class="modal-backdrop fixed inset-0 bg-black/60 backdrop-blur-[3px]"></div>

    <div class="flex min-h-full items-center justify-center px-4 py-6 sm:px-0">
        <div class="card w-full {{ $maxWidthClass }} relative z-10 p-0 modal-card">
            <div class="p-5 border-b flex justify-between items-center modal-header-shell">
                <div>
                    <h3 class="panel-title modal-title" id="{{ $id }}-title">{{ $title }}</h3>
                    @if($description)<p class="panel-copy">{{ $description }}</p>@endif
                </div>
                <div class="flex items-center gap-2">
                    @isset($headerActions){{ $headerActions }}@endisset
                    <button type="button" data-modal-close class="modal-close-btn transition-colors" aria-label="Close">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-5 modal-body-shell">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="p-5 border-t modal-footer-shell">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>
