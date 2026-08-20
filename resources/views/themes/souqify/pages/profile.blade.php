<div>
    <main class="bg-zinc-100 pt-6 pb-16 w-full">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Sidebar -->
                <aside class="lg:col-span-3 hidden lg:block">
                    <div class="bg-white rounded-2xl border border-neutral-200 p-5 sticky top-4">
                        <div class="flex items-center gap-3 pb-4 mb-4 border-b border-neutral-100">
                            <div
                                class="w-12 h-12 rounded-full bg-blue-700 text-white flex items-center justify-center font-bold text-lg">
                                {{ strtoupper(substr($customer->full_name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-900 truncate">
                                    {{ $customer->full_name ?? __('User') }}
                                </p>
                                <p class="text-xs text-neutral-500 truncate">{{ $customer->email }}</p>
                            </div>
                        </div>
                        <nav class="space-y-1">
                            <button type="button" wire:click="setTab('orders')"
                                class="w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium transition flex items-center gap-3
                                    {{ $activeTab === 'orders' ? 'bg-blue-700 text-white' : 'text-neutral-700 hover:bg-blue-50 hover:text-blue-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                {{ __('My Orders') }}
                            </button>
                            <button type="button" wire:click="setTab('profile')"
                                class="w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium transition flex items-center gap-3
                                    {{ $activeTab === 'profile' ? 'bg-blue-700 text-white' : 'text-neutral-700 hover:bg-blue-50 hover:text-blue-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ __('Profile') }}
                            </button>
                            <button type="button" wire:click="setTab('wishlist')"
                                class="w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium transition flex items-center gap-3
                                    {{ $activeTab === 'wishlist' ? 'bg-blue-700 text-white' : 'text-neutral-700 hover:bg-blue-50 hover:text-blue-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                                {{ __('Wishlist') }}
                            </button>
                            <button type="button" wire:click="setTab('returns')"
                                class="w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium transition flex items-center gap-3
                                    {{ $activeTab === 'returns' ? 'bg-blue-700 text-white' : 'text-neutral-700 hover:bg-blue-50 hover:text-blue-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                </svg>
                                {{ __('Returns') }}
                            </button>
                            <button type="button" wire:click="logout"
                                wire:confirm="{{ __('Are you sure you want to log out?') }}"
                                class="w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition flex items-center gap-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                {{ __('Logout') }}
                            </button>
                        </nav>
                    </div>
                </aside>

                <!-- Content -->
                <div class="lg:col-span-9">

                    {{-- Mobile: user card + tab nav (hidden on lg+) --}}
                    <div class="lg:hidden mb-5 space-y-3">
                        {{-- User info card --}}
                        <div class="bg-white rounded-2xl border border-neutral-200 px-4 py-3 flex items-center gap-3">
                            <div class="w-11 h-11 rounded-full bg-blue-700 text-white flex items-center justify-center font-bold text-base shrink-0">
                                {{ strtoupper(substr($customer->full_name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-900 truncate">{{ $customer->full_name ?? __('User') }}</p>
                                <p class="text-xs text-neutral-500 truncate">{{ $customer->email }}</p>
                            </div>
                            <button type="button" wire:click="logout"
                                wire:confirm="{{ __('Are you sure you want to log out?') }}"
                                class="shrink-0 p-2 rounded-lg text-red-500 hover:bg-red-50 transition" title="{{ __('Logout') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </button>
                        </div>
                        {{-- Tab bar --}}
                        <div class="bg-white rounded-2xl border border-neutral-200 p-1.5 flex gap-1">
                            <button wire:click="setTab('orders')"
                                class="flex-1 flex flex-col items-center gap-1 py-2.5 px-1 rounded-xl text-xs font-semibold transition
                                    {{ $activeTab === 'orders' ? 'bg-blue-700 text-white shadow-sm' : 'text-neutral-500 hover:text-blue-700 hover:bg-blue-50' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                {{ __('Orders') }}
                            </button>
                            <button wire:click="setTab('profile')"
                                class="flex-1 flex flex-col items-center gap-1 py-2.5 px-1 rounded-xl text-xs font-semibold transition
                                    {{ $activeTab === 'profile' ? 'bg-blue-700 text-white shadow-sm' : 'text-neutral-500 hover:text-blue-700 hover:bg-blue-50' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ __('Profile') }}
                            </button>
                            <button wire:click="setTab('wishlist')"
                                class="flex-1 flex flex-col items-center gap-1 py-2.5 px-1 rounded-xl text-xs font-semibold transition
                                    {{ $activeTab === 'wishlist' ? 'bg-blue-700 text-white shadow-sm' : 'text-neutral-500 hover:text-blue-700 hover:bg-blue-50' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                                {{ __('Wishlist') }}
                            </button>
                            <button wire:click="setTab('returns')"
                                class="flex-1 flex flex-col items-center gap-1 py-2.5 px-1 rounded-xl text-xs font-semibold transition
                                    {{ $activeTab === 'returns' ? 'bg-blue-700 text-white shadow-sm' : 'text-neutral-500 hover:text-blue-700 hover:bg-blue-50' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                </svg>
                                {{ __('Returns') }}
                            </button>
                        </div>
                    </div>

                    @if ($activeTab === 'orders')

                    {{-- Page header --}}
                    <div class="flex flex-col gap-2 mb-5">
                        <div class="flex items-center gap-1">
                            <a href="{{ route('tenant.home') }}"
                                class="text-sm text-zinc-500 font-['Outfit'] tracking-wide hover:text-zinc-700 transition">{{ __('Home') }}</a>
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                class="text-zinc-900">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                            <span class="text-sm text-zinc-900 font-['Outfit'] tracking-wide">{{ __('Orders') }}</span>
                        </div>
                        <h1 class="text-[40px] leading-[50px] font-medium text-[#242424] font-['Outfit']">
                            {{ __('My Orders') }}
                        </h1>
                    </div>

                    {{-- Search bar --}}
                    <div class="relative mb-5">
                        <input type="text" id="souqifyOrderSearch"
                            placeholder="{{ __('Item name / Order ID / Tracking No.') }}"
                            oninput="souqifySearchOrders()"
                            class="w-full px-4 py-3 bg-white rounded-3xl border border-neutral-300 text-sm text-neutral-800 font-['Outfit'] placeholder-neutral-400 outline-none focus:border-neutral-400 transition pr-12" />
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 shrink-0 pointer-events-none">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                        </svg>
                    </div>

                    {{-- Status filter chips --}}
                    <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap no-scrollbar mb-6">
                        <button type="button" wire:click="filterStatus(null)"
                            class="px-4 py-1.5 rounded-full border text-xs transition shrink-0
                                    {{ !$statusFilter ? 'border-blue-700 bg-blue-700 text-white font-semibold' : 'border-neutral-200 text-neutral-600 hover:border-blue-700' }}">
                            {{ __('All') }}
                        </button>
                        @foreach (\App\Enums\OrderStatus::cases() as $st)
                        <button type="button" wire:click="filterStatus('{{ $st->value }}')"
                            class="px-4 py-1.5 rounded-full border text-xs transition shrink-0 capitalize
                                        {{ $statusFilter === $st->value ? 'border-blue-700 bg-blue-700 text-white font-semibold' : 'border-neutral-200 text-neutral-600 hover:border-blue-700' }}">
                            {{ str_replace('_', ' ', $st->value) }}
                        </button>
                        @endforeach
                    </div>

                    @if ($orders->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach ($orders as $order)
                        @php
                        $statusVal = $order->status?->value ?? 'pending';
                        $firstItem = $order->items->first();
                        $productName = $firstItem?->product?->translationValue('name')
                        ?? $firstItem?->product?->slug
                        ?? __('Product');
                        $productImage = $firstItem?->variant?->thumbnail_url
                        ?? $firstItem?->product?->primary_image_url
                        ?? null;
                        $orderTotal = '$' . number_format((float) ($order->grand_total ?? 0), 2);
                        $paymentMethod = $order->payment_method ?? $order->gateway ?? '—';
                        $itemCount = $order->items->count();

                        $statusConfig = match(true) {
                        $statusVal === 'delivered' => ['bg' => 'bg-green-100 border border-green-200', 'text' =>
                        'text-green-700', 'label' => __('Delivered')],
                        in_array($statusVal, ['shipped']) => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label'
                        => __('Shipped')],
                        in_array($statusVal, ['pending', 'paid', 'processing']) => ['bg' => 'bg-yellow-100', 'text' =>
                        'text-yellow-700', 'label' => ucfirst($statusVal)],
                        $statusVal === 'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'label' =>
                        __('Cancelled')],
                        default => ['bg' => 'bg-neutral-100', 'text' => 'text-neutral-700', 'label' =>
                        ucfirst($statusVal)],
                        };

                        $isDelivered = $statusVal === 'delivered';
                        $isShipped = $statusVal === 'shipped';
                        $isCancellable = in_array($statusVal, ['pending', 'paid', 'processing']);
                        $isCancelled = $statusVal === 'cancelled';
                        @endphp

                        <div class="souqify-order-card bg-white border border-gray-200 rounded-[16.4px] px-3 py-4 flex flex-col gap-4"
                            data-order="{{ strtolower($order->order_number ?? ('ORD-' . str_pad($order->id, 7, '0', STR_PAD_LEFT))) }}"
                            data-uuid="{{ strtolower($order->uuid ?? '') }}" data-name="{{ strtolower($productName) }}"
                            data-date="{{ strtolower($order->created_at?->format('M d, Y') ?? '') }}"
                            data-status="{{ strtolower($statusVal) }}">

                            {{-- Card header --}}
                            <div class="border-b border-gray-100 px-1 pt-1 pb-1">
                                <div class="flex justify-between items-start">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-sm text-neutral-800 font-['Outfit'] tracking-wide">
                                            {{ $order->order_number ?? ('ORD-' . str_pad($order->id, 7, '0', STR_PAD_LEFT)) }}
                                        </span>
                                        <span class="text-xs text-zinc-500 font-['Outfit'] tracking-wide">
                                            {{ $order->created_at?->format('M d, Y') }}
                                        </span>
                                    </div>
                                    <div
                                        class="h-9 px-3 rounded-full flex items-center gap-2 {{ $statusConfig['bg'] }}">
                                        <span class="text-sm font-['Outfit'] tracking-wide {{ $statusConfig['text'] }}">
                                            {{ $statusConfig['label'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Product preview --}}
                            <div class="flex items-start gap-3 px-1">
                                @if ($productImage)
                                <img loading="lazy" src="{{ $productImage }}" alt="{{ $productName }}"
                                    class="w-20 h-20 rounded-[10px] border border-gray-200 object-cover shrink-0" />
                                @else
                                <div
                                    class="w-20 h-20 rounded-[10px] border border-gray-200 bg-neutral-100 flex items-center justify-center shrink-0">
                                    <svg class="w-8 h-8 text-neutral-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                @endif
                                <div class="flex-1 flex flex-col gap-1 min-w-0">
                                    <p class="text-base text-neutral-800 font-['Outfit'] truncate">{{ $productName }}
                                    </p>
                                    <p class="text-xs text-zinc-500 font-['Outfit'] tracking-wide">
                                        {{ $paymentMethod }}
                                        @if ($itemCount > 1)
                                        &middot; {{ $itemCount }} {{ __('items') }}
                                        @endif
                                    </p>
                                    <p class="text-base text-neutral-800 font-['Outfit']">{{ $orderTotal }}</p>
                                    <a href="{{ route('tenant.storefront.order-status', $order->uuid ?? $order->id) }}" class="lg:hidden text-[#0159ED] font-['Outfit'] flex items-center justify-end"><svg width="19" height="15" viewBox="0 0 19 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1.8125 10.1633C1.10417 9.24333 0.75 8.7825 0.75 7.41667C0.75 6.05 1.10417 5.59083 1.8125 4.67C3.22667 2.83333 5.59833 0.75 9.08333 0.75C12.5683 0.75 14.94 2.83333 16.3542 4.67C17.0625 5.59167 17.4167 6.05083 17.4167 7.41667C17.4167 8.78333 17.0625 9.2425 16.3542 10.1633C14.94 12 12.5683 14.0833 9.08333 14.0833C5.59833 14.0833 3.22667 12 1.8125 10.1633Z" stroke="#0159ED" stroke-width="1.5" />
                                            <path d="M11.5859 7.41699C11.5859 8.08003 11.3225 8.71592 10.8537 9.18476C10.3849 9.6536 9.74898 9.91699 9.08594 9.91699C8.4229 9.91699 7.78701 9.6536 7.31817 9.18476C6.84933 8.71592 6.58594 8.08003 6.58594 7.41699C6.58594 6.75395 6.84933 6.11807 7.31817 5.64922C7.78701 5.18038 8.4229 4.91699 9.08594 4.91699C9.74898 4.91699 10.3849 5.18038 10.8537 5.64922C11.3225 6.11807 11.5859 6.75395 11.5859 7.41699Z" stroke="#0159ED" stroke-width="1.5" />
                                        </svg>
                                        {{ __('View') }}
                                    </a>
                                </div>
                            </div>

                            {{-- Action buttons --}}
                            <div class="hidden lg:flex flex-col gap-3">

                                {{-- View Details --}}
                                <a href="{{ route('tenant.storefront.order-status', $order->uuid ?? $order->id) }}"
                                    class="w-full py-3 bg-[#242424] rounded-[40px] flex items-center justify-center gap-2 text-white text-base font-['Outfit']">
                                    {{ __('View Details') }}
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>

                                @if ($isCancellable)
                                {{-- Cancel Order --}}
                                <button type="button" wire:click="cancelOrder('{{ $order->uuid }}')"
                                    wire:confirm="{{ __('This action cannot be undone. Are you sure you want to cancel this order?') }}"
                                    class="w-full py-3 border border-red-600 rounded-[40px] flex items-center justify-center gap-2 text-red-600 text-base font-['Outfit'] hover:bg-red-50 transition">
                                    <svg width="14" height="16" viewBox="0 0 14 16" fill="currentColor">
                                        <path
                                            d="M1 4h12M5 4V2.667A.667.667 0 0 1 5.667 2h2.666A.667.667 0 0 1 9 2.667V4M2.333 4l.667 9.333A.667.667 0 0 0 3.667 14h6.666a.667.667 0 0 0 .667-.667L11.667 4" />
                                    </svg>
                                    {{ __('Cancel order') }}
                                </button>
                                @else
                                {{-- Track Order --}}
                                <a href="{{ route('tenant.storefront.order-tracking', $order->uuid ?? $order->id) }}"
                                    class="w-full py-3 border border-stone-300 rounded-[40px] flex items-center justify-center gap-2 text-neutral-800 text-base font-['Outfit']">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" />
                                    </svg>
                                    {{ __('Track Order') }}
                                </a>
                                @endif

                                {{-- Reorder --}}
                                <button type="button" wire:click="reorder('{{ $order->uuid }}')"
                                    wire:loading.attr="disabled" wire:target="reorder('{{ $order->uuid }}')"
                                    class="w-full py-3 border border-stone-300 rounded-[40px] flex items-center justify-center text-base font-['Outfit'] text-neutral-800 hover:border-neutral-500 transition">
                                    <span wire:loading.remove
                                        wire:target="reorder('{{ $order->uuid }}')">{{ __('Reorder') }}</span>
                                    <span wire:loading
                                        wire:target="reorder('{{ $order->uuid }}')">{{ __('Adding…') }}</span>
                                </button>


                                {{-- Leave a Review --}}
                                @php
                                $reviewProductId = $firstItem?->product?->id ?? null;
                                $reviewProductName = $firstItem?->product?->translationValue('name') ??
                                $firstItem?->product?->slug ?? '';
                                $alreadyReviewed = $reviewProductId && in_array($reviewProductId, $reviewedProductIds ??
                                []);
                                @endphp
                                @if ($alreadyReviewed)
                                <button type="button" disabled
                                    class="w-full py-3 border border-stone-200 rounded-[40px] flex items-center justify-center gap-2 text-base font-['Outfit'] text-stone-300 cursor-not-allowed">
                                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.719c-.783-.57-.38-1.81.588-1.81H7.03a1 1 0 00.951-.69l1.069-3.292z" />
                                    </svg>
                                    {{ __('Reviewed') }}
                                </button>
                                @else
                                <button type="button" @if ($reviewProductId)
                                    onclick="swalLeaveReview({{ $reviewProductId }}, @js($reviewProductName))" @endif
                                    class="w-full py-3 border border-stone-300 rounded-[40px] flex items-center justify-center text-base font-['Outfit'] {{ $reviewProductId ? 'text-neutral-800 hover:border-neutral-500 transition' : 'text-stone-300 pointer-events-none' }}">
                                    {{ __('Leave a Review') }}
                                </button>
                                @endif

                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="py-16 text-center text-neutral-500">
                        <svg width="131" height="131" viewBox="0 0 131 131" fill="none"
                            xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                            class="mx-auto">
                            <rect width="131" height="131" fill="url(#pattern0_180_12756)" />
                            <defs>
                                <pattern id="pattern0_180_12756" patternContentUnits="objectBoundingBox" width="1"
                                    height="1">
                                    <use xlink:href="#image0_180_12756" transform="scale(0.00666667)" />
                                </pattern>
                                <image id="image0_180_12756" width="150" height="150" preserveAspectRatio="none"
                                    xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJYAAACWCAYAAAA8AXHiAAAQAElEQVR4AeydB3wUZfrHfzO7s5vdTe+BEAgldARBkKZwIni208Oz3imHCnpWxBNUwHKCFRTRE5XD8zxB/Qtn97CAXawHIi2USCAJaaRuL/N/n4HByWbLJGRLNrsfZl/eed955553fvN9n/d539nwiH/iLRCCFogLKwSNGi8SiAsrroKQtEBcWCFp1nihcWHFNRCSFogLKyTNGi80LqyuooEwX2dcWGFu8K5yuriwusqdDvN1xoUV5gbvKqeLC6ur3OkwX2dcWGFu8K5yuriwusqdDvN1dllh7T/YLBaXNol7WBjmNj92utgOupywdpXUiyUVVtHhckEQtHCzkOK0Py6yjhN7lxFWcelRQmm0WjidLvgKRVEEiYtI1nFN3DVL6hLCIiJ53L8SSiZVoJAI1jUl0TFXHdPC2nPMhvJHqGD7iVxEMGrqBFOqmJiaI2bk9hZTs3qKqRk5YoIxRaS0+Na6BWJWWDv214naYzZUIDKRjRUo/enlD2PgsDFiZm5vZOb2hSk5G6kZ+UjOKERmXh/ExdVaVLQnZoUlCIJfWyoYqeT0F1evxPq3PoTVoYVWMMDjcYPnNdRuUshrdMgtGIKM7II4uaRW+fUrJoVFroRgJPKRfnyUKDDSbXj/Tbz66ivIzcvDmFNOQnZOtiQmEhc1H4UkMgqT0nvEyUWNoth4xf9j4r9kE7nY6M7XqE8mkZrwjfWvQUhIx6CiQqQmGzB54iipfXhGLBIThbSDQoozu4ui8e1YC8ScsIKRKFh6Q0MdPvjvWygrO4TGxkZs/PQrbPh4M9a+9rbUZCQiWUy0Q47T/41J6fEukRqCbTElLHIRtIdU327+Ep9s3IC7592Ca2dchuXLHoXVmdDKplKKSBYXhawdkWBIQnpGFv03vrEWiBlhkaj0CXrJk042UjAyyelnTxmD2ddcgdvn3owvNv+E2kYXnDAdFxWJibWT37jN2gTaGuurmI2mw28vukGaKqJjuvIWM8JqD6lWrVyBw1V1gMYEXqNHVdku1FQUo772EDxuhyQmEofLaaVAEpDDbgGJiMLamsMQddnQ54xH99FzkTV+KUptA3HX3Yu6vAc/ZoQlE6gtYb/+A5mAXNAJOoicgOTUXHigRVZ6IsaNOQljTi5CYX4qhg7ohfGjB+PsaZNw/eyrUVA4ANkjbsLg859Fz/F3IG/IdCSk9gTP8zCm9mL2WdlxchJJyfNPfjUarUoK7QJffDRfo9q6kYe8PcQaN2ESbp1zOxw2Ri2PDebmOmg1HJwuNxY/vAKLH3kaq158HcufeRH3P7gc8xcsxuV/uhq9ehZAMGYyonkkMXk8Hmi0ghTX6JhtBh2sNmsLP5ogCKDRKo1aqb4UkugopLjaa+0s+WJCWALzO7WFVMr8f5oxC8+uehmzrr0W8+ffhceWPonzzr8QH3/wLnZu/wk7d2xDyf492Fu8C+VlByUS2V1gghLYxrO4UwpJXEQst8sJMSEXNdVkc2lZuovZXr5DpU1IIiOyqQlJjJQvmkUWE8JS45fSBFjVMGLkGFx34+24+IqZOGPqOcjOZl2i6MFHn/0PdrsN//v+G3z7zZewWizSvaytLodEJgWpSFQkLg0jV5PYDZs2fdaCWJoA529r/WkVBpVHpItWkXV6YdGTqyRQe8mlPC4nrzu0TCC33Xw1hg4dwQQ3A5dcdhUGDBqMZnMTMrK6oar4v61IJYsrJW8YPvp8C6xWa0BidVS9iXzUDtFkw3V6YdGT29YnPlj+wUOG44fvvvZJHEHQo6x0v0QuIpQsJg0TojLOdb8Y111/I2pra32Wo+lAgsnXo7ThpApG8KvTC8vpdPq1YdpLhG7du2PAwKHY8N4brYjTyDzzR6r2I7PvVIlYZFPJ4qJQjoscB7HXLMyZ/yDWrl0D8ui3tz7tOY4IFkFddf6fMRLYaEt+YjsyPPd3FyEzKwerV63AutdextYt3+Pdt9dj9fMr0H/oRLgd5qOjQC9SabzifMFleOUTM+bdeR8z/svY6FEbNoLRerRIiattxIpULQOcV2kbCScwOvRVzqnjJmLyb36Ld95+QxodJienYt5dD2DYkCLYGsskYskGgGY1fSmf1+4eSoaUWQP/lGdVxUDjLdMXSIyMTBWnpWNdMKFxRmaXs9bEBTXqP4HKPpE3TJDrJdS0FtAAAA==" />
                            </defs>
                        </svg>
                        <p class="mt-4 text-base text-[#242424]">{{ __('No orders yet!') }}</p>
                        <p class="text-base text-[#717171]">{{ __("You don't have any orders at the moment.") }}</p>
                        <a href="{{ route('tenant.home') }}"
                            class="inline-block mt-4 px-6 py-2 rounded-full bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold transition">
                            {{ __('Start Shopping') }}
                        </a>
                    </div>
                    @endif
@elseif ($activeTab === 'returns')
                    {{-- Returns tab --}}
                    <div class="mb-6">
                        <h1 class="text-[40px] leading-[50px] font-medium text-[#242424] font-['Outfit']">
                            {{ __('Return Requests') }}
                        </h1>
                    </div>

                    @if ($returnRequests->isEmpty())
                        <div class="text-center py-12 text-sm text-neutral-500">
                            {{ __('You have no return requests.') }}
                        </div>
                    @else
                        <div class="flex flex-col gap-3">
                            @foreach ($returnRequests as $ret)
                            @php
                                $statusColor = match($ret->status->color()) {
                                    'green' => '#16a34a', 'blue'  => '#2563eb',
                                    'red'   => '#dc2626', 'gray'  => '#6b7280',
                                    default => '#d97706',
                                };
                            @endphp
                            <div class="bg-white border border-neutral-200 rounded-xl p-4">
                                <div class="flex items-center justify-between flex-wrap gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ __('Order') }} #{{ $ret->order_number }}
                                        </div>
                                        <div class="text-xs text-neutral-500 mt-0.5">
                                            {{ $ret->reason->label() }} · {{ $ret->created_at?->format('M d, Y') }}
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold px-3 py-1 rounded-full"
                                        style="background:{{ $statusColor }}22;color:{{ $statusColor }}">
                                        {{ $ret->status->label() }}
                                    </span>
                                </div>
                                @if ($ret->refund_amount)
                                    <div class="text-xs text-neutral-600 mt-2">
                                        {{ __('Refund') }}: {{ number_format((float)$ret->refund_amount, 2) }}
                                    </div>
                                @endif
                                <div class="mt-3">
                                    <a href="{{ route('tenant.storefront.order-status', $ret->order_number) }}"
                                        class="text-xs font-medium underline text-neutral-500 hover:text-blue-700">
                                        {{ __('View Order') }}
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
@elseif ($activeTab === 'wishlist')
                    {{-- Wishlist tab --}}
                    <div class="flex items-end justify-between flex-wrap gap-3 mb-6">
                        <div>
                            <h1 class="text-[40px] leading-[50px] font-medium text-[#242424] font-['Outfit']">
                                {{ __('My Wishlist') }}
                            </h1>
                            <p class="text-sm text-neutral-500 mt-1">
                                <span id="souqify-fav-count">0</span> {{ __('items saved') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" id="souqify-fav-search" oninput="souqifyFavSearch(this.value)"
                                placeholder="{{ __('Search wishlist') }}"
                                class="h-10 px-4 rounded-full border border-neutral-200 text-sm bg-white focus:outline-none focus:border-blue-700" />
                            <select id="souqify-fav-sort" onchange="souqifyFavSort(this.value)"
                                class="h-10 px-4 rounded-full border border-neutral-200 text-sm bg-white focus:outline-none focus:border-blue-700">
                                <option value="recent">{{ __('Recently added') }}</option>
                                <option value="price-asc">{{ __('Price: Low to High') }}</option>
                                <option value="price-desc">{{ __('Price: High to Low') }}</option>
                                <option value="name">{{ __('Name A–Z') }}</option>
                            </select>
                        </div>
                    </div>

                    <div id="souqify-fav-grid"
                        class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4"></div>

                    <div id="souqify-fav-empty"
                        class="hidden bg-white border border-neutral-200 rounded-2xl py-16 text-center">
                        <svg class="w-20 h-20 mx-auto mb-4 text-neutral-300" fill="none" stroke="currentColor"
                            stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                        <h2 class="text-xl font-bold text-slate-900 mb-2">{{ __('Your wishlist is empty') }}</h2>
                        <p class="text-sm text-neutral-500 mb-5">
                            {{ __('Browse products and tap the heart icon to save your favorites.') }}
                        </p>
                        <a href="{{ route('tenant.home') }}"
                            class="inline-block px-7 py-3 rounded-full bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold transition">
                            {{ __('Start Shopping') }}
                        </a>
                    </div>
                    @else
                    {{-- Profile tab --}}
                    <div class="bg-white rounded-2xl border border-neutral-200 p-5 sm:p-6">
                        <h2 class="text-xl font-bold text-slate-900 mb-5">{{ __('Profile Information') }}</h2>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                            <div>
                                <dt class="text-xs font-semibold text-neutral-500 uppercase tracking-wide mb-1">
                                    {{ __('Full name') }}
                                </dt>
                                <dd class="text-slate-900">{{ $customer->full_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-neutral-500 uppercase tracking-wide mb-1">
                                    {{ __('Email') }}
                                </dt>
                                <dd class="text-slate-900">{{ $customer->email ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-neutral-500 uppercase tracking-wide mb-1">
                                    {{ __('Phone') }}
                                </dt>
                                <dd class="text-slate-900">{{ $customer->phone ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-neutral-500 uppercase tracking-wide mb-1">
                                    {{ __('Member since') }}
                                </dt>
                                <dd class="text-slate-900">{{ $customer->created_at?->format('M Y') ?? '—' }}</dd>
                            </div>
                        </dl>
                        {{-- Saved Addresses --}}
                        <div class="mt-8">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-base font-bold text-slate-900">{{ __('Saved Addresses') }}</h3>
                                <button type="button" wire:click="openAddressModal()"
                                    class="flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-blue-700 text-white text-xs font-semibold hover:bg-blue-800 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    {{ __('Add Address') }}
                                </button>
                            </div>
                            @if ($addresses->count() > 0)
                            <div class="space-y-3">
                                @foreach ($addresses as $addr)
                                <div class="border border-neutral-200 rounded-xl p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="text-sm font-semibold text-slate-900">{{ $addr->full_name }}</p>
                                                @if ($addr->label)
                                                <span class="px-2 py-0.5 bg-neutral-100 rounded text-xs text-neutral-500">{{ $addr->label }}</span>
                                                @endif
                                                @if ($addr->is_default)
                                                <span class="px-2 py-0.5 bg-amber-400 rounded text-xs font-bold text-slate-900">{{ __('Default') }}</span>
                                                @endif
                                            </div>
                                            @if ($addr->phone)
                                            <p class="text-xs text-neutral-600 mt-1">{{ $addr->phone }}</p>
                                            @endif
                                            <p class="text-xs text-neutral-600 mt-1">
                                                {{ collect([$addr->address_line_1, $addr->city, $addr->state, $addr->country])->filter()->implode(', ') }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-1 shrink-0">
                                            @if (!$addr->is_default)
                                            <button type="button" wire:click="setDefaultAddress({{ $addr->id }})"
                                                class="p-1.5 rounded-lg text-neutral-400 hover:text-amber-500 hover:bg-amber-50 transition" title="{{ __('Set as default') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                            </button>
                                            @endif
                                            <button type="button" wire:click="openAddressModal({{ $addr->id }})"
                                                class="p-1.5 rounded-lg text-neutral-400 hover:text-blue-600 hover:bg-blue-50 transition" title="{{ __('Edit') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                            <button type="button" wire:click="deleteAddress({{ $addr->id }})"
                                                wire:confirm="{{ __('Delete this address?') }}"
                                                class="p-1.5 rounded-lg text-neutral-400 hover:text-red-600 hover:bg-red-50 transition" title="{{ __('Delete') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <p class="text-sm text-neutral-400">{{ __('No saved addresses yet.') }}</p>
                            @endif
                        </div>

                        {{-- Address Modal --}}
                        @if ($showAddressModal)
                        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" wire:click.self="closeAddressModal">
                            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                                <div class="flex items-center justify-between mb-5">
                                    <h3 class="text-base font-bold text-slate-900">
                                        {{ $editingAddressId ? __('Edit Address') : __('New Address') }}
                                    </h3>
                                    <button type="button" wire:click="closeAddressModal" class="text-neutral-400 hover:text-neutral-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-xs font-semibold text-neutral-500 block mb-1">{{ __('Full Name') }} <span class="text-red-500">*</span></label>
                                            <input wire:model="addrFullName" type="text" class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="{{ __('Full Name') }}" />
                                            @error('addrFullName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-neutral-500 block mb-1">{{ __('Label') }}</label>
                                            <input wire:model="addrLabel" type="text" class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="{{ __('e.g. Home, Work') }}" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-neutral-500 block mb-1">{{ __('Phone') }}</label>
                                        <input wire:model="addrPhone" type="tel" data-phone-input class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="{{ __('Phone') }}" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-neutral-500 block mb-1">{{ __('Address') }} <span class="text-red-500">*</span></label>
                                        <input wire:model="addrLine1" type="text" class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="{{ __('Street address') }}" />
                                        @error('addrLine1') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-xs font-semibold text-neutral-500 block mb-1">{{ __('City') }} <span class="text-red-500">*</span></label>
                                            <input wire:model="addrCity" type="text" class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="{{ __('City') }}" />
                                            @error('addrCity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-neutral-500 block mb-1">{{ __('State / Region') }}</label>
                                            <input wire:model="addrState" type="text" class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="{{ __('State') }}" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-neutral-500 block mb-1">{{ __('Country') }} <span class="text-red-500">*</span></label>
                                        @include('themes.souqify.sections.country-select', [
                                            'wireModel' => 'addrCountryId',
                                            'countries' => $countries,
                                            'currentId' => $addrCountryId,
                                        ])
                                        @error('addrCountryId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <label class="flex items-center gap-2 cursor-pointer select-none">
                                        <input wire:model="addrIsDefault" type="checkbox" class="w-4 h-4 accent-blue-700" />
                                        <span class="text-sm text-neutral-700">{{ __('Set as default address') }}</span>
                                    </label>
                                </div>
                                <div class="flex gap-2 mt-5">
                                    <button type="button" wire:click="saveAddress"
                                        class="flex-1 py-2.5 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-full transition">
                                        {{ __('Save') }}
                                    </button>
                                    <button type="button" wire:click="closeAddressModal"
                                        class="flex-1 py-2.5 border border-neutral-200 text-neutral-700 text-sm font-semibold rounded-full hover:bg-neutral-50 transition">
                                        {{ __('Cancel') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    @endif

                </div>
            </div>
        </div>
    </main>
</div>

<script>
    document.addEventListener('livewire:init', function() {
        Livewire.on('profile-swal', function(event) {
            var payload = event && event[0] ? event[0] : event;
            if (typeof showStorefrontToast === 'function') {
                showStorefrontToast(payload.message, payload.type || 'success');
            }
        });
    });
</script>

@script
<script>
    // ── Wishlist ──────────────────────────────────────────────────────────────
    (function () {
        const FAV_KEY = 'souqify_favorites';
        let currentSort = 'recent';
        let currentSearch = '';

        function getFavs() {
            try { return JSON.parse(localStorage.getItem(FAV_KEY) || '[]'); } catch { return []; }
        }
        function saveFavs(list) { localStorage.setItem(FAV_KEY, JSON.stringify(list)); }

        function buildCard(item, idx) {
            const img = item.image
                ? `<img loading="lazy" src="${item.image}" alt="" class="w-full h-full object-contain p-4">`
                : `<div class="w-full h-full flex items-center justify-center text-5xl text-neutral-400">🛍️</div>`;
            const old = item.old_price
                ? `<span class="text-xs text-neutral-400 line-through">$${parseFloat(item.old_price).toFixed(2)}</span>`
                : '';
            const stars = (() => {
                let h = '';
                for (let i = 1; i <= 5; i++) {
                    h += `<svg class="w-3 h-3" fill="${i <= Math.round(item.rating || 0) ? '#FFE100' : '#E5E5E5'}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>`;
                }
                return h;
            })();
            return `<div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden hover:shadow-lg transition group flex flex-col">
                <a href="${item.url || '#'}" class="block relative aspect-square bg-zinc-50 overflow-hidden">${img}
                    <button type="button" onclick="event.preventDefault(); souqifyRemoveFav(${idx})"
                        class="absolute top-3 right-3 w-9 h-9 rounded-full bg-white shadow flex items-center justify-center hover:bg-red-50 transition" title="{{ __('Remove') }}">
                        <svg class="w-4 h-4" fill="#0159ED" stroke="#0159ED" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    </button>
                </a>
                <div class="p-3 flex flex-col gap-1.5">
                    <a href="${item.url || '#'}" class="text-sm font-semibold text-slate-900 line-clamp-2 hover:text-blue-700">${item.name || ''}</a>
                    <div class="flex items-center gap-0.5">${stars}</div>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-base font-bold text-blue-700">$${parseFloat(item.price || 0).toFixed(2)}</span>
                        ${old}
                    </div>
                </div>
            </div>`;
        }

        function renderWishlist() {
            const grid = document.getElementById('souqify-fav-grid');
            const empty = document.getElementById('souqify-fav-empty');
            const count = document.getElementById('souqify-fav-count');
            if (!grid) return;
            let favs = getFavs();
            if (currentSearch) favs = favs.filter(f => (f.name || '').toLowerCase().includes(currentSearch));
            if (currentSort === 'price-asc') favs.sort((a, b) => (a.price || 0) - (b.price || 0));
            if (currentSort === 'price-desc') favs.sort((a, b) => (b.price || 0) - (a.price || 0));
            if (currentSort === 'name') favs.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
            if (count) count.textContent = favs.length;
            if (favs.length === 0) {
                grid.innerHTML = '';
                empty.classList.remove('hidden');
            } else {
                grid.innerHTML = favs.map((item, i) => buildCard(item, i)).join('');
                empty.classList.add('hidden');
            }
        }

        window.souqifyRemoveFav = function (idx) {
            const favs = getFavs();
            favs.splice(idx, 1);
            saveFavs(favs);
            renderWishlist();
            window.dispatchEvent(new CustomEvent('storefront-toast', { detail: { type: 'info', message: '{{ __('Removed from favorites') }}' } }));
        };
        window.souqifyFavSearch = function (val) { currentSearch = (val || '').toLowerCase(); renderWishlist(); };
        window.souqifyFavSort = function (val) { currentSort = val; renderWishlist(); };

        document.addEventListener('livewire:navigated', renderWishlist);
        window.addEventListener('souqify-favorites-changed', renderWishlist);
        Livewire.hook('commit', ({ succeed }) => { succeed(() => { setTimeout(renderWishlist, 50); }); });
        renderWishlist();
    })();

    // ── Order search ──────────────────────────────────────────────────────────
    window.souqifySearchOrders = function() {
        const q = (document.getElementById('souqifyOrderSearch')?.value ?? '').toLowerCase().trim();
        const cards = document.querySelectorAll('.souqify-order-card');
        let visible = 0;
        cards.forEach(card => {
            const match = !q ||
                (card.dataset.order || '').includes(q) ||
                (card.dataset.uuid || '').includes(q) ||
                (card.dataset.name || '').includes(q) ||
                (card.dataset.date || '').includes(q) ||
                (card.dataset.status || '').includes(q);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });
    };
    window.swalLeaveReview = function(productId, productName) {
        if (!productId) return;
        Swal.fire({
            title: @js(__('Leave a Review')),
            html: `
            <p class="text-sm text-gray-500 mb-4">${productName ? productName : ''}</p>
            <div id="star-rating" class="flex justify-center gap-2 mb-4" style="font-size:2rem;cursor:pointer">
                <span data-value="1" style="color:#D1D5DB">&#9733;</span>
                <span data-value="2" style="color:#D1D5DB">&#9733;</span>
                <span data-value="3" style="color:#D1D5DB">&#9733;</span>
                <span data-value="4" style="color:#D1D5DB">&#9733;</span>
                <span data-value="5" style="color:#D1D5DB">&#9733;</span>
            </div>
            <textarea id="review-comment" class="swal2-textarea" placeholder="${@js(__('Write your comment (optional)'))}" style="resize:vertical;min-height:100px"></textarea>
        `,
            showCancelButton: true,
            confirmButtonColor: '#242424',
            cancelButtonColor: '#6b7280',
            confirmButtonText: @js(__('Submit Review')),
            cancelButtonText: @js(__('Cancel')),
            reverseButtons: true,
            didOpen: () => {
                let selected = 0;
                const stars = document.querySelectorAll('#star-rating span');
                stars.forEach(star => {
                    star.addEventListener('mouseover', () => {
                        const v = parseInt(star.dataset.value);
                        stars.forEach(s => s.style.color = parseInt(s.dataset.value) <= v ?
                            '#F59E0B' : '#D1D5DB');
                    });
                    star.addEventListener('mouseout', () => {
                        stars.forEach(s => s.style.color = parseInt(s.dataset.value) <=
                            selected ? '#F59E0B' : '#D1D5DB');
                    });
                    star.addEventListener('click', () => {
                        selected = parseInt(star.dataset.value);
                        stars.forEach(s => s.style.color = parseInt(s.dataset.value) <=
                            selected ? '#F59E0B' : '#D1D5DB');
                    });
                });
                window._reviewSelected = () => selected;
            },
            preConfirm: () => {
                const stars = window._reviewSelected ? window._reviewSelected() : 0;
                const comment = document.getElementById('review-comment').value;
                if (!stars || stars < 1) {
                    Swal.showValidationMessage(@js(__('Please select a star rating.')));
                    return false;
                }
                return {
                    stars,
                    comment
                };
            },
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                $wire.submitReview(productId, result.value.stars, result.value.comment);
            }
        });
    };
</script>
@endscript
