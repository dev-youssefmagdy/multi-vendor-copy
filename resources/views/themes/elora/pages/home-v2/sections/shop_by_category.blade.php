{{-- ── Shop by Category V2 (violet gradient) ──────────────────────────────── --}}
@if ($categories->isNotEmpty())
<section wire:ignore class="elora-cats-section flex flex-col items-center justify-center"
    style="background:linear-gradient(89.62deg,#6B21A8 0.07%,#8A38F5 57.6%,#5B21B6 100%);">
    <div class="flex flex-row items-center justify-center" style="gap:16px; padding-top:32px; padding-bottom:16px;">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
            <path d="M16 10.667H27.333C27.864 10.667 28.373 10.878 28.748 11.253C29.123 11.628 29.333 12.137 29.333 12.667C29.333 13.197 29.123 13.706 28.748 14.081C28.373 14.456 27.864 14.667 27.333 14.667H17.333M18 14.667H20.667C21.197 14.667 21.706 14.878 22.081 15.253C22.456 15.628 22.667 16.137 22.667 16.667C22.667 17.197 22.456 17.706 22.081 18.081C21.706 18.456 21.197 18.667 20.667 18.667H17.333M19.333 18.667C19.864 18.667 20.373 18.878 20.748 19.253C21.123 19.628 21.333 20.137 21.333 20.667C21.333 21.197 21.123 21.706 20.748 22.081C20.373 22.456 19.864 22.667 19.333 22.667H17.333"
                  stroke="#FDFDFD" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M18 22.667C18.53 22.667 19.039 22.878 19.414 23.253C19.789 23.628 20 24.137 20 24.667C20 25.197 19.789 25.706 19.414 26.081C19.039 26.456 18.53 26.667 18 26.667H12C9.878 26.667 7.843 25.824 6.343 24.324C4.843 22.824 4 20.789 4 18.667V16V16.278C3.999 14.953 4.329 13.649 4.957 12.482C5.585 11.316 6.494 10.324 7.6 9.595L8 9.334C8.638 8.918 11.184 7.457 15.637 4.952C16.091 4.697 16.627 4.629 17.131 4.762C17.634 4.896 18.066 5.22 18.333 5.667C18.92 6.646 18.767 7.899 17.96 8.707L16 10.667"
                  stroke="#FDFDFD" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <h2 style="font-family:'Outfit',sans-serif;font-weight:700;font-size:24px;line-height:30px;color:#FDFDFD;margin:0;">
            {{ __('Shop by Category') }}
        </h2>
    </div>
    <div class="swiper categories-slide elora-cats-swiper w-full">
        <div class="swiper-wrapper" style="align-items:stretch;">
            @foreach ($categories as $cat)
            <div class="swiper-slide elora-cats-slide">
                <a href="{{ route('tenant.storefront.category', $cat->slug) }}" class="elora-cat-card">
                    @if ($cat->thumb_url ?? null)
                        <img loading="lazy" src="{{ $cat->thumb_url }}" alt="{{ $cat->translationValue('name') ?? $cat->name }}"
                            style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;"/>
                    @else
                        <div style="position:absolute;inset:0;background:linear-gradient(135deg,#6B21A8,#8A38F5);display:flex;align-items:center;justify-content:center;">
                            <span style="font-size:48px;">🛍️</span>
                        </div>
                    @endif
                    <div class="elora-cat-label">
                        <span>{{ \Str::limit($cat->translationValue('name') ?? $cat->name, 22) }}</span>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
    <div style="padding: 16px 0 32px;">
        <a href="{{ route('tenant.storefront.category') }}"
           class="inline-flex items-center justify-center font-semibold text-base rounded-full px-8"
           style="background:#1B1B1B;color:#fff;font-family:'Outfit',sans-serif;height:56px;min-width:200px;">
            {{ __('Explore all') }}
        </a>
    </div>
</section>
@endif
