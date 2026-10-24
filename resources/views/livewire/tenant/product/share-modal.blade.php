{{--
Share to Social Media Modal Content
Included by list-page.blade.php via $extraModals[].

Variables ($contentData):
$shareTitle – string product title to use in share text
$shareCaption – string AI-generated caption or default description
$shareUrl – string storefront product URL
$shareImageUrl – string|null primary product image URL
$hasAiContent – bool true when caption comes from AI social posts
--}}

@php
    $title      = $shareTitle ?? '';
    $caption    = $shareCaption ?? '';
    $url        = $shareUrl ?? '';

    // Short caption: first sentence or first 120 chars, used where space is limited.
    $shortCaption = strtok($caption, "\n") ?: $caption;
    if (mb_strlen($shortCaption) > 120) {
        $shortCaption = mb_substr($shortCaption, 0, 117) . '…';
    }

    // WhatsApp message: bold title + short description + link, clean & compact.
    $waText = "*{$title}*" . ($shortCaption ? "\n{$shortCaption}" : '') . ($url ? "\n\n{$url}" : '');

    // Twitter: short caption + URL (Twitter appends the URL itself, keep tweet under 280 chars).
    $tweetText = mb_strlen($shortCaption) > 200 ? mb_substr($shortCaption, 0, 197) . '…' : $shortCaption;

    $encodedTitle        = rawurlencode($title);
    $encodedShortCaption = rawurlencode($shortCaption);
    $encodedTweetText    = rawurlencode($tweetText);
    $encodedUrl          = rawurlencode($url);
    $encodedWaText       = rawurlencode($waText);

    $platforms = [
        [
            'id'    => 'facebook',
            'label' => 'Facebook',
            'icon'  => '👍',
            'color' => '#1877f2',
            // Facebook sharer only uses the URL; content is pulled via OG meta tags on the product page.
            'url'   => 'https://www.facebook.com/sharer/sharer.php?u=' . $encodedUrl,
        ],
        [
            'id'    => 'twitter',
            'label' => 'X / Twitter',
            'icon'  => '𝕏',
            'color' => '#000',
            'url'   => 'https://twitter.com/intent/tweet?text=' . $encodedTweetText . '&url=' . $encodedUrl,
        ],
        [
            'id'    => 'linkedin',
            'label' => 'LinkedIn',
            'icon'  => '💼',
            'color' => '#0a66c2',
            // LinkedIn's modern share endpoint only accepts the URL; content is read from OG/meta tags.
            'url'   => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encodedUrl,
        ],
        [
            'id'    => 'whatsapp',
            'label' => 'WhatsApp',
            'icon'  => '💬',
            'color' => '#25d366',
            'url'   => 'https://wa.me/?text=' . $encodedWaText,
        ],
        [
            'id'    => 'telegram',
            'label' => 'Telegram',
            'icon'  => '✈️',
            'color' => '#2ca5e0',
            'url'   => 'https://t.me/share/url?url=' . $encodedUrl . '&text=' . $encodedShortCaption,
        ],
        [
            'id'    => 'pinterest',
            'label' => 'Pinterest',
            'icon'  => '📌',
            'color' => '#e60023',
            'url'   => 'https://pinterest.com/pin/create/button/?url=' . $encodedUrl
                        . '&description=' . $encodedShortCaption
                        . ($shareImageUrl ? '&media=' . rawurlencode($shareImageUrl) : ''),
        ],
        [
            'id'    => 'tiktok',
            'label' => 'TikTok',
            'icon'  => '🎵',
            'color' => '#ff0050',
            'url'   => 'https://www.tiktok.com/share?url=' . $encodedUrl,
        ],
    ];
@endphp

<div class="share-modal">

    {{-- ── Product preview ─────────────────────────────────────────────────── --}}
    <div class="card mb-4"
        style="padding:14px 16px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:10px;display:flex;align-items:flex-start;gap:14px;">
        @if (!empty($shareImageUrl))
            <img src="{{ $shareImageUrl }}" alt="{{ $shareTitle }}"
                style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid var(--border);flex-shrink:0;" />
        @endif
        <div style="flex:1;min-width:0;">
            <div class="entity-title" style="margin-bottom:4px;">{{ $shareTitle }}</div>
            @if ($hasAiContent)
                <span class="badge badge-cyan" style="font-size:10px;margin-bottom:6px;display:inline-block;">✦ AI
                    Caption</span>
            @endif
            <p class="entity-subtitle"
                style="margin:0;font-size:12px;white-space:pre-wrap;line-height:1.5;max-height:80px;overflow:hidden;">
                {{ $shareCaption }}</p>
        </div>
    </div>

    @if ($shareUrl)
        <div x-data="{ copiedUrl: false }" class="flex items-center gap-2 mb-4 p-3 rounded-lg"
            style="background:rgba(255,255,255,.03);border:1px solid var(--border);font-size:12px;">
            <code
                style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--t3);">{{ $shareUrl }}</code>
            <button type="button" class="btn btn-secondary btn-sm" style="flex-shrink:0;font-size:11px;"
                @click="navigator.clipboard.writeText('{{ addslashes($shareUrl) }}'); copiedUrl=true; setTimeout(()=>copiedUrl=false,2000)">
                <span x-show="!copiedUrl">Copy Link</span>
                <span x-show="copiedUrl">✓ Copied</span>
            </button>
        </div>
    @endif

    {{-- ── Platform share buttons ───────────────────────────────────────────── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;">
        @foreach ($platforms as $p)
            <a href="{{ $p['url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary"
                style="display:flex;align-items:center;gap:8px;justify-content:center;font-size:13px;padding:10px 12px;border-color:{{ $p['color'] }}20;">
                <span style="font-size:16px;">{{ $p['icon'] }}</span>
                <span>{{ $p['label'] }}</span>
            </a>
        @endforeach
    </div>

    <p class="entity-subtitle mt-4" style="font-size:11px;">
        @if ($hasAiContent)
            Caption sourced from AI-generated social posts. Share links open the platform's native share dialog.
        @else
            Using default product details (no AI post generated yet). Generate social posts first to get AI-crafted
            captions.
        @endif
    </p>

</div>
