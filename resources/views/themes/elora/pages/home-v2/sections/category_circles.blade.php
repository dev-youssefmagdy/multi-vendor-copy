{{-- ── Category Circles V2 ─────────────────────────────────────────────────── --}}
@if ($categories->isNotEmpty())
<section class="py-4 bg-white border-t border-gray-100 sm:hidden" wire:ignore>
    <div class="max-w-screen-xl mx-auto px-3">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-bold" style="color:#1B1B1B;font-family:'Outfit',sans-serif;">{{ __('Categories') }}</h2>
            <a href="{{ route('tenant.storefront.category') }}"
               class="text-sm font-semibold" style="color:#8A38F5;">{{ __('see all') }}</a>
        </div>
        <div class="overflow-x-auto no-scrollbar -mx-3 px-3">
            <div style="display:grid;grid-template-rows:repeat(2,auto);grid-auto-flow:column;grid-auto-columns:max-content;gap:12px 10px;">
                @foreach ($categories as $cat)
                <a href="{{ route('tenant.storefront.category', $cat->slug) }}"
                   class="flex flex-col items-center gap-1.5 group" style="width:72px">
                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center transition-all overflow-hidden m-1"
                         style="border:2px solid transparent;"
                         onmouseover="this.style.borderColor='#8A38F5'" onmouseout="this.style.borderColor='transparent'">
                        @if ($cat->thumb_url ?? null)
                            <img loading="lazy" src="{{ $cat->thumb_url }}" alt="{{ $cat->name }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-2xl">🛍️</span>
                        @endif
                    </div>
                    <span class="text-[11px] text-center text-gray-800 leading-tight line-clamp-2">{{ $cat->translationValue('name') ?? $cat->name }}</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

@if ($categories->isNotEmpty())
<section class="py-5 sm:py-8 bg-white border-t border-gray-100 hidden sm:block" wire:ignore>
    <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-xl font-bold" style="color:#1B1B1B;font-family:'Outfit',sans-serif;">{{ __('Categories') }}</h2>
            <a href="{{ route('tenant.storefront.category') }}"
               class="text-sm font-semibold" style="color:#8A38F5;">{{ __('See all') }}</a>
        </div>
        <div class="overflow-x-auto no-scrollbar -mx-3 px-3">
            <div style="display:grid;grid-template-rows:repeat(2,auto);grid-auto-flow:column;grid-auto-columns:85px;gap:24px 32px;">
                @foreach ($categories as $cat)
                <a href="{{ route('tenant.storefront.category', $cat->slug) }}"
                   class="flex flex-col items-center gap-0 group" style="width:85px">
                    <div class="w-[81px] h-[81px] rounded-full bg-gray-100 flex items-center justify-center transition-all overflow-hidden mb-[12px] m-1"
                         style="border:2px solid transparent;"
                         onmouseover="this.style.borderColor='#8A38F5'" onmouseout="this.style.borderColor='transparent'">
                        @if ($cat->thumb_url ?? null)
                        <img loading="lazy" src="{{ $cat->thumb_url }}" alt="{{ $cat->name }}" class="w-full h-full object-cover">
                        @else
                        <span class="text-2xl">🛍️</span>
                        @endif
                    </div>
                    <span class="text-[14px] text-center text-gray-800 leading-[18px] tracking-[0.5px] line-clamp-2"
                          style="width:85px;font-family:'Outfit',sans-serif;">{{ $cat->translationValue('name') ?? $cat->name }}</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
