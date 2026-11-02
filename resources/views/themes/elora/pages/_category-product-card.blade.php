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
            'inStock' => (int) $v->stock > 0,
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

    // Rating
    $rating = (float) ($product->average_rating ?? 0);
    $fullStars = (int) round($rating);
    $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();


    // Stock
    $centralProd = $product->centralProduct;
    $manageStock = (bool) ($centralProd?->manage_stock ?? false);
    $stockQty = (int) ($centralProd?->stock ?? 0);
    $showLowStock = $manageStock && $stockQty > 0 && $stockQty <= 5; // Delivery estimate (3 business days)
    $deliveryDate = \Carbon\Carbon::now()->addDays(3)->translatedFormat('d F');

    // Sold % badge (design: yellow "70% Sold")
    $_soldCount = (int) ($product->centralProduct?->sold_count ?? 0);
    $_soldTotal = $_soldCount + (int) ($product->stock ?? 0);
    $soldPct = $_soldTotal > 0 ? (int) round($_soldCount / $_soldTotal * 100) : 0;

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

    // Stock
    $isOutOfStock = $product->stockStatus() === 'out_of_stock';
@endphp

<a href="{{ $favUrl }}"
    class="category-product-card group bg-fill rounded-lg border border-[#E5E5E5] card-w block w-full max-w-[210px] transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg"
    style="text-decoration:none">

    {{-- ── Image area ─────────────────────────────────────────────────────── --}}
    <div class="relative rounded-t-lg overflow-hidden h-48 sm:h-52">

        @if ($img)
            <img loading="lazy" src="{{ $img }}" alt="{{ $product->translationValue('name') }}"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" @if($sliderJson) data-slider='{{ $sliderJson }}' @endif />
        @else
            <div class="w-full h-full flex items-center justify-center bg-gray-100 text-5xl">🛍️</div>
        @endif


        {{-- Discount / ribbon badge – top-right --}}
        @if ($isOutOfStock)
            <span class="badge" style="background:#6b7280;">{{ __('Out of Stock') }}</span>
        @elseif ($hasDiscount && $discountPct > 0)
            <span class="badge badge-off">{{ $discountPct }}{{ __('% OFF') }}</span>
        @elseif ($soldPct > 0)
            <span class="badge badge-sold">{{ $soldPct }}% {{ __('Sold') }}</span>
        @elseif ($ribbonBadge)
            <span class="badge badge-new">{{ __($ribbonBadge) }}</span>
        @endif

        {{-- Out of stock overlay --}}
        @if ($isOutOfStock)
            <div class="absolute inset-0 bg-white/60 flex items-center justify-center z-10 pointer-events-none">
                <span class="bg-neutral-800 text-white text-xs font-semibold px-3 py-1.5 rounded-full uppercase tracking-wide">
                    {{ __('Out of Stock') }}
                </span>
            </div>
        @endif

        {{-- Favorite / heart button – top-left --}}
        <button type="button" onclick="event.preventDefault(); eloraHeartToggle(this)"
            data-fav="{{ $favData }}"
            data-logged-in="{{ auth()->guard('storefront')->check() ? 'true' : 'false' }}"
            data-product-id="{{ $product->id }}"
            class="heart-btn absolute top-2 left-2 w-8 h-8 bg-white rounded-full flex items-center justify-center z-10 transition-transform hover:scale-110"
            style="box-shadow:0 4px 4px rgba(0,0,0,.15)">
            <svg class="w-3 h-3 sm:w-5 sm:h-5" viewBox="0 0 24 24" fill="none" stroke="#242424" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path
                    d="M12.62 20.8101C12.28 20.9301 11.72 20.9301 11.38 20.8101C8.48 19.8201 2 15.6901 2 8.6901C2 5.6001 4.49 3.1001 7.56 3.1001C9.38 3.1001 10.99 3.9801 12 5.3401C13.01 3.9801 14.63 3.1001 16.44 3.1001C19.51 3.1001 22 5.6001 22 8.6901C22 15.6901 15.52 19.8201 12.62 20.8101Z" />
            </svg>
        </button>

        {{-- Add-to-cart button – bottom-right --}}
        @if ($isOutOfStock)
            <button type="button" disabled onclick="event.preventDefault()"
                class="card-cart-btn absolute bottom-2 end-2 bg-neutral-200 text-neutral-400 flex items-center justify-center px-3 py-2.5 rounded-2xl z-10 cursor-not-allowed">
                <svg class="w-3 h-3 sm:w-5 sm:h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8.25 7.75H12.25M10.25 5.75V9.75M1.46 3.15H16.674C18.052 3.15 19.047 4.42 18.669 5.698L17.015 11.298C16.76 12.158 15.946 12.75 15.02 12.75H5.862C4.935 12.75 4.12 12.157 3.866 11.298L1.46 3.15ZM1.46 3.15L0.75 0.75M14.25 18.75C14.6478 18.75 15.0294 18.592 15.3107 18.3107C15.592 18.0294 15.75 17.6478 15.75 17.25C15.75 16.8522 15.592 16.4706 15.3107 16.1893C15.0294 15.908 14.6478 15.75 14.25 15.75C13.8522 15.75 13.4706 15.908 13.1893 16.1893C12.908 16.4706 12.75 16.8522 12.75 17.25C12.75 17.6478 12.908 18.0294 13.1893 18.3107C13.4706 18.592 13.8522 18.75 14.25 18.75ZM6.25 18.75C6.64782 18.75 7.02936 18.592 7.31066 18.3107C7.59196 18.0294 7.75 17.6478 7.75 17.25C7.75 16.8522 7.59196 16.4706 7.31066 16.1893C7.02936 15.908 6.64782 15.75 6.25 15.75C5.85218 15.75 5.47064 15.908 5.18934 16.1893C4.90804 16.4706 4.75 16.8522 4.75 17.25C4.75 17.6478 4.90804 18.0294 5.18934 18.3107C5.47064 18.592 5.85218 18.75 6.25 18.75Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        @elseif ($hasMultipleVariants)
            <button type="button"
                onclick="event.preventDefault(); openVariantModal({{ $product->id }}, {{ Js::from($product->translationValue('name') ?? $product->slug) }}, {{ Js::from($variantModalData) }})"
                class="card-cart-btn absolute bottom-2 end-2 bg-fill flex items-center justify-center px-3 py-2.5 rounded-2xl transition-colors z-10 hover:bg-main hover:text-white">
                <svg class="w-3 h-3 sm:w-5 sm:h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8.25 7.75H12.25M10.25 5.75V9.75M1.46 3.15H16.674C18.052 3.15 19.047 4.42 18.669 5.698L17.015 11.298C16.76 12.158 15.946 12.75 15.02 12.75H5.862C4.935 12.75 4.12 12.157 3.866 11.298L1.46 3.15ZM1.46 3.15L0.75 0.75M14.25 18.75C14.6478 18.75 15.0294 18.592 15.3107 18.3107C15.592 18.0294 15.75 17.6478 15.75 17.25C15.75 16.8522 15.592 16.4706 15.3107 16.1893C15.0294 15.908 14.6478 15.75 14.25 15.75C13.8522 15.75 13.4706 15.908 13.1893 16.1893C12.908 16.4706 12.75 16.8522 12.75 17.25C12.75 17.6478 12.908 18.0294 13.1893 18.3107C13.4706 18.592 13.8522 18.75 14.25 18.75ZM6.25 18.75C6.64782 18.75 7.02936 18.592 7.31066 18.3107C7.59196 18.0294 7.75 17.6478 7.75 17.25C7.75 16.8522 7.59196 16.4706 7.31066 16.1893C7.02936 15.908 6.64782 15.75 6.25 15.75C5.85218 15.75 5.47064 15.908 5.18934 16.1893C4.90804 16.4706 4.75 16.8522 4.75 17.25C4.75 17.6478 4.90804 18.0294 5.18934 18.3107C5.47064 18.592 5.85218 18.75 6.25 18.75Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        @else
            <button wire:click.prevent="addToCart({{ $product->id }})" onclick="event.preventDefault()"
                class="card-cart-btn absolute bottom-2 end-2 bg-fill flex items-center justify-center px-3 py-2.5 rounded-2xl transition-colors z-10 hover:bg-main hover:text-white">
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

        {{-- Title --}}
        <div>
            <div class="flex items-start justify-between gap-1 mb-0.5">
                <span
                    class="text-sm md:text-base font-medium text-[#171717] leading-tight tracking-[0.5px] line-clamp-1">
                    {{ $product->translationValue('name') ?? $product->slug }}
                </span>
            </div>
            {{-- Category / subtitle --}}
            <p class="text-xs sm:text-sm text-[#ADADAD] truncate tracking-[0.5px] leading-tight">
                {{ \Illuminate\Support\Str::limit(strip_tags((string) ($product->translationValue('summary') ?: '')), 40, '') ?: ($product->centralProduct?->category?->name ?? '') }}
            </p>
        </div>

        {{-- Stars + average + count --}}
        <div class="flex items-center gap-2">
            <div class="flex items-center" style="gap:2px">
                @for ($i = 1; $i <= 5; $i++)
                    <span style="display:inline-block;width:9px;height:9px;background:{{ $i <= $fullStars ? '#ffe100' : '#d5d5d5' }};clip-path:polygon(50% 0%,61% 35%,98% 35%,68% 57%,79% 91%,50% 70%,21% 91%,32% 57%,2% 35%,39% 35%)"></span>
                @endfor
            </div>
            <span class="text-[11px] text-[#ADADAD] tracking-[0.5px]">{{ $ratingCount > 0 ? number_format($rating, 1).' (+'.$ratingCount.')' : '(0)' }}</span>
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
            <x-storefront.product-labels :labels="$product->cardLabels()" tag="div"
                class="gap-1 text-[8px] md:text-xs font-medium text-[#2AAF2F] tracking-[0.5px]" icon-class="w-[11px] h-[11px] shrink-0" />
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
