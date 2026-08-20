{{--
Elora – Customer Orders / Profile page
$customer Customer
$orders Collection<Order>
    $statusFilter string|null
    $activeTab string 'orders'|'profile'
    --}}
@push('body-attrs')data-page="client-orders"@endpush

@php
use App\Enums\OrderStatus;

$currency = $currentCurrency ?? null;
$symbol = $currency?->symbol ?? '$';
$rate = (float) ($currency?->conversion_rate ?? 1.0);
@endphp
<div class="bg-white">
    {{-- ═══ Mobile sidebar drawer (hidden on desktop) ═══ --}}
    <div id="elora-profile-drawer-overlay"
        class="fixed inset-0 bg-black/40 z-[900] hidden lg:hidden"
        onclick="eloraProfileDrawerClose()"></div>

    <div id="elora-profile-drawer"
        class="fixed top-0 end-0 h-full w-[280px] bg-white z-[901] shadow-2xl flex flex-col overflow-y-auto translate-x-full lg:hidden transition-transform duration-300 ease-in-out">

        {{-- Drawer header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-[#F0F0F0]">
            <span class="font-semibold text-[#171717]">{{ __('My Account') }}</span>
            <button type="button" onclick="eloraProfileDrawerClose()"
                class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- User info --}}
        <div class="px-5 py-4 border-b border-[#F0F0F0]">
            <p class="text-base font-semibold text-[#171717] mb-0.5">{{ $customer->full_name }}</p>
            <p class="text-sm text-[#ADADAD] mb-3">{{ $customer->email }}</p>
            <button wire:click="setTab('profile')" onclick="eloraProfileDrawerClose()"
                class="text-xs font-medium text-main border border-[#FFAC88] bg-[#FFF5F2] rounded-full px-4 py-1.5 hover:bg-orange-100 transition-colors">
                {{ __('Edit') }}
            </button>
        </div>

        {{-- My Orders nav --}}
        <div class="px-5 py-4 border-b border-[#F0F0F0]">
            <p class="text-sm font-semibold text-[#171717] mb-3">{{ __('My Orders') }}</p>
            <nav class="flex flex-col gap-0.5">
                <div wire:click="filterStatus(null)" onclick="eloraProfileDrawerClose()"
                    class="sidebar-nav-item {{ $statusFilter === null && $activeTab === 'orders' ? 'active' : '' }}">
                    {{ __('All') }}
                </div>
                @foreach ([
                'pending' => __('Pending'),
                'processing' => __('Processing'),
                'shipped' => __('Shipped'),
                'delivered' => __('Delivered'),
                'cancelled' => __('Cancelled'),
                ] as $val => $label)
                <div wire:click="filterStatus('{{ $val }}')" onclick="eloraProfileDrawerClose()"
                    class="sidebar-nav-item {{ $statusFilter === $val && $activeTab === 'orders' ? 'active' : '' }}">
                    {{ $label }}
                </div>
                @endforeach
            </nav>
        </div>

        {{-- Settings nav --}}
        <div class="px-5 py-4">
            <p class="text-sm font-semibold text-[#171717] mb-3">{{ __('Settings') }}</p>
            <nav class="flex flex-col gap-0.5">
                <div wire:click="setTab('profile')" onclick="eloraProfileDrawerClose()"
                    class="sidebar-settings-item">
                    {{ __('My personal details') }}
                </div>
                <div wire:click="setTab('returns')" onclick="eloraProfileDrawerClose()"
                    class="sidebar-settings-item">
                    {{ __('Returns') }}
                </div>
                <div wire:click="logout" onclick="eloraProfileDrawerClose()"
                    class="sidebar-settings-item" style="color:#dc2626">
                    {{ __('Sign out') }}
                </div>
            </nav>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <div class="bg-white">
        <div
            class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-10 py-2.5 flex items-center justify-between gap-1 text-sm text-[#808080] flex-wrap">
            <div class="flex items-center gap-1 flex-wrap">
                <a href="{{ route('tenant.home') }}"
                    class="hover:text-main text-[#ADADAD] transition-colors">{{ __('Home') }}</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="m9 18 6-6-6-6" />
                </svg>
                <span class="text-[#1B1B1B]">{{ $activeTab === 'profile' ? __('Profile') : ($activeTab === 'returns' ? __('Returns') : __('Orders')) }}</span>
            </div>
            {{-- Hamburger: only on mobile --}}
            <button id="elora-profile-menu-btn" type="button"
                class="lg:hidden flex flex-col gap-1 p-2 rounded-md hover:bg-gray-100 transition"
                aria-label="{{ __('Menu') }}">
                <span class="block w-5 h-0.5 bg-[#171717]"></span>
                <span class="block w-5 h-0.5 bg-[#171717]"></span>
                <span class="block w-5 h-0.5 bg-[#171717]"></span>
            </button>
        </div>
    </div>

    <div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-10 py-6 pb-16">
        <div class="flex gap-6 lg:gap-8 items-start">

            {{-- ════ LEFT SIDEBAR (desktop) ════ --}}
            <div class="desktop-sidebar flex-shrink-0" style="width:234px">

                {{-- User info card --}}
                <div class="bg-white border border-[#F0F0F0] rounded-lg p-5 mb-4">
                    <p class="text-lg font-semibold text-[#171717] mb-0.5">{{ $customer->full_name }}</p>
                    <p class="text-sm text-[#ADADAD] mb-3">{{ $customer->email }}</p>
                    <button wire:click="setTab('profile')"
                        class="text-xs font-medium text-main border border-[#FFAC88] bg-[#FFF5F2] rounded-full px-4 py-1.5 hover:bg-orange-100 transition-colors">
                        {{ __('Edit') }}
                    </button>
                </div>

                {{-- My Orders nav --}}
                <div class="bg-white border border-[#F0F0F0] rounded-lg p-4 mb-4">
                    <p class="text-base font-semibold text-[#171717] mb-3 pb-2 border-b border-[#F0F0F0]">
                        {{ __('My Orders') }}
                    </p>
                    <nav class="flex flex-col gap-0.5">
                        <div wire:click="filterStatus(null)"
                            class="sidebar-nav-item {{ $statusFilter === null && $activeTab === 'orders' ? 'active' : '' }}">
                            {{ __('All') }}
                        </div>
                        @foreach ([
                        'pending' => __('Pending'),
                        'processing' => __('Processing'),
                        'shipped' =>
                        __('Shipped'),
                        'delivered' => __('Delivered'),
                        'cancelled' => __('Cancelled')
                        ] as $val =>
                        $label)
                        <div wire:click="filterStatus('{{ $val }}')"
                            class="sidebar-nav-item {{ $statusFilter === $val && $activeTab === 'orders' ? 'active' : '' }}">
                            {{ $label }}
                        </div>
                        @endforeach
                    </nav>
                </div>

                {{--     Settings nav --}}
                <div class="bg-white border border-[#F0F0F0] rounded-lg p-4">
                    <p class="text-base font-semibold text-[#171717] mb-3 pb-2 border-b border-[#F0F0F0]">
                        {{ __('Settings') }}</p>
                    <nav class="flex flex-col gap-0.5">

                        <div wire:click="setTab('profile')" class="sidebar-settings-item">
                            {{ __('My personal details') }}</div>
                        <div wire:click="setTab('returns')" class="sidebar-settings-item">
                            {{ __('Returns') }}</div>
                        <div wire:click="logout" class="sidebar-settings-item" style="color:#dc2626">
                            {{ __('Sign out') }}</div>
                    </nav>
                </div>
            </div>

            {{-- ════ MAIN CONTENT ════ --}}
            <div class="flex-1 min-w-0">

                {{-- Mobile tab nav (visible on mobile only, hidden at ≥900px via CSS) --}}
                <div class="mobile-profile-tabs items-center gap-2 mb-5" style="display:none">
                    <button wire:click="setTab('orders')"
                        class="flex-1 py-2.5 text-sm font-semibold rounded-full border transition
                               {{ $activeTab === 'orders' ? 'bg-[#171717] text-white border-[#171717]' : 'bg-white text-[#555] border-[#E0E0E0] hover:border-[#171717]' }}">
                        {{ __('My Orders') }}
                    </button>
                    <button wire:click="setTab('profile')"
                        class="flex-1 py-2.5 text-sm font-semibold rounded-full border transition
                               {{ $activeTab === 'profile' ? 'bg-[#171717] text-white border-[#171717]' : 'bg-white text-[#555] border-[#E0E0E0] hover:border-[#171717]' }}">
                        {{ __('Profile & Addresses') }}
                    </button>
                    <button wire:click="setTab('returns')"
                        class="flex-1 py-2.5 text-sm font-semibold rounded-full border transition
                               {{ $activeTab === 'returns' ? 'bg-[#171717] text-white border-[#171717]' : 'bg-white text-[#555] border-[#E0E0E0] hover:border-[#171717]' }}">
                        {{ __('Returns') }}
                    </button>
                </div>

                @if ($activeTab === 'returns')

                <div class="max-w-[700px]">
                    <div class="flex items-center gap-3 mb-6">
                        <button wire:click="setTab('orders')"
                            class="w-9 h-9 flex items-center justify-center rounded-full border border-[#E0E0E0] bg-white hover:border-main transition-colors">
                            <svg class="w-4 h-4 text-[#555]" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path d="m15 18-6-6 6-6" stroke-linecap="round" />
                            </svg>
                        </button>
                        <h2 class="text-xl font-bold text-[#171717]">{{ __('Return Requests') }}</h2>
                    </div>

                    @if ($returnRequests->isEmpty())
                        <div class="text-center py-12 text-sm text-gray-500">
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
                            <div class="border border-[#F0F0F0] rounded-xl p-4">
                                <div class="flex items-center justify-between flex-wrap gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-[#171717]">
                                            {{ __('Order') }} #{{ $ret->order_number }}
                                        </div>
                                        <div class="text-xs text-[#808080] mt-0.5">
                                            {{ $ret->reason->label() }} · {{ $ret->created_at?->format('M d, Y') }}
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold px-3 py-1 rounded-full"
                                        style="background:{{ $statusColor }}22;color:{{ $statusColor }}">
                                        {{ $ret->status->label() }}
                                    </span>
                                </div>
                                @if ($ret->refund_amount)
                                    <div class="text-xs text-[#555] mt-2">
                                        {{ __('Refund') }}: {{ number_format((float)$ret->refund_amount, 2) }}
                                    </div>
                                @endif
                                <div class="mt-3">
                                    <a href="{{ route('tenant.storefront.order-status', $ret->order_number) }}"
                                        class="text-xs font-medium underline text-[#555] hover:text-[#171717]">
                                        {{ __('View Order') }}
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @elseif ($activeTab === 'profile')

                {{-- Profile Info --}}
                <div class="max-w-[500px]">
                    <div class="flex items-center gap-3 mb-6">
                        <button wire:click="setTab('orders')"
                            class="w-9 h-9 flex items-center justify-center rounded-full border border-[#E0E0E0] bg-white hover:border-main transition-colors">
                            <svg class="w-4 h-4 text-[#555]" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path d="m15 18-6-6 6-6" stroke-linecap="round" />
                            </svg>
                        </button>
                        <h2 class="text-xl font-bold text-[#171717]">{{ __('Profile Information') }}</h2>
                    </div>
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="text-xs font-semibold text-[#666] block mb-1.5">
                                {{ __('Full name') }}</label>
                            <p
                                class="text-[15px] font-medium text-[#242424] border border-[#eee] rounded-xl px-4 py-2.5">
                                {{ $customer->full_name }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-[#666] block mb-1.5">{{ __('Email') }}</label>
                            <p
                                class="text-[15px] font-medium text-[#242424] border border-[#eee] rounded-xl px-4 py-2.5">
                                {{ $customer->email }}</p>
                        </div>
                        @if ($customer->phone)
                        <div>
                            <label class="text-xs font-semibold text-[#666] block mb-1.5">{{ __('Phone') }}</label>
                            <p
                                class="text-[15px] font-medium text-[#242424] border border-[#eee] rounded-xl px-4 py-2.5">
                                {{ $customer->phone }}</p>
                        </div>
                        @endif
                    </div>
                    {{-- Saved Addresses --}}
                    <div class="mt-8">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-bold text-[#171717]">{{ __('Saved Addresses') }}</h3>
                            <button type="button" wire:click="openAddressModal()"
                                class="flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-[#171717] text-white text-xs font-semibold hover:bg-black transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                {{ __('Add Address') }}
                            </button>
                        </div>
                        @if ($addresses->count() > 0)
                        <div class="space-y-3">
                            @foreach ($addresses as $addr)
                            <div class="border border-[#F0F0F0] rounded-xl p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-semibold text-[#171717]">{{ $addr->full_name }}</p>
                                            @if ($addr->label)
                                            <span class="px-2 py-0.5 bg-[#F5F5F5] rounded text-xs text-[#888]">{{ $addr->label }}</span>
                                            @endif
                                            @if ($addr->is_default)
                                            <span class="px-2 py-0.5 bg-main/10 text-main rounded text-xs font-semibold">{{ __('Default') }}</span>
                                            @endif
                                        </div>
                                        @if ($addr->phone)
                                        <p class="text-xs text-[#888] mt-1">{{ $addr->phone }}</p>
                                        @endif
                                        <p class="text-xs text-[#888] mt-1">
                                            {{ collect([$addr->address_line_1, $addr->city, $addr->state, $addr->country])->filter()->implode(', ') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        @if (!$addr->is_default)
                                        <button type="button" wire:click="setDefaultAddress({{ $addr->id }})"
                                            class="p-1.5 rounded-lg text-[#ADADAD] hover:text-yellow-500 hover:bg-yellow-50 transition" title="{{ __('Set as default') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                        </button>
                                        @endif
                                        <button type="button" wire:click="openAddressModal({{ $addr->id }})"
                                            class="p-1.5 rounded-lg text-[#ADADAD] hover:text-[#171717] hover:bg-[#F5F5F5] transition" title="{{ __('Edit') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                        <button type="button" wire:click="deleteAddress({{ $addr->id }})"
                                            wire:confirm="{{ __('Delete this address?') }}"
                                            class="p-1.5 rounded-lg text-[#ADADAD] hover:text-red-500 hover:bg-red-50 transition" title="{{ __('Delete') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-sm text-[#ADADAD]">{{ __('No saved addresses yet.') }}</p>
                        @endif
                    </div>

                    {{-- Address Modal --}}
                    @if ($showAddressModal)
                    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4 py-8" wire:click.self="closeAddressModal">
                        <div class="bg-white rounded-[20px] shadow-2xl ring-1 ring-black/5 w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden">
                            <div class="flex items-center justify-between px-6 py-5 border-b border-[#F0F0F0] shrink-0">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-full bg-[#F5F5F5] flex items-center justify-center text-[#171717] shrink-0">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </span>
                                    <h3 class="text-[17px] font-bold text-[#171717] tracking-tight">
                                        {{ $editingAddressId ? __('Edit Address') : __('New Address') }}
                                    </h3>
                                </div>
                                <button type="button" wire:click="closeAddressModal" class="w-8 h-8 rounded-full flex items-center justify-center text-[#ADADAD] hover:text-[#171717] hover:bg-[#F5F5F5] transition">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="px-6 py-5 space-y-4 overflow-y-auto">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-semibold text-[#666] block mb-1.5">{{ __('Full Name') }} <span class="text-red-500">*</span></label>
                                        <input wire:model="addrFullName" type="text" class="w-full border border-[#E0E0E0] rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:border-[#171717] focus:ring-4 focus:ring-black/5" placeholder="{{ __('Full Name') }}" />
                                        @error('addrFullName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-[#666] block mb-1.5">{{ __('Label') }}</label>
                                        <input wire:model="addrLabel" type="text" class="w-full border border-[#E0E0E0] rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:border-[#171717] focus:ring-4 focus:ring-black/5" placeholder="{{ __('e.g. Home, Work') }}" />
                                    </div>
                                </div>
                                <div wire:ignore>
                                    <label class="text-xs font-semibold text-[#666] block mb-1.5">{{ __('Phone') }}</label>
                                    <input wire-event="addrPhone" value="{{$addrPhone ?? ''}}" type="tel" data-phone-input class="w-full border border-[#E0E0E0] rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:border-[#171717] focus:ring-4 focus:ring-black/5" placeholder="{{ __('Phone') }}" />
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-[#666] block mb-1.5">{{ __('Address') }} <span class="text-red-500">*</span></label>
                                    <input wire:model="addrLine1" type="text" class="w-full border border-[#E0E0E0] rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:border-[#171717] focus:ring-4 focus:ring-black/5" placeholder="{{ __('Street address') }}" />
                                    @error('addrLine1') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-semibold text-[#666] block mb-1.5">{{ __('City') }} <span class="text-red-500">*</span></label>
                                        <input wire:model="addrCity" type="text" class="w-full border border-[#E0E0E0] rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:border-[#171717] focus:ring-4 focus:ring-black/5" placeholder="{{ __('City') }}" />
                                        @error('addrCity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-[#666] block mb-1.5">{{ __('State / Region') }}</label>
                                        <input wire:model="addrState" type="text" class="w-full border border-[#E0E0E0] rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:border-[#171717] focus:ring-4 focus:ring-black/5" placeholder="{{ __('State') }}" />
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-[#666] block mb-1.5">{{ __('Country') }} <span class="text-red-500">*</span></label>
                                    @include('themes.elora.sections.country-select', [
                                        'wireModel' => 'addrCountryId',
                                        'countries' => $countries,
                                        'currentId' => $addrCountryId,
                                    ])
                                    @error('addrCountryId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <label class="flex items-center gap-2.5 cursor-pointer select-none bg-[#FAFAFA] rounded-xl px-3.5 py-3 border border-[#F0F0F0]">
                                    <input wire:model="addrIsDefault" type="checkbox" class="w-4 h-4 accent-[#171717] rounded" />
                                    <span class="text-[13.5px] font-medium text-[#171717]">{{ __('Set as default address') }}</span>
                                </label>
                            </div>
                            <div class="flex gap-2.5 px-6 py-5 border-t border-[#F0F0F0] shrink-0">
                                <button type="button" wire:click="closeAddressModal"
                                    class="flex-1 py-2.5 border border-[#E0E0E0] text-[#171717] text-sm font-semibold rounded-full hover:bg-[#F5F5F5] transition">
                                    {{ __('Cancel') }}
                                </button>
                                <button type="button" wire:click="saveAddress"
                                    class="flex-1 py-2.5 bg-[#171717] hover:bg-black text-white text-sm font-semibold rounded-full transition shadow-sm">
                                    {{ __('Save') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif

                    <button wire:click="logout"
                        class="mt-8 text-red-600 border border-red-200 hover:bg-red-50 transition rounded-full px-6 py-2 text-sm font-semibold">
                        {{ __('Sign out') }}
                    </button>
                </div>

                @else

                {{-- Search bar --}}

                <div class="relative mb-4">
                    <input type="text" id="orderSearch" placeholder="{{ __('Item name / Order ID / Tracking No.') }}"
                        class="w-full border border-[#E0E0E0] rounded-full bg-white px-4 py-3 pr-12 text-sm text-[#171717] placeholder-[#ADADAD] outline-none focus:border-[#ADADAD] transition-colors"
                        oninput="searchOrders()" />
                    <svg class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#ADADAD]" fill="none"
                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                </div>


                {{--  Info banner --}}
                <div class="flex items-center gap-3 bg-[#fff8285a] border border-[#FFF828] rounded-xl px-4 py-3 mb-5">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M8.5 13.5H9.5V8H8.5V13.5ZM9 6.577C9.17467 6.577 9.321 6.518 9.439 6.4C9.557 6.282 9.61567 6.13567 9.615 5.961C9.61433 5.78633 9.55533 5.64033 9.438 5.523C9.32067 5.40567 9.17467 5.34667 9 5.346C8.82533 5.34533 8.67933 5.40433 8.562 5.523C8.44467 5.64167 8.38567 5.788 8.385 5.962C8.38433 6.136 8.44333 6.282 8.562 6.4C8.68067 6.518 8.82667 6.577 9 6.577ZM9.003 18C7.75833 18 6.58833 17.764 5.493 17.292C4.39767 16.8193 3.44467 16.178 2.634 15.368C1.82333 14.558 1.18167 13.606 0.709 12.512C0.236333 11.418 0 10.2483 0 9.003C0 7.75767 0.236333 6.58767 0.709 5.493C1.181 4.39767 1.82133 3.44467 2.63 2.634C3.43867 1.82333 4.391 1.18167 5.487 0.709C6.583 0.236333 7.753 0 8.997 0C10.241 0 11.411 0.236333 12.507 0.709C13.6023 1.181 14.5553 1.82167 15.366 2.631C16.1767 3.44033 16.8183 4.39267 17.291 5.488C17.7637 6.58333 18 7.753 18 8.997C18 10.241 17.764 11.411 17.292 12.507C16.82 13.603 16.1787 14.556 15.368 15.366C14.5573 16.176 13.6053 16.8177 12.512 17.291C11.4187 17.7643 10.249 18.0007 9.003 18ZM9 17C11.2333 17 13.125 16.225 14.675 14.675C16.225 13.125 17 11.2333 17 9C17 6.76667 16.225 4.875 14.675 3.325C13.125 1.775 11.2333 1 9 1C6.76667 1 4.875 1.775 3.325 3.325C1.775 4.875 1 6.76667 1 9C1 11.2333 1.775 13.125 3.325 14.675C4.875 16.225 6.76667 17 9 17Z"
                            fill="#242424" />
                    </svg>

                    <p class="text-sm text-[#7A6800]">
                        {{ __('View all your orders in one place, including active, completed, and cancelled orders.') }}
                    </p>
                </div>

                {{--     Mobile filter tabs --}}
                <div class="mobile-filter-tabs gap-2 overflow-x-auto pb-1 mb-4" style="scrollbar-width:none">
                    <button wire:click="filterStatus(null)"
                        class="filter-tab flex-shrink-0 {{ $statusFilter === null ? 'active' : '' }}">
                        {{ __('All') }}
                    </button>
                    @foreach ([
                    'pending' => __('Pending'),
                    'processing' => __('Processing'),
                    'shipped' => __('Shipped'),
                    'delivered' => __('Delivered'),
                    'cancelled' => __('Cancelled')
                    ] as $val => $label)
                    <button wire:click="filterStatus('{{ $val }}')"
                        class="filter-tab flex-shrink-0 {{ $statusFilter === $val ? 'active' : '' }}">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>

                {{-- Orders --}}
                @if ($orders->isEmpty())
                {{--   Empty state --}}
                <div id="emptyState" class="hidden flex flex-col items-center justify-center py-24 text-center">
                    <img loading="lazy" src="{{ asset('elora/assets/images/empty-orders.svg') }}" alt="{{ __('broken heart') }}">
                    <h2 class=" text-xl font-semibold text-[#333] mb-2">{{ __('No Orders yet !?') }}</h2>
                    <p class="text-sm text-[#888] mb-6">
                        {{ __('You don\'t have any orders at the moment.') }}
                    </p>
                    <a href="{{ route('tenant.home') }}"
                        class="px-8 py-3 bg-[#171717] text-white text-sm font-medium rounded-full hover:bg-black transition-colors">
                        {{ __('Continue Shopping') }}
                    </a>
                </div>
                @else
                <div class="flex flex-col gap-4" id="ordersList">
                    @foreach ($orders as $order)
                    @php
                    $firstItem = $order->items->first();
                    $firstProduct = $firstItem?->product ?? $firstItem?->variant?->product;
                    $firstItemImage = $firstItem?->variant?->thumbnail_url ?? $firstProduct?->primary_image_url;
                    $extraCount = $order->items->count() - 1;
                    $badge = match ($order->status) {
                    OrderStatus::Pending => [
                    'bg' => '#FFFDE7',
                    'color' => '#B59A00',
                    'border' => '#F5E58A',
                    'label' =>
                    __('Pending')
                    ],
                    OrderStatus::Processing => [
                    'bg' => '#EEF2FF',
                    'color' => '#3B5BDB',
                    'border' => '#C5D0FA',
                    'label'
                    => __('Processing')
                    ],
                    OrderStatus::Shipped => [
                    'bg' => '#E8F4FD',
                    'color' => '#0369a1',
                    'border' => '#BAE6FD',
                    'label' =>
                    __('Shipped')
                    ],
                    OrderStatus::Delivered,
                    OrderStatus::Completed => [
                    'bg' => '#F0FBF0',
                    'color' => '#2AAF2F',
                    'border' => '#C6ECC6',
                    'label'
                    => __('Delivered')
                    ],
                    OrderStatus::Cancelled,
                    OrderStatus::Rejected => [
                    'bg' => '#FFF0F0',
                    'color' => '#dc2626',
                    'border' => '#FECACA',
                    'label' =>
                    __('Cancelled')
                    ],
                    default => [
                    'bg' => '#F5F5F5',
                    'color' => '#666',
                    'border' => '#E0E0E0',
                    'label' =>
                    $order->status->label()
                    ],
                    };
                    @endphp

                    <div class="order-card bg-white border border-[#F0F0F0] rounded-2xl p-5 sm:p-6 shadow-sm hover:shadow-md transition-shadow duration-200"
                        data-id="{{ $order->uuid }}" data-status="{{ $order->status->value }}"
                        data-date="{{ $order->created_at?->format('M j, Y') }}"
                        data-name="{{ $firstProduct?->translationValue('name') ?? $firstProduct?->slug ?? '' }}">

                        {{-- Order header --}}
                        <div class="flex items-start justify-between gap-3 mb-5">
                            <div>
                                <p class="text-base font-semibold text-[#171717] tracking-wide">
                                    #{{ $order->uuid }}
                                </p>
                                <p class="text-sm text-[#ADADAD] mt-0.5">
                                    {{ $order->created_at?->format('M j, Y') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full flex-shrink-0 text-sm font-medium"
                                style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};border:1px solid {{ $badge['border'] }}">
                                {{ $badge['label'] }}
                            </div>
                        </div>

                        {{-- Product row --}}
                        <div class="flex items-center gap-4 mb-5">
                            <div
                                class="w-16 h-16 sm:w-[84px] sm:h-[84px] rounded-xl flex-shrink-0 bg-[#F5F5F5] flex items-center justify-center overflow-hidden">
                                @if ($firstItemImage)
                                <img loading="lazy" src="{{ $firstItemImage }}" alt=""
                                    class="w-full h-full object-cover">
                                @else
                                <svg class="w-8 h-8 text-[#ccc]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                @endif
                            </div>
                            <div class="flex flex-col gap-1 min-w-0">
                                <p class="text-base font-medium text-[#171717] truncate">
                                    {{ $firstProduct?->translationValue('name') ?? $firstProduct?->slug ?? __('Item #:id', ['id' => $firstItem?->id ?? '']) }}
                                </p>
                                @if ($extraCount > 0)
                                <p class="text-sm text-[#ADADAD]">+{{ $extraCount }}
                                    {{ __('more item(s)') }}</p>
                                @endif
                                <p class="text-base font-semibold text-[#171717]">
                                    {{ $symbol }}{{ number_format($order->grand_total * $rate, 2) }}</p>
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="border-t border-[#F0F0F0] mb-4"></div>

                        {{-- Action buttons --}}
                        <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                            {{-- View Details --}}
                            <a href="{{ route('tenant.storefront.order-status', $order->uuid) }}"
                                class="flex items-center justify-center gap-2 bg-[#171717] text-white text-sm font-medium rounded-full px-6 py-2.5 hover:bg-black transition-colors whitespace-nowrap">
                                {{ __('View Details') }}
                                <svg class="w-4 h-4" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="m9 18 6-6-6-6" stroke-linecap="round" />
                                </svg>
                            </a>
                            {{-- Track Order (non-final statuses) --}}
                            @if (!in_array($order->status, [OrderStatus::Delivered, OrderStatus::Completed, OrderStatus::Cancelled, OrderStatus::Rejected]))
                            <a href="{{ route('tenant.storefront.order-tracking', $order->uuid) }}"
                                class="flex items-center justify-center gap-2 border border-[#E0E0E0] text-[#171717] text-sm font-normal rounded-full px-5 py-2.5 hover:border-[#171717] hover:bg-gray-50 transition-all whitespace-nowrap">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <rect x="1" y="3" width="15" height="13" rx="1" />
                                    <path d="M16 8h4l3 4v4h-7V8z" />
                                    <circle cx="5.5" cy="18.5" r="2.5" />
                                    <circle cx="18.5" cy="18.5" r="2.5" />
                                </svg>
                                {{ __('Track Order') }}
                            </a>
                            @endif
                            {{-- Leave a Review --}}
                            @php
                                $reviewProductId   = $firstProduct?->id ?? null;
                                $reviewProductName = $firstProduct?->translationValue('name') ?? $firstProduct?->slug ?? '';
                                $alreadyReviewed   = $reviewProductId && in_array($reviewProductId, $reviewedProductIds ?? []);
                            @endphp
                            @if ($alreadyReviewed)
                                <button type="button" disabled
                                    class="flex items-center justify-center gap-1.5 border border-[#E0E0E0] text-[#ADADAD] text-sm font-normal rounded-full px-5 py-2.5 whitespace-nowrap cursor-not-allowed">
                                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.719c-.783-.57-.38-1.81.588-1.81H7.03a1 1 0 00.951-.69l1.069-3.292z"/></svg>
                                    {{ __('Reviewed') }}
                                </button>
                            @else
                                <button onclick="swalLeaveReview({{ $reviewProductId ?? 'null' }}, @js($reviewProductName))"
                                    class="flex items-center justify-center border border-[#E0E0E0] text-[#171717] text-sm font-normal rounded-full px-5 py-2.5 hover:border-[#171717] hover:bg-gray-50 transition-all whitespace-nowrap {{ !$reviewProductId ? 'pointer-events-none opacity-40' : '' }}">
                                    {{ __('Leave a Review') }}
                                </button>
                            @endif
                            {{-- Reorder (all statuses) – always sits right before Cancel --}}
                            <button wire:click="reorder('{{ $order->uuid }}')"
                                class="flex items-center justify-center gap-1.5 border border-[#E0E0E0] text-[#171717] text-sm font-normal rounded-full px-5 py-2.5 hover:border-[#171717] hover:bg-gray-50 transition-all whitespace-nowrap">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ __('Reorder') }}
                            </button>
                            {{-- Cancel Order (pending/processing only) --}}
                            @if (in_array($order->status, [OrderStatus::Pending, OrderStatus::Processing]))
                            <button onclick="swalCancelOrder('{{ $order->uuid }}')"
                                class="flex items-center justify-center border border-red-200 text-red-500 text-sm font-normal rounded-full px-5 py-2.5 hover:border-red-400 hover:bg-red-50 transition-all whitespace-nowrap">
                                {{ __('Cancel Order') }}
                            </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @endif

            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
function searchOrders() {
    const q = document.getElementById('orderSearch').value.toLowerCase();
    document.querySelectorAll('.order-card').forEach(card => {
        const id = (card.dataset.id || '').toLowerCase();
        const name = (card.dataset.name || '').toLowerCase();
        const date = (card.dataset.date || '').toLowerCase();
        card.style.display = (!q || id.includes(q) || name.includes(q) || date.includes(q)) ?
            '' : 'none';
    });
}

// ── Mobile profile drawer ─────────────────────────────────────────────────
function eloraProfileDrawerOpen() {
    const drawer = document.getElementById('elora-profile-drawer');
    const overlay = document.getElementById('elora-profile-drawer-overlay');
    if (!drawer || !overlay) return;
    overlay.classList.remove('hidden');
    requestAnimationFrame(() => {
        drawer.classList.remove('translate-x-full', '-translate-x-full');
    });
    document.body.style.overflow = 'hidden';
}

function eloraProfileDrawerClose() {
    const drawer = document.getElementById('elora-profile-drawer');
    const overlay = document.getElementById('elora-profile-drawer-overlay');
    if (!drawer || !overlay) return;
    if (document.documentElement.dir === 'rtl') {
        drawer.classList.add('-translate-x-full');
    } else {
        drawer.classList.add('translate-x-full');
    }
    overlay.classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('elora-profile-menu-btn');
    const drawer = document.getElementById('elora-profile-drawer');
    if (drawer && document.documentElement.dir === 'rtl') {
        drawer.classList.remove('translate-x-full');
        drawer.classList.add('-translate-x-full');
    }
    if (btn) btn.addEventListener('click', eloraProfileDrawerOpen);
});
</script>
@endpush

@script
<script>
// ── SweetAlert2 toast helper ──────────────────────────────────────────────
function profileSwal(message, type) {
    const pos = document.documentElement.getAttribute('dir') === 'rtl' ? 'top-start' : 'top-end';
    const colors = {
        success: { bg: '#2AAF2F', color: '#fff' },
        error:   { bg: '#dc2626', color: '#fff' },
        warning: { bg: '#f59e0b', color: '#fff' },
        info:    { bg: '#3b82f6', color: '#fff' },
    };
    const scheme = colors[type] || colors.success;
    Swal.mixin({
        toast: true,
        position: pos,
        showConfirmButton: false,
        showCloseButton: true,
        timer: 3500,
        timerProgressBar: true,
        background: scheme.bg,
        color: scheme.color,
        customClass: {
            popup: 'shadow-xl rounded-xl',
            title: 'text-[13px] font-medium',
        },
    }).fire({ icon: type || 'success', title: message });
}

// ── Listen for profile-swal Livewire events ───────────────────────────────
$wire.on('profile-swal', (event) => {
    profileSwal(event.message, event.type || 'success');
});

// ── SweetAlert2 leave-a-review modal ──────────────────────────────────────
window.swalLeaveReview = function (productId, productName) {
    if (!productId) return;

    Swal.fire({
        title: @js(__('Leave a Review')),
        html: `
            <p class="text-sm text-gray-500 mb-4">${productName}</p>
            <div id="swal-stars" class="flex justify-center gap-2 mb-4 text-3xl">
                ${[1,2,3,4,5].map(i => `<span data-star="${i}" class="cursor-pointer text-gray-300 hover:text-yellow-400 transition-colors">&#9733;</span>`).join('')}
            </div>
            <input type="hidden" id="swal-star-value" value="0">
            <textarea id="swal-comment" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-gray-300" rows="3" placeholder="${@js(__('Share your experience (optional)'))}"></textarea>
        `,
        showCancelButton: true,
        confirmButtonColor: '#171717',
        cancelButtonColor:  '#6b7280',
        confirmButtonText:  @js(__('Submit Review')),
        cancelButtonText:   @js(__('Cancel')),
        reverseButtons: true,
        didOpen: () => {
            const stars = document.querySelectorAll('#swal-stars [data-star]');
            stars.forEach(star => {
                star.addEventListener('mouseenter', () => {
                    const val = parseInt(star.dataset.star);
                    stars.forEach(s => s.style.color = parseInt(s.dataset.star) <= val ? '#facc15' : '');
                });
                star.addEventListener('mouseleave', () => {
                    const selected = parseInt(document.getElementById('swal-star-value').value);
                    stars.forEach(s => s.style.color = parseInt(s.dataset.star) <= selected ? '#facc15' : '');
                });
                star.addEventListener('click', () => {
                    document.getElementById('swal-star-value').value = star.dataset.star;
                    const val = parseInt(star.dataset.star);
                    stars.forEach(s => s.style.color = parseInt(s.dataset.star) <= val ? '#facc15' : '');
                });
            });
        },
        preConfirm: () => {
            const stars = parseInt(document.getElementById('swal-star-value').value);
            if (!stars) {
                Swal.showValidationMessage(@js(__('Please select a star rating.')));
                return false;
            }
            return { stars, comment: document.getElementById('swal-comment').value.trim() };
        },
    }).then((result) => {
        if (result.isConfirmed) {
            $wire.submitReview(productId, result.value.stars, result.value.comment);
        }
    });
};

// ── SweetAlert2 cancel-order confirmation ─────────────────────────────────
window.swalCancelOrder = function (uuid) {
    Swal.fire({
        title: @js(__('Cancel order?')),
        text:  @js(__('This action cannot be undone. Are you sure you want to cancel this order?')),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor:  '#6b7280',
        confirmButtonText:  @js(__('Yes, cancel it')),
        cancelButtonText:   @js(__('No, keep it')),
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            $wire.cancelOrder(uuid);
        }
    });
};
</script>
@endscript

@push('scripts')
    <script>
        document.addEventListener('storefront-open-address-modal-changed', function (event) {
            window.bootPhoneInputs();
        });
    </script>
@endpush
