{{--
    Elora – Order tracking page (Figma redesign)
    $order  Order (with items, activities, paymentGateway)
--}}
@push('body-attrs')data-page="client-tracking-order"@endpush

@php
    use App\Enums\OrderStatus;

    $currency  = $currentCurrency ?? null;
    $symbol    = $currency?->symbol ?? '$';
    $rate      = (float) ($currency?->conversion_rate ?? 1.0);
    $fmt       = fn(float|int $v): string => $symbol . number_format($v * $rate, 2);
    $customer  = auth('storefront')->user();

    $carrier        = $order->shipping_address['carrier'] ?? null;
    $trackingNumber = null; // Tracking number is admin-only, not shown on storefront

    // Timeline step definitions
    $stepDefinitions = [
        ['key' => 'placed',        'label' => __('Order Placed'),      'desc' => __('Your order has been placed successfully')],
        ['key' => 'confirmed',     'label' => __('Order Confirmed'),   'desc' => __('Your order has been confirmed')],
        ['key' => 'processing',    'label' => __('Processing'),        'desc' => __('Your order is being prepared')],
        ['key' => 'shipped',       'label' => __('Shipped'),           'desc' => __('Package has been handed to carrier')],
        ['key' => 'out_delivery',  'label' => __('Out for Delivery'),  'desc' => __('Package is out for delivery')],
        ['key' => 'delivered',     'label' => __('Delivered'),         'desc' => __('Package delivered successfully')],
    ];

    // Map order status to current step index (0-based, 6 steps total)
    $statusToStepIdx = [
        OrderStatus::Pending->value    => 0,
        OrderStatus::Processing->value => 2,
        OrderStatus::Shipped->value    => 3,
        OrderStatus::Delivered->value  => 5,
        OrderStatus::Completed->value  => 5,
        OrderStatus::Cancelled->value  => 0,
        OrderStatus::Rejected->value   => 0,
    ];
    $currentStepIdx = $statusToStepIdx[$order->status->value] ?? 0;

    // Location labels for each step
    $stepLocations = [
        'placed'       => $order->shipping_address['city'] ?? null,
        'confirmed'    => $order->shipping_address['city'] ?? null,
        'processing'   => __('Warehouse'),
        'shipped'      => __('Carrier Hub'),
        'out_delivery' => $order->shipping_address['city'] ?? null,
        'delivered'    => trim(($order->shipping_address['address'] ?? '') . (!empty($order->shipping_address['city']) ? ', ' . $order->shipping_address['city'] : ''), ', '),
    ];

    // Map each step index to the order status value that represents it
    $stepToStatus = [
        0 => OrderStatus::Pending->value,
        2 => OrderStatus::Processing->value,
        3 => OrderStatus::Shipped->value,
        5 => OrderStatus::Delivered->value,
    ];
    // Index activities by their recorded status for correct per-step date lookup
    $activityByStatus = $order->activities->whereNotNull('status')->groupBy('status');

    $stepDates = [];
    foreach ($stepDefinitions as $i => $step) {
        if ($i === 0) {
            $stepDates[$i] = $order->created_at;
        } elseif ($i <= $currentStepIdx) {
            $statusKey = $stepToStatus[$i] ?? null;
            $dateFromActivity = $statusKey
                ? ($activityByStatus->get($statusKey)?->first()?->created_at ?? null)
                : null;
            $stepDates[$i] = $dateFromActivity ?? null;
        } else {
            $stepDates[$i] = null; // pending – show "Expected"
        }
    }

    // First product for the sidebar
    $firstItem    = $order->items->first();
    $firstProduct = $firstItem?->product ?? $firstItem?->variant?->product;

    // Expected delivery
    $expectedDelivery = $order->estimated_delivery_at ?? $order->created_at?->copy()->addDays(7);
@endphp

