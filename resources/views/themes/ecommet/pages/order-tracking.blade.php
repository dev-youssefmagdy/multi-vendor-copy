{{--
    Ecommet – Order tracking page
    $order  Order (with items, activities, paymentGateway)
--}}
@php
    use App\Enums\OrderStatus;

    $currency  = $currentCurrency ?? null;
    $symbol    = $currency?->symbol ?? '$';
    $rate      = (float) ($currency?->conversion_rate ?? 1.0);
    $fmt       = fn(float|int $v): string => $symbol . number_format($v * $rate, 2);

    $carrier        = $order->shipping_address['carrier'] ?? null;
    $trackingNumber = null; // Tracking number is admin-only, not shown on storefront

    $stepDefinitions = [
        ['key' => 'placed',        'label' => __('Order Placed'),      'desc' => __('Your order has been placed successfully')],
        ['key' => 'confirmed',     'label' => __('Order Confirmed'),   'desc' => __('Your order has been confirmed')],
        ['key' => 'processing',    'label' => __('Processing'),        'desc' => __('Your order is being prepared')],
        ['key' => 'shipped',       'label' => __('Shipped'),           'desc' => __('Package has been handed to carrier')],
        ['key' => 'out_delivery',  'label' => __('Out for Delivery'),  'desc' => __('Package is out for delivery')],
        ['key' => 'delivered',     'label' => __('Delivered'),         'desc' => __('Package delivered successfully')],
    ];

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

    // Map each step index to the order status value that represents it
    $stepToStatus = [
        0 => OrderStatus::Pending->value,
        2 => OrderStatus::Processing->value,
        3 => OrderStatus::Shipped->value,
        5 => OrderStatus::Delivered->value,
    ];
    // Index activities by their recorded status for O(1) date lookup
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
            $stepDates[$i] = null;
        }
    }

    $expectedDelivery = $order->estimated_delivery_at ?? $order->created_at?->copy()->addDays(7);
    $firstItem        = $order->items->first();
    $firstProduct     = $firstItem?->product ?? $firstItem?->variant?->product;
@endphp

