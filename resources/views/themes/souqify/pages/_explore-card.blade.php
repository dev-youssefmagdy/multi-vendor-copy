@php
    $currency = $currentCurrency ?? null;
    $symbol = $currency->symbol ?? '$';
    $rate = (float) ($currency->conversion_rate ?? 1.0);

    $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
    $pricing = $product->storefrontPricing($variant);
    $sellPrice = (float) $pricing['current_price'];
    $originalPrice = $pricing['original_price'];
    $hasDiscount = (bool) $pricing['has_discount'];
    $discountPct = $hasDiscount ? (int) round((float) $pricing['discount_percentage']) : 0;
    $displaySell = $symbol . number_format($sellPrice * $rate, 2);
    $displayOriginal = ($hasDiscount && $originalPrice !== null) ? $symbol . number_format($originalPrice * $rate, 2) : null;

    // Per-product fixed shipping cost for detected customer country
    $_scRaw = (!empty($customerCountryId)) ? $product->fixedShippingCostForCountry((int) $customerCountryId) : null;
    $displayShipping = $_scRaw !== null ? '+ ' . $symbol . number_format($_scRaw * $rate, 2) . ' ' . __('shipping') : null;

    $img = $product->primary_image_url ?? asset('souqify/assets/images/product-default.png');

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

    $productName = $product->translationValue('name') ?? $product->slug;
    $productUrl = route('tenant.storefront.product', $product->slug);
    $rating = (float) ($product->average_rating ?? 0);
    $ratingRound = (int) round($rating);

    // Brand: first category name if categories are loaded, otherwise null
    $firstCat = $product->relationLoaded('categories') ? $product->categories->first() : null;
    $brandName = $firstCat ? ($firstCat->translationValue('name') ?? $firstCat->slug) : null;

    // Stock remaining (null = unlimited)
    $stockLeft = $product->stock;

    // Flash sale deal-ends timer (flashSales is eager-loaded by the storefront repo)
    $flashSale = $product->activeStorefrontFlashSale();
    $dealEndsAt = $flashSale?->end_date;
    $hasTimer = $dealEndsAt && $dealEndsAt->isFuture();
    if ($hasTimer) {
        $remaining = (int) now()->diffInSeconds($dealEndsAt);
        $timerH = str_pad((int) floor($remaining / 3600), 2, '0', STR_PAD_LEFT);
        $timerM = str_pad((int) floor(($remaining % 3600) / 60), 2, '0', STR_PAD_LEFT);
        $timerS = str_pad((int) ($remaining % 60), 2, '0', STR_PAD_LEFT);
    } else {
        $timerH = $timerM = $timerS = '00';
    }

    // Sold / progress — no sold_count field on the model; use 0 as placeholder
    $soldCount = 0;
    $progressPct = ($stockLeft === 0) ? 100 : ($hasTimer ? 60 : 0);
