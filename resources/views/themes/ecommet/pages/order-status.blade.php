{{--
    Ecommet – Order status page
    $order  Order (with items, activities, paymentGateway)
--}}
@php
    use App\Enums\OrderStatus;

    $currency  = $currentCurrency ?? null;
    $symbol    = $currency?->symbol ?? '$';
    $rate      = (float) ($currency?->conversion_rate ?? 1.0);
    $fmt       = fn(float|int $v): string => $symbol . number_format($v * $rate, 2);

    $statusColor = match($order->status) {
        OrderStatus::Pending    => ['bg' => '#FFF82859', 'text' => '#b47f00', 'label' => __('Pending')],
        OrderStatus::Processing => ['bg' => '#E8F0FE',   'text' => '#1a56db', 'label' => __('Processing')],
        OrderStatus::Shipped    => ['bg' => '#E8F4FD',   'text' => '#0369a1', 'label' => __('Shipped')],
        OrderStatus::Delivered  => ['bg' => '#DCFCE7',   'text' => '#15803d', 'label' => __('Delivered')],
        OrderStatus::Completed  => ['bg' => '#DCFCE7',   'text' => '#15803d', 'label' => __('Completed')],
        OrderStatus::Cancelled  => ['bg' => '#FEE2E2',   'text' => '#dc2626', 'label' => __('Cancelled')],
        OrderStatus::Rejected   => ['bg' => '#FEE2E2',   'text' => '#dc2626', 'label' => __('Rejected')],
        default                 => ['bg' => '#F5F5F5',   'text' => '#666',    'label' => $order->status->label()],
    };

    $timeline = [
        OrderStatus::Pending->value    => ['icon' => 'clock',     'label' => __('Order Placed')],
        OrderStatus::Processing->value => ['icon' => 'settings',  'label' => __('Processing')],
        OrderStatus::Shipped->value    => ['icon' => 'truck',     'label' => __('Shipped')],
        OrderStatus::Delivered->value  => ['icon' => 'check',     'label' => __('Delivered')],
    ];
    $statusValue   = $order->status->value;
    $timelineOrder = array_keys($timeline);
    $activeIdx     = array_search($statusValue, $timelineOrder);
@endphp

