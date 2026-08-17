@php
    $fonts = \App\Repositories\Tenant\StorefrontRepository::LOGO_FONTS;
@endphp

<style>
    .logo-builder-modes {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
    }
    .logo-builder-mode {
        padding: 8px 18px;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: var(--card2);
        color: var(--t2);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    .logo-builder-mode.act {
        background: var(--card);
        color: var(--t1);
        box-shadow: var(--shadow-sm);
    }
    .logo-builder-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 12px;
    }
    .logo-builder-preview {
        margin-top: 12px;
        padding: 18px;
        border-radius: 12px;
        border: 1px dashed var(--border2);
        background: #ffffff;
        min-height: 76px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .logo-builder-preview span {
        font-size: 30px;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }
    .logo-builder-grid .locale-fields-group {
        padding: 16px;
        border-radius: 14px;
        border: 1px solid var(--border2);
        background: rgba(255, 255, 255, 0.02);
        margin-bottom: 0;
    }
    .logo-builder-grid .locale-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.07);
        color: var(--t2);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }
    .logo-builder-preview .logo-preview-img {
        max-width: 100%;
        max-height: 56px;
        object-fit: contain;
    }
    .logo-builder-color {
        width: 56px;
        height: 38px;
        padding: 0;
        border: 1px solid var(--border2);
        border-radius: 10px;
        background: transparent;
        cursor: pointer;
    }
</style>

<div class="span-2">
    <label class="field-label">Logo</label>

    <div class="logo-builder-modes">
        @foreach (['text' => 'Text Logo', 'image' => 'Image Logo'] as $mode => $label)
            <button type="button" class="logo-builder-mode {{ $logoMode === $mode ? 'act' : '' }}"
                wire:click="$set('logoMode', '{{ $mode }}')">{{ $label }}</button>
        @endforeach
    </div>

    @if ($logoMode === 'text')
        <div style="margin-bottom:12px;display:flex;flex-wrap:wrap;align-items:center;gap:20px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <label class="field-label" style="margin:0;">Text Color</label>
                <input type="color" class="logo-builder-color" wire:model.live="logoColor" />
                <span class="panel-copy">{{ $logoColor }}</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <label class="field-label" style="margin:0;">Background Color</label>
                <input type="color" class="logo-builder-color" wire:model.live="logoBgColorHex"
                    @if ($logoBgColor === 'transparent') disabled @endif
                    style="{{ $logoBgColor === 'transparent' ? 'opacity:.4;cursor:not-allowed;' : '' }}" />
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="checkbox" wire:click="toggleLogoBgTransparent"
                        @checked($logoBgColor === 'transparent') />
                    <span class="panel-copy">Transparent</span>
                </label>
                <span class="panel-copy">{{ $logoBgColor }}</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <label class="field-label" style="margin:0;">Shape</label>
                <x-select wire:model.live="logoShape" style="width:auto;">
                    <option value="rectangle">Rectangle</option>
                    <option value="rounded">Rounded</option>
                </x-select>
            </div>
        </div>
        @error('logoColor')<div class="field-error">{{ $message }}</div>@enderror
        @error('logoBgColor')<div class="field-error">{{ $message }}</div>@enderror
        @error('logoShape')<div class="field-error">{{ $message }}</div>@enderror

        @php
            $previewRadius = $logoShape === 'rounded' ? '999px' : '10px';
        @endphp

        <div class="logo-builder-grid">
            {{-- Arabic --}}
            <div class="locale-fields-group">
                <span class="locale-badge">Arabic</span>

                <label class="field-label">Logo Text</label>
                <x-input type="text" dir="rtl" wire:model.live.debounce.300ms="logoTextAr"
                    :error="$errors->has('logoTextAr')" />
                @error('logoTextAr')<div class="field-error">{{ $message }}</div>@enderror

                <label class="field-label" style="margin-top:10px;">Font</label>
                <x-select wire:model.live="logoFontAr">
                    @foreach ($fonts['ar'] as $key => $font)
                        <option value="{{ $key }}" style="font-family:{{ $font['family'] }}">{{ $font['label'] }}</option>
                    @endforeach
                </x-select>

                <div class="logo-builder-preview">
                    <span dir="rtl" style="font-family:{{ $fonts['ar'][$logoFontAr]['family'] ?? "'Cairo', sans-serif" }};color:{{ $logoColor }};background-color:{{ $logoBgColor }};border-radius:{{ $previewRadius }};padding:0.4em 0.9em;">{{ $logoTextAr !== '' ? $logoTextAr : 'اسم المتجر' }}</span>
                </div>
            </div>

            {{-- English --}}
            <div class="locale-fields-group">
                <span class="locale-badge">English</span>

                <label class="field-label">Logo Text</label>
                <x-input type="text" wire:model.live.debounce.300ms="logoTextEn"
                    :error="$errors->has('logoTextEn')" />
                @error('logoTextEn')<div class="field-error">{{ $message }}</div>@enderror

                <label class="field-label" style="margin-top:10px;">Font</label>
                <x-select wire:model.live="logoFontEn">
                    @foreach ($fonts['en'] as $key => $font)
                        <option value="{{ $key }}" style="font-family:{{ $font['family'] }}">{{ $font['label'] }}</option>
                    @endforeach
                </x-select>

                <div class="logo-builder-preview">
                    <span style="font-family:{{ $fonts['en'][$logoFontEn]['family'] ?? "'Poppins', sans-serif" }};color:{{ $logoColor }};background-color:{{ $logoBgColor }};border-radius:{{ $previewRadius }};padding:0.4em 0.9em;">{{ $logoTextEn !== '' ? $logoTextEn : 'Store Name' }}</span>
                </div>
            </div>
        </div>
    @else
        <div class="logo-builder-grid">
            {{-- Arabic --}}
            <div class="locale-fields-group">
                <span class="locale-badge">Arabic</span>
                <x-dropzone id="logo-upload-ar" style="width:100%" model="logoUploadAr" :multiple="false"
                    accept="image/*" label="Upload Arabic logo" sublabel="PNG, JPG, SVG, WEBP up to 2MB" />
                @error('logoUploadAr')<div class="field-error">{{ $message }}</div>@enderror

                <div class="logo-builder-preview">
                    @if ($logoUploadAr)
                        <img src="{{ $logoUploadAr->temporaryUrl() }}" alt="Arabic logo preview" class="logo-preview-img" />
                    @elseif ($logoPathAr)
                        <img src="{{ $logoPathAr }}" alt="Arabic logo" class="logo-preview-img" />
                    @else
                        <span class="panel-copy" style="font-size:13px;color:#666;">No Arabic logo uploaded.</span>
                    @endif
                </div>
            </div>

            {{-- English --}}
            <div class="locale-fields-group">
                <span class="locale-badge">English</span>
                <x-dropzone id="logo-upload-en" style="width:100%" model="logoUploadEn" :multiple="false"
                    accept="image/*" label="Upload English logo" sublabel="PNG, JPG, SVG, WEBP up to 2MB" />
                @error('logoUploadEn')<div class="field-error">{{ $message }}</div>@enderror

                <div class="logo-builder-preview">
                    @if ($logoUploadEn)
                        <img src="{{ $logoUploadEn->temporaryUrl() }}" alt="English logo preview" class="logo-preview-img" />
                    @elseif ($logoPathEn)
                        <img src="{{ $logoPathEn }}" alt="English logo" class="logo-preview-img" />
                    @else
                        <span class="panel-copy" style="font-size:13px;color:#666;">No English logo uploaded.</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