@endphp
<div class="w-[342px] lg:w-[472px] bg-white rounded-[10px] p-2.5 lg:p-3 flex items-start gap-2.5 lg:gap-3">
    <!-- thumbnail -->
    <a href="{{ $productUrl }}"
        class="w-[118px] lg:w-44 aspect-square rounded-[7px] overflow-hidden relative flex-shrink-0">
        <!-- image -->
        <img loading="lazy" src="{{ $img }}" alt="{{ $productName }}" class="w-full h-full object-cover"
            @if($sliderJson) data-slider='{{ $sliderJson }}' @endif>
        @if($sliderJson)
            <button type="button"
                class="absolute left-1 top-1/2 -translate-y-1/2 z-20 w-5 h-5 flex items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70 transition"
                style="box-shadow:0 2px 6px rgba(0,0,0,.45)"
                onclick="event.preventDefault();event.stopPropagation();cardSliderNav(this,-1)">
                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button type="button"
                class="absolute right-1 top-1/2 -translate-y-1/2 z-20 w-5 h-5 flex items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70 transition"
                style="box-shadow:0 2px 6px rgba(0,0,0,.45)"
                onclick="event.preventDefault();event.stopPropagation();cardSliderNav(this,1)">
                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        @endif
        <!-- discount badge -->
        @if ($hasDiscount && $discountPct > 0)
            <p
                class="absolute top-0 left-0 bg-[#FB2C36] rounded-[4px] px-[6px] py-[2px] lg:py-1 text-[10px] lg:text-xs text-white">
                -{{ $discountPct }}%
            </p>
        @endif
        <!-- cart button -->
        <button type="button" wire:click.prevent="addToCart({{ $product->id }})"
            class="bg-white p-[7px] lg:p-2.5 rounded-lg absolute bottom-2.5 right-2.5">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M8.25 7.75024H12.25M10.25 5.75024V9.75024M1.46 3.15024H16.674C18.052 3.15024 19.047 4.42024 18.669 5.69824L17.015 11.2982C16.76 12.1582 15.946 12.7502 15.02 12.7502H5.862C4.935 12.7502 4.12 12.1572 3.866 11.2982L1.46 3.15024ZM1.46 3.15024L0.75 0.750244M14.25 18.7502C14.6478 18.7502 15.0294 18.5922 15.3107 18.3109C15.592 18.0296 15.75 17.6481 15.75 17.2502C15.75 16.8524 15.592 16.4709 15.3107 16.1896C15.0294 15.9083 14.6478 15.7502 14.25 15.7502C13.8522 15.7502 13.4706 15.9083 13.1893 16.1896C12.908 16.4709 12.75 16.8524 12.75 17.2502C12.75 17.6481 12.908 18.0296 13.1893 18.3109C13.4706 18.5922 13.8522 18.7502 14.25 18.7502ZM6.25 18.7502C6.64782 18.7502 7.02936 18.5922 7.31066 18.3109C7.59196 18.0296 7.75 17.6481 7.75 17.2502C7.75 16.8524 7.59196 16.4709 7.31066 16.1896C7.02936 15.9083 6.64782 15.7502 6.25 15.7502C5.85218 15.7502 5.47064 15.9083 5.18934 16.1896C4.90804 16.4709 4.75 16.8524 4.75 17.2502C4.75 17.6481 4.90804 18.0296 5.18934 18.3109C5.47064 18.5922 5.85218 18.7502 6.25 18.7502Z"
                    stroke="#0159ED" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
    </a>
    <!-- body -->
    <div class="flex-1">
        <!-- brand -->
        <p class="text-[#6A7282] text-[8px] lg:text-xs mb-1">{{ $brandName ?? '' }}</p>
        <!-- title -->
        <a href="{{ $productUrl }}">
            <h3 class="text-[#1E2939] text-base lg:text-xl mb-1 lg:mb-2 font-semibold line-clamp-1">
                {{ $productName }}
            </h3>
        </a>
        <div class="flex items-center gap-1 mb-1 lg:mb-2">
            <!-- price -->
            <p class="text-[#0159ED] font-semibold">{{ $displaySell }}</p>
            <!-- original / strikethrough price -->
            @if ($displayOriginal)
                <p class="text-[#99A1AF] text-xs line-through">{{ $displayOriginal }}</p>
            @endif
            @if($displayShipping !== null)
                <p class="text-[#99A1AF] text-xs mt-0.5 w-full">{{ $displayShipping }}</p>
            @endif
        </div>
        <!-- star rating -->
        <div class="items-center gap-0.5 mb-2 hidden lg:flex">
            @for ($i = 1; $i <= 5; $i++)
                <svg class="w-2.5 h-2.5 {{ $i <= $ratingRound ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path
                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                </svg>
            @endfor
        </div>
        <!-- sold progress -->
        <div class="flex items-center justify-between flex-wrap mb-1 lg:mb-2">
            <div class="h-1 bg-[#E5E7EB] rounded-sm w-full overflow-hidden">
                <span class="h-full rounded-sm bg-[#DE1709] block" style="width: {{ $progressPct }}%"></span>
            </div>
            <p class="text-[#6A7282] text-xs">{{ __('Sold') }}: <span class="text-[#0159ED]">{{ $soldCount }}</span>
            </p>
            @if ($stockLeft !== null)
                <p class="text-xs text-[#DE1709]">{{ $stockLeft }} {{ __('left') }}</p>
            @endif
        </div>
        <!-- deal ends timer -->
        <div class="flex items-center justify-between text-xs">
            <span class="text-neutral-800 font-medium hidden lg:block">{{ __('Deal Ends') }}</span>
            <div class="flex items-center gap-px deal-timer" @if($hasTimer) data-deal-ends="{{ $dealEndsAt->valueOf() }}" @endif>
                <div class="bg-[#F2F2F2] text-[#0159ED] p-1 rounded text-sm lg:text-xl font-bold">
                    <span data-timer-h>{{ $timerH }}</span>:
                </div>
                <div class="bg-[#F2F2F2] text-[#0159ED] p-1 rounded text-sm lg:text-xl font-bold">
                    <span data-timer-m>{{ $timerM }}</span>:
                </div>
                <div class="bg-[#F2F2F2] text-[#0159ED] p-1 rounded text-sm lg:text-xl font-bold">
                    <span data-timer-s>{{ $timerS }}</span>
                </div>
            </div>
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
                    var idx = parseInt(img.getAttribute('data-slider-idx') || '0', 10);
                    idx = (idx + dir + imgs.length) % imgs.length;
                    img.setAttribute('data-slider-idx', String(idx));
                    img.src = imgs[idx];
                };
            })();
        </script>
    @endonce

    @once
        <script>
            (function () {
                if (window.__dealTimersInit) return;
                window.__dealTimersInit = true;

                function pad(n) {
                    n = Math.max(0, n);
                    return String(n).padStart(2, '0');
                }

                function tick() {
                    document.querySelectorAll('.deal-timer[data-deal-ends]').forEach(function (el) {
                        var endsAt = parseInt(el.getAttribute('data-deal-ends'), 10);
                        var remaining = Math.floor((endsAt - Date.now()) / 1000);
                        if (remaining <= 0) {
                            remaining = 0;
                            el.removeAttribute('data-deal-ends');
                        }
                        var h = Math.floor(remaining / 3600);
                        var m = Math.floor((remaining % 3600) / 60);
                        var s = remaining % 60;
                        var hEl = el.querySelector('[data-timer-h]');
                        var mEl = el.querySelector('[data-timer-m]');
                        var sEl = el.querySelector('[data-timer-s]');
                        if (hEl) hEl.textContent = pad(h);
                        if (mEl) mEl.textContent = pad(m);
                        if (sEl) sEl.textContent = pad(s);
                    });
                }

                tick();
                setInterval(tick, 1000);
            })();
        </script>
    @endonce
</div>