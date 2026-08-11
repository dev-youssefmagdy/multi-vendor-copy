@php
    $currency = $currentCurrency ?? null;
    $symbol = data_get($currency, 'symbol', '$');
    $rate = (float) data_get($currency, 'conversion_rate', 1.0);

    // Pricing — use the cheapest active variant so the displayed price matches
    // the price the storefront query sorts/filters by (MIN active-variant price).
    $variant = $product->variants->where('active', true)->sortBy(
        fn($v) => $v->sellPriceForCountry((int) session('storefront_country_id') ?: null)
    )->first() ?? $product->variants->first();
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

    // Image
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

    // Rating
    $rating = (float) ($product->average_rating ?? 0);
    $fullStars = (int) round($rating);
    $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : 0;

    // Weight
    $weightGrams = $product->centralProduct?->weight_grams ?? $product?->weight_grams ?? null;
    $weightLabel = null;
    if ($weightGrams) {
        $weightLabel = $weightGrams >= 1000
            ? number_format($weightGrams / 1000, 1) . __('kg')
            : $weightGrams . __('g');
    }

    // Stock
    $centralProd = $product->centralProduct;
    $manageStock = (bool) ($centralProd?->manage_stock ?? false);
    $stockQty = (int) ($centralProd?->stock ?? 0);
    // variants stock
    if ($centralProd?->variants && $centralProd->variants->count() > 0) {
        $stockQty = (int) $centralProd->variants->sum('stock');
    }
    $showLowStock = $manageStock && $stockQty > 0 && $stockQty <= 5;

    // Brand / category
    $brand = $product->centralProduct?->brand?->name ?? $product->centralProduct?->category?->name ?? '';
    $productName = $product->translationValue('name') ?? $product->slug;
    $productUrl = route('tenant.storefront.product', $product->slug);

    // Badge + tag
    $badgeLabel = $badge ?? null;
    $tagBg = match (true) {
        in_array($badgeLabel, ['Flash Sale', 'Hot Deal', 'Hot Deals']) => 'bg-gradient-to-br from-red-700 to-red-600',
        $badgeLabel === 'New In' => 'bg-amber-500',
        default => 'bg-gradient-to-br from-red-700 to-red-600',
    };
    // $tagText = $showLowStock
    //     ? __('Only :n left – hurry up', ['n' => $stockQty])
    //     : __('Only 5 left – hurry up');

    $stockDisplay = $stockQty > 99 ? '+99' : $stockQty;
    $tagText = __('Only :n left – hurry up', ['n' => $stockDisplay]);

    // Discount badge colour (cycles through 3 styles)
    $discBadgeCls = match ($product->id % 3) {
        0 => 'bg-orange-600 text-white',
        1 => 'bg-red-600 text-white',
        default => 'bg-amber-500 text-neutral-900',
    };

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

