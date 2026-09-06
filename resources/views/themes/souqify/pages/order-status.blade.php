@php
    use App\Enums\OrderStatus;

    $currency = $currentCurrency ?? null;
    $symbol   = optional($currency)->symbol ?? '$';
    $rate     = (float) (optional($currency)->conversion_rate ?? 1.0);
    $fmt      = fn(float|int $v): string => $symbol . number_format($v * $rate, 2);

    $orderNumber = $order->order_number ?? ('ORD-' . str_pad($order->id, 7, '0', STR_PAD_LEFT));

    $isDelivered = in_array($order->status, [OrderStatus::Delivered, OrderStatus::Completed], true);
    $isCancelled = in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true);

    $statusBadge = match (true) {
        $isDelivered => ['cls' => 'bg-green-100 border border-green-200 text-green-700', 'dot' => 'bg-green-500', 'label' => __('Delivered')],
        $order->status === OrderStatus::Pending => ['cls' => 'bg-amber-100 border border-amber-200 text-amber-700', 'dot' => 'bg-amber-400', 'label' => __('Pending')],
        $order->status === OrderStatus::Processing => ['cls' => 'bg-blue-100 border border-blue-200 text-blue-700', 'dot' => 'bg-blue-500', 'label' => __('Processing')],
        $order->status === OrderStatus::Shipped => ['cls' => 'bg-sky-100 border border-sky-200 text-sky-700', 'dot' => 'bg-sky-500', 'label' => __('Shipped')],
        $isCancelled => ['cls' => 'bg-red-100 border border-red-200 text-red-700', 'dot' => 'bg-red-500', 'label' => __('Cancelled')],
        default => ['cls' => 'bg-neutral-100 border border-neutral-200 text-neutral-700', 'dot' => 'bg-neutral-400', 'label' => $order->status->label()],
    };

    $shippingAddr   = $order->shipping_address ?? [];
    $recipient      = $shippingAddr['name']    ?? null;
    $recipientPhone = $shippingAddr['phone']   ?? null;
    $recipientEmail = $shippingAddr['email']   ?? ($order->customer?->email ?? null);
    $addressLine    = implode(', ', array_filter([
        $shippingAddr['address'] ?? null,
        $shippingAddr['city']    ?? null,
        $shippingAddr['state']   ?? null,
        $shippingAddr['country'] ?? null,
    ]));
    $carrier        = $shippingAddr['carrier'] ?? null;
    $trackingNumber = null; // Tracking number is admin-only, not shown on storefront
    $customer       = auth('storefront')->user();
@endphp

