@php
    $running = in_array($card['translation_status'], ['queued', 'running'], true);
    $summary = json_decode((string) $card['translation_summary'], true);
@endphp
<div class="card fu d2 ai-translation-card" data-ai-card="{{ $card['id'] }}" style="padding:20px;display:flex;flex-direction:column;gap:12px">
    <div style="display:flex;align-items:center;gap:10px">
        <div style="font-size:22px;font-weight:700;color:var(--accent)">{{ strtoupper($card['code']) }}</div>
        <div>
            <div class="entity-title">{{ $card['name'] }}</div>
            <div class="entity-subtitle" style="font-size:11px">{{ $card['native_name'] }}</div>
        </div>
        @unless ($card['is_active'])
            <span class="badge badge-gray" style="margin-left:auto;font-size:10px">{{ __('Not active') }}</span>
        @endunless
    </div>

    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
        @if ($card['is_free'])
            <span class="badge badge-green">{{ __('Free') }}</span>
        @else
            <span class="badge badge-violet">${{ number_format((float) $card['price'], 2) }}</span>
        @endif

        <span data-ai-card-last-status>
            @if ($card['last_status'] === 'completed')
                <span class="badge badge-blue" style="font-size:10px">{{ __('Last run: :lastrun', ['lastrun' => $card['last_run']]) }}</span>
            @elseif ($card['last_status'] === 'pending')
                <span class="badge badge-amber" style="font-size:10px">{{ __('Running…') }}</span>
            @elseif ($card['last_status'] === 'failed')
                <span class="badge badge-red" style="font-size:10px">{{ __('Last run failed') }}</span>
            @endif
        </span>
    </div>

    <p class="panel-copy" style="font-size:11px;margin:0">
        {{ __('Translates all products, categories, banners and UI strings into :name, adapted to your store brand.', ['name' => $card['name']]) }}
    </p>

    <div data-ai-card-progress @unless($running) hidden @endunless>
        <div class="progress-track" style="height:8px;border-radius:999px;background:var(--border);overflow:hidden;">
            <div data-ai-card-progress-bar style="height:100%;width:{{ $card['translation_progress'] }}%;background:var(--primary,#FF4B2B);transition:width .3s;"></div>
        </div>
        <p class="panel-copy" style="margin:4px 0 0;font-size:11px;" data-ai-card-progress-label>
            {{ __(ucfirst((string) $card['translation_status'])) }} — {{ $card['translation_progress'] }}%
        </p>
    </div>

    <span data-ai-card-completed @unless($card['translation_status'] === 'completed') hidden @endunless>
        <span class="badge badge-green" style="font-size:10px">{{ __('Completed — :itemstranslated items translated', ['itemstranslated' => $summary['items_translated'] ?? 0]) }}</span>
    </span>

    <span data-ai-card-failed @unless($card['translation_status'] === 'failed') hidden @endunless>
        <span class="badge badge-red" style="font-size:10px">{{ __('Translation failed') }}</span>
    </span>

    <button type="button" class="btn btn-primary"
            data-ai-card-action
            data-run-url="{{ route('tenant.settings.ai-translation.run', ['language' => $card['id']]) }}"
            data-language-id="{{ $card['id'] }}"
            data-language-name="{{ $card['name'] }}"
            data-language-native="{{ $card['native_name'] }}"
            data-language-code="{{ strtoupper($card['code']) }}"
            data-language-free="{{ $card['is_free'] ? '1' : '0' }}"
            data-language-price="{{ number_format((float) $card['price'], 2) }}"
            @if(!$card['is_active'] || $running) disabled @endif>
        @if ($running)
            {{ __('Translating…') }}
        @elseif ($card['is_free'])
            {{ __('Run AI Translation') }}
        @else
            {{ __('Buy AI Translation ($:price)', ['price' => number_format((float) $card['price'], 2)]) }}
        @endif
    </button>
</div>
