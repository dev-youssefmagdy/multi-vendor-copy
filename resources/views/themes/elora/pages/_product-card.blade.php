@php
    use Illuminate\Support\Js;

    $currency = $currentCurrency ?? null;
    $symbol = data_get($currency, 'symbol', '$');
    $rate = (float) data_get($currency, 'conversion_rate', 1.0);

    // Variants
    $activeVariants = $product->variants->where('active', true)->values();
    $hasMultipleVariants = $activeVariants->count() > 1;
    $variantModalData = null;
    if ($hasMultipleVariants) {
        $variantModalData = $activeVariants->map(fn($v) => [
            'id' => $v->id,
            'label' => $v->centralVariant?->title ?? __('Variant #:id', ['id' => $v->id]),
            'price' => $symbol . number_format((float) $product->storefrontPricing($v)['current_price'] * $rate, 2),
        ])->values()->all();
    }

    // Pricing
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
    $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

    // Weight
    $weightGrams = $product->centralProduct?->weight_grams ?? $product->weight_grams ?? null;
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
    $showLowStock = $manageStock && $stockQty > 0 && $stockQty <= 5; // Delivery estimate (3 business days)
    $deliveryDate = \Carbon\Carbon::now()->addDays(3)->translatedFormat('d F');

    // Badge
    $ribbonBadge = $badge ?? null;

    // Favorites payload (all HTML-safe via JSON_HEX_* flags)
    $favUrl = route('tenant.storefront.product', $product->slug);
    $favData = json_encode([
        'slug' => $product->slug,
        'name' => $product->translationValue('name') ?? $product->slug,
        'price' => round($sellPrice * $rate, 2),
        'old_price' => $displayReal,
        'discount' => $hasDiscount ? $discountPct . '% Off' : null,
        'rating' => $rating,
        'image' => $img,
        'url' => $favUrl,
        'badge' => $ribbonBadge,
        'added' => time(),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
@endphp

<a href="{{ $favUrl }}" class="product-card bg-fill rounded-lg card-w block w-full max-w-[210px]"
    style="text-decoration:none">

    {{-- ── Image area ─────────────────────────────────────────────────────── --}}
    <div class="relative rounded-t-lg overflow-hidden h-48 sm:h-52">

        @if ($img)
            <img loading="lazy" src="{{ $img }}" alt="{{ $product->translationValue('name') }}"
                class="w-full h-full object-cover" @if($sliderJson) data-slider='{{ $sliderJson }}' @endif />
        @else
            <div class="w-full h-full flex items-center justify-center bg-gray-100 text-5xl">🛍️</div>
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

        {{-- Discount / ribbon badge – top-right --}}
        @if ($hasDiscount && $discountPct > 0)
            <span class="badge badge-off">{{ $discountPct }}{{ __('% OFF') }}</span>
        @elseif ($ribbonBadge)
            <span class="badge badge-new">{{ __($ribbonBadge) }}</span>
        @endif

        {{-- Favorite / heart button – top-left --}}
        <button type="button" onclick="event.preventDefault(); eloraHeartToggle(this)" data-fav="{{ $favData }}"
            class="heart-btn absolute top-2 left-2 w-8 h-8 bg-white rounded-full flex items-center justify-center z-10 transition-transform hover:scale-110"
            style="box-shadow:0 4px 4px rgba(0,0,0,.15)">
            <svg class="w-3 h-3 sm:w-5 sm:h-5" viewBox="0 0 24 24" fill="none" stroke="#242424" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path
                    d="M12.62 20.8101C12.28 20.9301 11.72 20.9301 11.38 20.8101C8.48 19.8201 2 15.6901 2 8.6901C2 5.6001 4.49 3.1001 7.56 3.1001C9.38 3.1001 10.99 3.9801 12 5.3401C13.01 3.9801 14.63 3.1001 16.44 3.1001C19.51 3.1001 22 5.6001 22 8.6901C22 15.6901 15.52 19.8201 12.62 20.8101Z" />
            </svg>
        </button>

        {{-- Add-to-cart button – bottom-right --}}
        @if ($hasMultipleVariants)
            <button type="button"
                onclick="event.preventDefault(); openVariantModal({{ $product->id }}, {{ Js::from($product->translationValue('name') ?? $product->slug) }}, {{ Js::from($variantModalData) }})"
                class="card-cart-btn absolute bottom-2 right-2 bg-fill flex items-center justify-center px-3 py-2.5 rounded-2xl transition-colors z-10 hover:bg-main">
                <svg class="w-3 h-3 sm:w-5 sm:h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8.25 7.75H12.25M10.25 5.75V9.75M1.46 3.15H16.674C18.052 3.15 19.047 4.42 18.669 5.698L17.015 11.298C16.76 12.158 15.946 12.75 15.02 12.75H5.862C4.935 12.75 4.12 12.157 3.866 11.298L1.46 3.15ZM1.46 3.15L0.75 0.75M14.25 18.75C14.6478 18.75 15.0294 18.592 15.3107 18.3107C15.592 18.0294 15.75 17.6478 15.75 17.25C15.75 16.8522 15.592 16.4706 15.3107 16.1893C15.0294 15.908 14.6478 15.75 14.25 15.75C13.8522 15.75 13.4706 15.908 13.1893 16.1893C12.908 16.4706 12.75 16.8522 12.75 17.25C12.75 17.6478 12.908 18.0294 13.1893 18.3107C13.4706 18.592 13.8522 18.75 14.25 18.75ZM6.25 18.75C6.64782 18.75 7.02936 18.592 7.31066 18.3107C7.59196 18.0294 7.75 17.6478 7.75 17.25C7.75 16.8522 7.59196 16.4706 7.31066 16.1893C7.02936 15.908 6.64782 15.75 6.25 15.75C5.85218 15.75 5.47064 15.908 5.18934 16.1893C4.90804 16.4706 4.75 16.8522 4.75 17.25C4.75 17.6478 4.90804 18.0294 5.18934 18.3107C5.47064 18.592 5.85218 18.75 6.25 18.75Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        @else
            <button wire:click.prevent="addToCart({{ $product->id }})" onclick="event.preventDefault()"
                class="card-cart-btn absolute bottom-2 right-2 bg-fill flex items-center justify-center px-3 py-2.5 rounded-2xl transition-colors z-10 hover:bg-main">
                <svg class="w-3 h-3 sm:w-5 sm:h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8.25 7.75H12.25M10.25 5.75V9.75M1.46 3.15H16.674C18.052 3.15 19.047 4.42 18.669 5.698L17.015 11.298C16.76 12.158 15.946 12.75 15.02 12.75H5.862C4.935 12.75 4.12 12.157 3.866 11.298L1.46 3.15ZM1.46 3.15L0.75 0.75M14.25 18.75C14.6478 18.75 15.0294 18.592 15.3107 18.3107C15.592 18.0294 15.75 17.6478 15.75 17.25C15.75 16.8522 15.592 16.4706 15.3107 16.1893C15.0294 15.908 14.6478 15.75 14.25 15.75C13.8522 15.75 13.4706 15.908 13.1893 16.1893C12.908 16.4706 12.75 16.8522 12.75 17.25C12.75 17.6478 12.908 18.0294 13.1893 18.3107C13.4706 18.592 13.8522 18.75 14.25 18.75ZM6.25 18.75C6.64782 18.75 7.02936 18.592 7.31066 18.3107C7.59196 18.0294 7.75 17.6478 7.75 17.25C7.75 16.8522 7.59196 16.4706 7.31066 16.1893C7.02936 15.908 6.64782 15.75 6.25 15.75C5.85218 15.75 5.47064 15.908 5.18934 16.1893C4.90804 16.4706 4.75 16.8522 4.75 17.25C4.75 17.6478 4.90804 18.0294 5.18934 18.3107C5.47064 18.592 5.85218 18.75 6.25 18.75Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        @endif
    </div>

    {{-- ── Info area ───────────────────────────────────────────────────────── --}}
    <div class="p-2 flex flex-col gap-0.5 lg:gap-2">

        {{-- Title + weight --}}
        <div>
            <div class="flex items-start justify-between gap-1 mb-0.5">
                <span
                    class="text-sm md:text-base font-medium text-[#171717] leading-tight tracking-[0.5px] line-clamp-1">
                    {{ $product->translationValue('name') ?? $product->slug }}
                </span>
                @if ($weightLabel)
                    <span
                        class="text-xs sm:text-sm text-main font-normal flex-shrink-0 tracking-[0.5px] leading-tight pt-0.5">
                        {{ $weightLabel }}
                    </span>
                @endif
            </div>
            {{-- Category / subtitle --}}
            <p class="text-xs sm:text-sm text-[#ADADAD] truncate tracking-[0.5px] leading-tight">
                {{ $product->centralProduct?->category?->name ?? '' }}
            </p>
        </div>

        {{-- Stars + average + count --}}
        <div class="flex items-center gap-2">
            <div class="stars">
                @for ($i = 1; $i <= 5; $i++)
                    <div class="star {{ $i <= $fullStars ? 'filled' : 'empty' }}">
                    </div>
                @endfor
            </div>
            <span class="text-[11px] text-[#ADADAD] tracking-[0.5px]">
                {{ number_format($rating, 1) }}
                @if ($ratingCount > 0)
                    (+{{ $ratingCount >= 1000 ? number_format($ratingCount / 1000, 1) . __('k') : $ratingCount }})
                @endif
            </span>
        </div>

        {{-- Price: current on top, original (strikethrough) + % Off below --}}
        <div class="flex flex-col gap-0.5">
            <span class="text-sm md:text-lg font-medium text-[#171717] tracking-tight leading-none">
                {{ $symbol }}{{ $displaySell }}
            </span>
            @if ($displayReal)
                <div class="flex items-center gap-1.5">
                    <span class="text-[11px] sm:text-xs text-[#ADADAD] line-through font-light leading-none">
                        {{ $symbol }}{{ $displayReal }}
                    </span>
                    <span class="text-[11px] sm:text-xs text-[#FF522C] tracking-[0.5px] leading-none">
                        {{ $discountPct }}% {{ __('Off') }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Delivery estimate + low-stock warning --}}
        <div class="flex flex-col gap-0.5 mt-1 md:mt-0">
            <div class="flex items-center gap-1">
                {{-- Truck icon --}}
                <svg width="11" height="9" viewBox="0 0 11 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M7.85352 8.35352C8.4058 8.35352 8.85352 7.9058 8.85352 7.35352C8.85352 6.80123 8.4058 6.35352 7.85352 6.35352C7.30123 6.35352 6.85352 6.80123 6.85352 7.35352C6.85352 7.9058 7.30123 8.35352 7.85352 8.35352Z"
                        stroke="#2AAF2F" stroke-width="0.707143" />
                    <path
                        d="M2.85352 8.35352C3.4058 8.35352 3.85352 7.9058 3.85352 7.35352C3.85352 6.80123 3.4058 6.35352 2.85352 6.35352C2.30123 6.35352 1.85352 6.80123 1.85352 7.35352C1.85352 7.9058 2.30123 8.35352 2.85352 8.35352Z"
                        stroke="#2AAF2F" stroke-width="0.707143" />
                    <path
                        d="M1.85352 7.33952C1.30502 7.31252 0.963516 7.23102 0.719516 6.98752C0.475516 6.74402 0.394516 6.40202 0.367516 5.85352M3.85352 7.35352H6.85352M8.85352 7.33952C9.40202 7.31252 9.74352 7.23102 9.98752 6.98752C10.3535 6.62102 10.3535 6.03202 10.3535 4.85352V3.85352H8.00352C7.63102 3.85352 7.44502 3.85352 7.29452 3.80452C7.14444 3.75575 7.00804 3.67216 6.89646 3.56058C6.78487 3.44899 6.70128 3.31259 6.65252 3.16252C6.60352 3.01202 6.60352 2.82602 6.60352 2.45352C6.60352 1.89502 6.60352 1.61602 6.53002 1.39002C6.45686 1.1649 6.33148 0.960298 6.16411 0.792926C5.99673 0.625554 5.79213 0.500167 5.56702 0.427016C5.34102 0.353516 5.06202 0.353516 4.50352 0.353516H0.353516M0.353516 2.35352H3.35352M0.353516 3.85352H2.35352"
                        stroke="#2AAF2F" stroke-width="0.707143" stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M6.60352 1.35352H7.51402C8.24202 1.35352 8.60552 1.35352 8.90152 1.53052C9.19802 1.70702 9.37052 2.02752 9.71552 2.66852L10.3535 3.85352"
                        stroke="#2AAF2F" stroke-width="0.707143" stroke-linecap="round" stroke-linejoin="round" />
                </svg>

                <span class="text-[8px] md:text-xs font-medium text-[#2AAF2F] tracking-[0.5px]">
                    {{ __('Delivered by') }} {{ $deliveryDate }}
                </span>
            </div>
            @if ($showLowStock)
                <div class="flex items-center gap-1">
                    {{-- Box icon --}}
                    <svg class="w-[15px] h-[15px] flex-shrink-0" fill="none" stroke="#2AAF2F" stroke-width="1.25"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M20 7H4a1 1 0 00-1 1v11a1 1 0 001 1h16a1 1 0 001-1V8a1 1 0 00-1-1z" />
                        <path d="M16 7V5a2 2 0 00-4 0v2M12 7V5a2 2 0 00-4 0v2" />
                    </svg>
                    <span class="text-[11px] sm:text-xs font-medium text-[#2AAF2F] tracking-[0.5px]">
                        {{ __('Only :count left', ['count' => $stockQty]) }}
                    </span>
                </div>
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
