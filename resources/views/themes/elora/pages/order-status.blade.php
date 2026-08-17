{{--
Elora – Order details page
$order Order (with items, activities, paymentGateway)
--}}
@push('body-attrs')data-page="client-order-details"@endpush

@php
    use App\Enums\OrderStatus;

    $currency = $currentCurrency ?? null;
    $symbol = $currency?->symbol ?? '$';
    $rate = (float) ($currency?->conversion_rate ?? 1.0);
    $fmt = fn(float|int $v): string => $symbol . number_format($v * $rate, 2);
    $customer = auth('storefront')->user();

    $statusConfig = match ($order->status) {
        OrderStatus::Pending    => ['bg' => '#FFFDE7', 'color' => '#B59A00', 'border' => '#F5E58A',  'label' => __('Pending')],
        OrderStatus::Processing => ['bg' => '#EEF2FF', 'color' => '#3B5BDB', 'border' => '#C5D0FA',  'label' => __('Processing')],
        OrderStatus::Shipped    => ['bg' => '#E8F4FD', 'color' => '#0369a1', 'border' => '#BAE6FD',  'label' => __('Shipped')],
        OrderStatus::Delivered  => ['bg' => '#DCFCE7', 'color' => '#008236', 'border' => '#B9F8CF',  'label' => __('Delivered')],
        OrderStatus::Completed  => ['bg' => '#DCFCE7', 'color' => '#008236', 'border' => '#B9F8CF',  'label' => __('Completed')],
        OrderStatus::Cancelled  => ['bg' => '#FFF0F0', 'color' => '#dc2626', 'border' => '#FECACA',  'label' => __('Cancelled')],
        OrderStatus::Rejected   => ['bg' => '#FFF0F0', 'color' => '#dc2626', 'border' => '#FECACA',  'label' => __('Rejected')],
        default                 => ['bg' => '#F5F5F5', 'color' => '#666',    'border' => '#E0E0E0',  'label' => $order->status->label()],
    };

    $carrier        = $order->shipping_address['carrier'] ?? null;
    $trackingNumber = null; // Tracking number is admin-only, not shown on storefront
    $isFinalStatus  = in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected]);
    $isCompleted    = in_array($order->status, [OrderStatus::Delivered, OrderStatus::Completed]);
    $canCancel      = in_array($order->status, [OrderStatus::Pending, OrderStatus::Processing]);
@endphp