<div class="bg-white pb-20">
    <div class="max-w-[1100px] mx-auto px-4 md:px-8 mt-4 md:mt-6">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 text-[13px] font-normal text-[#808080] mb-6">
            <a href="{{ route('tenant.home') }}" class="hover:text-black transition">{{ __('Home') }}</a>
            <img loading="lazy" src="{{ asset('ecommet/assets/images/home/arrow-Breadcrumb.png') }}" class="w-[5px]" alt="">
            @auth('storefront')
                <a href="{{ route('tenant.storefront.profile') }}" class="hover:text-black transition">{{ __('My Orders') }}</a>
                <img loading="lazy" src="{{ asset('ecommet/assets/images/home/arrow-Breadcrumb.png') }}" class="w-[5px]" alt="">
            @endauth
            <a href="{{ route('tenant.storefront.order-status', $order->uuid) }}" class="hover:text-black transition">
                {{ __('Order #:order', ['order' => $order->uuid]) }}
            </a>
            <img loading="lazy" src="{{ asset('ecommet/assets/images/home/arrow-Breadcrumb.png') }}" class="w-[5px]" alt="">
            <span class="text-[#242424] font-medium">{{ __('Track Order') }}</span>
        </div>

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('tenant.storefront.order-status', $order->uuid) }}"
               class="w-9 h-9 flex items-center justify-center rounded-full border border-[#e5e5e5] hover:border-gray-darkest transition shrink-0">
                <svg class="w-4 h-4 text-[#555]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="m15 18-6-6 6-6"/>
                </svg>
            </a>
            <div>
                <h1 class="text-[20px] font-bold text-[#242424]">{{ __('Track Your Order') }}</h1>
                <p class="text-[12px] text-[#888]">{{ __('Order #:order', ['order' => $order->uuid]) }}</p>
            </div>
            <div class="ml-auto">
                <span class="text-[12px] font-semibold px-3 py-1 rounded-full bg-[#F0F4FF] text-[#1a56db]">
                    {{ $order->status->label() }}
                </span>
            </div>
        </div>

        {{-- Tracking meta --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8 bg-[#fafafa] rounded-xl p-5 border border-[#eee]">
            <div>
                <p class="text-[11px] text-[#888] mb-0.5">{{ __('Carrier') }}</p>
                <p class="text-[14px] font-semibold text-[#242424]">{{ $carrier ?? __('N/A') }}</p>
            </div>
            <div>
                <p class="text-[11px] text-[#888] mb-0.5">{{ __('Expected Delivery') }}</p>
                <p class="text-[14px] font-semibold text-[#242424]">{{ $expectedDelivery?->format('M j, Y') ?? __('N/A') }}</p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-8 items-start">

            {{-- Shipping Timeline --}}
            <div class="flex-1 w-full">
                <h2 class="text-[16px] font-bold text-[#242424] mb-5">{{ __('Shipping Timeline') }}</h2>

                <div class="flex flex-col">
                    @foreach ($stepDefinitions as $i => $step)
                    @php
                        $isDone    = $i < $currentStepIdx;
                        $isCurrent = $i === $currentStepIdx;
                        $isPending = $i > $currentStepIdx;
                        $isLast    = $loop->last;
                        $stepDate  = $stepDates[$i] ?? null;
                    @endphp
                    <div class="flex gap-3">
                        {{-- Icon + connector --}}
                        <div class="flex flex-col items-center shrink-0 w-8">
                            @if ($isDone || $isCurrent)
                                <div class="w-8 h-8 rounded-full bg-gray-darkest flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @else
                                <div class="w-8 h-8 rounded-full bg-[#e5e5e5] flex items-center justify-center shrink-0">
                                    <div class="w-3 h-3 rounded-full bg-[#b0b0b0]"></div>
                                </div>
                            @endif
                            @unless ($isLast)
                                <div class="w-[2px] flex-1 min-h-[24px] {{ $isDone ? 'bg-gray-darkest' : 'bg-[#e5e5e5]' }}"></div>
                            @endunless
                        </div>

                        {{-- Step content --}}
                        <div class="flex-1 min-w-0 {{ $isLast ? 'pb-0' : 'pb-5' }}">
                            @if ($isCurrent)
                                <div class="bg-[#f5f5f5] rounded-xl px-4 py-3">
                                    <div class="flex items-start justify-between gap-2 mb-1">
                                        <p class="text-[14px] font-bold text-[#242424]">{{ $step['label'] }}</p>
                                        @if ($stepDate)
                                            <div class="text-right shrink-0">
                                                <p class="text-[11px] text-[#888]">{{ $stepDate->format('M j, Y') }}</p>
                                                <p class="text-[10px] text-[#aaa]">{{ $stepDate->format('h:i A') }}</p>
                                            </div>
                                        @endif
                                    </div>
                                    <p class="text-[12px] text-[#888] mb-2">{{ $step['desc'] }}</p>
                                    <div class="bg-white border border-[#e5e5e5] rounded-lg px-3 py-2.5 flex gap-2 items-start">
                                        <svg class="w-4 h-4 shrink-0 mt-0.5 text-[#1a56db]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <rect x="1" y="3" width="15" height="13" rx="1"/>
                                            <path d="M16 8h4l3 4v4h-7V8z"/>
                                            <circle cx="5.5" cy="18.5" r="2.5"/>
                                            <circle cx="18.5" cy="18.5" r="2.5"/>
                                        </svg>
                                        <p class="text-[12px] text-[#444]">
                                            @if ($order->activities->isNotEmpty())
                                                {{ $order->activities->last()->description }}
                                            @else
                                                {{ __('Your order is currently :status. You will receive an update when the status changes.', ['status' => $order->status->label()]) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-[14px] font-semibold {{ $isPending ? 'text-[#b0b0b0]' : 'text-[#242424]' }}">{{ $step['label'] }}</p>
                                    @if ($stepDate)
                                        <div class="text-right shrink-0">
                                            <p class="text-[11px] text-[#888]">{{ $stepDate->format('M j, Y') }}</p>
                                            <p class="text-[10px] text-[#aaa]">{{ $stepDate->format('h:i A') }}</p>
                                        </div>
                                    @elseif ($isPending)
                                        <p class="text-[11px] text-[#b0b0b0] shrink-0">{{ __('Expected') }}</p>
                                    @endif
                                </div>
                                <p class="text-[12px] {{ $isPending ? 'text-[#c0c0c0]' : 'text-[#888]' }} mt-0.5">{{ $step['desc'] }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Activity log --}}
                @if ($order->activities->isNotEmpty())
                    <div class="mt-8">
                        <h2 class="text-[16px] font-bold text-[#242424] mb-4">{{ __('Order Activity') }}</h2>
                        <div class="flex flex-col gap-0 relative pl-5">
                            <div class="absolute left-[7px] top-2 bottom-2 w-[2px] bg-[#e5e5e5]"></div>
                            @foreach ($order->activities as $act)
                                <div class="flex items-start gap-3 py-3 relative">
                                    <div class="absolute left-[-14px] top-[14px] w-3 h-3 rounded-full bg-gray-darkest border-2 border-white"></div>
                                    <div>
                                        <p class="text-[13px] font-semibold text-[#242424]">{{ $act->description }}</p>
                                        <p class="text-[11px] text-[#888]">{{ $act->created_at?->format('M j, Y H:i') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="w-full md:w-[280px] shrink-0 flex flex-col gap-5">

                {{-- Product preview --}}
                <div class="bg-[#fafafa] rounded-xl p-5 border border-[#eee]">
                    <h3 class="text-[15px] font-bold text-[#242424] mb-4">{{ __('Product Details') }}</h3>
                    <div class="flex items-start gap-3">
                        <div class="w-[64px] h-[64px] bg-white rounded-lg border border-[#eee] shrink-0 overflow-hidden flex items-center justify-center">
                            @if ($firstProduct?->primary_image_url)
                                <img loading="lazy" src="{{ $firstProduct->primary_image_url }}" alt="" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-medium text-[#242424] line-clamp-2">
                                {{ $firstProduct?->translationValue('name') ?? $firstProduct?->slug ?? '' }}
                            </p>
                            <p class="text-[12px] text-[#888] mt-1">{{ __('Qty: :qty', ['qty' => $firstItem?->qty ?? 1]) }}</p>
                            <p class="text-[13px] font-bold text-[#242424] mt-1">{{ $fmt($firstItem?->sub_total ?? 0) }}</p>
                        </div>
                    </div>
                    @if ($order->items->count() > 1)
                        <p class="text-[11px] text-[#aaa] mt-3 pt-3 border-t border-[#eee]">
                            +{{ $order->items->count() - 1 }} {{ __('more item(s)') }}
                        </p>
                    @endif
                </div>

                {{-- Delivery Address --}}
                @if ($order->shipping_address)
                    <div class="bg-[#fafafa] rounded-xl p-5 border border-[#eee]">
                        <h3 class="text-[15px] font-bold text-[#242424] mb-3">{{ __('Delivery Address') }}</h3>
                        <div class="flex flex-col gap-1 text-[12px] text-[#666]">
                            @if (!empty($order->shipping_address['name']))
                                <p class="font-medium text-[#242424]">{{ $order->shipping_address['name'] }}</p>
                            @endif
                            @if (!empty($order->shipping_address['address']))
                                <p>{{ $order->shipping_address['address'] }}</p>
                            @endif
                            @if (!empty($order->shipping_address['city']))
                                <p>{{ $order->shipping_address['city'] }}{{ !empty($order->shipping_address['zip']) ? ', ' . $order->shipping_address['zip'] : '' }}</p>
                            @endif
                            @if (!empty($order->shipping_address['country']))
                                <p>{{ $order->shipping_address['country'] }}</p>
                            @endif
                            @if (!empty($order->shipping_address['phone']))
                                <p class="mt-1">{{ __('Phone:') }} {{ $order->shipping_address['phone'] }}</p>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Need Help --}}
                <div class="bg-[#F0F4FF] rounded-xl p-5 border border-[#d0dcf8]">
                    <h3 class="text-[15px] font-bold text-[#242424] mb-2">{{ __('Need Help?') }}</h3>
                    <p class="text-[12px] text-[#666] mb-3">{{ __('If you have any questions about your order, please contact our support team.') }}</p>
                    <a href="{{ route('website.contact') }}"
                       class="inline-block text-[12px] font-medium text-white bg-gray-darkest rounded-full px-4 py-2 hover:opacity-80 transition">
                        {{ __('Contact Support') }}
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
