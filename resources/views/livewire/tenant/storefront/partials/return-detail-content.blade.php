@php
    $record = $returnRecord;
    $statusMap = ['green' => '#16a34a', 'blue' => '#2563eb', 'red' => '#dc2626', 'gray' => '#6b7280', 'amber' => '#d97706'];
    $color = $statusMap[$record['status_color']] ?? '#d97706';
@endphp

<div class="max-w-[720px] mx-auto px-4 sm:px-6 py-10">
    <a href="{{ route('tenant.storefront.profile', ['tab' => 'returns']) }}"
        class="text-sm text-[#808080] hover:text-main mb-6 block">
        &larr; {{ __('Back to Returns') }}
    </a>

    <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
        <div>
            <h1 class="text-2xl font-semibold text-[#171717]">{{ __('Return Request #:id', ['id' => $record['id']]) }}</h1>
            <p class="text-sm text-[#808080] mt-1">{{ __('Order #:order', ['order' => $record['order_number']]) }} · {{ $record['created_at'] }}</p>
        </div>
        <span class="text-sm font-semibold px-4 py-1.5 rounded-full"
            style="background:{{ $color }}22;color:{{ $color }}">
            {{ $record['status_label'] }}
        </span>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-5 mb-4">
        <div class="text-sm text-[#808080] mb-1">{{ __('Reason') }}</div>
        <div class="text-sm font-medium text-[#171717]">{{ $record['reason'] }}</div>
        @if($record['description'])
            <div class="text-sm text-[#555] mt-3">{{ $record['description'] }}</div>
        @endif
    </div>

    @if(!empty($record['media']))
        <div class="bg-white border border-gray-200 rounded-xl p-5 mb-4">
            <div class="text-sm text-[#808080] mb-3">{{ __('Evidence') }}</div>
            <div class="flex gap-3 flex-wrap">
                @foreach($record['media'] as $m)
                    @if($m['type'] === 'photo')
                        <a href="{{ $m['url'] }}" target="_blank">
                            <img src="{{ $m['url'] }}" class="w-20 h-20 object-cover rounded-lg border border-gray-200">
                        </a>
                    @else
                        <a href="{{ $m['url'] }}" target="_blank"
                            class="flex items-center gap-2 text-xs font-medium text-[#555] underline">
                            {{ __('Video') }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    @if($record['refund_amount'])
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4 text-sm font-semibold text-green-700">
            {{ __('Refund Issued: :amount', ['amount' => number_format((float) $record['refund_amount'], 2)]) }}
        </div>
    @endif

    @if(!empty($record['notes']))
        <div class="bg-white border border-gray-200 rounded-xl p-5 mb-4">
            <div class="text-sm text-[#808080] mb-3">{{ __('Messages') }}</div>
            <div class="flex flex-col gap-4">
                @foreach($record['notes'] as $note)
                    <div class="{{ $note['author_type'] === 'customer' ? 'ml-6' : 'mr-6' }}">
                        <div class="text-xs font-semibold {{ $note['author_type'] === 'customer' ? 'text-main' : 'text-[#555]' }} mb-1">
                            {{ $note['author'] }}
                            <span class="font-normal text-[#808080]">· {{ $note['created_at'] }}</span>
                        </div>
                        <div class="text-sm text-[#242424] bg-gray-50 rounded-lg p-3">
                            {{ $note['note'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($record['can_reply'])
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
            <p class="text-sm font-semibold text-amber-700 mb-4">
                {{ __('The team has requested more information. Please reply below.') }}
            </p>

            <form wire:submit.prevent="submitReply" class="flex flex-col gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#242424] mb-1">{{ __('Your Reply') }}</label>
                    <textarea wire:model="replyText" rows="4"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm"
                        placeholder="{{ __('Provide the requested information…') }}"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#242424] mb-1">{{ __('Additional Photos (optional)') }}</label>
                    <input type="file" wire:model="replyPhotos" multiple accept="image/*" class="text-sm w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#242424] mb-1">{{ __('Video (optional)') }}</label>
                    <input type="file" wire:model="replyVideo" accept="video/*" class="text-sm w-full">
                </div>
                <button type="submit" wire:loading.attr="disabled"
                    class="self-start bg-main text-white text-sm font-semibold px-6 py-2.5 rounded-lg hover:opacity-90 transition-opacity">
                    {{ __('Submit Reply') }}
                </button>
            </form>
        </div>
    @endif
</div>
