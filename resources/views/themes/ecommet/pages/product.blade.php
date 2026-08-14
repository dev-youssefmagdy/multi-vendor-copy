<main class="flex-grow bg-white pt-4 pb-14 w-full">
    <div class="max-w-[1280px] mx-auto px-5 sm:px-8">

        {{-- Breadcrumb --}}
        <nav class="text-[13px] text-[#787878] mb-6 flex items-center gap-1 flex-wrap" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('tenant.home') }}" class="hover:underline">{{ __('Home') }}</a>
            @foreach ($categoryAncestors as $ancestor)
                <span class="mx-1" aria-hidden="true">&gt;</span>
                <a href="{{ route('tenant.storefront.category', $ancestor->slug) }}" class="hover:underline">
                    {{ $ancestor->translationValue('name') ?? $ancestor->slug }}
                </a>
            @endforeach
            <span class="mx-1" aria-hidden="true">&gt;</span>
            <span class="text-[#222] font-medium truncate max-w-[200px]" aria-current="page">{{ $product->translationValue('name') ?? $product->slug }}</span>
        </nav>

        {{-- Main layout --}}
        <div class="flex flex-col lg:grid lg:grid-cols-2 gap-6 lg:gap-10">

            {{-- ── LEFT: Gallery (wire:ignore protects JS-managed DOM) ─── --}}
            <div class="flex flex-col gap-4 lg:gap-6" wire:ignore>
                <div class="flex flex-col lg:flex-row gap-4">

                    {{-- Desktop thumbnail strip --}}
                    <div class="hidden lg:flex flex-col gap-2 w-[70px]" style="max-height:500px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:#d1d5db transparent;" data-product-thumbs>
                        @foreach ($mediaItems as $idx => $item)
                            <button type="button"
                                class="manti-thumb-btn w-full h-[70px] bg-[#f5f5f5] rounded-md flex items-center justify-center p-1 border cursor-pointer relative {{ $idx === 0 ? 'border-[#222]' : 'border-transparent hover:border-[#ccc]' }}"
                                data-media-index="{{ $idx }}"
                                aria-label="{{ $item['type'] === 'video' ? __('Video slide :index', ['index' => $idx + 1]) : __('Product image :index', ['index' => $idx + 1]) }}">
                                @if ($item['type'] === 'video')
                                    <div class="w-full h-[50px] bg-[#1a1a1a] rounded flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white opacity-80" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                                        </svg>
                                    </div>
                                @else
                                    <img loading="lazy" src="{{ $item['src'] }}" class="object-contain w-full h-full pointer-events-none" alt="{{ $item['alt'] ?? '' }}">
                                @endif
                            </button>
                        @endforeach
                    </div>

                    {{-- Main slider --}}
                    <div id="mantiProductMediaSlider"
                        data-media-items="{{ json_encode($mediaItems->values()->all()) }}"
                        class="flex-1 bg-white lg:bg-[#fbfbfb] lg:border lg:border-[#f0f0f0] lg:rounded-xl relative w-[500px] h-[500px] max-w-full overflow-hidden flex items-center justify-center p-0 touch-pan-y select-none mx-auto">

                        @if ($mediaItems->count() > 1)
                            <button type="button"
                                class="absolute left-3 lg:left-4 top-1/2 -translate-y-1/2 z-10 w-9 h-9 lg:w-11 lg:h-11 rounded-full bg-white/90 border border-[#e5e5e5] text-[#222] flex items-center justify-center shadow-sm hover:bg-white transition"
                                data-media-nav="prev" aria-label="{{ __('Previous') }}">
                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5L7 10l5.5 5.5"/>
                                </svg>
                            </button>
                            <button type="button"
                                class="absolute right-3 lg:right-4 top-1/2 -translate-y-1/2 z-10 w-9 h-9 lg:w-11 lg:h-11 rounded-full bg-white/90 border border-[#e5e5e5] text-[#222] flex items-center justify-center shadow-sm hover:bg-white transition"
                                data-media-nav="next" aria-label="{{ __('Next') }}">
                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5L13 10l-5.5 5.5"/>
                                </svg>
                            </button>
                        @endif

                        {{-- Stage: JS renders images/videos here --}}
                        <div data-product-media-stage
                            class="w-[500px] h-[500px] max-w-full flex items-center justify-center bg-white lg:bg-transparent relative">
                            @if($mediaItems->first() && $mediaItems->first()['type'] !== 'video')
                                <img src="{{ $mediaItems->first()['src'] }}"
                                     alt="{{ $mediaItems->first()['alt'] ?? $product->translationValue('name') }}"
                                     fetchpriority="high"
                                     class="object-contain w-[500px] h-[500px] max-w-full max-h-full">
                            @endif
                        </div>

                        {{-- Dot indicators --}}
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2 z-10">
                            @foreach ($mediaItems as $idx => $item)
                                <button type="button"
                                    class="w-2.5 h-2.5 rounded-full transition {{ $idx === 0 ? 'bg-[#222]' : 'bg-[#d1d5db]' }}"
                                    data-media-dot="{{ $idx }}"
                                    aria-label="{{ $item['type'] === 'video' ? __('Video') : __('Image :index', ['index' => $idx + 1]) }}">
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Desktop review/details panel --}}
                <div class="hidden lg:flex flex-col px-1">
                    <div class="flex items-center gap-3 border-b border-[#e5e5e5] pb-3 mb-4">
                        <h2 class="text-[#000000] text-[15px] border-r border-[#e5e5e5] pr-3">
                            {{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }}
                        </h2>
                        <div class="flex items-center gap-1">
                            <span class="text-[#000000] text-[14px]">{{ $avgRating }}</span>
                            <div class="flex">
                                @for ($s = 1; $s <= 5; $s++)
                                    <svg class="w-4 h-4 {{ $s <= round($avgRating) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                        </div>
                    </div>

                    <h3 class="font-bold text-[14px] text-[#242424] mb-3">{{ __('Product details') }}</h3>
                    <div class="flex flex-col gap-0.5 text-[12px] text-[#808080] mb-5">
                        @if ($primaryCategory)
                            <span>{{ __('Category: :category', ['category' => $primaryCategory->translationValue('name') ?? $primaryCategory->slug]) }}</span>
                        @endif
                        @if ($product->translationValue('description') ?? null)
                        <section class="mt-4  max-w-4xl">
                            <div class="text-[15px] text-gray-700 leading-relaxed space-y-4 prose max-w-none">
                                {!! $product->translationValue('description') !!}
                            </div>
                        </section>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── RIGHT: Product info ──────────────────────────────────── --}}
            <div class="flex flex-col pt-2 pb-6 px-4 lg:pt-2 lg:pl-8 lg:px-0">

                {{-- Product name --}}
                <h1 class="text-[#222222] text-[16px] lg:text-[20px] font-medium leading-[24px] lg:leading-[28px] mb-3">
                    {{ $product->translationValue('name') ?? $product->slug }}
                </h1>

                {{-- Sold / rating row --}}
                <div class="flex flex-wrap items-center gap-2.5 text-[12px] text-[#666] mb-4">
                    <span class="font-bold text-[#222] bg-gray-100 px-2 py-0.5 rounded-md">{{ __(':count sold', ['count' => $soldCount]) }}</span>
                    <span class="w-[3px] h-[3px] rounded-full bg-[#ccc]"></span>
                    <div class="flex items-center gap-1.5">
                        <span class="font-medium text-[#888]">{{ __('Rating:') }}</span>
                        <span class="text-[#222] font-semibold">{{ $avgRating }}</span>
                        <div class="flex gap-[2px]">
                            @for ($s = 1; $s <= 5; $s++)
                                <svg class="w-3 h-3 {{ $s <= round($avgRating) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                    </div>
                </div>

                {{-- Badge / category pills --}}
                <div class="flex items-center gap-2 mb-5 overflow-x-auto no-scrollbar whitespace-nowrap">
                    <span class="bg-[#DE1709] text-white text-[10px] lg:text-[11px] font-bold px-2.5 py-1 rounded-md flex items-center gap-1 shrink-0 uppercase tracking-wide">
                        {{ __($badgeLabel) }}
                    </span>
                    @if ($primaryCategory)
                        <a href="{{ route('tenant.storefront.category', $primaryCategory->slug) }}"
                            class="text-[#666] text-[12px] hover:text-[#222] transition flex items-center gap-1.5 shrink-0">
                            {{ __('in') }} <span class="font-medium underline decoration-[#ccc] underline-offset-2">{{ $primaryCategory->translationValue('name') ?? $primaryCategory->slug }}</span>
                            <svg class="w-1.5 h-2.5 opacity-60" viewBox="0 0 6 10" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M1 1l4 4-4 4"/></svg>
                        </a>
                    @endif
                </div>

                {{-- Price block --}}
                <div class="flex flex-wrap items-end gap-2.5 mb-6">
                    <span class="text-[#222] text-[28px] lg:text-[32px] font-bold leading-none tracking-tight">
                        {{ $symbol }}<span id="ecommet-sell-price">{{ $displaySell }}</span>
                    </span>
                    <span id="ecommet-real-price" class="text-[#989898] text-[16px] font-medium line-through leading-none {{ $hasDiscount ? '' : 'hidden' }}">
                        {{ $symbol }}<span id="ecommet-real-price-val">{{ $displayReal }}</span>
                    </span>
                    <span id="ecommet-discount-badge" class="bg-primary text-white text-[11px] font-bold px-2 py-1 rounded-md leading-none {{ $hasDiscount ? '' : 'hidden' }}">
                        -<span id="ecommet-discount-pct">{{ $discountPct }}</span>%
                    </span>
                </div>

                {{-- Variant selector --}}
                @if ($variants->isNotEmpty())
                    <div class="flex flex-col gap-6 mb-6 pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-[14px] font-semibold text-[#222]">{{ __('Choose option') }}</h3>
                            @if ($activeVariant)
                                <span id="ecommet-variant-title" class="text-[13px] text-[#666] font-medium">{{ $activeVariant->display_label ?? '' }}</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($variants as $variant)
                                @php
                                    $vMediaIndex = $variantData[$variant->id]['mediaIndex'] ?? null;
                                @endphp
                                @php
                                    $vIsInStock = !$manageStock || (($variant->stock ?? 9999) > 0);
                                @endphp
                                <button type="button"
                                    data-variant-id="{{ $variant->id }}"
                                    onclick="ecommetSelectVariant({{ $variant->id }})"
                                    @if($vMediaIndex !== null) onmouseenter="window.mantiShowMediaIndex && window.mantiShowMediaIndex({{ $vMediaIndex }})" @endif
                                    @if(!$vIsInStock) aria-disabled="true" @endif
                                    class="flex flex-col items-center gap-1 transition-all active:scale-95 {{ $vIsInStock ? 'cursor-pointer' : 'cursor-not-allowed' }}">
                                    @php
                                        $vThumb = $variant->thumbnail_url ?? $variant->centralVariant?->thumbnail_url ?? null;
                                        $vTitle = $variant->display_label ?? __('Not available');
                                        $isActive = $activeVariant && $activeVariant->id === $variant->id;
                                        $variantPricing = $product->storefrontPricing($variant);
                                        $vSell = (float) $variantPricing['current_price'];
                                        $vDisplay = number_format($vSell * $rate, 2);
                                    @endphp
                                    <div data-variant-ring class="relative w-[60px] h-[60px] rounded-[8px] border-2 {{ $isActive ? 'border-[#222]' : 'border-transparent ring-1 ring-[#e5e5e5]' }} overflow-hidden bg-white p-[2px] {{ !$vIsInStock ? 'opacity-40' : '' }}">
                                        @if ($vThumb)
                                            <img loading="lazy" src="{{ $vThumb }}" alt="{{ $vTitle }}" class="w-full h-full object-cover rounded-[4px]">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-[9px] text-[#888] text-center leading-tight p-0.5">{{ $vTitle }}</div>
                                        @endif
                                        @if (!$vIsInStock)
                                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                                <div class="w-[110%] h-px bg-[#aaa] rotate-45 origin-center"></div>
                                            </div>
                                        @endif
                                    </div>
                                    <span class="text-[10px] {{ $vIsInStock ? 'text-[#555]' : 'text-[#aaa] line-through' }} font-medium">{{ $symbol }}{{ $vDisplay }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Stock badge --}}
                <div id="ecommet-stock-badge" class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-[12px] font-semibold mb-5 w-fit {{ $isInStock ? 'bg-[#dbfce7] text-[#166534]' : 'bg-red-100 text-red-700' }}">
                    <svg id="ecommet-stock-icon-in" class="h-4 w-4 {{ $isInStock ? '' : 'hidden' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.414 7.414a1 1 0 01-1.414 0L3.29 9.538a1 1 0 111.414-1.414l3.88 3.88 6.707-6.714a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <svg id="ecommet-stock-icon-out" class="h-4 w-4 {{ $isInStock ? 'hidden' : '' }}" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    <span id="ecommet-stock-text">{{ $isInStock ? __('In Stock') : __('Out of Stock') }}</span>
                </div>

                {{-- Qty controls --}}
                <div class="flex items-center justify-between sm:justify-start sm:gap-6 mb-8 p-3 sm:p-0 bg-gray-50 sm:bg-transparent rounded-xl">
                    <span class="text-[14px] font-semibold text-[#222] ml-1 sm:ml-0">{{ __('Qty') }}</span>
                    <div class="flex items-center bg-white border border-[#e5e5e5] rounded-full overflow-hidden shadow-sm h-[40px]">
                        <button type="button"
                            onclick="ecommetDecrementQty()"
                            class="w-[40px] h-full flex items-center justify-center text-[#222] text-lg hover:bg-gray-50 active:bg-gray-100 transition">−</button>
                        <input
                            id="ecommet-qty-input"
                            oninput="ecommetSetQty(this.value)"
                            type="number" min="1" value="{{ $qty }}"
                            class="w-[48px] h-full text-center text-[14px] font-semibold text-[#222] outline-none border-x border-[#f0f0f0]">
                        <button type="button"
                            onclick="ecommetIncrementQty()"
                            class="w-[40px] h-full flex items-center justify-center text-[#222] text-lg hover:bg-gray-50 active:bg-gray-100 transition">+</button>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col gap-3 mb-8">
                    <button type="button"
                        id="ecommet-add-to-cart-btn"
                        onclick="ecommetAddToCart()"
                        @if (!$isInStock) disabled @endif
                        class="bg-[#242424] text-white py-3.5 rounded-full text-[15px] font-bold shadow-[0_4px_12px_rgba(0,0,0,0.1)] hover:bg-black active:scale-[0.98] transition-all w-full flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span id="ecommet-add-to-cart-label">{{ __('Add to Cart') }}</span>
                    </button>
                    <a href="{{ $cartUrl }}"
                        class="bg-white border border-[#e5e5e5] text-[#242424] py-3.5 rounded-full text-[15px] font-semibold text-center shadow-sm hover:bg-gray-50 active:scale-[0.98] transition-all w-full">
                        {{ __('View Cart') }}
                    </a>
                </div>

                @if($shippingThreshold > 0)
                <div id="ecommet-shipping-widget" class="bg-[#fffdf7] border border-[#f5e6c4] rounded-2xl p-4 sm:p-5 mb-4">
                    <p id="ecommet-shipping-message" class="text-[13px] text-[#242424] font-medium mb-2">
                        @if ($remainingForFreeShipping <= 0)
                            {{ __("You've reached free shipping!") }}
                        @else
                            {{ __('Add :weight more to qualify for free shipping', ['weight' => $remainingForFreeShipping >= 1000 ? number_format($remainingForFreeShipping / 1000, 2) . __('kg') : number_format($remainingForFreeShipping) . __('g')]) }}
                        @endif
                    </p>
                    <div class="h-2 bg-[#f5e6c4] rounded-full overflow-hidden">
                        <div id="ecommet-shipping-bar" class="h-full bg-[#242424] rounded-full transition-all duration-500" style="width: {{ $shippingPct }}%"></div>
                    </div>
                </div>
                @endif

                <div class="bg-[#fffdf7] border border-[#f5e6c4] rounded-2xl p-4 sm:p-5 flex gap-3 shadow-sm">
                    <div class="mt-0.5">
                        <svg class="w-5 h-5 text-[#d9a036]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="text-[13px] text-[#555]">
                        <div class="font-bold text-[#222] mb-1 text-[14px]">{{ __('Estimated Delivery') }}</div>
                        <div class="leading-relaxed">{{ $deliveryMinDays }}–{{ $deliveryMaxDays }} {{ __('business days') }} &middot; {{ $deliveryFrom }}–{{ $deliveryTo }}</div>
                    </div>
                </div>
                {{-- Trust badge --}}
                @if($shippingThreshold > 0)
                <div class="bg-[#fffdf7] border border-[#f5e6c4] rounded-2xl p-4 sm:p-5 flex gap-3 shadow-sm">
                    <div class="mt-0.5">
                        <svg class="w-5 h-5 text-[#d9a036]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div class="text-[13px] text-[#555]">
                        <div class="font-bold text-[#222] mb-1 text-[14px]">{{ __('Free shipping on selected orders') }}</div>
                        <div class="leading-relaxed">{{ __('Safe payments, secure privacy, and order protection are available on this product.') }}</div>
                    </div>
                </div>
                @endif

                {{-- Shipping countries --}}
                @if($shippingCountries->isNotEmpty())
                <div class="bg-[#fffdf7] border border-[#f5e6c4] rounded-2xl p-4 sm:p-5 flex flex-col gap-2 shadow-sm">
                    <div class="flex items-center gap-2 text-[13px] font-bold text-[#222]">
                        <svg class="w-4 h-4 text-[#d9a036] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                        </svg>
                        <span>{{ __('Ships to') }}</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($shippingCountries as $country)
                        <span class="inline-flex items-center gap-1 bg-[#f5e6c4] text-[#555] text-xs px-2 py-1 rounded-full">
                            @if($country->flag_emoji)<span>{{ $country->flag_emoji }}</span>@endif
                            {{ $country->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Related products --}}
        @if ($related->isNotEmpty())
            <section class="mt-14">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-[22px] font-bold text-[#222]">{{ __('You might also like') }}</h2>
                    <a href="{{ route('tenant.home') }}"
                        class="text-[12px] font-semibold text-[#2272e2] hover:underline">{{ __('Browse more') }}</a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4">
                    @foreach ($related as $relatedProduct)
                        @include($this->pageView('_product-card'), ['product' => $relatedProduct, 'badge' => null])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</main>

@push('head')
@php
    $jsonLdImages = $mediaItems->pluck('src')->filter()->values()->toArray();
    $jsonLdRating = $avgRating > 0 ? $avgRating : null;
    $jsonLdReviewCount = $reviewCount ?? 0;
    $jsonLdDesc = $seoDesc ?? strip_tags($product->translationValue('description') ?? $product->centralProduct?->translationValue('description') ?? '');
    $jsonLdName = $product->translationValue('name') ?? $product->slug;
    $jsonLdSku = $product->sku ?? $product->centralProduct?->sku ?? null;
    $jsonLdBrand = $storeName ?? null;
    $jsonLdUrl = route('tenant.storefront.product', $product->slug);
    $jsonLdPrice = number_format($sellPrice * $rate, 2, '.', '');
    $jsonLdCurrency = data_get($currentCurrency ?? null, 'code', 'USD');
    $jsonLdAvailability = $isInStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
    $jsonLd = [
        '@context' => 'https://schema.org/',
        '@type' => 'Product',
        'name' => $jsonLdName,
        'description' => mb_substr($jsonLdDesc, 0, 500),
        'url' => $jsonLdUrl,
        'image' => count($jsonLdImages) === 1 ? $jsonLdImages[0] : $jsonLdImages,
        'offers' => [
            '@type' => 'Offer',
            'url' => $jsonLdUrl,
            'priceCurrency' => $jsonLdCurrency,
            'price' => $jsonLdPrice,
            'availability' => $jsonLdAvailability,
            'priceValidUntil' => now()->addYear()->toDateString(),
        ],
    ];
    if ($jsonLdSku) $jsonLd['sku'] = $jsonLdSku;
    if ($jsonLdBrand) $jsonLd['brand'] = ['@type' => 'Brand', 'name' => $jsonLdBrand];
    if ($jsonLdRating && $jsonLdReviewCount > 0) {
        $jsonLd['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $jsonLdRating,
            'reviewCount' => $jsonLdReviewCount,
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@push('scripts')
@vite('resources/js/ecommet/product.js')
    <script>
        // ── Variant selection – no Livewire re-render ─────────────────────────────
        const ECOMMET_VARIANTS = @json($variantData ?? []);
        const ECOMMET_CART_ADD_URL = @json($cartAddUrl);
        const ECOMMET_PRODUCT_SLUG = @json($product->slug);
        let ecommetSelectedVariantId = @json($activeVariant?->id);
        let ecommetQty = @json($qty);

        function ecommetFormatWeight(grams) {
            if (!grams) return '';
            return grams >= 1000
                ? (grams / 1000).toFixed(2) + @json(__('kg'))
                : grams + @json(__('g'));
        }

        window.storefrontUpdateShippingProgress = function (data) {
            const threshold = data.shippingThreshold ?? 0;
            if (!threshold) return;
            const pct = Math.max(0, Math.min(100, data.shippingPct ?? 0));
            const remaining = data.remainingForFreeShipping ?? 0;
            const message = remaining <= 0
                ? @json(__("You've reached free shipping!"))
                : @json(__('Add :weight more to qualify for free shipping', ['weight' => '__WEIGHT__'])).replace('__WEIGHT__', ecommetFormatWeight(remaining));

            const bar = document.getElementById('ecommet-shipping-bar');
            if (bar) bar.style.width = pct + '%';
            const msg = document.getElementById('ecommet-shipping-message');
            if (msg) msg.textContent = message;
        };

        function ecommetSetQty(value) {
            ecommetQty = Math.max(1, parseInt(value, 10) || 1);
            const el = document.getElementById('ecommet-qty-input');
            if (el) el.value = ecommetQty;
        }

        function ecommetIncrementQty() {
            ecommetQty++;
            ecommetSetQty(ecommetQty);
        }

        function ecommetDecrementQty() {
            if (ecommetQty > 1) ecommetQty--;
            ecommetSetQty(ecommetQty);
        }

        function ecommetAddToCart() {
            const btn = document.getElementById('ecommet-add-to-cart-btn');
            const label = document.getElementById('ecommet-add-to-cart-label');
            if (btn) { btn.disabled = true; btn.classList.add('opacity-60', 'cursor-not-allowed'); }
            if (label) label.textContent = @json(__('Adding…'));
            window.storefrontCartAdd({
                url: ECOMMET_CART_ADD_URL,
                slug: ECOMMET_PRODUCT_SLUG,
                variantId: ecommetSelectedVariantId,
                qty: ecommetQty,
            }).finally(function () {
                if (btn) { btn.disabled = false; btn.classList.remove('opacity-60', 'cursor-not-allowed'); }
                if (label) label.textContent = @json(__('Add to Cart'));
            });
        }

        function ecommetSelectVariant(id) {
            const data = ECOMMET_VARIANTS[id];
            if (!data) return;
            if (!data.isInStock) {
                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('storefront-toast', { message: @json(__('This option is out of stock')), type: 'error' });
                }
                return;
            }

            ecommetSelectedVariantId = id;

            document.querySelectorAll('[data-variant-id]').forEach(function (btn) {
                const ring = btn.querySelector('[data-variant-ring]');
                if (!ring) return;
                const isActive = parseInt(btn.dataset.variantId) === id;
                if (isActive) {
                    ring.classList.remove('border-transparent', 'ring-1', 'ring-[#e5e5e5]');
                    ring.classList.add('border-[#222]');
                } else {
                    ring.classList.remove('border-[#222]');
                    ring.classList.add('border-transparent', 'ring-1', 'ring-[#e5e5e5]');
                }
            });

            const titleEl = document.getElementById('ecommet-variant-title');
            if (titleEl) titleEl.textContent = data.title;

            const sellEl = document.getElementById('ecommet-sell-price');
            if (sellEl) sellEl.textContent = data.displaySell;

            const realEl = document.getElementById('ecommet-real-price');
            const realVal = document.getElementById('ecommet-real-price-val');
            const badgeEl = document.getElementById('ecommet-discount-badge');
            const pctEl = document.getElementById('ecommet-discount-pct');
            if (data.hasDiscount && data.displayReal) {
                if (realEl) realEl.classList.remove('hidden');
                if (realVal) realVal.textContent = data.displayReal;
                if (badgeEl) badgeEl.classList.remove('hidden');
                if (pctEl) pctEl.textContent = data.discountPct;
            } else {
                if (realEl) realEl.classList.add('hidden');
                if (badgeEl) badgeEl.classList.add('hidden');
            }

            const stockIconIn = document.getElementById('ecommet-stock-icon-in');
            const stockIconOut = document.getElementById('ecommet-stock-icon-out');
            const stockBadge = document.getElementById('ecommet-stock-badge');
            const stockText = document.getElementById('ecommet-stock-text');
            if (stockBadge) {
                if (data.isInStock) {
                    stockBadge.classList.remove('bg-red-100', 'text-red-700');
                    stockBadge.classList.add('bg-[#dbfce7]', 'text-[#166534]');
                    if (stockIconIn) stockIconIn.classList.remove('hidden');
                    if (stockIconOut) stockIconOut.classList.add('hidden');
                    if (stockText) stockText.textContent = @json(__('In Stock'));
                } else {
                    stockBadge.classList.remove('bg-[#dbfce7]', 'text-[#166534]');
                    stockBadge.classList.add('bg-red-100', 'text-red-700');
                    if (stockIconIn) stockIconIn.classList.add('hidden');
                    if (stockIconOut) stockIconOut.classList.remove('hidden');
                    if (stockText) stockText.textContent = @json(__('Out of Stock'));
                }
            }

            const addBtn = document.getElementById('ecommet-add-to-cart-btn');
            if (addBtn) addBtn.disabled = !data.isInStock;

            if (data.mediaIndex !== null && data.mediaIndex !== undefined && window.mantiShowMediaIndex) {
                window.mantiShowMediaIndex(data.mediaIndex);
            }
        }
    </script>
    <script>
        // ── Image zoom on hover ────────────────────────────────────────────
        (function () {
            if (window.matchMedia('(hover: none)').matches) return;
            const slider = document.getElementById('mantiProductMediaSlider');
            const stage = slider ? slider.querySelector('[data-product-media-stage]') : null;
            if (!stage) return;
            stage.style.overflow = 'hidden';
            stage.style.cursor = 'crosshair';
            stage.addEventListener('mousemove', function (e) {
                const img = stage.querySelector('img');
                if (!img) return;
                const rect = stage.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width * 100).toFixed(2);
                const y = ((e.clientY - rect.top) / rect.height * 100).toFixed(2);
                img.style.transformOrigin = x + '% ' + y + '%';
                img.style.transform = 'scale(2)';
                img.style.transition = 'transform 0.1s ease';
                img.style.pointerEvents = 'none';
            });
            stage.addEventListener('mouseleave', function () {
                const img = stage.querySelector('img');
                if (!img) return;
                img.style.transform = 'scale(1)';
                img.style.transformOrigin = 'center center';
            });
        })();
    </script>
@endpush
