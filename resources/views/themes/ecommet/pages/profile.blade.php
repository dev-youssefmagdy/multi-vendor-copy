{{--
Ecommet – Customer Profile page
$customer Customer
$orders Collection<Order>
    $statusFilter string|null e.g. 'pending','processing','shipped','delivered','cancelled'
    $activeTab string 'orders'|'profile'
    --}}
@php
    use App\Enums\OrderStatus;

    $currency = $currentCurrency ?? null;
    $symbol   = $currency?->symbol ?? '$';
    $rate     = (float) ($currency?->conversion_rate ?? 1.0);
@endphp

    <div class="bg-white pb-20">
        <div class="max-w-[1400px] mx-auto px-4 md:px-8 mt-4 md:mt-6">

            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 text-[14px] font-normal text-[#808080] mb-6 md:mb-8">
                <a href="{{ route('tenant.home') }}"  class="hover:text-black transition">{{ __('Home') }}</a>
                <img loading="lazy" src="{{ asset('ecommet/assets/images/home/arrow-Breadcrumb.png') }}" class="w-[5px]" alt="">
                <span class="text-[#242424] font-medium">{{ __('Profile') }}</span>
            </div>

            <div class="flex flex-col md:flex-row gap-8 lg:gap-[50px] items-start">

                {{-- Sidebar --}}
                <div class="hidden md:flex w-full md:w-[260px] shrink-0 flex-col gap-2 tracking-wide">
                    <button wire:click="setTab('orders')"
                        class="flex items-center gap-4 px-4 py-3 text-[15px] font-semibold transition-colors rounded-md relative
                               {{ $activeTab === 'orders' ? 'bg-[#f7f7f7] text-[#111]' : 'hover:bg-gray-50 text-[#333] font-medium' }}">
                        @if ($activeTab === 'orders')
                            <div
                                class="absolute left-0 top-1/2 -translate-y-1/2 w-[3px] h-[70%] bg-[#242424] rounded-r-full">
                            </div>
                        @endif
                        <img loading="lazy" src="{{ asset('ecommet/assets/images/home/document.png') }}" class="w-[20px] ml-6" alt="">
                        {{ __('Your orders') }}
                    </button>
                    <button wire:click="setTab('profile')"
                        class="flex items-center gap-4 px-4 py-3 text-[15px] transition-colors rounded-md relative
                               {{ $activeTab === 'profile' ? 'bg-[#f7f7f7] text-[#111] font-semibold' : 'hover:bg-gray-50 text-[#333] font-medium' }}">
                        @if ($activeTab === 'profile')
                            <div
                                class="absolute left-0 top-1/2 -translate-y-1/2 w-[3px] h-[70%] bg-[#242424] rounded-r-full">
                            </div>
                        @endif
                        <img loading="lazy" src="{{ asset('ecommet/assets/images/home/user-2.png') }}" class="w-[20px] ml-6" alt="">
                        {{ __('Your Profile') }}
                    </button>
                    <button wire:click="logout"
                        class="flex items-center gap-4 px-4 py-3 text-[15px] font-medium text-red-600 hover:bg-red-50 transition-colors rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] ml-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        {{ __('Sign out') }}
                    </button>
                </div>

                {{-- Main content --}}
                <div class="flex-1 w-full flex flex-col gap-6 overflow-hidden">

                    {{-- Mobile tab nav (hidden on md+) --}}
                    <div class="flex items-center gap-2 md:hidden">
                        <button wire:click="setTab('orders')"
                            class="flex-1 py-2.5 text-[13px] font-semibold rounded-full border transition
                                   {{ $activeTab === 'orders' ? 'bg-[#242424] text-white border-[#242424]' : 'bg-white text-[#333] border-[#dcdcdc] hover:border-[#242424]' }}">
                            {{ __('My Orders') }}
                        </button>
                        <button wire:click="setTab('profile')"
                            class="flex-1 py-2.5 text-[13px] font-semibold rounded-full border transition
                                   {{ $activeTab === 'profile' ? 'bg-[#242424] text-white border-[#242424]' : 'bg-white text-[#333] border-[#dcdcdc] hover:border-[#242424]' }}">
                            {{ __('Profile & Addresses') }}
                        </button>
                    </div>

                    @if ($activeTab === 'orders')
                        {{-- Status filter pills --}}
                        <div class="flex items-center gap-1.5 overflow-x-auto w-full pb-1 no-scrollbar">
                            @foreach ([null => __('All'), 'pending' => __('Pending'), 'processing' => __('Processing'), 'shipped' => __('Shipped'), 'delivered' => __('Delivered'), 'cancelled' => __('Cancelled')] as $val => $label)
                                <button wire:click="filterStatus({{ $val === null ? 'null' : "'$val'" }})"
                                    class="px-5 py-2.5 min-w-[60px] cursor-pointer text-[13px] font-medium rounded-full shrink-0 transition
                                                   {{ ($statusFilter === $val) ? 'bg-gray-darkest text-white border border-gray-darkest shadow-sm' : 'bg-[#fafafa] border border-transparent text-[#242424] hover:border-[#eaeaea]' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Info banner --}}
                        <div class="w-full bg-[#FFF82859] rounded-lg px-4 py-3 flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#b47f00] shrink-0" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-[13px] text-[#000] font-normal">{{ __('View all your orders in one place, including active, completed, and cancelled orders.') }}</span>
                        </div>

                        {{-- Orders list --}}
                        @if ($orders->isEmpty())
                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                <img loading="lazy" src="{{ asset('ecommet/assets/images/home/document.png') }}" alt=""
                                    class="w-16 h-16 opacity-30 mb-4">
                                <p class="text-[15px] font-medium text-gray-medium">{{ __('No orders found') }}</p>
                            </div>
                        @else
                            <div class="flex flex-col divide-y divide-[#f0f0f0]">
                                @foreach ($orders as $order)
                                    @php
                                        $statusStyle = match ($order->status) {
                                            OrderStatus::Pending => 'bg-yellow-100 text-yellow-700',
                                            OrderStatus::Processing => 'bg-blue-100 text-blue-700',
                                            OrderStatus::Shipped => 'bg-sky-100 text-sky-700',
                                            OrderStatus::Delivered,
                                            OrderStatus::Completed => 'bg-green-100 text-green-700',
                                            OrderStatus::Cancelled,
                                            OrderStatus::Rejected => 'bg-red-100 text-red-600',
                                            default => 'bg-gray-100 text-gray-600',
                                        };
                                    @endphp
                                    <div class="flex items-start gap-4 py-5">
                                        {{-- First item image --}}
                                        <div
                                            class="w-[70px] h-[70px] bg-[#f5f5f5] shrink-0 rounded-lg overflow-hidden flex items-center justify-center">
                                            @php
                                                $firstItem = $order->items->first();
                                                $firstProduct = $firstItem?->product ?? $firstItem?->variant?->product;
                                            @endphp
                                            @if ($firstProduct?->primary_image_url)
                                                <img loading="lazy" src="{{ $firstProduct->primary_image_url }}" alt=""
                                                    class="w-[60px] h-[60px] object-contain">
                                            @endif
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                <span class="text-[13px] font-bold text-[#242424]">#{{ $order->uuid }}</span>
                                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $statusStyle }}">
                                                    {{ $order->status->label() }}
                                                </span>
                                            </div>
                                            <p class="text-[12px] text-gray-medium mb-1">
                                                {{ __(':count item(s)', ['count' => $order->items->count()]) }} · {{ $order->created_at?->format('M j, Y') }}
                                            </p>
                                            <p class="text-[14px] font-bold text-[#242424]">
                                                {{ $symbol }}{{ number_format($order->grand_total * $rate, 2) }}</p>
                                        </div>

                                        <a href="{{ route('tenant.storefront.order-status', $order->uuid) }}"
                                            class="shrink-0 text-[12px] font-semibold text-[#242424] border border-[#dcdcdc] hover:border-[#242424] transition rounded-full px-4 py-1.5">
                                            {{ __('View') }}
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                    @else
                        {{-- Profile info tab --}}
                        <div class="max-w-[500px]">
                            <h2 class="text-[20px] font-bold text-[#242424] mb-6">{{ __('Profile Information') }}</h2>
                            <div class="flex flex-col gap-4">
                                <div>
                                    <label class="text-[12px] font-semibold text-[#666] block mb-1.5">{{ __('Full name') }}</label>
                                    <p
                                        class="text-[15px] font-medium text-[#242424] border border-[#eee] rounded-lg px-4 py-2.5">
                                        {{ $customer->full_name }}</p>
                                </div>
                                <div>
                                    <label class="text-[12px] font-semibold text-[#666] block mb-1.5">{{ __('Email') }}</label>
                                    <p
                                        class="text-[15px] font-medium text-[#242424] border border-[#eee] rounded-lg px-4 py-2.5">
                                        {{ $customer->email }}</p>
                                </div>
                                @if ($customer->phone)
                                    <div>
                                        <label class="text-[12px] font-semibold text-[#666] block mb-1.5">{{ __('Phone') }}</label>
                                        <p
                                            class="text-[15px] font-medium text-[#242424] border border-[#eee] rounded-lg px-4 py-2.5">
                                            {{ $customer->phone }}</p>
                                    </div>
                                @endif
                            </div>
                            {{-- Saved Addresses --}}
                            <div class="mt-8">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-[16px] font-bold text-[#242424]">{{ __('Saved Addresses') }}</h3>
                                    <button type="button" wire:click="openAddressModal()"
                                        class="flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-[#242424] text-white text-xs font-semibold hover:bg-black transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        {{ __('Add Address') }}
                                    </button>
                                </div>
                                @if ($addresses->count() > 0)
                                <div class="space-y-3">
                                    @foreach ($addresses as $addr)
                                    <div class="border border-[#eee] rounded-lg p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <p class="text-[14px] font-semibold text-[#242424]">{{ $addr->full_name }}</p>
                                                    @if ($addr->label)
                                                    <span class="px-2 py-0.5 bg-[#f7f7f7] rounded text-xs text-[#666]">{{ $addr->label }}</span>
                                                    @endif
                                                    @if ($addr->is_default)
                                                    <span class="px-2 py-0.5 bg-[#242424] text-white rounded text-xs font-semibold">{{ __('Default') }}</span>
                                                    @endif
                                                </div>
                                                @if ($addr->phone)
                                                <p class="text-xs text-[#808080] mt-1">{{ $addr->phone }}</p>
                                                @endif
                                                <p class="text-xs text-[#808080] mt-1">
                                                    {{ collect([$addr->address_line_1, $addr->city, $addr->state, $addr->country])->filter()->implode(', ') }}
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0">
                                                @if (!$addr->is_default)
                                                <button type="button" wire:click="setDefaultAddress({{ $addr->id }})"
                                                    class="p-1.5 rounded-lg text-[#aaa] hover:text-yellow-500 hover:bg-yellow-50 transition" title="{{ __('Set as default') }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                                </button>
                                                @endif
                                                <button type="button" wire:click="openAddressModal({{ $addr->id }})"
                                                    class="p-1.5 rounded-lg text-[#aaa] hover:text-[#242424] hover:bg-[#f7f7f7] transition" title="{{ __('Edit') }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                </button>
                                                <button type="button" wire:click="deleteAddress({{ $addr->id }})"
                                                    wire:confirm="{{ __('Delete this address?') }}"
                                                    class="p-1.5 rounded-lg text-[#aaa] hover:text-red-600 hover:bg-red-50 transition" title="{{ __('Delete') }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <p class="text-[13px] text-[#aaa]">{{ __('No saved addresses yet.') }}</p>
                                @endif
                            </div>

                            {{-- Address Modal --}}
                            @if ($showAddressModal)
                            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" wire:click.self="closeAddressModal">
                                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                                    <div class="flex items-center justify-between mb-5">
                                        <h3 class="text-[16px] font-bold text-[#242424]">
                                            {{ $editingAddressId ? __('Edit Address') : __('New Address') }}
                                        </h3>
                                        <button type="button" wire:click="closeAddressModal" class="text-[#aaa] hover:text-[#242424]">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="text-[12px] font-semibold text-[#666] block mb-1">{{ __('Full Name') }} <span class="text-red-500">*</span></label>
                                                <input wire:model="addrFullName" type="text" class="w-full border border-[#eee] rounded-lg px-3 py-2 text-[14px] outline-none focus:border-[#aaa]" placeholder="{{ __('Full Name') }}" />
                                                @error('addrFullName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="text-[12px] font-semibold text-[#666] block mb-1">{{ __('Label') }}</label>
                                                <input wire:model="addrLabel" type="text" class="w-full border border-[#eee] rounded-lg px-3 py-2 text-[14px] outline-none focus:border-[#aaa]" placeholder="{{ __('e.g. Home, Work') }}" />
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-[12px] font-semibold text-[#666] block mb-1">{{ __('Phone') }}</label>
                                            <input wire:model="addrPhone" type="text" class="w-full border border-[#eee] rounded-lg px-3 py-2 text-[14px] outline-none focus:border-[#aaa]" placeholder="{{ __('Phone') }}" />
                                        </div>
                                        <div>
                                            <label class="text-[12px] font-semibold text-[#666] block mb-1">{{ __('Address') }} <span class="text-red-500">*</span></label>
                                            <input wire:model="addrLine1" type="text" class="w-full border border-[#eee] rounded-lg px-3 py-2 text-[14px] outline-none focus:border-[#aaa]" placeholder="{{ __('Street address') }}" />
                                            @error('addrLine1') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="text-[12px] font-semibold text-[#666] block mb-1">{{ __('City') }} <span class="text-red-500">*</span></label>
                                                <input wire:model="addrCity" type="text" class="w-full border border-[#eee] rounded-lg px-3 py-2 text-[14px] outline-none focus:border-[#aaa]" placeholder="{{ __('City') }}" />
                                                @error('addrCity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="text-[12px] font-semibold text-[#666] block mb-1">{{ __('State / Region') }}</label>
                                                <input wire:model="addrState" type="text" class="w-full border border-[#eee] rounded-lg px-3 py-2 text-[14px] outline-none focus:border-[#aaa]" placeholder="{{ __('State') }}" />
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-[12px] font-semibold text-[#666] block mb-1">{{ __('Country') }} <span class="text-red-500">*</span></label>
                                            @include('themes.ecommet.sections.country-select', [
                                                'wireModel' => 'addrCountryId',
                                                'countries' => $countries,
                                                'currentId' => $addrCountryId,
                                            ])
                                            @error('addrCountryId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <label class="flex items-center gap-2 cursor-pointer select-none">
                                            <input wire:model="addrIsDefault" type="checkbox" class="w-4 h-4 accent-[#242424]" />
                                            <span class="text-[14px] text-[#242424]">{{ __('Set as default address') }}</span>
                                        </label>
                                    </div>
                                    <div class="flex gap-2 mt-5">
                                        <button type="button" wire:click="saveAddress"
                                            class="flex-1 py-2.5 bg-[#242424] hover:bg-black text-white text-[14px] font-semibold rounded-full transition">
                                            {{ __('Save') }}
                                        </button>
                                        <button type="button" wire:click="closeAddressModal"
                                            class="flex-1 py-2.5 border border-[#eee] text-[#242424] text-[14px] font-semibold rounded-full hover:bg-[#f7f7f7] transition">
                                            {{ __('Cancel') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <button wire:click="logout"
                                class="mt-8 text-red-600 border border-red-200 hover:bg-red-50 transition rounded-full px-6 py-2 text-[14px] font-semibold">
                                {{ __('Sign out') }}
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
