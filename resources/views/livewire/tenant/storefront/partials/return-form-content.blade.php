@php
    $product = $item?->product ?? $item?->variant?->product;
    $productName = $product?->translationValue('name') ?? $product?->slug ?? __('Item');
@endphp

<div class="max-w-[720px] mx-auto px-4 sm:px-6 py-10">
    <div class="mb-6">
        <a href="{{ route('tenant.storefront.order-status', $order->uuid) }}" class="text-sm text-[#808080] hover:text-main">
            &larr; {{ __('Back to Order') }}
        </a>
        <h1 class="text-2xl font-semibold text-[#171717] mt-3">{{ __('Request a Return') }}</h1>
        <p class="text-sm text-[#808080] mt-1">
            {{ __('Order #:uuid', ['uuid' => $order->uuid]) }} — {{ $productName }}
        </p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit.prevent="submit" class="bg-white border border-gray-200 rounded-2xl p-6 flex flex-col gap-5">
        <div>
            <label class="block text-sm font-medium text-[#242424] mb-1">{{ __('Reason') }}</label>
            <select wire:model="reason" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                <option value="">{{ __('Select a reason') }}</option>
                @foreach ($reasons as $r)
                    <option value="{{ $r->value }}">{{ $r->label() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-[#242424] mb-1">{{ __('Description (optional)') }}</label>
            <textarea wire:model="description" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm"
                placeholder="{{ __('Tell us more about the issue…') }}"></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-[#242424] mb-1">{{ __('Photos (required)') }}</label>
            <input type="file" wire:model="photos" multiple accept="image/*" class="w-full text-sm">
            <div wire:loading wire:target="photos" class="text-xs text-[#808080] mt-1">{{ __('Uploading…') }}</div>
            @if ($photos)
                <div class="flex gap-2 mt-2 flex-wrap">
                    @foreach ($photos as $photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="w-16 h-16 object-cover rounded-lg border border-gray-200">
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-[#242424] mb-1">{{ __('Video (optional)') }}</label>
            <input type="file" wire:model="video" accept="video/*" class="w-full text-sm">
            <div wire:loading wire:target="video" class="text-xs text-[#808080] mt-1">{{ __('Uploading…') }}</div>
        </div>

        <button type="submit" wire:loading.attr="disabled"
            class="bg-[#FF4D00] text-white font-medium text-base px-8 py-3 rounded-full hover:bg-orange-600 transition-colors">
            {{ __('Submit Return Request') }}
        </button>
    </form>
</div>
