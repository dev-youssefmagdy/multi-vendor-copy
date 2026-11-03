{{--
Reusable product card partial.
$product App\Models\Tenant\Product (with variants, rates loaded)
$badge optional string ribbon label e.g. 'Best-Selling', 'New In'
--}}
@php
    // ── Currency conversion ──────────────────────────────────────────────────
    $currency = $currentCurrency ?? null;
    $symbol = data_get($currency, 'symbol', '$');
    $rate = (float) data_get($currency, 'conversion_rate', 1.0);

    // ── Variants ─────────────────────────────────────────────────────────────
    $activeVariants = $product->variants->where('active', true)->values();
    $hasMultipleVariants = $activeVariants->count() > 1;
    $variantModalData = null;
    if ($hasMultipleVariants) {
        $variantModalData = $activeVariants->map(fn($v) => [
            'id' => $v->id,
            'label' => $v->centralVariant?->title ?? __('Variant #:id', ['id' => $v->id]),
            'price' => $symbol . number_format((float) $product->storefrontPricing($v)['current_price'] * $rate, 2),
            'inStock' => (int) $v->stock > 0,
        ])->values()->all();
    }

    // ── Prices ───────────────────────────────────────────────────────────────
    $variant = $product->variants->first();
    $pricing = $product->storefrontPricing($variant);
    $sellPrice = (float) $pricing['current_price'];
    $realPrice = $pricing['original_price'];
    $hasDiscount = (bool) $pricing['has_discount'];
    $discountPct = (int) round((float) $pricing['discount_percentage']);

    $displaySell = number_format($sellPrice * $rate, 2);
    $displayReal = $hasDiscount && $realPrice !== null ? number_format($realPrice * $rate, 2) : null;

    // Per-product fixed shipping cost for detected customer country
    $_scRaw = (!empty($customerCountryId)) ? $product->fixedShippingCostForCountry((int) $customerCountryId) : null;
    $displayShipping = $_scRaw !== null ? '+ ' . $symbol . number_format($_scRaw * $rate, 2) . ' ' . __('shipping') : null;

    // ── Ribbon badge (top-left of image, from $badge param) ─────────────────
    $ribbonBadge = $product->cardBadgeText();

    // ── Pill badge (below stars, from product badges) ───────────────────────
    $pillBadge = $product->cardBadgeText()
        ?? null;

    if (!$pillBadge && $pricing['is_flash_sale']) {
        $pillBadge = __('Flash Sale');
    }
    $pillBgColor = (strtolower($pillBadge ?? '') === 'new in' || strtolower($pillBadge ?? '') === 'new-in')
        ? '#0EA5E9'
        : '#621780';

    // ── Image & meta ─────────────────────────────────────────────────────────
    $img = $product->centralProduct->primary_image_url ?? $product->primary_image_url ?? null;

    // Slider: collect all gallery + primary image URLs from central & tenant files
    $_sliderSrcs = $product instanceof \App\Models\Tenant\Product
        ? $product->storefrontSliderImageUrls()
        : ($product->centralProduct?->files ?? collect())
            ->merge($product->files ?? collect())
            ->whereIn('key', ['gallery', 'primary_medium', 'primary_original'])
            ->where('file_type', 'image')
            ->map(fn($f) => $f->full_path)
            ->filter()->unique()->values()->all();
    if ($img && !in_array($img, $_sliderSrcs)) {
        array_unshift($_sliderSrcs, $img);
    }
    if (empty($_sliderSrcs) && $img) {
        $_sliderSrcs = [$img];
    }
    $sliderJson = count($_sliderSrcs) > 1
        ? json_encode($_sliderSrcs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES)
        : null;

    $rating = (float) ($product->average_rating ?? 0);
    $fullStars = (int) round($rating);

    // ── Stock ────────────────────────────────────────────────────────────────
    $isOutOfStock = $product->stockStatus() === 'out_of_stock';
@endphp

