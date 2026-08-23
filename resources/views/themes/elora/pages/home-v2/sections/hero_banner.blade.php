{{-- ── Hero Banner V2 (purple gradient background, yellow CTA) ──────────────── --}}
<section class="hero-wrapper" wire:ignore>
    <div class="swiper hero-slider w-full overflow-hidden" style="position:relative;">
        <div class="swiper-wrapper">
            @foreach ($banners as $idx => $banner)
            @php
            $img = $banner->image_path ?? null;
            $url = $img
                ? (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . ltrim($img, '/')))
                : null;
            @endphp
            @if ($url)
            <div class="swiper-slide" style="position:relative; min-height:400px; max-height:580px; overflow:hidden; background:#C3ACCF;">
                <img loading="lazy" src="{{ $url }}" alt="{{ $banner->title ?? $storeName }}"
                     class="w-full h-full object-cover" style="max-height:580px;" draggable="false"/>
                <div style="position:absolute;inset:0;background:linear-gradient(to bottom, rgba(0,0,0,0) 40%, rgba(0,0,0,0.36) 100%);"></div>
                @if($banner->title || $banner->subtitle || $banner->cta_text)
                <div style="position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;padding:22px 56px;">
                    @if($banner->title)
                    <h1 style="font-family:'Outfit',sans-serif;font-weight:700;font-size:clamp(32px,4.5vw,64px);color:#fff;line-height:1;letter-spacing:0.69px;max-width:412px;margin-bottom:19px;">
                        {{ $banner->title }}
                    </h1>
                    @endif
                    @if($banner->cta_text ?? null)
                    <a href="{{ $banner->cta_url ?? route('tenant.storefront.best-selling') }}"
                       style="display:inline-flex;align-items:center;justify-content:center;background:#FFD428;border-radius:34px;height:68px;max-width:412px;font-family:'Outfit',sans-serif;font-size:24px;font-weight:500;color:#1B1B1B;text-decoration:none;letter-spacing:0.5px;">
                        {{ $banner->cta_text }}
                    </a>
                    @endif
                </div>
                @endif
            </div>
            @endif
            @endforeach
        </div>
        <div class="swiper-pagination" style="--swiper-pagination-color:#8A38F5;--swiper-pagination-bullet-inactive-color:#D9D9D9;"></div>
        <div class="swiper-button-prev hidden sm:flex w-10 h-10 rounded-full bg-white drop-shadow-2xl" style="inset-inline-start:1.5rem;inset-inline-end:auto;"></div>
        <div class="swiper-button-next hidden sm:flex w-10 h-10 rounded-full bg-white drop-shadow-2xl" style="inset-inline-end:1.5rem;inset-inline-start:auto;"></div>

        @if ($banners->isEmpty())
        <div style="position:relative;min-height:400px;background:linear-gradient(135deg,#C3ACCF 0%,#8A38F5 100%);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:20px;padding:56px;">
            <h1 style="font-family:'Outfit',sans-serif;font-weight:700;font-size:clamp(32px,5vw,64px);color:#fff;line-height:1;max-width:412px;">
                {{ __('Explore New Products') }}
            </h1>
            <a href="{{ route('tenant.storefront.best-selling') }}"
               style="display:inline-flex;align-items:center;justify-content:center;background:#FFD428;border-radius:34px;height:68px;min-width:200px;max-width:412px;font-family:'Outfit',sans-serif;font-size:24px;font-weight:500;color:#1B1B1B;text-decoration:none;padding:0 32px;">
                {{ __('Shop Now') }}
            </a>
        </div>
        @endif
    </div>
</section>
