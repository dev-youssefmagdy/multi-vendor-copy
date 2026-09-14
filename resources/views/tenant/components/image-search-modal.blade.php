@props(['action', 'id' => null])

@php($modalId = $id ?? 'tenant-image-search-modal')

<div id="{{ $modalId }}" class="t-image-search-modal" data-tenant-image-search-modal data-action="{{ $action }}" hidden>
    <div class="t-ism-backdrop" data-ism-backdrop></div>
    <div class="t-ism-sheet">
        <div class="t-ism-header">
            <div class="t-ism-header-left">
                <div class="t-ism-header-icon">
                    <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 6H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1.5l-1.2-1.6A2 2 0 0 0 14.7 5H9.3a2 2 0 0 0-1.6.8L6.5 6z"/>
                        <circle cx="12" cy="13" r="3.2"/>
                    </svg>
                </div>
                <div>
                    <div class="t-ism-title">Search by Image</div>
                    <div class="t-ism-subtitle">Use your camera or upload a file to find similar products</div>
                </div>
            </div>
            <button type="button" class="t-ism-close-btn" data-ism-close aria-label="Close">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="t-ism-body">
            <div class="t-ism-choice" data-ism-choice>
                <button type="button" class="t-ism-option" data-ism-take-photo>
                    <div class="t-ism-option-icon">
                        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 6H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1.5l-1.2-1.6A2 2 0 0 0 14.7 5H9.3a2 2 0 0 0-1.6.8L6.5 6z"/>
                            <circle cx="12" cy="13" r="3.5"/>
                        </svg>
                    </div>
                    <span class="t-ism-option-label">Take Photo</span>
                    <span class="t-ism-option-hint">Use device camera</span>
                </button>
                <button type="button" class="t-ism-option" data-ism-upload>
                    <div class="t-ism-option-icon">
                        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                            <polyline stroke-linecap="round" stroke-linejoin="round" points="16 9 12 4 8 9"/>
                            <line x1="12" y1="4" x2="12" y2="16" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="t-ism-option-label">Upload Image</span>
                    <span class="t-ism-option-hint">JPG &middot; PNG &middot; WEBP &middot; 8 MB</span>
                </button>
            </div>

            <input type="file" accept="image/*" class="t-ism-file-input" data-ism-file-input hidden>

            <div class="t-ism-camera" data-ism-camera hidden>
                <div class="t-ism-viewfinder">
                    <video class="t-ism-video" data-ism-video autoplay playsinline muted></video>
                    <canvas class="t-ism-canvas" data-ism-canvas hidden></canvas>
                </div>
                <div class="t-ism-camera-bar">
                    <button type="button" class="t-ism-btn-ghost" data-ism-cancel-camera>Cancel</button>
                    <button type="button" class="t-ism-shutter" data-ism-capture aria-label="Capture photo">
                        <span class="t-ism-shutter-dot"></span>
                    </button>
                </div>
            </div>

            <div class="t-ism-preview" data-ism-preview hidden>
                <div class="t-ism-preview-frame">
                    <img class="t-ism-preview-img" data-ism-preview-img alt="">
                </div>
                <div class="t-ism-preview-bar">
                    <button type="button" class="t-ism-btn-ghost" data-ism-retake>Retake</button>
                    <button type="button" class="t-ism-btn-primary" data-ism-submit>Find Similar Products</button>
                </div>
            </div>

            <div class="t-ism-loading" data-ism-loading hidden>
                <span class="t-ism-spinner"></span>
                <span class="t-ism-loading-text">Analyzing image&hellip;</span>
            </div>

            <div class="t-ism-error" data-ism-error hidden></div>
        </div>
    </div>
</div>