<div>
    <main class="bg-zinc-100 pt-6 pb-16 w-full">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                {{-- ── Sidebar ──────────────────────────────────────────────── --}}
                @auth('storefront')
                    <aside class="lg:col-span-3">
                        <div class="bg-white rounded-2xl border border-neutral-200 p-5 sticky top-4">
                            <div class="flex items-center gap-3 pb-4 mb-4 border-b border-neutral-100">
                                <div class="w-12 h-12 rounded-full bg-blue-700 text-white flex items-center justify-center font-bold text-lg shrink-0">
                                    {{ strtoupper(substr($customer->full_name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-slate-900 truncate">{{ $customer->full_name ?? __('User') }}</p>
                                    <p class="text-xs text-neutral-500 truncate">{{ $customer->email }}</p>
                                </div>
                            </div>
                            <nav class="space-y-1">
                                <a href="{{ route('tenant.storefront.profile') }}"
                                    class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium bg-blue-700 text-white transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    {{ __('My Orders') }}
                                </a>
                                <a href="{{ route('tenant.storefront.profile') }}"
                                    class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-neutral-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    {{ __('Profile') }}
                                </a>
                            </nav>
                        </div>
                    </aside>
                @endauth

                {{-- ── Main content ─────────────────────────────────────────── --}}
                <div class="{{ auth('storefront')->check() ? 'lg:col-span-9' : 'lg:col-span-12' }}">

                    {{-- Header --}}
                    <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
                        <div class="flex items-center gap-4">
                            @auth('storefront')
                                <a href="{{ route('tenant.storefront.profile') }}"
                                    class="w-9 h-9 flex items-center justify-center rounded-[10px] border border-neutral-200 bg-white hover:border-neutral-400 transition shrink-0">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <path d="M10.0003 15.8337L4.16699 10.0003L10.0003 4.16699" stroke="#0A0A0A" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M15.8337 10H4.16699" stroke="#0A0A0A" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                            @endauth
                            <div>
                                <h1 class="text-2xl font-medium text-[#242424] font-['Outfit'] leading-[30px]">
                                    {{ __('Order #:num', ['num' => $orderNumber]) }}
                                </h1>
                                <p class="text-xs text-[#808080] font-['Outfit'] tracking-wide mt-0.5">
                                    {{ $order->created_at?->format('M j, Y') }}
                                </p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-2 h-9 px-4 rounded-full text-sm font-['Outfit'] tracking-wide {{ $statusBadge['cls'] }}">
                            @if ($isDelivered)
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.67" viewBox="0 0 20 20">
                                    <circle cx="10" cy="10" r="8"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 10.5l2 2 4-4"/>
                                </svg>
                            @else
                                <span class="w-2 h-2 rounded-full {{ $statusBadge['dot'] }} shrink-0"></span>
                            @endif
                            {{ $statusBadge['label'] }}
                        </span>
                    </div>
                    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">

                {{-- ── LEFT column ───────────────────────────────────────────── --}}
                <div class="xl:col-span-8 space-y-6">

                    {{-- Items Ordered --}}
                    <div class="bg-white border border-gray-200 rounded-[16.4px] px-3 py-4 flex flex-col gap-4">
                        <p class="text-sm text-neutral-800 font-['Outfit'] tracking-wide px-1">{{ __('Items Ordered') }}</p>

                        @foreach ($order->items as $item)
                            @php
                                $orderedProduct = $item->product ?? $item->variant?->product;
                                $orderedName    = $orderedProduct?->translationValue('name') ?? $orderedProduct?->slug ?? __('Item #:id', ['id' => $item->id]);
                                $variantLabel   = $item->variant?->display_label ?? null;
                                $paymentLabel   = $order->paymentGateway?->label ?? $order->payment_method ?? null;
                                $subInfo        = collect([$paymentLabel, $variantLabel])->filter()->implode(' · ');
                                $itemImage      = $item->variant?->thumbnail_url ?? $orderedProduct?->primary_image_url;
                            @endphp

                            @unless ($loop->first)
                                <div class="border-t border-gray-100"></div>
                            @endunless

                            <div class="flex items-start gap-3 px-4">
                                <div class="w-20 h-20 rounded-lg shrink-0 overflow-hidden border border-neutral-200 bg-zinc-100 flex items-center justify-center">
                                    @if ($itemImage)
                                        <img loading="lazy" src="{{ $itemImage }}" alt="{{ $orderedName }}" class="w-full h-full object-cover">
                                    @else
                                        <svg class="w-8 h-8 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1 flex flex-col gap-2 min-w-0">
                                    <div class="flex items-start justify-between gap-1.5">
                                        <span class="text-base text-neutral-800 font-['Outfit'] truncate">{{ $orderedName }}</span>
                                        <span class="text-base text-neutral-800 font-['Outfit'] shrink-0">{{ __('Qty: :qty', ['qty' => $item->qty]) }}</span>
                                    </div>
                                    @if ($subInfo)
                                        <p class="text-xs text-zinc-500 font-['Outfit'] tracking-wide">{{ $subInfo }}</p>
                                    @endif
                                    <p class="text-base text-neutral-800 font-['Outfit']">{{ $fmt($item->sub_total) }}</p>
                                    @if ($isDelivered)
                                        @include('livewire.tenant.storefront.partials.return-item-action')
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Shipping Information --}}
                    @if (!empty($shippingAddr))
                        <div class="bg-white border border-gray-200 rounded-[16.4px] px-6 py-[18px] flex flex-col gap-3">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-neutral-800 shrink-0" fill="none" stroke="currentColor" stroke-width="1.67" viewBox="0 0 20 20">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 2C7.24 2 5 4.24 5 7c0 4.25 5 11 5 11S15 11.25 15 7c0-2.76-2.24-5-5-5z"/>
                                    <circle cx="10" cy="7" r="2" stroke-linecap="round"/>
                                </svg>
                                <h2 class="text-base text-neutral-800 font-['Arial'] leading-6">{{ __('Shipping Information') }}</h2>
                            </div>

                            <div class="bg-[#F6F8FF] border border-neutral-200/60 rounded-lg px-4 py-6 flex flex-col gap-3">
                                @if ($recipient || $recipientPhone)
                                    <div class="flex items-center gap-12 flex-wrap">
                                        @if ($recipient)
                                            <span class="text-base font-semibold text-[#001537] font-['Outfit']">{{ $recipient }}</span>
                                        @endif
                                        @if ($recipientPhone)
                                            <span class="text-base text-[#767676] font-['Outfit']">{{ $recipientPhone }}</span>
                                        @endif
                                    </div>
                                @endif
                                @if ($recipientEmail)
                                    <p class="text-base text-[#767676] font-['Outfit']">{{ $recipientEmail }}</p>
                                @endif
                                @if ($addressLine)
                                    <p class="text-base text-[#767676] font-['Outfit']">{{ $addressLine }}</p>
                                @endif
                            </div>

                            @if ($carrier || $trackingNumber)
                                <div class="pt-3 border-t border-gray-100 flex flex-col gap-2">
                                    @if ($carrier)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-zinc-500 font-['Arial'] leading-5">{{ __('Carrier') }}</span>
                                            <span class="text-sm text-neutral-800 font-['Arial'] leading-5">{{ $carrier }}</span>
                                        </div>
                                    @endif
                                    @if ($trackingNumber)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-zinc-500 font-['Arial'] leading-5">{{ __('Tracking Number') }}</span>
                                            <span class="text-sm text-neutral-800 font-['Arial'] leading-5">{{ $trackingNumber }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Order Activity --}}
                    @if ($order->activities->isNotEmpty())
                        <div class="bg-white border border-gray-200 rounded-[16.4px] px-6 py-5">
                            <h2 class="text-base text-neutral-800 font-['Arial'] leading-6 mb-5">{{ __('Order Activity') }}</h2>
                            <ol class="relative border-s-2 border-neutral-200 ms-2 space-y-6">
                                @foreach ($order->activities as $act)
                                    <li class="ms-6">
                                        <span class="absolute -start-[7px] mt-1 w-3 h-3 rounded-full bg-blue-700 ring-4 ring-white"></span>
                                        <p class="text-sm font-semibold text-slate-900">{{ $act->description }}</p>
                                        <p class="text-xs text-neutral-500 mt-1">{{ $act->created_at?->format('M j, Y H:i') }}</p>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif

                </div>

                {{-- ── RIGHT column ──────────────────────────────────────────── --}}
                <div class="xl:col-span-4 flex flex-col gap-4">

                    {{-- Payment Summary --}}
                    <div class="bg-white border border-gray-200 rounded-[16.4px] px-[17px] pt-[17px] pb-px flex flex-col gap-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-neutral-800 shrink-0" fill="none" stroke="currentColor" stroke-width="1.67" viewBox="0 0 20 20">
                                <rect x="1.67" y="3.33" width="16.67" height="13.33" rx="1.33" stroke-linecap="round" stroke-linejoin="round"/>
                                <path stroke-linecap="round" d="M1.67 8.33h16.67"/>
                            </svg>
                            <h2 class="text-base text-neutral-800 font-['Arial'] leading-6">{{ __('Payment Summary') }}</h2>
                        </div>

                        <div class="flex flex-col gap-2 pb-3">
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-sm text-zinc-500 font-['Arial'] leading-5">{{ __('Subtotal') }}</span>
                                <span class="text-sm text-neutral-800 font-['Arial'] leading-5">{{ $fmt($order->subtotal) }}</span>
                            </div>
                            @if ($order->discount_percentage > 0)
                                <div class="flex items-start justify-between gap-3 text-red-600">
                                    <span class="text-sm font-['Arial'] leading-5">{{ __('Discount (:p%)', ['p' => $order->discount_percentage]) }}</span>
                                    <span class="text-sm font-['Arial'] leading-5">-{{ $fmt($order->discount_amount) }}</span>
                                </div>
                            @endif
                            @if ($order->tax_percentage > 0)
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-sm text-zinc-500 font-['Arial'] leading-5">{{ __('Tax') }}</span>
                                    <span class="text-sm text-neutral-800 font-['Arial'] leading-5">{{ $fmt($order->tax_amount) }}</span>
                                </div>
                            @endif
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-sm text-zinc-500 font-['Arial'] leading-5">{{ __('Shipping Fee') }}</span>
                                <span class="text-sm text-neutral-800 font-['Arial'] leading-5">
                                    {{ $order->resolved_shipping_charge > 0 ? $fmt($order->resolved_shipping_charge) : __('Free') }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between gap-3 pt-2 border-t border-gray-200">
                                <span class="text-base text-neutral-800 font-['Arial'] leading-6">{{ __('Total') }}</span>
                                <span class="text-base text-neutral-800 font-['Arial'] leading-6">{{ $fmt($order->grand_total) }}</span>
                            </div>
                            <div class="flex flex-col gap-1 pt-2 border-t border-gray-100">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-sm text-zinc-500 font-['Arial'] leading-5">{{ __('Payment Method') }}</span>
                                    <span class="text-sm text-neutral-800 font-['Arial'] leading-5 text-right">
                                        {{ $order->paymentGateway?->label ?? $order->payment_method ?? __('N/A') }}
                                    </span>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-sm text-zinc-500 font-['Arial'] leading-5">{{ __('Payment Status') }}</span>
                                    <span class="text-sm font-['Arial'] leading-5 {{ $order->paid ? 'text-green-600' : 'text-amber-600' }}">
                                        {{ $order->paid ? __('Paid') : __('Unpaid') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="pt-2 flex flex-col gap-4">

                        {{-- 1. Track Order --}}
                        @if (!$isCancelled)
                            <button type="button" onclick="window.open('{{ route('tenant.storefront.order-tracking', $order->uuid) }}', '_blank')"
                                class="w-full px-12 py-4 bg-[#242424] rounded-[32px] flex items-center justify-center gap-2 text-white text-base font-['Arial'] leading-6 hover:bg-black transition">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="white" stroke-width="1.67" viewBox="0 0 20 20">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M1 3h10v10H1zM11 7h4l2 4v3h-6V7z"/>
                                    <circle cx="4.5" cy="15" r="1.5" stroke-width="1.67"/>
                                    <circle cx="14.5" cy="15" r="1.5" stroke-width="1.67"/>
                                </svg>
                                {{ __('Track Order') }}
                            </button>
                        @endif

                        {{-- 2. Reorder --}}
                        <button type="button" wire:click="reorder"
                            wire:loading.attr="disabled" wire:target="reorder"
                            class="w-full px-12 py-4 bg-white border border-[#D1D5DC] rounded-[32px] flex items-center justify-center gap-2 text-neutral-800 text-base font-['Arial'] leading-6 hover:border-neutral-500 transition disabled:opacity-60">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.67" viewBox="0 0 20 20">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 2.5v5h5"/>
                                <path stroke-linecap="round" d="M2.5 7.5A8 8 0 1 1 4.7 14"/>
                            </svg>
                            <span wire:loading.remove wire:target="reorder">{{ __('Reorder') }}</span>
                            <span wire:loading wire:target="reorder">{{ __('Adding…') }}</span>
                        </button>

                        {{-- 3. Leave a Review --}}
                        @php
                            $reviewProduct   = $order->items->first()?->product ?? $order->items->first()?->variant?->product;
                            $reviewProductId = $reviewProduct?->id ?? null;
                            $alreadyReviewed = $reviewProductId && in_array($reviewProductId, $reviewedProductIds ?? []);
                        @endphp
                        @if ($alreadyReviewed)
                            <button type="button" disabled
                                class="w-full px-12 py-4 bg-transparent border border-neutral-200 rounded-[32px] flex items-center justify-center gap-2 text-neutral-400 text-base font-['Arial'] leading-6 cursor-not-allowed">
                                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.719c-.783-.57-.38-1.81.588-1.81H7.03a1 1 0 00.951-.69l1.069-3.292z"/>
                                </svg>
                                {{ __('Reviewed') }}
                            </button>
                        @else
                            <button type="button" wire:click="openReviewModal"
                                class="w-full px-12 py-4 bg-transparent border border-[#0159ED] rounded-[32px] flex items-center justify-center gap-2 text-[#0159ED] text-base font-['Arial'] leading-6 hover:bg-blue-50 transition">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="#0159ED" stroke-width="1.67" viewBox="0 0 20 20">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 1.67l2.47 5.32 5.86.85-4.24 4.13 1 5.84L10 15.17l-5.09 2.64 1-5.84L1.67 7.84l5.86-.85z"/>
                                </svg>
                                {{ __('Leave a Review') }}
                            </button>
                        @endif

                        {{-- 4. Download Invoice --}}
                        @auth('storefront')
                            <a href="{{ route('tenant.storefront.order-invoice', $order->uuid) }}"
                                target="_blank"
                                class="w-full px-12 py-4 bg-[#0159ED] rounded-[32px] flex items-center justify-center gap-2 text-white text-base font-['Arial'] leading-6 hover:bg-blue-800 transition">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="white" stroke-width="1.67" viewBox="0 0 20 20">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 2v9M6 8l4 4 4-4"/>
                                    <path stroke-linecap="round" d="M3 15v2a1.5 1.5 0 0 0 1.5 1.5h11A1.5 1.5 0 0 0 17 17v-2"/>
                                </svg>
                                {{ __('Download Invoice') }}
                            </a>
                        @else
                            <a href="{{ route('tenant.storefront.login') }}"
                                class="w-full px-12 py-4 bg-[#0159ED] rounded-[32px] flex items-center justify-center gap-2 text-white text-base font-['Arial'] leading-6 hover:bg-blue-800 transition">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="white" stroke-width="1.67" viewBox="0 0 20 20">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 2v9M6 8l4 4 4-4"/>
                                    <path stroke-linecap="round" d="M3 15v2a1.5 1.5 0 0 0 1.5 1.5h11A1.5 1.5 0 0 0 17 17v-2"/>
                                </svg>
                                {{ __('Download Invoice') }}
                            </a>
                        @endauth

                    </div>
                </div>

            </div>
        </div>
                @if ($showReviewModal)
                    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                         wire:click.self="closeReviewModal">
                        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 relative" wire:click.stop>

                            <button type="button" wire:click="closeReviewModal"
                                    class="absolute top-4 right-4 text-neutral-400 hover:text-neutral-600 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>

                            <h3 class="text-lg font-semibold text-[#242424] font-['Outfit'] mb-1">{{ __('Leave a Review') }}</h3>
                            <p class="text-sm text-neutral-500 mb-6 font-['Outfit']">{{ __('Share your experience with this order.') }}</p>

                            <div class="flex justify-center gap-2 mb-5">
                                @for ($s = 1; $s <= 5; $s++)
                                    <button type="button" wire:click="setReviewStars({{ $s }})"
                                            class="text-[2rem] leading-none transition {{ $s <= $reviewStars ? 'text-amber-400' : 'text-neutral-300' }} hover:text-amber-300">
                                        &#9733;
                                    </button>
                                @endfor
                            </div>

                            <textarea wire:model="reviewComment" rows="4"
                                      placeholder="{{ __('Write your comment (optional)') }}"
                                      class="w-full border border-neutral-200 rounded-xl px-4 py-3 text-sm text-neutral-800 placeholder-neutral-400 outline-none focus:border-blue-400 transition resize-none mb-5 font-['Outfit']">
                </textarea>

                            <div class="flex gap-3">
                                <button type="button" wire:click="closeReviewModal"
                                        class="flex-1 py-3 rounded-[32px] border border-neutral-200 text-neutral-700 text-sm font-semibold hover:bg-neutral-50 transition font-['Outfit']">
                                    {{ __('Cancel') }}
                                </button>
                                <button type="button" wire:click="submitReview"
                                        wire:loading.attr="disabled" wire:target="submitReview"
                                        class="flex-1 py-3 rounded-[32px] bg-[#0159ED] text-white text-sm font-semibold hover:bg-blue-800 transition disabled:opacity-60 font-['Outfit']">
                                    <span wire:loading.remove wire:target="submitReview">{{ __('Submit Review') }}</span>
                                    <span wire:loading wire:target="submitReview">{{ __('Submitting…') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
        @endif
    </main>
</div>

@script
<script>
$wire.on('order-status-swal', (event) => {
    const pos = document.documentElement.getAttribute('dir') === 'rtl' ? 'top-start' : 'top-end';
    const colors = {
        success: { bg: '#2AAF2F', color: '#fff' },
        error:   { bg: '#dc2626', color: '#fff' },
        warning: { bg: '#f59e0b', color: '#fff' },
        info:    { bg: '#3b82f6', color: '#fff' },
    };
    const scheme = colors[event.type] || colors.success;
    Swal.mixin({
        toast: true,
        position: pos,
        showConfirmButton: false,
        showCloseButton: true,
        timer: 3500,
        timerProgressBar: true,
        background: scheme.bg,
        color: scheme.color,
        customClass: { popup: 'shadow-xl rounded-xl', title: 'text-[13px] font-medium' },
    }).fire({ icon: event.type || 'success', title: event.message });
});
</script>
@endscript
