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
    $ribbonBadge = $badge ?? null;

    // ── Pill badge (below stars, from product badges) ───────────────────────
    $pillBadge = $product->badges->firstWhere('active', true)?->text
        ?? $product->badges->first()?->text
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
    $_sliderSrcs = ($product->centralProduct?->files ?? collect())
        ->merge($product->files ?? collect())
        ->where('file_type', 'image')
        ->whereIn('key', ['gallery', 'primary_medium', 'primary_original'])
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

    $rating = $product->average_rating;
    $fullStars = (int) round($rating);
@endphp

<a href="{{ route('tenant.storefront.product', $product->slug) }}"
    class="group flex flex-col items-center bg-white rounded-[8px] overflow-hidden hover:shadow-md transition"
    style="border: 0.4px solid #E8E8E8;">

    {{-- Image area: #F6F6F6 bg, rounded top, 136px tall --}}
    <div
        class="w-full h-[136px] bg-[#f6f6f6] rounded-t-[8px] flex items-center justify-center overflow-hidden shrink-0 relative">

        @if ($img)
            <img loading="lazy" src="{{ $img }}" alt="{{ $product->translationValue('name') }}"
                class="w-full h-full  group-hover:scale-105 transition-transform duration-300"
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

        {{-- ── Ribbon badge (Figma 104×23 red arrow shape) ──────────────────── --}}
        @if ($ribbonBadge)
            <div class="absolute top-0 left-0 flex items-center">
                <svg width="104" height="23" viewBox="0 0 104 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 8C0 3.58172 3.58172 0 8 0H104L95.4353 12.075L104 23H0V8Z" fill="#DE1709" />
                    <path
                        d="M10.03 16V14.852H12.97C13.53 14.852 13.9687 14.6887 14.286 14.362C14.6033 14.026 14.762 13.6247 14.762 13.158C14.762 12.8407 14.692 12.556 14.552 12.304C14.412 12.0427 14.2067 11.8373 13.936 11.688C13.6747 11.5387 13.3667 11.464 13.012 11.464H10.03V10.316H12.816C13.2827 10.316 13.6513 10.1947 13.922 9.952C14.202 9.7 14.342 9.34067 14.342 8.874C14.342 8.40733 14.1973 8.05267 13.908 7.81C13.6187 7.558 13.236 7.432 12.76 7.432H10.03V6.284H12.788C13.4227 6.284 13.95 6.40067 14.37 6.634C14.7993 6.858 15.1213 7.15667 15.336 7.53C15.56 7.90333 15.672 8.314 15.672 8.762C15.672 9.28467 15.5273 9.742 15.238 10.134C14.958 10.526 14.5427 10.834 13.992 11.058L14.104 10.638C14.7293 10.862 15.2147 11.2027 15.56 11.66C15.9147 12.108 16.092 12.64 16.092 13.256C16.092 13.7693 15.966 14.2313 15.714 14.642C15.462 15.0527 15.098 15.384 14.622 15.636C14.1553 15.8787 13.5813 16 12.9 16H10.03ZM9.148 16V6.284H10.464V16H9.148ZM21.2267 16.14C20.564 16.14 19.9667 15.9907 19.4347 15.692C18.9027 15.384 18.4827 14.9687 18.1747 14.446C17.8667 13.9233 17.7127 13.3307 17.7127 12.668C17.7127 12.0147 17.862 11.4267 18.1607 10.904C18.4687 10.3813 18.8793 9.97067 19.3927 9.672C19.9153 9.364 20.4987 9.21 21.1427 9.21C21.7587 9.21 22.3 9.35 22.7667 9.63C23.2427 9.91 23.6113 10.2973 23.8727 10.792C24.1433 11.2867 24.2787 11.8467 24.2787 12.472C24.2787 12.5653 24.274 12.668 24.2647 12.78C24.2553 12.8827 24.2367 13.004 24.2087 13.144H18.5947V12.094H23.5367L23.0747 12.5C23.0747 12.052 22.9953 11.674 22.8367 11.366C22.678 11.0487 22.454 10.806 22.1647 10.638C21.8753 10.4607 21.5253 10.372 21.1147 10.372C20.6853 10.372 20.3073 10.4653 19.9807 10.652C19.654 10.8387 19.402 11.1 19.2247 11.436C19.0473 11.772 18.9587 12.1687 18.9587 12.626C18.9587 13.0927 19.052 13.5033 19.2387 13.858C19.4253 14.2033 19.6913 14.474 20.0367 14.67C20.382 14.8567 20.7787 14.95 21.2267 14.95C21.6 14.95 21.9407 14.8847 22.2487 14.754C22.566 14.6233 22.8367 14.4273 23.0607 14.166L23.8727 14.992C23.5553 15.3653 23.1633 15.65 22.6967 15.846C22.2393 16.042 21.7493 16.14 21.2267 16.14ZM28.2665 16.14C27.8932 16.14 27.5385 16.0933 27.2025 16C26.8758 15.8973 26.5725 15.7573 26.2925 15.58C26.0125 15.3933 25.7698 15.174 25.5645 14.922L26.3765 14.11C26.6192 14.4087 26.8992 14.6327 27.2165 14.782C27.5338 14.922 27.8885 14.992 28.2805 14.992C28.6725 14.992 28.9758 14.9267 29.1905 14.796C29.4052 14.656 29.5125 14.4647 29.5125 14.222C29.5125 13.9793 29.4238 13.7927 29.2465 13.662C29.0785 13.522 28.8592 13.41 28.5885 13.326C28.3178 13.2327 28.0285 13.144 27.7205 13.06C27.4218 12.9667 27.1372 12.85 26.8665 12.71C26.5958 12.57 26.3718 12.3787 26.1945 12.136C26.0265 11.8933 25.9425 11.5713 25.9425 11.17C25.9425 10.7687 26.0405 10.4233 26.2365 10.134C26.4325 9.83533 26.7032 9.60667 27.0485 9.448C27.4032 9.28933 27.8278 9.21 28.3225 9.21C28.8452 9.21 29.3072 9.30333 29.7085 9.49C30.1192 9.66733 30.4552 9.938 30.7165 10.302L29.9045 11.114C29.7178 10.8713 29.4845 10.6847 29.2045 10.554C28.9338 10.4233 28.6258 10.358 28.2805 10.358C27.9165 10.358 27.6365 10.4233 27.4405 10.554C27.2538 10.6753 27.1605 10.848 27.1605 11.072C27.1605 11.296 27.2445 11.4687 27.4125 11.59C27.5805 11.7113 27.7998 11.814 28.0705 11.898C28.3505 11.982 28.6398 12.0707 28.9385 12.164C29.2372 12.248 29.5218 12.3647 29.7925 12.514C30.0632 12.6633 30.2825 12.864 30.4505 13.116C30.6278 13.368 30.7165 13.6993 30.7165 14.11C30.7165 14.7353 30.4925 15.23 30.0445 15.594C29.6058 15.958 29.0132 16.14 28.2665 16.14ZM33.6491 16V6.564H34.9091V16H33.6491ZM32.0111 10.498V9.35H36.5471V10.498H32.0111ZM38.008 13.032V11.842H42.614V13.032H38.008ZM47.4633 16.14C46.6979 16.14 46.0446 16 45.5033 15.72C44.9619 15.44 44.4813 15.034 44.0613 14.502L44.9573 13.606C45.2653 14.0353 45.6199 14.362 46.0213 14.586C46.4226 14.8007 46.9173 14.908 47.5053 14.908C48.0839 14.908 48.5459 14.782 48.8913 14.53C49.2459 14.278 49.4233 13.9327 49.4233 13.494C49.4233 13.13 49.3393 12.836 49.1713 12.612C49.0033 12.388 48.7746 12.206 48.4853 12.066C48.2053 11.9167 47.8973 11.786 47.5613 11.674C47.2253 11.5527 46.8893 11.4267 46.5533 11.296C46.2173 11.156 45.9093 10.988 45.6293 10.792C45.3493 10.5867 45.1206 10.3207 44.9433 9.994C44.7753 9.66733 44.6913 9.25667 44.6913 8.762C44.6913 8.21133 44.8219 7.74467 45.0833 7.362C45.3539 6.97 45.7179 6.67133 46.1753 6.466C46.6419 6.25133 47.1646 6.144 47.7433 6.144C48.3779 6.144 48.9473 6.27 49.4513 6.522C49.9553 6.76467 50.3659 7.08667 50.6833 7.488L49.7873 8.384C49.4979 8.048 49.1853 7.796 48.8493 7.628C48.5226 7.46 48.1446 7.376 47.7153 7.376C47.1926 7.376 46.7773 7.49267 46.4693 7.726C46.1613 7.95 46.0073 8.26733 46.0073 8.678C46.0073 9.00467 46.0913 9.27067 46.2593 9.476C46.4366 9.672 46.6653 9.84 46.9453 9.98C47.2253 10.12 47.5333 10.2507 47.8693 10.372C48.2146 10.484 48.5553 10.61 48.8913 10.75C49.2273 10.89 49.5353 11.0673 49.8153 11.282C50.0953 11.4967 50.3193 11.7767 50.4873 12.122C50.6646 12.458 50.7533 12.8827 50.7533 13.396C50.7533 14.2547 50.4546 14.9267 49.8573 15.412C49.2693 15.8973 48.4713 16.14 47.4633 16.14ZM55.7872 16.14C55.1245 16.14 54.5272 15.9907 53.9952 15.692C53.4632 15.384 53.0432 14.9687 52.7352 14.446C52.4272 13.9233 52.2732 13.3307 52.2732 12.668C52.2732 12.0147 52.4225 11.4267 52.7212 10.904C53.0292 10.3813 53.4399 9.97067 53.9532 9.672C54.4759 9.364 55.0592 9.21 55.7032 9.21C56.3192 9.21 56.8605 9.35 57.3272 9.63C57.8032 9.91 58.1719 10.2973 58.4332 10.792C58.7039 11.2867 58.8392 11.8467 58.8392 12.472C58.8392 12.5653 58.8345 12.668 58.8252 12.78C58.8159 12.8827 58.7972 13.004 58.7692 13.144H53.1552V12.094H58.0972L57.6352 12.5C57.6352 12.052 57.5559 11.674 57.3972 11.366C57.2385 11.0487 57.0145 10.806 56.7252 10.638C56.4359 10.4607 56.0859 10.372 55.6752 10.372C55.2459 10.372 54.8679 10.4653 54.5412 10.652C54.2145 10.8387 53.9625 11.1 53.7852 11.436C53.6079 11.772 53.5192 12.1687 53.5192 12.626C53.5192 13.0927 53.6125 13.5033 53.7992 13.858C53.9859 14.2033 54.2519 14.474 54.5972 14.67C54.9425 14.8567 55.3392 14.95 55.7872 14.95C56.1605 14.95 56.5012 14.8847 56.8092 14.754C57.1265 14.6233 57.3972 14.4273 57.6212 14.166L58.4332 14.992C58.1159 15.3653 57.7239 15.65 57.2572 15.846C56.7999 16.042 56.3099 16.14 55.7872 16.14ZM60.7131 16V6.004H61.9731V16H60.7131ZM64.3166 16V6.004H65.5766V16H64.3166ZM67.9341 16V9.35H69.1941V16H67.9341ZM68.5641 8.02C68.3308 8.02 68.1394 7.94533 67.9901 7.796C67.8408 7.63733 67.7661 7.44133 67.7661 7.208C67.7661 6.984 67.8408 6.79733 67.9901 6.648C68.1394 6.48933 68.3308 6.41 68.5641 6.41C68.7974 6.41 68.9888 6.48933 69.1381 6.648C69.2874 6.79733 69.3621 6.984 69.3621 7.208C69.3621 7.44133 69.2874 7.63733 69.1381 7.796C68.9888 7.94533 68.7974 8.02 68.5641 8.02ZM76.255 16V12.122C76.255 11.618 76.0963 11.2027 75.779 10.876C75.4616 10.5493 75.051 10.386 74.547 10.386C74.211 10.386 73.9123 10.4607 73.651 10.61C73.3896 10.7593 73.1843 10.9647 73.035 11.226C72.8856 11.4873 72.811 11.786 72.811 12.122L72.293 11.828C72.293 11.324 72.405 10.876 72.629 10.484C72.853 10.092 73.1656 9.784 73.567 9.56C73.9683 9.32667 74.421 9.21 74.925 9.21C75.429 9.21 75.8723 9.336 76.255 9.588C76.647 9.84 76.955 10.1713 77.179 10.582C77.403 10.9833 77.515 11.4127 77.515 11.87V16H76.255ZM71.551 16V9.35H72.811V16H71.551ZM82.4789 18.926C81.7975 18.926 81.1909 18.8 80.6589 18.548C80.1362 18.296 79.7162 17.9413 79.3989 17.484L80.2109 16.658C80.4815 17.0033 80.8035 17.2647 81.1769 17.442C81.5502 17.6287 81.9935 17.722 82.5069 17.722C83.1882 17.722 83.7249 17.54 84.1169 17.176C84.5182 16.8213 84.7189 16.3407 84.7189 15.734V14.082L84.9429 12.584L84.7189 11.1V9.35H85.9789V15.734C85.9789 16.3687 85.8295 16.924 85.5309 17.4C85.2415 17.876 84.8309 18.2493 84.2989 18.52C83.7762 18.7907 83.1695 18.926 82.4789 18.926ZM82.4789 15.888C81.8722 15.888 81.3262 15.7433 80.8409 15.454C80.3649 15.1647 79.9869 14.768 79.7069 14.264C79.4269 13.7507 79.2869 13.1767 79.2869 12.542C79.2869 11.9073 79.4269 11.3427 79.7069 10.848C79.9869 10.344 80.3649 9.94733 80.8409 9.658C81.3262 9.35933 81.8722 9.21 82.4789 9.21C83.0015 9.21 83.4635 9.31267 83.8649 9.518C84.2662 9.72333 84.5835 10.0127 84.8169 10.386C85.0595 10.75 85.1902 11.1793 85.2089 11.674V13.438C85.1809 13.9233 85.0455 14.3527 84.8029 14.726C84.5695 15.09 84.2522 15.3747 83.8509 15.58C83.4495 15.7853 82.9922 15.888 82.4789 15.888ZM82.7309 14.698C83.1415 14.698 83.5009 14.6093 83.8089 14.432C84.1262 14.2547 84.3689 14.0073 84.5369 13.69C84.7049 13.3633 84.7889 12.9853 84.7889 12.556C84.7889 12.1267 84.7002 11.7533 84.5229 11.436C84.3549 11.1093 84.1169 10.8573 83.8089 10.68C83.5009 10.4933 83.1369 10.4 82.7169 10.4C82.2969 10.4 81.9282 10.4933 81.6109 10.68C81.2935 10.8573 81.0415 11.1093 80.8549 11.436C80.6775 11.7533 80.5889 12.122 80.5889 12.542C80.5889 12.962 80.6775 13.3353 80.8549 13.662C81.0415 13.9887 81.2935 14.2453 81.6109 14.432C81.9375 14.6093 82.3109 14.698 82.7309 14.698Z"
                        fill="white" />
                </svg>
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
            @if ($hasMultipleVariants)
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