{{-- bigProductCard design --}}
<div class="bg-white overflow-hidden shadow-sm hover:shadow-md transition group max-w-[394px] w-full">
    {{-- Image area --}}
    <div class="relative product-img-bg aspect-square overflow-hidden">
        <a href="{{ $productUrl }}">
            @if ($img)
                <img loading="lazy" src="{{ $img }}" alt="{{ $productName }}"
                    class="w-full h-full object-cover group-hover:scale-105 transition duration-500" @if($sliderJson)
                    data-slider='{{ $sliderJson }}' @endif />
            @else
                <div class="w-full h-full flex items-center justify-center text-4xl text-neutral-300">🛍️</div>
            @endif
        </a>

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

        {{-- Wishlist --}}
        <button
            class="souqify-fav-btn absolute top-3 right-3 z-10 w-9 h-9 bg-white rounded-full shadow flex items-center justify-center hover:text-red-500 transition"
            onclick="souqifyToggleFavorite(this)" data-slug="{{ $product->slug }}" data-fav='{{ $favData }}'
            aria-label="{{ __('Wishlist') }}">
            <svg class="fav-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
        </button>

        {{-- Add to cart --}}
        <button wire:click="addToCart({{ $product->id }})" wire:loading.attr="disabled"
            wire:target="addToCart({{ $product->id }})"
            class="absolute bottom-3 right-3 z-10 w-8 h-8 sm:w-12 sm:h-12 bg-white rounded-lg shadow-md flex items-center justify-center hover:bg-blue-700 hover:text-white transition group/cart"
            aria-label="{{ __('Add to cart') }}">
            <svg class="w-3 h-3 sm:w-5 sm:h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M8.25 7.75H12.25M10.25 5.75V9.75M1.46 3.15H16.674C18.052 3.15 19.047 4.42 18.669 5.698L17.015 11.298C16.76 12.158 15.946 12.75 15.02 12.75H5.862C4.935 12.75 4.12 12.157 3.866 11.298L1.46 3.15ZM1.46 3.15L0.75 0.75M14.25 18.75C14.6478 18.75 15.0294 18.592 15.3107 18.3107C15.592 18.0294 15.75 17.6478 15.75 17.25C15.75 16.8522 15.592 16.4706 15.3107 16.1893C15.0294 15.908 14.6478 15.75 14.25 15.75C13.8522 15.75 13.4706 15.908 13.1893 16.1893C12.908 16.4706 12.75 16.8522 12.75 17.25C12.75 17.6478 12.908 18.0294 13.1893 18.3107C13.4706 18.592 13.8522 18.75 14.25 18.75ZM6.25 18.75C6.64782 18.75 7.02936 18.592 7.31066 18.3107C7.59196 18.0294 7.75 17.6478 7.75 17.25C7.75 16.8522 7.59196 16.4706 7.31066 16.1893C7.02936 15.908 6.64782 15.75 6.25 15.75C5.85218 15.75 5.47064 15.908 5.18934 16.1893C4.90804 16.4706 4.75 16.8522 4.75 17.25C4.75 17.6478 4.90804 18.0294 5.18934 18.3107C5.47064 18.592 5.85218 18.75 6.25 18.75Z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>

        {{-- Bottom-left urgency tag --}}
        <div
            class="absolute bottom-0 left-0 {{ $tagBg }} text-white text-[11px] px-3 py-2 rounded-tr-2xl flex items-center gap-1 leading-tight whitespace-nowrap">
            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1" />
            </svg>
            <span>{{ __('Fast delivery') }}</span>
            <span>·</span>
            <span>{{ $tagText }}</span>
        </div>

        {{-- Trending badge --}}
        <div
            class="absolute top-0 left-0 bg-blue-700 text-white text-[10px] px-3 py-1.5 rounded-ee-lg font-medium whitespace-nowrap">
            🔥 {{ $badgeLabel ?? __('Trending') }}
        </div>
    </div>

    {{-- Body --}}
    <a href="{{ $productUrl }}" class="block p-2 sm:p-3 sm:p-4">
        <div class="flex items-start justify-between gap-2 mb-1.5">
            <h3 class="text-zinc-900 text-sm sm:text-base font-medium line-clamp-1 leading-snug">{{ $productName }}</h3>
            @if ($brand)
                <span class="text-orange-600 text-xs font-medium shrink-0 line-clamp-1 max-w-[80px]">{{ $brand }}</span>
            @endif
            @if ($weightLabel)
                <span class="text-orange-600 text-xs font-medium shrink-0 line-clamp-1">{{ $weightLabel }}</span>
            @endif
        </div>

        <div class="flex items-center gap-1.5 mb-2">
            <div class="flex items-center gap-0.5">
                @for ($i = 1; $i <= 5; $i++)
                    <svg class="w-3 h-3 {{ $i <= $fullStars ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                    </svg>
                @endfor
            </div>
            <span class="text-zinc-400 text-xs">{{ number_format($rating, 1) }}
                (+{{ $ratingCount }})</span>
        </div>

        <div class="flex items-center gap-0.5 md:gap-2 flex-wrap">
            <span
                class="text-blue-700 text-base sm:text-xl font-semibold leading-none">{{ $symbol }}{{ $displaySell }}</span>
            @if ($displayReal)
                <span class="text-neutral-400 text-sm line-through">{{ $symbol }}{{ $displayReal }}</span>
                <span class="{{ $discBadgeCls }} text-xs px-1 sm:px-2 py-0.5 rounded font-medium">{{ $discountPct }}%
                    {{ __('off') }}</span>
            @endif
        </div>
    </a>
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
                var idx = parseInt(img.getAttribute('data-slider-idx') || '0', 10);
                idx = (idx + dir + imgs.length) % imgs.length;
                img.setAttribute('data-slider-idx', String(idx));
                img.src = imgs[idx];
            };
        })();
    </script>
@endonce