{{-- ── Category Strip V2 (violet) ─────────────────────────────────────────── --}}
@if ($categories->isNotEmpty())
<div class="py-4 sm:py-6" style="background:linear-gradient(89.62deg,#6B21A8 0%,#8A38F5 58%,#5B21B6 100%);" wire:ignore>
    <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6 flex gap-4 sm:gap-6 overflow-x-auto no-scrollbar">
        @foreach ($categories as $cat)
        <a href="{{ route('tenant.storefront.category', $cat->slug) }}"
           class="flex flex-col items-center gap-1.5 sm:gap-2 flex-shrink-0" style="min-width:70px">
            <div class="w-14 h-14 sm:w-20 sm:h-20 rounded-full flex items-center justify-center overflow-hidden"
                 style="background:rgba(255,255,255,.2)">
                @if ($cat->thumb_url ?? null)
                <img loading="lazy" src="{{ $cat->thumb_url }}" alt="{{ $cat->name }}" class="w-full h-full object-cover">
                @else
                <span class="text-2xl sm:text-3xl">🛍️</span>
                @endif
            </div>
            <span class="text-xs text-white text-center">{{ \Str::words($cat->translationValue('name') ?? $cat->name, 2) }}</span>
        </a>
        @endforeach
    </div>
</div>
@endif
