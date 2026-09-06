{{--
    Image search modal — camera capture or file upload.
    Shared across storefront (redirect mode) and admin/tenant panels (JSON callback mode).

    Props:
      action     - POST endpoint URL (required)
      onResults  - JS callback name called with parsed JSON; omit for storefront redirect mode
      id         - override the auto-generated modal ID
--}}
@props(['action', 'onResults' => null, 'id' => null])

@php($modalId = $id ?? 'image-search-modal-' . \Illuminate\Support\Str::random(6))

<div
    id="{{ $modalId }}"
    class="image-search-modal fixed inset-0 z-[9999] hidden items-center justify-center p-4"
    data-action="{{ $action }}"
    @if($onResults) data-on-results="{{ $onResults }}" @endif
    style="background:rgba(4,9,20,0.78);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);"
>
    <div class="ism-sheet">

        {{-- ── Header ─────────────────────────────────────────────────── --}}
        <div class="ism-header">
            <div class="ism-header-left">
                <div class="ism-header-icon">
                    <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6.5 6H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1.5l-1.2-1.6A2 2 0 0 0 14.7 5H9.3a2 2 0 0 0-1.6.8L6.5 6z"/>
                        <circle cx="12" cy="13" r="3.2"/>
                    </svg>
                </div>
                <div>
                    <div class="ism-title">{{ __('Search by Image') }}</div>
                    <div class="ism-subtitle">{{ __('Use your camera or upload a file to find similar products') }}</div>
                </div>
            </div>
            <button type="button" class="image-search-close ism-close-btn" aria-label="{{ __('Close') }}">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- ── Body ──────────────────────────────────────────────────── --}}
        <div class="ism-body">

            {{-- Choice --}}
            <div class="image-search-choice ism-choice">
                <button type="button" class="image-search-take-photo ism-option">
                    <div class="ism-option-icon">
                        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M6.5 6H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1.5l-1.2-1.6A2 2 0 0 0 14.7 5H9.3a2 2 0 0 0-1.6.8L6.5 6z"/>
                            <circle cx="12" cy="13" r="3.5"/>
                        </svg>
                    </div>
                    <span class="ism-option-label">{{ __('Take Photo') }}</span>
                    <span class="ism-option-hint">{{ __('Use device camera') }}</span>
                </button>

                <button type="button" class="image-search-upload ism-option">
                    <div class="ism-option-icon">
                        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                            <polyline stroke-linecap="round" stroke-linejoin="round" points="16 9 12 4 8 9"/>
                            <line x1="12" y1="4" x2="12" y2="16" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="ism-option-label">{{ __('Upload Image') }}</span>
                    <span class="ism-option-hint">{{ __('JPG · PNG · WEBP · 8 MB') }}</span>
                </button>
            </div>

            <input type="file" accept="image/*" class="image-search-file-input hidden">

            {{-- Camera --}}
            <div class="image-search-camera hidden">
                <div class="ism-viewfinder">
                    <video class="image-search-video ism-video" autoplay playsinline muted></video>
                    <canvas class="image-search-canvas hidden"></canvas>
                    <svg class="ism-corners" viewBox="0 0 100 100" fill="none" preserveAspectRatio="none">
                        <path d="M8 22 8 8 22 8"   stroke="rgba(255,255,255,.75)" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M78 8 92 8 92 22"  stroke="rgba(255,255,255,.75)" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M8 78 8 92 22 92"  stroke="rgba(255,255,255,.75)" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M78 92 92 92 92 78" stroke="rgba(255,255,255,.75)" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="ism-camera-bar">
                    <button type="button" class="image-search-cancel-camera ism-btn-ghost">{{ __('Cancel') }}</button>
                    <button type="button" class="image-search-capture ism-shutter" aria-label="{{ __('Capture photo') }}">
                        <span class="ism-shutter-dot"></span>
                    </button>
                    <div style="width:80px;"></div>
                </div>
            </div>

            {{-- Preview --}}
            <div class="image-search-preview hidden">
                <div class="ism-preview-frame">
                    <img class="image-search-preview-img ism-preview-img" alt="">
                </div>
                <div class="ism-preview-bar">
                    <button type="button" class="image-search-retake ism-btn-ghost">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4.06 13A8 8 0 1 0 4.56 9"/>
                        </svg>
                        {{ __('Retake') }}
                    </button>
                    <button type="button" class="image-search-submit ism-btn-primary">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/>
                            <path stroke-linecap="round" d="m21 21-4.35-4.35"/>
                        </svg>
                        {{ __('Find Similar Products') }}
                    </button>
                </div>
            </div>

            {{-- Loading --}}
            <div class="image-search-loading hidden ism-loading">
                <span class="ism-spinner"></span>
                <span class="ism-loading-text">{{ __('Analyzing image…') }}</span>
            </div>

            {{-- Error --}}
            <div class="image-search-error hidden ism-error"></div>

        </div>
    </div>