<div class="bg-white pb-20">
    <div class="max-w-[1100px] mx-auto px-4 md:px-8 mt-4 md:mt-6">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 text-[13px] font-normal text-[#808080] mb-6">
            <a href="{{ route('tenant.home') }}"  class="hover:text-black transition">{{ __('Home') }}</a>
            <img loading="lazy" src="{{ asset('ecommet/assets/images/home/arrow-Breadcrumb.png') }}" class="w-[5px]" alt="">
            @auth('storefront')
                <a href="{{ route('tenant.storefront.profile') }}"  class="hover:text-black transition">{{ __('My Orders') }}</a>
                <img loading="lazy" src="{{ asset('ecommet/assets/images/home/arrow-Breadcrumb.png') }}" class="w-[5px]" alt="">
            @endauth
            <span class="text-[#242424] font-medium">{{ __('Order #:order', ['order' => $order->uuid]) }}</span>
        </div>

        {{-- Status Banner --}}
        <div class="rounded-xl px-5 py-4 mb-8 flex items-center gap-4"
             style="background-color: {{ $statusColor['bg'] }}">
            <div class="flex-1">
                <p class="text-[12px] font-medium text-[#666] mb-0.5">{{ __('Order Status') }}</p>
                <p class="text-[22px] font-bold" style="color: {{ $statusColor['text'] }}">
                    {{ $statusColor['label'] }}
                </p>
                <p class="text-[12px] text-[#888] mt-1">{{ __('Order #:order', ['order' => $order->uuid]) }}</p>
            </div>
            @if (in_array($order->status, [OrderStatus::Shipped]))
                <img loading="lazy" src="{{ asset('ecommet/assets/images/home/truck-tick.png') }}" alt="" class="w-14 h-14 opacity-60">
            @elseif (in_array($order->status, [OrderStatus::Delivered, OrderStatus::Completed]))
                <img loading="lazy" src="{{ asset('ecommet/assets/images/home/trueIcon.png') }}" alt="" class="w-14 h-14 opacity-60">
            @endif
        </div>

        {{-- Timeline (non-cancelled orders) --}}
        @if (!in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected]))
            <div class="flex items-center justify-between mb-10 overflow-x-auto no-scrollbar px-4">
                @foreach ($timelineOrder as $idx => $stepKey)
                    @php $isActive = $activeIdx !== false && $idx <= $activeIdx; @endphp
                    <div class="flex flex-col items-center gap-2 relative shrink-0">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center
                                    {{ $isActive ? 'bg-gray-darkest' : 'bg-[#e5e5e5]' }} transition">
                            @if ($stepKey === 'pending')
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 {{ $isActive ? 'text-white' : 'text-gray-medium' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2"/></svg>
                            @elseif ($stepKey === 'processing')
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 {{ $isActive ? 'text-white' : 'text-gray-medium' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            @elseif ($stepKey === 'shipped')
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 {{ $isActive ? 'text-white' : 'text-gray-medium' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l1 1h3M13 6h4.414a1 1 0 01.707.293L21 9.172A4 4 0 0122 11.828V15a1 1 0 01-1 1h-1"/></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 {{ $isActive ? 'text-white' : 'text-gray-medium' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </div>
                        <span class="text-[11px] font-medium {{ $isActive ? 'text-[#242424]' : 'text-gray-medium' }} text-center">
                            {{ $timeline[$stepKey]['label'] }}
                        </span>
                    </div>
                    @if (!$loop->last)
                        <div class="flex-1 h-[2px] {{ ($activeIdx !== false && $idx < $activeIdx) ? 'bg-gray-darkest' : 'bg-[#e5e5e5]' }} mx-2 mt-[-18px]"></div>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- Two columns --}}
        <div class="flex flex-col md:flex-row gap-8 items-start">

            {{-- Order items --}}
            <div class="flex-1 w-full">
                <h2 class="text-[16px] font-bold text-[#242424] mb-4">{{ __('Order Items') }}</h2>
                <div class="flex flex-col divide-y divide-[#f0f0f0]">
                    @foreach ($order->items as $item)
                        @php
                            $orderedProduct = $item->product ?? $item->variant?->product;
                            $orderedVariantTitle = $item->variant?->display_label;
                        @endphp
                        <div class="flex items-start gap-4 py-4">
                            <div class="w-[70px] h-[70px] bg-[#f5f5f5] shrink-0 rounded-lg overflow-hidden flex items-center justify-center">
                                @if ($orderedProduct?->primary_image_url)
                                    <img loading="lazy" src="{{ $orderedProduct->primary_image_url }}" alt="" class="w-[60px] h-[60px] object-contain">
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-[13px] font-medium text-[#242424] line-clamp-2">{{ $orderedProduct?->translationValue('name') ?? $orderedProduct?->slug ?? ('Item #' . $item->id) }}</p>
                                @if ($orderedVariantTitle)
                                    <p class="text-[11px] text-gray-medium mt-1">{{ $orderedVariantTitle }}</p>
                                @endif
                                <div class="flex items-center gap-3 mt-1 text-[12px] text-gray-medium">
                                    <span>{{ __('Qty: :qty', ['qty' => $item->qty]) }}</span>
                                    <span>×</span>
                                    <span class="font-medium text-[#242424]">{{ $fmt($item->price) }}</span>
                                </div>
                            </div>
                            <div class="text-[14px] font-bold text-[#242424] shrink-0">
                                {{ $fmt($item->sub_total) }}
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Activity timeline --}}
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
                                        <p class="text-[11px] text-gray-medium">{{ $act->created_at?->format('M j, Y H:i') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Order summary --}}
            <div class="w-full md:w-[280px] shrink-0">
                <div class="bg-[#fafafa] rounded-xl p-5 border border-[#eee]">
                    <h3 class="text-[15px] font-bold text-[#242424] mb-4">{{ __('Order Summary') }}</h3>
                    <div class="flex flex-col gap-2 text-[13px]">
                        <div class="flex justify-between">
                            <span class="text-gray-medium">{{ __('Subtotal') }}</span>
                            <span class="font-medium">{{ $fmt($order->subtotal) }}</span>
                        </div>
                        @if ($order->discount_percentage > 0)
                            <div class="flex justify-between text-primary">
                                <span>{{ __('Discount (:percent%)', ['percent' => $order->discount_percentage]) }}</span>
                                <span>-{{ $fmt($order->discount_amount) }}</span>
                            </div>
                        @endif
                        @if ($order->tax_percentage > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-medium">{{ __('Tax (:percent%)', ['percent' => $order->tax_percentage]) }}</span>
                                <span>{{ $fmt($order->tax_amount) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-success">
                            <span>{{ __('Shipping') }}</span>
                            <span class="font-medium">{{ $order->resolved_shipping_charge > 0 ? $fmt($order->resolved_shipping_charge) : __('Free') }}</span>
                        </div>
                        <div class="flex justify-between text-[15px] font-bold border-t border-[#eee] pt-3 mt-1">
                            <span>{{ __('Total') }}</span>
                            <span>{{ $fmt($order->grand_total) }}</span>
                        </div>
                    </div>

                    <div class="border-t border-[#eee] mt-4 pt-4 text-[12px] text-gray-medium flex flex-col gap-1.5">
                        <div><span class="font-medium text-[#444]">{{ __('Payment:') }}</span> {{ $order->paymentGateway?->label ?? $order->payment_method ?? __('N/A') }}</div>
                        @if ($order->shipping_address)
                            <div><span class="font-medium text-[#444]">{{ __('Ship to:') }}</span> {{ $order->shipping_address['address'] ?? '' }}, {{ $order->shipping_address['name'] ?? '' }}</div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