<div>
{{-- Breadcrumb --}}
<div class="bg-white border-b border-gray-100">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-10 py-2.5 flex items-center gap-1 text-sm text-[#808080] flex-wrap">
        <a href="{{ route('tenant.home') }}" class="hover:text-main transition-colors">{{ __('Home') }}</a>
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
        @auth('storefront')
            <a href="{{ route('tenant.storefront.profile') }}" class="hover:text-main transition-colors">{{ __('Orders') }}</a>
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
        @endauth
        <a href="{{ route('tenant.storefront.order-status', $order->uuid) }}" class="hover:text-main transition-colors truncate max-w-[120px] sm:max-w-none">
            {{ __('Order #:uuid', ['uuid' => $order->uuid]) }}
        </a>
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
        <span class="text-[#1B1B1B] font-semibold">{{ __('Track Order') }}</span>
    </div>
</div>

<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-10 py-6 pb-16">
    <div class="flex gap-6 lg:gap-8 items-start">

        {{-- ════ LEFT PROFILE SIDEBAR — hidden on mobile/tablet, visible lg+ ════ --}}
        @auth('storefront')
        <div class="prof-sidebar flex-shrink-0 hidden lg:block" style="width:230px">
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

        {{-- ════ MAIN TRACKING CONTENT ════ --}}
        <div class="flex-1 min-w-0 flex flex-col gap-6">

            {{-- ── Top Header Card ── --}}
            <div class="bg-white border border-[#D4D4D4] rounded-2xl px-4 sm:px-6 pt-5 sm:pt-6">
                {{-- Title & status badge --}}
                <div class="flex items-start justify-between gap-3 mb-4 flex-wrap gap-y-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3 mb-1">
                            <a href="{{ route('tenant.storefront.order-status', $order->uuid) }}"
                                class="w-9 h-9 flex items-center justify-center rounded-full border border-[#E0E0E0] bg-white hover:border-main transition-colors flex-shrink-0">
                                <svg class="w-4 h-4 text-[#555]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="m15 18-6-6 6-6" stroke-linecap="round"/>
                                </svg>
                            </a>
                            <h1 class="text-xl sm:text-2xl font-semibold text-[#0A0A0A] font-['Inter'] leading-8">{{ __('Track Your Order') }}</h1>
                        </div>
                        <p class="text-sm sm:text-base text-[#4A5565] font-['Inter'] leading-6 ml-12 truncate">{{ __('Order #:uuid', ['uuid' => $order->uuid]) }}</p>
                    </div>
                    {{-- Status badge --}}
                    <div class="flex items-center gap-2 px-4 sm:px-6 py-2 sm:py-2.5 bg-[#FF4D00]/10 rounded-full flex-shrink-0">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="#FF4D00" stroke-width="1.67" viewBox="0 0 24 24">
                            <rect x="1" y="3" width="15" height="13" rx="1"/>
                            <path d="M16 8h4l3 4v4h-7V8z"/>
                            <circle cx="5.5" cy="18.5" r="2.5"/>
                            <circle cx="18.5" cy="18.5" r="2.5"/>
                        </svg>
                        <span class="text-xs sm:text-sm font-medium text-[#FF4D00] font-['Inter'] leading-5 whitespace-nowrap">{{ $order->status->label() }}</span>
                    </div>
                </div>
                {{-- Three info columns --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 pt-4 pb-4 border-t border-[#E5E7EB]">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs sm:text-sm text-[#6A7282] font-['Inter'] leading-5">{{ __('Carrier') }}</span>
                        <span class="text-sm font-medium text-[#0A0A0A] font-['Inter'] leading-5">{{ $carrier ?? __('N/A') }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-xs sm:text-sm text-[#6A7282] font-['Inter'] leading-5">{{ __('Expected Delivery') }}</span>
                        <span class="text-sm font-medium text-[#FF4D00] font-['Inter'] leading-5">{{ $expectedDelivery?->format('M j, Y') ?? __('N/A') }}</span>
                    </div>
                </div>
            </div>

            {{-- ── Two-column layout: Timeline + Sidebar (stacks on mobile) ── --}}
            <div class="flex flex-col xl:flex-row gap-6 items-start">

                {{-- LEFT: Shipping Timeline --}}
                <div class="w-full xl:flex-1 min-w-0 bg-white border border-[#D4D4D4] rounded-2xl px-4 sm:px-6 pt-5 sm:pt-6 pb-6">
                    <h2 class="text-base sm:text-lg font-semibold text-[#0A0A0A] font-['Inter'] leading-7 mb-5 sm:mb-6">{{ __('Shipping Timeline') }}</h2>

                    <div class="flex flex-col">
                        @foreach ($stepDefinitions as $i => $step)
                        @php
                            $isDone    = $i < $currentStepIdx;
                            $isCurrent = $i === $currentStepIdx;
                            $isPending = $i > $currentStepIdx;
                            $isLast    = $loop->last;
                            $stepDate  = $stepDates[$i] ?? null;
                            $location  = $stepLocations[$step['key']] ?? null;
                            $connectorOrange = ($i < $currentStepIdx);
                        @endphp

                        <div class="flex gap-3">

                            {{-- Icon column + connector line --}}
                            <div class="flex flex-col items-center flex-shrink-0 w-8">

                                @if ($isDone || $isCurrent)
                                <div class="w-8 h-8 rounded-full bg-[#FF4D00] border-2 border-[#FF4D00] flex items-center justify-center flex-shrink-0">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clip-path="url(#clip0_3_5550)">
                                            <path d="M18.1669 8.33357C18.5474 10.2013 18.2762 12.1431 17.3984 13.8351C16.5206 15.527 15.0893 16.8669 13.3431 17.6313C11.597 18.3957 9.64154 18.5384 7.80293 18.0355C5.96433 17.5327 4.35368 16.4147 3.23958 14.8681C2.12548 13.3214 1.57529 11.4396 1.68074 9.53639C1.78619 7.63318 2.54092 5.82364 3.81906 4.40954C5.0972 2.99545 6.8215 2.06226 8.7044 1.76561C10.5873 1.46897 12.515 1.82679 14.166 2.7794" stroke="white" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M7.5 9.16634L10 11.6663L18.3333 3.33301" fill="white"/>
                                            <path d="M7.5 9.16634L10 11.6663L18.3333 3.33301" stroke="white" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_3_5550">
                                                <rect width="20" height="20" fill="white"/>
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </div>
                                @else
                                <div class="w-8 h-8 rounded-full bg-white border-2 border-[#ADADAD] flex items-center justify-center flex-shrink-0">
                                    <div class="w-3 h-3 rounded-full bg-[#ADADAD]"></div>
                                </div>
                                @endif

                                @unless ($isLast)
                                <div class="w-0.5 flex-1 min-h-[24px]"
                                    style="background-color: {{ $connectorOrange ? '#FF4D00' : '#D4D4D4' }}"></div>
                                @endunless
                            </div>

                            {{-- Step content --}}
                            <div class="flex-1 min-w-0 {{ $isLast ? 'pb-0' : 'pb-5 sm:pb-6' }}">

                                @if ($isCurrent)
                                {{-- Current step: highlighted card --}}
                                <div class="bg-zinc-100/50 rounded-[10px] px-3 sm:px-4 py-3">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <h3 class="text-base sm:text-lg font-semibold text-[#101828] font-['Inter'] leading-7">{{ $step['label'] }}</h3>
                                        @if ($stepDate)
                                        <div class="text-right flex-shrink-0">
                                            <p class="text-xs sm:text-sm text-[#8F8F8F] font-['Inter'] leading-5">{{ $stepDate->format('M j, Y') }}</p>
                                            <p class="text-xs text-[#8F8F8F] font-['Inter'] leading-4">{{ $stepDate->format('h:i A') }}</p>
                                        </div>
                                        @endif
                                    </div>
                                    <p class="text-xs sm:text-sm text-[#8F8F8F] font-['Inter'] leading-5 mb-2">{{ $step['desc'] }}</p>
                                    @if ($location)
                                    <div class="flex items-center gap-1 mb-3">
                                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="#6A7282" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                            <circle cx="12" cy="10" r="3"/>
                                        </svg>
                                        <span class="text-xs text-[#6A7282] font-['Inter'] tracking-wide leading-4">{{ $location }}</span>
                                    </div>
                                    @else
                                    <div class="mb-3"></div>
                                    @endif
                                    {{-- Current Status info box --}}
                                    <div class="bg-[#FF4D00]/10 border border-[#FF4D00] rounded-[10px] px-3 py-3 flex gap-3">
                                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="#FF4D00" stroke-width="1.67" viewBox="0 0 24 24">
                                            <rect x="1" y="3" width="15" height="13" rx="1"/>
                                            <path d="M16 8h4l3 4v4h-7V8z"/>
                                            <circle cx="5.5" cy="18.5" r="2.5"/>
                                            <circle cx="18.5" cy="18.5" r="2.5"/>
                                        </svg>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-[#FF4D00] font-['Inter'] leading-5 mb-1">{{ __('Current Status') }}</p>
                                            <p class="text-xs text-[#364153] font-['Inter'] leading-4">
                                                @if ($order->activities->isNotEmpty())
                                                    {{ $order->activities->last()->description }}
                                                @else
                                                    {{ __('Your order is currently :status. You will receive an update when the status changes.', ['status' => $order->status->label()]) }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                @else
                                {{-- Done or pending step --}}
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="text-base sm:text-lg font-semibold font-['Inter'] leading-7 {{ $isPending ? 'text-[#8F8F8F]' : 'text-[#101828]' }}">
                                        {{ $step['label'] }}
                                    </h3>
                                    @if ($stepDate)
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-xs sm:text-sm text-[#8F8F8F] font-['Inter'] leading-5">{{ $stepDate->format('M j, Y') }}</p>
                                        <p class="text-xs text-[#8F8F8F] font-['Inter'] leading-4">{{ $stepDate->format('h:i A') }}</p>
                                    </div>
                                    @elseif ($isPending)
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-xs text-[#8F8F8F] font-['Inter'] leading-4">{{ __('Expected') }}</p>
                                    </div>
                                    @endif
                                </div>
                                <p class="text-xs sm:text-sm text-[#8F8F8F] font-['Inter'] leading-5 mt-1 mb-1">{{ $step['desc'] }}</p>
                                @if ($location)
                                <div class="flex items-center gap-1">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="none"
                                        stroke="{{ $isPending ? '#8F8F8F' : '#6A7282' }}" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span class="text-xs font-['Inter'] tracking-wide leading-4 {{ $isPending ? 'text-[#8F8F8F]' : 'text-[#6A7282]' }}">{{ $location }}</span>
                                </div>
                                @endif
                                @endif

                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- RIGHT: Sidebar cards (full-width on mobile, fixed on xl) --}}
                <div class="flex flex-col gap-5 sm:gap-6 w-full xl:w-[406px] xl:flex-shrink-0">

                    {{-- Delivery Address --}}
                    @if ($order->shipping_address)
                    <div class="bg-white border border-[#D4D4D4] rounded-2xl px-4 sm:px-6 pt-5 sm:pt-6 pb-5 sm:pb-6 flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="#364153" stroke-width="1.67" viewBox="0 0 24 24">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                            <h3 class="text-base sm:text-lg font-semibold text-[#0A0A0A] font-['Inter'] leading-7">{{ __('Delivery Address') }}</h3>
                        </div>
                        <div class="flex flex-col gap-1">
                            @if (!empty($order->shipping_address['name']))
                            <p class="text-sm font-medium text-[#101828] font-['Inter'] leading-5">{{ $order->shipping_address['name'] }}</p>
                            @endif
                            @if (!empty($order->shipping_address['address']))
                            <p class="text-sm text-[#4A5565] font-['Inter'] leading-5">{{ $order->shipping_address['address'] }}</p>
                            @endif
                            @if (!empty($order->shipping_address['city']))
                            <p class="text-sm text-[#4A5565] font-['Inter'] leading-5">
                                {{ $order->shipping_address['city'] }}{{ !empty($order->shipping_address['zip']) ? ', ' . $order->shipping_address['zip'] : '' }}
                            </p>
                            @endif
                            @if (!empty($order->shipping_address['country']))
                            <p class="text-sm text-[#4A5565] font-['Inter'] leading-5">{{ $order->shipping_address['country'] }}</p>
                            @endif
                            @if (!empty($order->shipping_address['phone']))
                            <p class="text-sm text-[#4A5565] font-['Inter'] leading-5 pt-2">{{ __('Phone:') }} {{ $order->shipping_address['phone'] }}</p>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Product Details --}}
                    <div class="bg-white border border-[#D4D4D4] rounded-2xl px-4 sm:px-6 pt-5 sm:pt-6 pb-5 sm:pb-6 flex flex-col gap-4">
                        <h3 class="text-base sm:text-lg font-semibold text-[#0A0A0A] font-['Inter'] leading-7">{{ __('Product Details') }}</h3>
                        <div class="flex items-start gap-3">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-lg flex-shrink-0 bg-[#F5F5F5] overflow-hidden">
                                @if ($firstProduct?->primary_image_url)
                                <img loading="lazy" src="{{ $firstProduct->primary_image_url }}" alt="" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="flex-1 min-w-0 flex flex-col gap-1">
                                <p class="text-sm sm:text-base font-medium text-[#0A0A0A] font-['Inter'] leading-6 line-clamp-2">
                                    {{ $firstProduct?->translationValue('name') ?? $firstProduct?->slug ?? '' }}
                                </p>
                                <p class="text-xs sm:text-sm text-[#4A5565] font-['Inter'] leading-5">{{ __('Qty:') }} {{ $firstItem?->qty ?? 1 }}</p>
                                <p class="text-sm sm:text-base font-semibold text-[#0A0A0A] font-['Inter'] leading-6">{{ $fmt($firstItem?->sub_total ?? 0) }}</p>
                            </div>
                        </div>
                        @if ($order->items->count() > 1)
                        <p class="text-xs text-[#ADADAD] pt-3 border-t border-[#F0F0F0]">
                            +{{ $order->items->count() - 1 }} {{ __('more item(s)') }}
                        </p>
                        @endif
                    </div>

                    {{-- Need Help --}}
                    <div class="rounded-2xl px-4 sm:px-5 py-5 sm:py-6 flex flex-col gap-3"
                        style="background: linear-gradient(135deg, #FFF7ED 0%, #FFEDD4 100%); border: 1px solid #FFD6A8; box-shadow: 0px 1px 3px rgba(0,0,0,0.1), 0px 1px 2px -1px rgba(0,0,0,0.1)">
                        <h3 class="text-base sm:text-lg font-semibold text-[#0A0A0A] font-['Inter'] leading-7">{{ __('Need Help?') }}</h3>
                        <p class="text-sm text-[#364153] font-['Inter'] leading-5">{{ __('If you have any questions about your order, please contact our support team.') }}</p>
                        <a href="{{ route('website.contact') }}" class="self-start text-sm font-medium text-main border border-[#FFAC88] bg-[#FFF5F2] rounded-full px-4 py-2 hover:bg-orange-100 transition-colors">
                            {{ __('Contact Support') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
