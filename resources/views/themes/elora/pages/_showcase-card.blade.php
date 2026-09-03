{{--
Elora showcase card variant — used in best-selling / new-in rows.
$product App\Models\Tenant\Product
$badgeText optional pill label e.g. 'Best-Selling', 'New In'
$badge optional ribbon arrow label (top-left of image)
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

    $img = $product->centralProduct->primary_image_url ?? $product->primary_image_url ?? null;

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
    $fullStars = (int) round($rating);

    $ribbonBadge = $badgeText ?? $badge ?? null;
@endphp

<a href="{{ route('tenant.storefront.product', $product->slug) }}" class="product-card bg-fill rounded-lg card-w block">

    <div class="relative card-img-h rounded-t-lg overflow-hidden">
        @if ($img)
            <img loading="lazy" src="{{ $img }}" alt="{{ $product->translationValue('name') }}" class="w-full h-full object-cover"
                @if($sliderJson) data-slider='{{ $sliderJson }}' @endif />
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

        @if ($hasDiscount && $discountPct > 0)
            <span class="badge badge-off">{{ $discountPct }}{{ __('% OFF') }}</span>
        @elseif ($ribbonBadge)
            <span class="badge badge-new">{{ __($ribbonBadge) }}</span>
        @endif

        <button type="button" onclick="event.preventDefault()"
            class="heart-btn absolute top-2 left-2 bg-white rounded-full w-7 h-7 flex items-center justify-center shadow-md">
            <svg class="w-3.5 h-3.5" fill="none" stroke="#171717" stroke-width="1.5" viewBox="0 0 24 24">
                <path
                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
        </button>

        <button type="button" wire:click.prevent="addToCart({{ $product->id }})" onclick="event.preventDefault()"
            class="card-cart-btn absolute bottom-2 right-2 bg-fill rounded-2xl p-2 transition-colors">
            <svg class="w-3 h-3 sm:w-5 sm:h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8.25 7.75H12.25M10.25 5.75V9.75M1.46 3.15H16.674C18.052 3.15 19.047 4.42 18.669 5.698L17.015 11.298C16.76 12.158 15.946 12.75 15.02 12.75H5.862C4.935 12.75 4.12 12.157 3.866 11.298L1.46 3.15ZM1.46 3.15L0.75 0.75M14.25 18.75C14.6478 18.75 15.0294 18.592 15.3107 18.3107C15.592 18.0294 15.75 17.6478 15.75 17.25C15.75 16.8522 15.592 16.4706 15.3107 16.1893C15.0294 15.908 14.6478 15.75 14.25 15.75C13.8522 15.75 13.4706 15.908 13.1893 16.1893C12.908 16.4706 12.75 16.8522 12.75 17.25C12.75 17.6478 12.908 18.0294 13.1893 18.3107C13.4706 18.592 13.8522 18.75 14.25 18.75ZM6.25 18.75C6.64782 18.75 7.02936 18.592 7.31066 18.3107C7.59196 18.0294 7.75 17.6478 7.75 17.25C7.75 16.8522 7.59196 16.4706 7.31066 16.1893C7.02936 15.908 6.64782 15.75 6.25 15.75C5.85218 15.75 5.47064 15.908 5.18934 16.1893C4.90804 16.4706 4.75 16.8522 4.75 17.25C4.75 17.6478 4.90804 18.0294 5.18934 18.3107C5.47064 18.592 5.85218 18.75 6.25 18.75Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
        </button>
    </div>

    <div class="p-2">
        <span class="text-xs sm:text-sm font-medium text-black truncate block" style="max-width:110px">
            {{ $product->translationValue('name') ?? $product->slug }}
        </span>

        <p class="text-xs text-gray3 mb-1.5 truncate">
            {{ $product->centralProduct?->category?->name ?? '' }}
        </p>

        <div class="stars mb-1.5">
            @for ($i = 1; $i <= 5; $i++)
                <div class="star {{ $i <= $fullStars ? 'filled' : 'empty' }}"></div>
            @endfor
            @if ($rating > 0)
                <span class="text-xs text-gray3 ml-1">{{ number_format($rating, 1) }}</span>
            @endif
        </div>

        <div class="flex items-end gap-1.5 mb-1">
            <span class="text-sm sm:text-base font-medium text-black">{{ $symbol }}{{ $displaySell }}</span>
            @if ($displayReal)
                <span class="text-xs text-gray3 line-through">{{ $symbol }}{{ $displayReal }}</span>
                <span class="text-xs text-red-500">{{ $discountPct }}{{ __('% Off') }}</span>
            @endif
        </div>
        @if($displayShipping !== null)
            <p class="text-xs text-gray-500 mt-0.5">{{ $displayShipping }}</p>
        @endif
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
</a>