</div>

@once
<style>
/* ─── Hidden always wins over the display:flex helpers below ──────────── */
.image-search-modal .hidden { display: none !important; }

/* ─── Sheet ──────────────────────────────────────────────────────────── */
.ism-sheet {
    background: var(--card, #141f30);
    border: 1px solid var(--border, rgba(99,130,179,.14));
    border-radius: 18px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 28px 72px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.04) inset;
    overflow: hidden;
}

/* ─── Header ─────────────────────────────────────────────────────────── */
.ism-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px 16px;
    border-bottom: 1px solid var(--border, rgba(99,130,179,.14));
}
.ism-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.ism-header-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(0,229,255,.1);
    border: 1px solid rgba(0,229,255,.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: var(--cyan, #00e5ff);
}
.ism-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--t1, #fff);
    line-height: 1.2;
}
.ism-subtitle {
    font-size: 11.5px;
    color: var(--t3, #c7d1e6);
    margin-top: 2px;
}
.ism-close-btn {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid var(--border, rgba(99,130,179,.14));
    background: var(--surface, rgba(255,255,255,.04));
    color: var(--t3, #c7d1e6);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background .14s, color .14s;
}
.ism-close-btn:hover {
    background: rgba(255,255,255,.08);
    color: var(--t1, #fff);
}

/* ─── Body ───────────────────────────────────────────────────────────── */
.ism-body { padding: 20px; }

/* ─── Choice cards ───────────────────────────────────────────────────── */
.ism-choice {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.ism-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 22px 10px;
    border-radius: 13px;
    border: 1px solid var(--border, rgba(99,130,179,.14));
    background: var(--surface, rgba(255,255,255,.03));
    cursor: pointer;
    transition: border-color .15s, background .15s, transform .12s;
    text-align: center;
}
.ism-option:hover {
    border-color: var(--cyan, #00e5ff);
    background: rgba(0,229,255,.05);
    transform: translateY(-2px);
}
.ism-option:active { transform: translateY(0); }
.ism-option-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: var(--input, rgba(17,27,42,1));
    border: 1px solid var(--border, rgba(99,130,179,.14));
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--cyan, #00e5ff);
    transition: background .15s;
}
.ism-option:hover .ism-option-icon { background: rgba(0,229,255,.12); }
.ism-option-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--t1, #fff);
}
.ism-option-hint {
    font-size: 11px;
    color: var(--t3, #c7d1e6);
}

/* ─── Camera ─────────────────────────────────────────────────────────── */
.ism-viewfinder {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    background: #000;
    aspect-ratio: 4/3;
}
.ism-video { width: 100%; height: 100%; object-fit: cover; display: block; }
.ism-corners {
    position: absolute;
    inset: 8px;
    width: calc(100% - 16px);
    height: calc(100% - 16px);
    pointer-events: none;
}
.ism-camera-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-top: 16px;
}
.ism-shutter {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 3px solid rgba(0,229,255,.45);
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: border-color .15s;
}
.ism-shutter:hover { border-color: var(--cyan, #00e5ff); }
.ism-shutter-dot {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: var(--cyan, #00e5ff);
    display: block;
    transition: transform .12s;
}
.ism-shutter:active .ism-shutter-dot { transform: scale(.87); }

/* ─── Preview ────────────────────────────────────────────────────────── */
.ism-preview-frame {
    border-radius: 12px;
    overflow: hidden;
    background: var(--input, rgba(17,27,42,1));
    border: 1px solid var(--border, rgba(99,130,179,.14));
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 200px;
}
.ism-preview-img {
    max-height: 260px;
    max-width: 100%;
    object-fit: contain;
    display: block;
}
.ism-preview-bar {
    display: flex;
    gap: 10px;
    margin-top: 14px;
}

/* ─── Buttons ────────────────────────────────────────────────────────── */
.ism-btn-primary {
    flex: 2;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 10px 16px;
    border-radius: 9px;
    background: var(--cyan, #00e5ff);
    color: #060c18;
    font-size: 13px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: opacity .15s, transform .12s;
}
.ism-btn-primary:hover { opacity: .87; }
.ism-btn-primary:active { transform: scale(.97); }
.ism-btn-ghost {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 14px;
    border-radius: 9px;
    background: var(--surface, rgba(255,255,255,.04));
    border: 1px solid var(--border, rgba(99,130,179,.14));
    color: var(--t2, #e8edf7);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .14s;
}
.ism-btn-ghost:hover { background: rgba(255,255,255,.08); }

/* ─── Loading ────────────────────────────────────────────────────────── */
.ism-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    padding: 40px 0;
}
.ism-spinner {
    display: block;
    width: 36px;
    height: 36px;
    border: 3px solid var(--border, rgba(99,130,179,.2));
    border-top-color: var(--cyan, #00e5ff);
    border-radius: 50%;
    animation: ism-spin .75s linear infinite;
}
@keyframes ism-spin { to { transform: rotate(360deg); } }
.ism-loading-text { font-size: 13px; color: var(--t3, #c7d1e6); }

/* ─── Error ──────────────────────────────────────────────────────────── */
.ism-error {
    margin-top: 12px;
    padding: 11px 14px;
    border-radius: 9px;
    background: rgba(239,68,68,.1);
    border: 1px solid rgba(239,68,68,.25);
    font-size: 13px;
    color: #fca5a5;
    line-height: 1.5;
}

/* ─── Light-mode overrides (storefront / light panels) ───────────────── */
@media (prefers-color-scheme: light) {
    .ism-sheet {
        background: #ffffff;
        border-color: rgba(30,60,120,.1);
        box-shadow: 0 28px 72px rgba(0,0,0,.16);
    }
    .ism-header-icon { background: rgba(0,151,196,.1); border-color: rgba(0,151,196,.2); color: #0097c4; }
    .ism-title { color: #0a0e17; }
    .ism-subtitle { color: #6b7280; }
    .ism-close-btn { background: #f4f6fb; border-color: rgba(30,60,120,.1); color: #6b7280; }
    .ism-close-btn:hover { background: #e8ecf5; color: #0a0e17; }
    .ism-option { background: #f8fafc; border-color: rgba(30,60,120,.1); }
    .ism-option:hover { border-color: #0097c4; background: rgba(0,151,196,.05); }
    .ism-option-icon { background: #eef2f9; border-color: rgba(30,60,120,.1); color: #0097c4; }
    .ism-option:hover .ism-option-icon { background: rgba(0,151,196,.12); }
    .ism-option-label { color: #0a0e17; }
    .ism-option-hint { color: #6b7280; }
    .ism-preview-frame { background: #eef2f9; border-color: rgba(30,60,120,.1); }
    .ism-btn-primary { background: #0097c4; color: #fff; }
    .ism-btn-ghost { background: #f4f6fb; border-color: rgba(30,60,120,.1); color: #2d3748; }
    .ism-btn-ghost:hover { background: #e8ecf5; }
    .ism-loading-text { color: #6b7280; }
    .ism-spinner { border-color: rgba(30,60,120,.15); border-top-color: #0097c4; }
    .ism-shutter { border-color: rgba(0,151,196,.45); }
    .ism-shutter-dot { background: #0097c4; }
    .ism-error { background: rgba(239,68,68,.07); border-color: rgba(239,68,68,.2); color: #b91c1c; }
}
</style>
@endonce

@once
    @push('scripts')
        @vite('resources/js/image-search.js')
    @endpush
@endonce
