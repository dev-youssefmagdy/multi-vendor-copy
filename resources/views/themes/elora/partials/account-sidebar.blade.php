{{--
Elora – Customer account sidebar (desktop), shared by the profile and order pages.
$customer       Customer
$activeTab      string|null  'orders'|'profile'|'returns' — null highlights nothing
$statusFilter   string|null  active order filter when $activeTab === 'orders'
$livewire       bool         true on ProfilePage: links switch tabs in place via wire:click
--}}
@php
$activeTab = $activeTab ?? null;
$statusFilter = $statusFilter ?? null;
$livewire = $livewire ?? false;

$profileUrl = fn (array $query = []) => route('tenant.storefront.profile', $query);
$orderFilters = [
    null => __('All'),
    'pending' => __('Pending'),
    'processing' => __('Processing'),
    'shipped' => __('Shipped'),
    'delivered' => __('Delivered'),
    'cancelled' => __('Cancelled'),
];
@endphp
<style>
    .ep-nav-link {
        display: block;
        width: 100%;
        padding: 10px 14px;
        font-size: 14px;
        line-height: 1.35;
        color: #555;
        text-align: start;
        text-decoration: none;
        cursor: pointer;
        border-radius: 6px;
        border-inline-start: 3px solid transparent;
        transition: background-color .15s, color .15s, border-color .15s;
        user-select: none;
    }
    .ep-nav-link:hover {
        background: #fff5f2;
        color: #111827;
    }
    .ep-nav-link:focus-visible {
        outline: 2px solid #111827;
        outline-offset: 1px;
    }
    .ep-nav-link.is-active {
        background: rgba(255, 77, 0, .07);
        color: #111827;
        font-weight: 500;
        border-inline-start-color: #111827;
    }
    .ep-nav-link.is-danger { color: #dc2626; }
    .ep-nav-link.is-danger:hover { background: #fef2f2; color: #b91c1c; }

    .ep-sidebar { display: none; }
    @media (min-width: 1024px) {
        .ep-sidebar { display: block; }
    }
</style>

<aside class="ep-sidebar flex-shrink-0" style="width:234px">

    {{-- User info card --}}
    <div class="bg-white border border-[#F0F0F0] rounded-lg p-5 mb-4">
        <p class="text-lg font-semibold text-[#171717] mb-0.5">{{ $customer->full_name }}</p>
        <p class="text-sm text-[#ADADAD] mb-3 break-all">{{ $customer->email }}</p>
        <a href="{{ $profileUrl(['tab' => 'profile']) }}" @if ($livewire) wire:click.prevent="setTab('profile')" @endif
            class="inline-block text-xs font-medium text-main border border-[#FFAC88] bg-[#FFF5F2] rounded-full px-4 py-1.5 hover:bg-orange-100 transition-colors cursor-pointer">
            {{ __('Edit') }}
        </a>
    </div>

    {{-- My Orders nav --}}
    <div class="bg-white border border-[#F0F0F0] rounded-lg p-4 mb-4">
        <p class="text-base font-semibold text-[#171717] mb-3 pb-2 border-b border-[#F0F0F0]">
            {{ __('My Orders') }}
        </p>
        <nav class="flex flex-col gap-0.5">
            @foreach ($orderFilters as $val => $label)
            @php $val = $val === '' ? null : $val; $isActive = $activeTab === 'orders' && $statusFilter === $val; @endphp
            <a href="{{ $profileUrl($val ? ['status' => $val] : []) }}"
                @if ($livewire) wire:click.prevent="filterStatus({{ $val ? "'{$val}'" : 'null' }})" @endif
                @if ($isActive) aria-current="page" @endif
                class="ep-nav-link {{ $isActive ? 'is-active' : '' }}">
                {{ $label }}
            </a>
            @endforeach
        </nav>
    </div>

    {{-- Settings nav --}}
    <div class="bg-white border border-[#F0F0F0] rounded-lg p-4">
        <p class="text-base font-semibold text-[#171717] mb-3 pb-2 border-b border-[#F0F0F0]">
            {{ __('Settings') }}</p>
        <nav class="flex flex-col gap-0.5">
            <a href="{{ $profileUrl(['tab' => 'profile']) }}" @if ($livewire) wire:click.prevent="setTab('profile')" @endif
                @if ($activeTab === 'profile') aria-current="page" @endif
                class="ep-nav-link {{ $activeTab === 'profile' ? 'is-active' : '' }}">
                {{ __('My personal details') }}</a>
            <a href="{{ $profileUrl(['tab' => 'returns']) }}" @if ($livewire) wire:click.prevent="setTab('returns')" @endif
                @if ($activeTab === 'returns') aria-current="page" @endif
                class="ep-nav-link {{ $activeTab === 'returns' ? 'is-active' : '' }}">
                {{ __('Returns') }}</a>
            @if ($livewire)
            <button type="button" wire:click="logout" class="ep-nav-link is-danger">
                {{ __('Sign out') }}</button>
            @else
            <form method="POST" action="{{ route('tenant.storefront.logout') }}" class="m-0">
                @csrf
                <button type="submit" class="ep-nav-link is-danger">
                    {{ __('Sign out') }}</button>
            </form>
            @endif
        </nav>
    </div>
</aside>