<div>
    {{-- Breadcrumb --}}
    <div class="bg-white border-b border-gray-100">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-10 py-2.5 flex items-center gap-1 text-sm text-[#808080] flex-wrap">
            <a href="{{ route('tenant.home') }}" class="hover:text-main transition-colors">{{ __('Home') }}</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
            @auth('storefront')
            <a href="{{ route('tenant.storefront.profile') }}" class="hover:text-main transition-colors">{{ __('Orders') }}</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
            @endauth
            <span class="text-[#1B1B1B] font-semibold">{{ __('Order #:uuid', ['uuid' => $order->uuid]) }}</span>
        </div>
    </div>

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-10 py-6 pb-16">
        <div class="flex gap-6 lg:gap-8 items-start">

            {{-- ════ LEFT SIDEBAR ════ --}}
            @auth('storefront')
            <div class="prof-sidebar flex-shrink-0" style="width:230px">
                <div class="bg-white border border-[#F0F0F0] rounded-2xl p-5 mb-4 shadow-sm">
                    <p class="text-lg font-semibold text-[#171717] mb-0.5">{{ $customer->full_name }}</p>
                    <p class="text-sm text-[#ADADAD] mb-3">{{ $customer->email }}</p>
                    <a href="{{ route('tenant.storefront.profile') }}"
                        class="text-xs font-medium text-main border border-[#FFAC88] bg-[#FFF5F2] rounded-full px-4 py-1.5 hover:bg-orange-100 transition-colors inline-block">
                        {{ __('Edit') }}
                    </a>
                </div>
                <div class="bg-white border border-[#F0F0F0] rounded-2xl p-4 mb-4 shadow-sm">
                    <p class="text-base font-semibold text-[#171717] mb-3 pb-2 border-b border-[#F0F0F0]">{{ __('My Orders') }}</p>
                    <nav class="flex flex-col gap-0.5">
                        <a href="{{ route('tenant.storefront.profile') }}" class="sidebar-item active">{{ __('All Orders') }}</a>
                        <a href="{{ route('tenant.storefront.profile') }}" class="sidebar-item">{{ __('Pending') }}</a>
                        <a href="{{ route('tenant.storefront.profile') }}" class="sidebar-item">{{ __('Processing') }}</a>
                        <a href="{{ route('tenant.storefront.profile') }}" class="sidebar-item">{{ __('Shipped') }}</a>
                        <a href="{{ route('tenant.storefront.profile') }}" class="sidebar-item">{{ __('Delivered') }}</a>
                        <a href="{{ route('tenant.storefront.profile') }}" class="sidebar-item">{{ __('Cancelled') }}</a>
                    </nav>
                </div>
                <div class="bg-white border border-[#F0F0F0] rounded-2xl p-4 shadow-sm">
                    <p class="text-base font-semibold text-[#171717] mb-3 pb-2 border-b border-[#F0F0F0]">{{ __('Settings') }}</p>
                    <nav class="flex flex-col gap-0.5">
                        <a href="{{ route('tenant.storefront.profile') }}" class="settings-item">{{ __('My personal details') }}</a>
                    </nav>
                </div>
            </div>
            @endauth

            {{-- ════ MAIN DETAIL CONTENT ════ --}}
            <div class="flex-1 min-w-0">

                {{-- ── Page header ── --}}
                <div class="flex items-center justify-between gap-3 mb-6 flex-wrap gap-y-3">
                    <div class="flex items-center gap-3 flex-wrap">
                        @auth('storefront')
                        <a href="{{ route('tenant.storefront.profile') }}"
                            class="w-9 h-9 flex items-center justify-center rounded-[10px] border border-[#E0E0E0] bg-white hover:border-main transition-colors">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10.0013 15.8337L4.16797 10.0003L10.0013 4.16699" stroke="#0A0A0A" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M15.8346 10H4.16797" stroke="#0A0A0A" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        @endauth
                        <div>
                            <div class="flex items-center gap-3 flex-wrap">
                                <h1 class="text-lg font-medium text-[#242424] font-['Outfit']">
                                    {{ __('Order #:uuid', ['uuid' => $order->uuid]) }}
                                </h1>
                                {{-- Status badge --}}
                                <div class="h-9 px-4 inline-flex items-center gap-2 rounded-full border text-sm font-normal font-['Outfit'] tracking-wide"
                                    style="background:{{ $statusConfig['bg'] }};color:{{ $statusConfig['color'] }};border-color:{{ $statusConfig['border'] }}">
                                    @if ($isCompleted)
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="{{ $statusConfig['color'] }}" stroke-width="1.67" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4"/>
                                    </svg>
                                    @endif
                                    {{ $statusConfig['label'] }}
                                </div>
                            </div>
                            <p class="text-xs text-[#808080] mt-0.5 font-['Outfit'] tracking-wide">{{ $order->created_at?->format('M j, Y') }}</p>
                        </div>
                    </div>
                    {{-- Download Invoice button --}}
                    <a href="{{ route('tenant.storefront.order-invoice', $order->uuid) }}"
                        class="flex items-center gap-2 bg-[#FF4D00] text-white font-normal text-base px-12 py-4 rounded-[32px] hover:bg-orange-600 transition-colors whitespace-nowrap">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="white" stroke-width="1.67" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13M5 16l7 5 7-5M3 20h18"/>
                        </svg>
                        {{ __('Download Invoice') }}
                    </a>
                </div>

                {{-- ── Two-column content layout ── --}}
                <div class="detail-layout flex gap-5 items-start">

                    {{-- LEFT: Items + Shipping --}}
                    <div class="flex-1 min-w-0 flex flex-col gap-4">

                        {{-- Items Ordered --}}
                        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                            <div class="px-4 pt-3 pb-3 border-b border-gray-100">
                                <h3 class="text-base font-normal text-[#242424] font-['Outfit']">{{ __('Items Ordered') }}</h3>
                            </div>
                            <div class="flex flex-col divide-y divide-[#F0F0F0]">
                                @foreach ($order->items as $item)
                                @php
                                    $orderedProduct      = $item->product ?? $item->variant?->product;
                                    $orderedVariantTitle = $item->variant?->display_label;
                                    $itemImage           = $item->variant?->thumbnail_url ?? $orderedProduct?->primary_image_url;
                                @endphp
                                <div class="flex items-start gap-3 px-4 pt-3 pb-3">
                                    <div class="w-[86px] h-[81px] rounded-lg flex-shrink-0 bg-[#F5F5F5] overflow-hidden">
                                        @if ($itemImage)
                                        <img loading="lazy" src="{{ $itemImage }}" alt="" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0 flex flex-col gap-1">
                                        <p class="text-base text-[#242424] leading-6">
                                            {{ $orderedProduct?->translationValue('name') ?? $orderedProduct?->slug ?? __('Item #:id', ['id' => $item->id]) }}
                                        </p>
                                        @if ($orderedVariantTitle)
                                        <p class="text-sm text-[#808080] leading-5">{{ $orderedVariantTitle }}</p>
                                        @endif
                                        <p class="text-sm text-[#808080] leading-5">{{ __('Qty:') }} {{ $item->qty }}</p>
                                        <p class="text-base text-[#242424] leading-6">{{ $fmt($item->sub_total) }}</p>
                                        @if ($isCompleted)
                                            @include('livewire.tenant.storefront.partials.return-item-action')
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Shipping Information --}}
                        @if ($order->shipping_address)
                        <div class="bg-white rounded-2xl border border-gray-200 px-4 pt-4 pb-3 flex flex-col gap-3">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 flex-shrink-0 text-[#242424]" fill="none" stroke="currentColor" stroke-width="1.67" viewBox="0 0 24 24">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <h3 class="text-base text-[#242424] leading-6">{{ __('Shipping Information') }}</h3>
                            </div>
                            <p class="text-sm text-[#808080] leading-5">
                                {{ implode(', ', array_filter([$order->shipping_address['address'] ?? null, $order->shipping_address['city'] ?? null, $order->shipping_address['country'] ?? null])) }}
                            </p>
                            {{-- Carrier & Tracking rows --}}
                            @if ($carrier || $trackingNumber)
                            <div class="border-t border-gray-100 pt-3 flex flex-col gap-2">
                                @if ($carrier)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-[#808080] leading-5">{{ __('Carrier') }}</span>
                                    <span class="text-[#242424] leading-5">{{ $carrier }}</span>
                                </div>
                                @endif
                                @if ($trackingNumber)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-[#808080] leading-5">{{ __('Tracking Number') }}</span>
                                    <span class="text-[#242424] leading-5">{{ $trackingNumber }}</span>
                                </div>
                                @endif
                            </div>
                            @else
                            <div class="border-t border-gray-100 pt-3 flex flex-col gap-2">
                                @if (!empty($order->shipping_address['name']))
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-[#808080]">{{ __('Recipient') }}</span>
                                    <span class="text-[#242424]">{{ $order->shipping_address['name'] }}</span>
                                </div>
                                @endif
                                @if (!empty($order->shipping_address['phone']))
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-[#808080]">{{ __('Phone') }}</span>
                                    <span class="text-[#242424]">{{ $order->shipping_address['phone'] }}</span>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                        @endif

                    </div>

                    {{-- RIGHT: Payment Summary + Actions --}}
                    <div class="detail-right flex flex-col gap-4" style="width:358px;flex-shrink:0">

                        {{-- Payment Summary --}}
                        <div class="bg-white rounded-2xl border border-gray-200 px-4 pt-4 pb-3 flex flex-col gap-3">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 flex-shrink-0 text-[#242424]" fill="none" stroke="currentColor" stroke-width="1.67" viewBox="0 0 24 24">
                                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                                    <path d="M2 10h20"/>
                                </svg>
                                <h3 class="text-base text-[#242424] leading-6">{{ __('Payment Summary') }}</h3>
                            </div>

                            <div class="flex flex-col gap-2">
                                {{-- Subtotal --}}
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-[#808080] leading-5">{{ __('Subtotal') }}</span>
                                    <span class="text-[#242424] text-right leading-5">{{ $fmt($order->subtotal) }}</span>
                                </div>
                                {{-- Discount --}}
                                @if ($order->discount_percentage > 0)
                                <div class="flex items-center justify-between text-sm text-red-500">
                                    <span>{{ __('Discount (:percent%)', ['percent' => $order->discount_percentage]) }}</span>
                                    <span>-{{ $fmt($order->discount_amount) }}</span>
                                </div>
                                @endif
                                {{-- Tax --}}
                                @if ($order->tax_percentage > 0)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-[#808080]">{{ __('Tax') }}</span>
                                    <span class="text-[#242424]">{{ $fmt($order->tax_amount) }}</span>
                                </div>
                                @endif
                                {{-- Shipping Fee --}}
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-[#808080] leading-5">{{ __('Shipping Fee') }}</span>
                                    <span class="text-[#242424] text-right leading-5">
                                        {{ $order->resolved_shipping_charge > 0 ? $fmt($order->resolved_shipping_charge) : __('Free') }}
                                    </span>
                                </div>
                                {{-- Total --}}
                                <div class="flex items-center justify-between border-t border-gray-200 pt-2 mt-1">
                                    <span class="text-base text-[#242424] leading-6">{{ __('Total') }}</span>
                                    <span class="text-base text-[#242424] text-right leading-6">{{ $fmt($order->grand_total) }}</span>
                                </div>
                                {{-- Payment Method + Status --}}
                                <div class="border-t border-gray-100 pt-2 flex flex-col gap-1">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-[#808080] leading-5">{{ __('Payment Method') }}</span>
                                        <span class="text-[#242424] leading-5">{{ $order->paymentGateway?->label ?? $order->payment_method ?? __('N/A') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-[#808080] leading-5">{{ __('Payment Status') }}</span>
                                        <span class="leading-5 {{ $order->paid ? 'text-[#00A63E]' : 'text-[#808080]' }}">
                                            {{ $order->paid ? __('Paid') : __('Unpaid') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Action buttons --}}
                        <div class="flex flex-col gap-4 pt-2">

                            {{-- Track Order --}}
                            @if (!$isFinalStatus)
                            <a href="{{ route('tenant.storefront.order-tracking', $order->uuid) }}"
                                class="w-full flex items-center justify-center gap-2 bg-[#242424] text-white font-normal text-base px-12 py-4 rounded-[32px] hover:bg-black transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="white" stroke-width="1.67" viewBox="0 0 24 24">
                                    <rect x="1" y="3" width="15" height="13" rx="1"/>
                                    <path d="M16 8h4l3 4v4h-7V8z"/>
                                    <circle cx="5.5" cy="18.5" r="2.5"/>
                                    <circle cx="18.5" cy="18.5" r="2.5"/>
                                </svg>
                                {{ __('Track Order') }}
                            </a>
                            @endif

                            {{-- Reorder --}}
                            <button wire:click="reorder"
                                class="w-full flex items-center justify-center gap-2 bg-white text-[#242424] font-normal text-base px-12 py-4 rounded-[32px] border border-[#D1D5DC] hover:border-[#242424] hover:bg-gray-50 transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.67" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                {{ __('Reorder') }}
                            </button>

                            {{-- Leave a Review (delivered/completed) --}}
                            @if ($isCompleted)
                            <button wire:click="openReviewModal"
                                class="w-full flex items-center justify-center gap-2 bg-[#FFF3EE] text-[#FF4D00] font-normal text-base px-12 py-4 rounded-[32px] border border-[#FF4D00] hover:bg-orange-100 transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="#FF4D00" stroke-width="1.67" viewBox="0 0 24 24">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                                {{ __('Leave a Review') }}
                            </button>
                            @endif

                        </div>
                    </div>
                </div>

                {{-- Order Activity --}}
{{--                @if ($order->activities->isNotEmpty())--}}
{{--                <div class="mt-8">--}}
{{--                    <h2 class="text-base font-semibold text-[#171717] mb-4">{{ __('Order Activity') }}</h2>--}}
{{--                    <div class="bg-white border border-[#F0F0F0] rounded-2xl p-5 shadow-sm">--}}
{{--                        <div class="flex flex-col gap-0 relative pl-5">--}}
{{--                            <div class="absolute left-[7px] top-2 bottom-2 w-[2px] bg-[#e5e5e5]"></div>--}}
{{--                            @foreach ($order->activities as $act)--}}
{{--                            <div class="flex items-start gap-3 py-3 relative">--}}
{{--                                <div class="absolute left-[-14px] top-[14px] w-3 h-3 rounded-full bg-[#171717] border-2 border-white"></div>--}}
{{--                                <div>--}}
{{--                                    <p class="text-sm font-semibold text-[#242424]">{{ $act->description }}</p>--}}
{{--                                    <p class="text-xs text-[#888]">{{ $act->created_at?->format('M j, Y H:i') }}</p>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            @endforeach--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--                @endif--}}

            </div>
        </div>
    </div>

    {{-- ════ Leave a Review Modal ════ --}}
    @if ($showReviewModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center px-4"
     x-data
     x-init="
         document.body.style.overflow = 'hidden';
         $el.addEventListener('remove', () => document.body.style.overflow = '');
     ">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeReviewModal"></div>

    {{-- Modal card --}}
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-auto z-10 overflow-hidden"
         x-data="{
             stars: $wire.reviewStars,
             hover: 0,
             setStars(n) { this.stars = n; $wire.set('reviewStars', n); }
         }">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-[#F0F0F0]">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-[#FF4D00]" fill="none" stroke="currentColor" stroke-width="1.67" viewBox="0 0 24 24">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <h2 class="text-lg font-semibold text-[#0A0A0A] font-['Inter']">{{ __('Leave a Review') }}</h2>
            </div>
            <button wire:click="closeReviewModal"
                class="w-8 h-8 flex items-center justify-center rounded-full border border-[#E0E0E0] hover:border-[#FF4D00] hover:text-[#FF4D00] transition-colors text-[#808080]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M18 6 6 18M6 6l12 12" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div class="px-6 pt-5 pb-6 flex flex-col gap-5">

            {{-- Product selector (only shown when order has multiple distinct products) --}}
            @php $reviewableItems = $order->items->filter(fn($i) => $i->product_id)->unique('product_id'); @endphp
            @if ($reviewableItems->count() > 1)
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-[#364153] font-['Inter']">{{ __('Product') }}</label>
                <select wire:model="reviewProductId"
                    class="w-full border border-[#D1D5DC] rounded-xl px-4 py-2.5 text-sm text-[#0A0A0A] focus:outline-none focus:border-[#FF4D00] transition-colors bg-white">
                    @foreach ($reviewableItems as $rItem)
                    @php $rProduct = $rItem->product ?? $rItem->variant?->product; @endphp
                    <option value="{{ $rItem->product_id }}">
                        {{ $rProduct?->translationValue('name') ?? $rProduct?->slug ?? __('Item #:id', ['id' => $rItem->id]) }}
                    </option>
                    @endforeach
                </select>
                @error('reviewProductId') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            @else
            @php $singleItem = $reviewableItems->first(); $singleProduct = $singleItem?->product ?? $singleItem?->variant?->product; @endphp
            @if ($singleProduct)
            <div class="flex items-center gap-3 bg-[#F9F9F9] rounded-xl px-4 py-3">
                <div class="w-12 h-12 rounded-lg bg-[#F0F0F0] overflow-hidden flex-shrink-0">
                    @if ($singleProduct->primary_image_url)
                    <img loading="lazy" src="{{ $singleProduct->primary_image_url }}" alt="" class="w-full h-full object-cover">
                    @endif
                </div>
                <p class="text-sm font-medium text-[#0A0A0A] font-['Inter'] line-clamp-2">
                    {{ $singleProduct->translationValue('name') ?? $singleProduct->slug }}
                </p>
            </div>
            @endif
            @endif

            {{-- Star rating --}}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-[#364153] font-['Inter']">{{ __('Rating') }}</label>
                <div class="flex items-center gap-2">
                    @for ($s = 1; $s <= 5; $s++)
                    <button type="button"
                        @click="setStars({{ $s }})"
                        @mouseover="hover = {{ $s }}"
                        @mouseout="hover = 0"
                        class="focus:outline-none transition-transform hover:scale-110">
                        <svg class="w-9 h-9 transition-colors"
                            :class="(hover || stars) >= {{ $s }} ? 'text-yellow-400' : 'text-[#D1D5DC]'"
                            fill="currentColor" viewBox="0 0 24 24">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    </button>
                    @endfor
                    <span class="text-sm text-[#808080] font-['Inter'] ml-1" x-text="@js(['', __('Poor'), __('Fair'), __('Good'), __('Very Good'), __('Excellent')])[stars]"></span>
                </div>
                @error('reviewStars') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Comment --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-[#364153] font-['Inter']">
                    {{ __('Comment') }} <span class="text-[#ADADAD] font-normal">({{ __('optional') }})</span>
                </label>
                <textarea wire:model="reviewComment" rows="4"
                    placeholder="{{ __('Share your experience with this product...') }}"
                    maxlength="1000"
                    class="w-full border border-[#D1D5DC] rounded-xl px-4 py-3 text-sm text-[#0A0A0A] font-['Inter'] resize-none focus:outline-none focus:border-[#FF4D00] transition-colors placeholder:text-[#ADADAD]"></textarea>
                @error('reviewComment') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-1">
                <button wire:click="closeReviewModal" type="button"
                    class="flex-1 py-3 rounded-[32px] border border-[#D1D5DC] text-sm font-medium text-[#364153] hover:border-[#808080] hover:bg-gray-50 transition-colors font-['Inter']">
                    {{ __('Cancel') }}
                </button>
                <button wire:click="submitReview" type="button"
                    class="flex-1 py-3 rounded-[32px] bg-[#FF4D00] text-white text-sm font-medium hover:bg-orange-600 transition-colors font-['Inter']"
                    wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed"
                    wire:target="submitReview">
                    <span wire:loading.remove wire:target="submitReview">{{ __('Submit Review') }}</span>
                    <span wire:loading wire:target="submitReview">{{ __('Submitting...') }}</span>
                </button>
            </div>

        </div>
    </div>
</div>
    @endif

</div>{{-- /root --}}

@script
<script>
// ── SweetAlert2 toast for order-status page ───────────────────────────────
$wire.on('order-status-swal', (event) => {
    document.body.style.overflow = '';
    const pos = document.documentElement.getAttribute('dir') === 'rtl' ? 'top-start' : 'top-end';
    const colors = {
        success: '#2AAF2F',
        error:   '#dc2626',
        warning: '#f59e0b',
        info:    '#3b82f6',
    };
    Swal.mixin({
        toast: true,
        position: pos,
        showConfirmButton: false,
        showCloseButton: true,
        timer: 3500,
        timerProgressBar: true,
        background: colors[event.type] || colors.success,
        color: '#fff',
        customClass: {
            popup: 'shadow-xl rounded-xl',
            title: 'text-[13px] font-medium',
        },
    }).fire({ icon: event.type || 'success', title: event.message });
});
</script>
@endscript
