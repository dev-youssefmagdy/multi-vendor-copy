{{--
Souqify compact product card — compactProductCard design.
Vars: $product (Product model), $badgeText (optional)
--}}
@php
    $currency = $currentCurrency ?? null;
    $symbol = data_get($currency, 'symbol', '$');
    $rate = (float) data_get($currency, 'conversion_rate', 1.0);

    $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
    $pricing = $product->storefrontPricing($variant);
    $sellPrice = (float) $pricing['current_price'];
    $realPrice = $pricing['original_price'];
    $hasDiscount = (bool) $pricing['has_discount'];
    $discountPct = $hasDiscount ? (int) round((float) $pricing['discount_percentage']) : 0;
    $displaySell = number_format($sellPrice * $rate, 2);
    $displayReal = $hasDiscount && $realPrice !== null ? number_format($realPrice * $rate, 2) : null;

    // Per-product fixed shipping cost for detected customer country
    $_scRaw = (!empty($customerCountryId)) ? $product->fixedShippingCostForCountry((int) $customerCountryId) : null;
    $displayShipping = $_scRaw !== null ? '+ ' . $symbol . number_format($_scRaw * $rate, 2) . ' ' . __('shipping') : null;

    $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null;

    // Slider: collect all gallery + primary image URLs from central & tenant files
    $_sliderSrcs = ($product->centralProduct?->files ?? collect())
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
    $productName = $product->translationValue('name') ?? $product->slug;
    $productUrl = route('tenant.storefront.product', $product->slug);

    $favData = json_encode([
        'slug' => $product->slug,
        'name' => $productName,
        'price' => round($sellPrice * $rate, 2),
        'old_price' => $displayReal,
        'discount' => $hasDiscount ? $discountPct . '% Off' : null,
        'rating' => $rating,
        'image' => $img,
        'url' => $productUrl,
        'added' => time(),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
@endphp

{{-- compactProductCard design --}}
<div class="bg-white rounded-xl p-4 hover:shadow-md transition flex flex-col cursor-pointer"
    onclick="sqNavigateToProduct(event, '{{ $productUrl }}')">
    {{-- Image --}}
    <div class="relative product-img-bg rounded-lg aspect-square overflow-hidden mb-4">
        <a href="{{ $productUrl }}">
            @if ($img)
                <img loading="lazy" src="{{ $img }}" alt="{{ $productName }}" class="w-full h-full object-cover" @if($sliderJson)
                data-slider='{{ $sliderJson }}' @endif />
            @else
                <div class="w-full h-full flex items-center justify-center text-4xl text-neutral-300">🛍️</div>
            @endif
        </a>
        @if($sliderJson)
            <button type="button"
                class="absolute start-1.5 top-1/2 -translate-y-1/2 z-20 w-6 h-6 flex items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70 transition"
                style="box-shadow:0 2px 6px rgba(0,0,0,.45)"
                onclick="event.preventDefault();event.stopPropagation();cardSliderNav(this,-1)">
                <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button type="button"
                class="absolute end-1.5 top-1/2 -translate-y-1/2 z-20 w-6 h-6 flex items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70 transition"
                style="box-shadow:0 2px 6px rgba(0,0,0,.45)"
                onclick="event.preventDefault();event.stopPropagation();cardSliderNav(this,1)">
                <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        @endif
        @if ($hasDiscount && $discountPct > 0)
            <span
                class="absolute top-0 left-0 bg-rose-700 text-white text-[10px] font-bold px-2 py-1 rounded-br-lg leading-tight">
                {{ $discountPct }}% OFF
            </span>
        @endif
        @if ($rating > 0)
            <span
                class="absolute bottom-0 right-0 bg-yellow-400 text-neutral-900 text-xs font-bold px-2 py-1 rounded-tl-lg flex items-center gap-0.5 leading-tight">
                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                </svg>
                {{ number_format($rating, 1) }}
            </span>
        @endif
    </div>

    {{-- Name --}}
    <a href="{{ $productUrl }}" class="flex-1">
        <h3
            class="text-zinc-900 text-sm font-semibold mb-2 line-clamp-2 min-h-[2.5rem] hover:text-blue-700 transition leading-snug">
            {{ $productName }}
        </h3>
    </a>

    {{-- Price --}}
    <div class="flex items-center gap-2 mb-3 flex-wrap">
        <span class="text-blue-700 text-lg font-semibold leading-none">{{ $symbol }}{{ $displaySell }}</span>
        @if ($displayReal)
            <span class="text-neutral-400 text-sm line-through">{{ $symbol }}{{ $displayReal }}</span>
        @endif
        @if($displayShipping !== null)
            <span class="text-xs text-gray-500 block mt-0.5">{{ $displayShipping }}</span>
        @endif
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-2">
        <button wire:click="addToCart({{ $product->id }})" wire:loading.attr="disabled"
            wire:target="addToCart({{ $product->id }})"
            class="flex-1 h-11 px-4 bg-blue-700 hover:bg-blue-800 text-white rounded-lg flex items-center justify-center gap-2 transition text-sm font-medium">
            <svg class="w-3 h-3 sm:w-5 sm:h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8.25 7.75H12.25M10.25 5.75V9.75M1.46 3.15H16.674C18.052 3.15 19.047 4.42 18.669 5.698L17.015 11.298C16.76 12.158 15.946 12.75 15.02 12.75H5.862C4.935 12.75 4.12 12.157 3.866 11.298L1.46 3.15ZM1.46 3.15L0.75 0.75M14.25 18.75C14.6478 18.75 15.0294 18.592 15.3107 18.3107C15.592 18.0294 15.75 17.6478 15.75 17.25C15.75 16.8522 15.592 16.4706 15.3107 16.1893C15.0294 15.908 14.6478 15.75 14.25 15.75C13.8522 15.75 13.4706 15.908 13.1893 16.1893C12.908 16.4706 12.75 16.8522 12.75 17.25C12.75 17.6478 12.908 18.0294 13.1893 18.3107C13.4706 18.592 13.8522 18.75 14.25 18.75ZM6.25 18.75C6.64782 18.75 7.02936 18.592 7.31066 18.3107C7.59196 18.0294 7.75 17.6478 7.75 17.25C7.75 16.8522 7.59196 16.4706 7.31066 16.1893C7.02936 15.908 6.64782 15.75 6.25 15.75C5.85218 15.75 5.47064 15.908 5.18934 16.1893C4.90804 16.4706 4.75 16.8522 4.75 17.25C4.75 17.6478 4.90804 18.0294 5.18934 18.3107C5.47064 18.592 5.85218 18.75 6.25 18.75Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            {{ __('Add to cart') }}
        </button>
        <button
            class="souqify-fav-btn w-11 h-11 border border-blue-700 hover:bg-blue-50 rounded-lg flex items-center justify-center text-blue-700 transition"
            onclick="souqifyToggleFavorite(this)" data-slug="{{ $product->slug }}" data-fav='{{ $favData }}'
            aria-label="{{ __('Wishlist') }}">
            <svg class="fav-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
        </button>
    </div>
</div>

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
                var rtl = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
                var idx = parseInt(img.getAttribute('data-slider-idx') || '0', 10);
                idx = (idx + (rtl ? -dir : dir) + imgs.length) % imgs.length;
                img.setAttribute('data-slider-idx', String(idx));
                img.src = imgs[idx];
            };
        })();
    </script>
@endonce