<a href="{{ route('tenant.storefront.product', $product->slug) }}"
    class="group flex flex-col items-center w-full max-w-[210px] max-[424px]:[zoom:0.88] max-[374px]:[zoom:0.8] max-[339px]:[zoom:0.7] justify-self-center mx-auto bg-white rounded-[8px] overflow-hidden transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg"
    style="border: 0.4px solid #E8E8E8;">

    {{-- Image area: #F6F6F6 bg, rounded top, same size as elora (h-48 / sm:h-52, max 210px wide) --}}
    <div
        class="w-full aspect-square bg-[#f6f6f6] rounded-t-[8px] flex items-center justify-center overflow-hidden shrink-0 relative">

        @if ($img)
            <img loading="lazy" src="{{ $img }}" alt="{{ $product->translationValue('name') }}"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                @if($sliderJson) data-slider='{{ $sliderJson }}' @endif>
        @else
            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        @endif

        @if($sliderJson)
            <button type="button"
                class="absolute left-1.5 top-1/2 -translate-y-1/2 z-20 w-6 h-6 flex items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70 transition"
                style="box-shadow:0 2px 6px rgba(0,0,0,.45)"
                onclick="event.preventDefault();event.stopPropagation();cardSliderNav(this,-1)">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button type="button"
                class="absolute right-1.5 top-1/2 -translate-y-1/2 z-20 w-6 h-6 flex items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70 transition"
                style="box-shadow:0 2px 6px rgba(0,0,0,.45)"
                onclick="event.preventDefault();event.stopPropagation();cardSliderNav(this,1)">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        @endif

        {{-- ── Ribbon badge (Figma 104×23 red arrow shape), text from the product's badge ── --}}
        @if ($ribbonBadge)
            <div class="absolute top-0 left-0 flex items-center pl-[10px] pr-5 h-[23px]
                                            text-white text-[11px] font-bold font-['Outfit'] tracking-[0.3px] whitespace-nowrap"
                style="background:#DE1709;
                                            clip-path:polygon(0 0, calc(100% - 10px) 0, 100% 50%, calc(100% - 10px) 100%, 0 100%);">
                {{ $ribbonBadge }}
            </div>
        @endif

        {{-- Favorite / heart button – top-right (shared StorefrontFavorites store) --}}
        <button type="button" data-fav-slug="{{ $product->slug }}" data-fav='{{ $product->storefrontFavData($currentCurrency ?? null) }}' aria-pressed="false"
            aria-label="{{ __('Add to favorites') }}"
            onclick="event.preventDefault(); event.stopPropagation(); ecommetToggleFavorite(this)"
            class="ecommet-fav-btn absolute top-2 right-2 w-8 h-8 bg-white rounded-full flex items-center justify-center z-20 transition-transform hover:scale-110"
            style="box-shadow:0 2px 6px rgba(0,0,0,.15)">
            <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="#242424" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path
                    d="M12.62 20.8101C12.28 20.9301 11.72 20.9301 11.38 20.8101C8.48 19.8201 2 15.6901 2 8.6901C2 5.6001 4.49 3.1001 7.56 3.1001C9.38 3.1001 10.99 3.9801 12 5.3401C13.01 3.9801 14.63 3.1001 16.44 3.1001C19.51 3.1001 22 5.6001 22 8.6901C22 15.6901 15.52 19.8201 12.62 20.8101Z" />
            </svg>
        </button>

        {{-- Out of stock overlay --}}
        @if ($isOutOfStock)
            <div class="absolute inset-0 bg-white/60 flex items-center justify-center z-10">
                <span class="bg-[#242424] text-white text-[10px] font-semibold px-2.5 py-1 rounded-full uppercase tracking-wide">
                    {{ __('Out of Stock') }}
                </span>
            </div>
        @endif

    </div>

    {{-- Info section --}}
    <div class="flex flex-col items-start gap-[6px] px-[13px] py-2 w-full">

        {{-- Product name --}}
        <p class="text-[#808080] text-[12px] leading-[15px] tracking-[0.5px] line-clamp-1 w-full">
            {{ $product->translationValue('name') ?? $product->slug }}
        </p>

        {{-- Prices --}}
        <div class="flex flex-row items-center gap-2">
            @if ($hasDiscount)
                <span class="text-[#808080] text-[14px] leading-[18px] tracking-[0.5px] line-through">
                    {{ $symbol }}{{ $displayReal }}
                </span>
            @endif
            <span class="text-black text-[16px] leading-[20px]">
                {{ $symbol }}{{ $displaySell }}
            </span>
        </div>

        <x-storefront.product-labels :labels="$product->cardLabels()" tag="div" class="gap-1 text-[12px] text-gray-500" icon-class="w-3 h-3 shrink-0" />

        {{-- Stars + badge + cart --}}
        <div class="flex flex-row items-center justify-between w-full">

            {{-- Left: stars + badge --}}
            <div class="flex flex-col items-start gap-2">
                {{-- Stars --}}
                <div class="flex items-center gap-[2px]">
                    @for ($i = 1; $i <= 5; $i++)
                        <svg class="w-4 h-4 {{ $i <= $fullStars ? 'text-[#FFB00A]' : 'text-gray-200' }}" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    @endfor
                    <span class="text-[10px] text-gray-400 ml-1">({{ $rating > 0 ? number_format($rating, 1) : '0' }})</span>
                </div>

                {{-- ── Pill badge (Figma 72×17 shape) ─────────────────────── --}}
                @if ($pillBadge && !$ribbonBadge)
                    <div class="inline-flex items-center gap-[4px] px-[6px] rounded-full h-[17px] whitespace-nowrap"
                        style="background: {{ $pillBgColor }};">
                        {{-- star icon matching SVG path in Figma badge --}}
                        <svg width="10" height="10" viewBox="0 0 12 12" fill="white" xmlns="http://www.w3.org/2000/svg"
                            class="shrink-0">
                            <path
                                d="M6.012 0.9L7.166 4.14H10.59C10.865 4.14 10.975 4.492 10.753 4.656L7.994 6.637L9.056 9.809C9.148 10.071 8.847 10.289 8.62 10.121L6 8.24L3.38 10.121C3.153 10.289 2.852 10.071 2.944 9.809L4.006 6.637L1.247 4.656C1.025 4.492 1.135 4.14 1.41 4.14H4.834L5.988 0.9C6.076 0.638 6.438 0.638 6.012 0.9Z" />
                        </svg>
                        <span class="text-white text-[10px] font-normal font-['Outfit'] leading-none tracking-[0.5px]">
                            {{ __($pillBadge) }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Cart button --}}
            @if ($isOutOfStock)
                <button type="button" disabled onclick="event.preventDefault()"
                    class="flex items-center justify-center w-[48px] h-[32px] rounded-[16px] border border-[#cccccc] text-[#cccccc] cursor-not-allowed shrink-0">
                    <svg class="w-3 h-3 sm:w-5 sm:h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8.25 7.75H12.25M10.25 5.75V9.75M1.46 3.15H16.674C18.052 3.15 19.047 4.42 18.669 5.698L17.015 11.298C16.76 12.158 15.946 12.75 15.02 12.75H5.862C4.935 12.75 4.12 12.157 3.866 11.298L1.46 3.15ZM1.46 3.15L0.75 0.75M14.25 18.75C14.6478 18.75 15.0294 18.592 15.3107 18.3107C15.592 18.0294 15.75 17.6478 15.75 17.25C15.75 16.8522 15.592 16.4706 15.3107 16.1893C15.0294 15.908 14.6478 15.75 14.25 15.75C13.8522 15.75 13.4706 15.908 13.1893 16.1893C12.908 16.4706 12.75 16.8522 12.75 17.25C12.75 17.6478 12.908 18.0294 13.1893 18.3107C13.4706 18.592 13.8522 18.75 14.25 18.75ZM6.25 18.75C6.64782 18.75 7.02936 18.592 7.31066 18.3107C7.59196 18.0294 7.75 17.6478 7.75 17.25C7.75 16.8522 7.59196 16.4706 7.31066 16.1893C7.02936 15.908 6.64782 15.75 6.25 15.75C5.85218 15.75 5.47064 15.908 5.18934 16.1893C4.90804 16.4706 4.75 16.8522 4.75 17.25C4.75 17.6478 4.90804 18.0294 5.18934 18.3107C5.47064 18.592 5.85218 18.75 6.25 18.75Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                </button>
            @elseif ($hasMultipleVariants)
                <button type="button"
                    onclick="event.preventDefault(); openVariantModal({{ $product->id }}, {{ \Illuminate\Support\Js::from($product->translationValue('name') ?? $product->slug) }}, {{ \Illuminate\Support\Js::from($variantModalData) }})"
                    class="flex items-center justify-center w-[48px] h-[32px] rounded-[16px] border border-[#242424] hover:bg-gray-50 transition shrink-0">
                    <svg class="w-3 h-3 sm:w-5 sm:h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8.25 7.75H12.25M10.25 5.75V9.75M1.46 3.15H16.674C18.052 3.15 19.047 4.42 18.669 5.698L17.015 11.298C16.76 12.158 15.946 12.75 15.02 12.75H5.862C4.935 12.75 4.12 12.157 3.866 11.298L1.46 3.15ZM1.46 3.15L0.75 0.75M14.25 18.75C14.6478 18.75 15.0294 18.592 15.3107 18.3107C15.592 18.0294 15.75 17.6478 15.75 17.25C15.75 16.8522 15.592 16.4706 15.3107 16.1893C15.0294 15.908 14.6478 15.75 14.25 15.75C13.8522 15.75 13.4706 15.908 13.1893 16.1893C12.908 16.4706 12.75 16.8522 12.75 17.25C12.75 17.6478 12.908 18.0294 13.1893 18.3107C13.4706 18.592 13.8522 18.75 14.25 18.75ZM6.25 18.75C6.64782 18.75 7.02936 18.592 7.31066 18.3107C7.59196 18.0294 7.75 17.6478 7.75 17.25C7.75 16.8522 7.59196 16.4706 7.31066 16.1893C7.02936 15.908 6.64782 15.75 6.25 15.75C5.85218 15.75 5.47064 15.908 5.18934 16.1893C4.90804 16.4706 4.75 16.8522 4.75 17.25C4.75 17.6478 4.90804 18.0294 5.18934 18.3107C5.47064 18.592 5.85218 18.75 6.25 18.75Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                </button>
            @else
                <button wire:click.prevent="addToCart({{ $product->id }})" onclick="event.preventDefault()"
                    class="flex items-center justify-center w-[48px] h-[32px] rounded-[16px] border border-[#242424] hover:bg-gray-50 transition shrink-0">
                    <svg class="w-3 h-3 sm:w-5 sm:h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8.25 7.75H12.25M10.25 5.75V9.75M1.46 3.15H16.674C18.052 3.15 19.047 4.42 18.669 5.698L17.015 11.298C16.76 12.158 15.946 12.75 15.02 12.75H5.862C4.935 12.75 4.12 12.157 3.866 11.298L1.46 3.15ZM1.46 3.15L0.75 0.75M14.25 18.75C14.6478 18.75 15.0294 18.592 15.3107 18.3107C15.592 18.0294 15.75 17.6478 15.75 17.25C15.75 16.8522 15.592 16.4706 15.3107 16.1893C15.0294 15.908 14.6478 15.75 14.25 15.75C13.8522 15.75 13.4706 15.908 13.1893 16.1893C12.908 16.4706 12.75 16.8522 12.75 17.25C12.75 17.6478 12.908 18.0294 13.1893 18.3107C13.4706 18.592 13.8522 18.75 14.25 18.75ZM6.25 18.75C6.64782 18.75 7.02936 18.592 7.31066 18.3107C7.59196 18.0294 7.75 17.6478 7.75 17.25C7.75 16.8522 7.59196 16.4706 7.31066 16.1893C7.02936 15.908 6.64782 15.75 6.25 15.75C5.85218 15.75 5.47064 15.908 5.18934 16.1893C4.90804 16.4706 4.75 16.8522 4.75 17.25C4.75 17.6478 4.90804 18.0294 5.18934 18.3107C5.47064 18.592 5.85218 18.75 6.25 18.75Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                </button>
            @endif

        </div>
    </div>

</a>

@once
    <script>
        (function () {
            if (window.__cardSliderInit) return;
            window.__cardSliderInit = true;
            window.cardSliderNav = function (btn, dir) {
                var container = btn.parentElement;
                var img = container.querySelector('img[data-slider]');
                if (!img) return;
                var raw = img.getAttribute('data-slider');
                var imgs;
                try { imgs = JSON.parse(raw); } catch (e) { return; }
                if (!imgs || imgs.length < 2) return;
                var idx = parseInt(img.getAttribute('data-slider-idx') || '0', 10);
                idx = (idx + dir + imgs.length) % imgs.length;
                img.setAttribute('data-slider-idx', String(idx));
                img.src = imgs[idx];
            };
        })();
    </script>
@endonce

@include($this->pageView('_favorite-script'))
