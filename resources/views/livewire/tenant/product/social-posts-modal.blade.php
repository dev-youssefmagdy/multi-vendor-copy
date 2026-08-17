{{--
Social Posts Modal Content
Included by livewire/tenant/pages/list-page.blade.php via $modalContentView.

Variables available from $modalContentData:
$posts – array<locale, array<platform, caption>>
    $imageB64 – string|null (base64 PNG, or null)
    $activeLang – string (currently selected locale tab)
    $generating – bool (true while AI is working)
    $error – string (error message, or empty)
    $storefrontUrl – string
    $selectedPlatform – string (active platform filter, or 'all')
    --}}

    @php
        $platforms = ['instagram', 'facebook', 'twitter', 'linkedin', 'tiktok', 'generic'];
        $platformLabels = [
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'twitter' => 'Twitter / X',
            'linkedin' => 'LinkedIn',
            'tiktok' => 'TikTok',
            'generic' => 'Generic',
        ];
        $platformIcons = [
            'instagram' => '📷',
            'facebook' => '👍',
            'twitter' => '🐦',
            'linkedin' => '💼',
            'tiktok' => '🎵',
            'generic' => '📢',
        ];
        $platformColors = [
            'instagram' => '#e1306c',
            'facebook' => '#1877f2',
            'twitter' => '#1d9bf0',
            'linkedin' => '#0a66c2',
            'tiktok' => '#fe2c55',
            'generic' => '#6366f1',
        ];

        $platformShareUrl = [
            'facebook' => fn($c, $u) => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($u),
            'twitter' => fn($c, $u) => 'https://twitter.com/intent/tweet?text=' . rawurlencode($c) . '&url=' . rawurlencode($u),
            'linkedin' => fn($c, $u) => 'https://www.linkedin.com/shareArticle?mini=true&url=' . rawurlencode($u) . '&summary=' . rawurlencode($c),
            'tiktok' => fn($c, $u) => 'https://www.tiktok.com/share?url=' . rawurlencode($u),
            'instagram' => null,
            'generic' => fn($c, $u) => 'https://wa.me/?text=' . rawurlencode($c . "\n\n" . $u),
        ];

        $storefrontUrl = $storefrontUrl ?? '';
        $enabledLanguages = $enabledLanguages ?? [];
        $selectedLanguage = $selectedLanguage ?? 'all';
        $includeImage = (bool) $includeImage;
        $selectedPlatform = $selectedPlatform ?? 'all';
        $langs = array_keys($posts ?? []);
        $activeLang = (is_string($activeLang ?? null) && $activeLang !== '')
            ? $activeLang
            : (array_key_first($posts ?? []) ?: app()->getLocale());
        $hasPosts = !empty($langs);

        // Which platforms to render in the results section
        $visiblePlatforms = ($selectedPlatform === 'all') ? $platforms : [$selectedPlatform];
    @endphp

    <div class="social-posts-modal" style="min-height:200px;">

        {{-- ── Error banner ─────────────────────────────────────────────────── --}}
        @if ($error)
            <div
                style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:12px 16px;color:var(--danger,#ef4444);font-size:13px;margin-bottom:16px;">
                ⚠ {{ $error }}
            </div>
        @endif

        {{-- ── Language selector ────────────────────────────────────────────── --}}
        <div style="margin-bottom:16px;">
            <p style="margin-bottom:8px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--t2,#9ca3af);">🌐 Language</p>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <div style="position:relative;flex-shrink:0;">
                    <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:14px;pointer-events:none;">🌐</span>
                    <select wire:model.live="socialSelectedLanguage"
                        style="min-width:230px;padding:9px 36px 9px 34px;border-radius:10px;border:1.5px solid rgba(99,102,241,.35);background:rgba(99,102,241,.08);color:#e5e7eb;font-size:13px;font-weight:500;appearance:none;-webkit-appearance:none;cursor:pointer;outline:none;transition:border-color .2s,background .2s;">
                        <option value="all" style="background:#1e1e2e;color:#e5e7eb;">All enabled languages</option>
                        @foreach ($enabledLanguages as $langCode => $langName)
                            <option value="{{ $langCode }}" style="background:#1e1e2e;color:#e5e7eb;">{{ $langName }} ({{ strtoupper($langCode) }})</option>
                        @endforeach
                    </select>
                    <span style="position:absolute;right:11px;top:50%;transform:translateY(-50%);pointer-events:none;color:#a5b4fc;font-size:10px;">▼</span>
                </div>
                <span style="font-size:11px;color:var(--t2,#9ca3af);background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:5px 10px;line-height:1.4;">
                    @if ($selectedLanguage === 'all')
                        Runs for <strong style="color:#a5b4fc;">every enabled</strong> language
                    @else
                        Runs only for <strong style="color:#a5b4fc;">{{ $enabledLanguages[$selectedLanguage] ?? strtoupper($selectedLanguage) }}</strong>
                    @endif
                </span>
            </div>
        </div>

        {{-- ── Image generation option ─────────────────────────────────────── --}}
        <div style="margin-bottom:16px;">
            <p style="margin-bottom:8px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--t2,#9ca3af);">🖼 Image Option</p>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <div style="position:relative;flex-shrink:0;">
                    <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:14px;pointer-events:none;">{{ $includeImage ? '🖼' : '📝' }}</span>
                    <select wire:model.live="socialIncludeImage"
                        style="min-width:230px;padding:9px 36px 9px 34px;border-radius:10px;border:1.5px solid rgba({{ $includeImage ? '168,85,247' : '99,102,241' }},.35);background:rgba({{ $includeImage ? '168,85,247' : '99,102,241' }},.08);color:#e5e7eb;font-size:13px;font-weight:500;appearance:none;-webkit-appearance:none;cursor:pointer;outline:none;transition:border-color .2s,background .2s;">
                        <option value="on" style="background:#1e1e2e;color:#e5e7eb;">Generate captions with image</option>
                        <option value="off" style="background:#1e1e2e;color:#e5e7eb;">Generate captions only (no image)</option>
                    </select>
                    <span style="position:absolute;right:11px;top:50%;transform:translateY(-50%);pointer-events:none;color:{{ $includeImage ? '#d8b4fe' : '#a5b4fc' }};font-size:10px;">▼</span>
                </div>
                <span style="font-size:11px;color:var(--t2,#9ca3af);background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:5px 10px;line-height:1.4;">
                    @if ($includeImage)
                        AI will generate a <strong style="color:#d8b4fe;">product image</strong> with captions
                    @else
                        <strong style="color:#a5b4fc;">Text only</strong> — image generation disabled
                    @endif
                </span>
            </div>
        </div>

        {{-- ── Platform selector ────────────────────────────────────────────── --}}
        <div style="margin-bottom:16px;">
            <p class="entity-subtitle" style="margin-bottom:8px;font-size:12px;">Select platform to generate for:</p>
            <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                {{-- "All platforms" option --}}
                <button type="button" wire:click="$set('socialSelectedPlatform', 'all')"
                    style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:1.5px solid {{ $selectedPlatform === 'all' ? '#6366f1' : 'var(--border)' }};background:{{ $selectedPlatform === 'all' ? 'rgba(99,102,241,.18)' : 'rgba(255,255,255,.04)' }};color:{{ $selectedPlatform === 'all' ? '#a5b4fc' : 'var(--t2)' }};cursor:pointer;transition:all .15s;">
                    🌐 All
                </button>
                @foreach ($platforms as $p)
                    <button type="button" wire:click="$set('socialSelectedPlatform', '{{ $p }}')"
                        style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:1.5px solid {{ $selectedPlatform === $p ? $platformColors[$p] : 'var(--border)' }};background:{{ $selectedPlatform === $p ? 'rgba(0,0,0,.25)' : 'rgba(255,255,255,.04)' }};color:{{ $selectedPlatform === $p ? '#fff' : 'var(--t2)' }};cursor:pointer;transition:all .15s;">
                        {{ $platformIcons[$p] }} {{ $platformLabels[$p] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ── Generate / Regenerate row ────────────────────────────────────── --}}
        <div
            style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
            <p class="entity-subtitle" style="flex:1;min-width:0;font-size:12px;">
                @if ($hasPosts)
                    @if ($selectedPlatform === 'all')
                        AI captions exist for all platforms. Regenerate to refresh, or pick a specific platform above.
                    @else
                        @php $langHas = collect($langs)->filter(fn($l) => !empty($posts[$l][$selectedPlatform]))->count(); @endphp
                        {{ $langHas > 0 ? 'Existing caption found — regenerate to refresh.' : 'No caption yet for ' . $platformLabels[$selectedPlatform] . '. Click Generate.' }}
                    @endif
                @else
                    Click <strong>Generate</strong> to create AI captions
                    {{ $selectedPlatform !== 'all' ? 'for ' . $platformLabels[$selectedPlatform] : 'for all platforms' }}.
                @endif
            </p>
            <button type="button" class="btn btn-primary btn-sm" wire:click="generateSocialPosts"
                wire:loading.attr="disabled" wire:target="generateSocialPosts"
                style="white-space:nowrap;flex-shrink:0;">
                <span wire:loading.remove wire:target="generateSocialPosts">
                    @if ($selectedPlatform !== 'all')
                        ✦ Generate for {{ $platformLabels[$selectedPlatform] ?? $selectedPlatform }}
                    @elseif ($hasPosts)
                        ↺ Regenerate All
                    @else
                        ✦ Generate All Platforms
                    @endif
                </span>
                <span wire:loading wire:target="generateSocialPosts">Generating…</span>
            </button>
        </div>

        {{-- ── Loading state ────────────────────────────────────────────────── --}}
        @if ($generating)
            <div style="text-align:center;padding:40px 0;">
                <div
                    style="display:inline-block;width:32px;height:32px;border:3px solid rgba(255,255,255,.1);border-top-color:var(--accent,#6366f1);border-radius:50%;animation:sp-spin .8s linear infinite;">
                </div>
                <p class="entity-subtitle" style="margin-top:12px;">
                    @if ($selectedPlatform !== 'all')
                        Generating {{ $platformLabels[$selectedPlatform] ?? $selectedPlatform }} post
                        {{ $selectedLanguage !== 'all' ? 'for ' . ($enabledLanguages[$selectedLanguage] ?? strtoupper($selectedLanguage)) : 'for all enabled languages' }}
                        {{ $includeImage ? 'with image' : 'without image' }}
                        — usually 10–20 s…
                    @else
                        Generating posts for all platforms ×
                        {{ $selectedLanguage === 'all' ? 'all enabled languages' : ($enabledLanguages[$selectedLanguage] ?? strtoupper($selectedLanguage)) }}
                        {{ $includeImage ? 'with image' : 'without image' }}
                        — may take 30–60 s…
                    @endif
                </p>
            </div>

        @elseif ($hasPosts)

            {{-- ── Language tabs (only when > 1 language) ─────────────────────── --}}
            @if (count($langs) > 1)
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;" role="tablist">
                    @foreach ($langs as $lang)
                        <button type="button" role="tab"
                            class="btn btn-sm {{ $lang === $activeLang ? 'btn-primary' : 'btn-secondary' }}"
                            wire:click="$set('socialActiveLang', '{{ $lang }}')">
                            {{ strtoupper($lang) }}
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- ── Per-platform captions ────────────────────────────────────── --}}
            @php $activePosts = $posts[$activeLang] ?? []; @endphp

            @if (empty($activePosts))
                <p class="entity-subtitle" style="text-align:center;padding:24px 0;">No posts for
                    <strong>{{ strtoupper($activeLang) }}</strong> yet.
                </p>
            @else
                <div style="display:flex;flex-direction:column;gap:12px;">
                    @foreach ($visiblePlatforms as $platform)
                        @php $caption = $activePosts[$platform] ?? null; @endphp
                        @if ($caption)
                            @php
                                $shareBuilder = $platformShareUrl[$platform] ?? null;
                                $shareHref = ($shareBuilder && $storefrontUrl) ? $shareBuilder($caption, $storefrontUrl) : null;
                                $pColor = $platformColors[$platform] ?? '#6366f1';
                            @endphp
                            <div x-data="{ copied: false, caption: {{ Js::from($caption) }} }"
                                style="padding:14px 16px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-left:3px solid {{ $pColor }};border-radius:10px;">

                                {{-- Card header --}}
                                <div
                                    style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px;flex-wrap:wrap;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <span style="font-size:18px;line-height:1;">{{ $platformIcons[$platform] }}</span>
                                        <span style="font-weight:700;font-size:13px;color:#fff;">{{ $platformLabels[$platform] }}</span>
                                        <span
                                            style="background:rgba(255,255,255,.08);padding:2px 8px;border-radius:12px;font-size:10px;color:var(--t2);">{{ strtoupper($activeLang) }}</span>
                                    </div>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <button type="button"
                                            style="padding:3px 10px;font-size:11px;border-radius:6px;border:1px solid var(--border);background:rgba(255,255,255,.06);color:var(--t2);cursor:pointer;"
                                            @click="navigator.clipboard.writeText(caption); copied=true; setTimeout(()=>copied=false,2000)"
                                            :title="copied ? 'Copied!' : 'Copy caption'">
                                            <span x-show="!copied">📋 Copy</span>
                                            <span x-show="copied">✓ Copied!</span>
                                        </button>
                                        @if ($shareHref)
                                            <a href="{{ $shareHref }}" target="_blank" rel="noopener noreferrer"
                                                style="padding:3px 10px;font-size:11px;border-radius:6px;background:{{ $pColor }};color:#fff;text-decoration:none;font-weight:600;">
                                                ↗ Share
                                            </a>
                                        @endif
                                        {{-- Per-platform regenerate --}}
                                        <button type="button"
                                            style="padding:3px 10px;font-size:11px;border-radius:6px;border:1px solid var(--border);background:rgba(255,255,255,.04);color:var(--t2);cursor:pointer;"
                                            wire:click="$set('socialSelectedPlatform', '{{ $platform }}')"
                                            title="Set to {{ $platformLabels[$platform] }} and regenerate">
                                            ↺
                                        </button>
                                    </div>
                                </div>

                                {{-- Caption text --}}
                                <p style="margin:0;white-space:pre-wrap;font-size:13px;line-height:1.65;color:var(--t1);">{{ $caption }}
                                </p>
                            </div>
                        @elseif ($selectedPlatform !== 'all')
                            {{-- Show placeholder only when a specific platform was selected but has no caption yet --}}
                            <div
                                style="padding:20px;background:rgba(255,255,255,.02);border:1px dashed var(--border);border-radius:10px;text-align:center;">
                                <span style="font-size:28px;">{{ $platformIcons[$platform] }}</span>
                                <p class="entity-subtitle" style="margin-top:8px;">No caption for {{ $platformLabels[$platform] }} yet.
                                    Click <strong>Generate</strong> above.</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- ── AI-generated image ───────────────────────────────────────── --}}
            @if ($imageB64)
                <div style="border-top:1px solid var(--border);padding-top:16px;margin-top:20px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <span style="font-weight:700;font-size:13px;">✦ AI-Generated Product Image</span>
                        <a href="data:image/png;base64,{{ $imageB64 }}" download="social-product-image.png"
                            style="padding:3px 12px;font-size:11px;border-radius:6px;border:1px solid var(--border);background:rgba(255,255,255,.06);color:var(--t2);text-decoration:none;">
                            ⬇ Download PNG
                        </a>
                    </div>
                    <img src="data:image/png;base64,{{ $imageB64 }}" alt="AI-generated product image"
                        style="width:100%;max-width:380px;border-radius:10px;border:1px solid var(--border);display:block;" />
                </div>
            @endif

        @else
            {{-- ── Empty state ─────────────────────────────────────────────── --}}
            <div style="text-align:center;padding:40px 0;color:var(--t3);">
                <div style="font-size:40px;margin-bottom:8px;">
                    {{ $selectedPlatform !== 'all' ? ($platformIcons[$selectedPlatform] ?? '✦') : '✦' }}
                </div>
                <p class="entity-subtitle">
                    @if ($selectedPlatform !== 'all')
                        No {{ $platformLabels[$selectedPlatform] ?? $selectedPlatform }} post yet.
                    @else
                        No posts generated yet.
                    @endif
                    Click <strong>Generate</strong> above to get started.
                </p>
            </div>
        @endif

    </div>

    <style>
        @keyframes sp-spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
