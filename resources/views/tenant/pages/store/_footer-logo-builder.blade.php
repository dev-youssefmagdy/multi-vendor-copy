{{--
    Footer logo builder partial — mirrors `_logo-builder.blade.php` but uses
    the `footer_logo_` field prefix so the footer logo is stored independently
    of the header logo. An extra `footer_logo_width` field controls the image
    display width in the storefront footer.

    Expected variables:
      $footerLogoMode      string   'text' | 'image'
      $footerLogoTextAr    string   Arabic logo text
      $footerLogoTextEn    string   English logo text
      $footerLogoColor     string   '#RRGGBB'
      $footerLogoBgColor   string   '#RRGGBB' | 'transparent'
      $footerLogoShape     string   'rectangle' | 'rounded'
      $footerLogoFontAr    string   key of $logoFonts['ar']
      $footerLogoFontEn    string   key of $logoFonts['en']
      $footerLogoPathAr    ?string  current uploaded Arabic logo URL
      $footerLogoPathEn    ?string  current uploaded English logo URL
      $footerLogoWidth     string   recommended width in px (may be empty)
      $logoFonts           array    App\Repositories\Tenant\StorefrontRepository::LOGO_FONTS
--}}
<div class="t-logo-builder span-2" data-logo-builder="footer_logo_"
    data-logo-color="{{ $footerLogoColor }}"
    data-logo-bg-color="{{ $footerLogoBgColor }}"
    data-logo-shape="{{ $footerLogoShape }}"
    data-logo-font-ar="{{ $footerLogoFontAr }}"
    data-logo-font-en="{{ $footerLogoFontEn }}">
    <label class="field-label">Footer Logo</label>
    <p class="panel-copy" style="margin-top:-0.25rem;margin-bottom:0.75rem">Override the logo shown in the storefront footer. Leave empty to use the same logo as the header.</p>

    <input type="hidden" name="footer_logo_mode" value="{{ $footerLogoMode }}" data-logo-mode-input>

    <div class="logo-builder-modes">
        <button type="button" class="logo-builder-mode {{ $footerLogoMode === 'text' ? 'act' : '' }}" data-logo-mode-btn="text">Text Logo</button>
        <button type="button" class="logo-builder-mode {{ $footerLogoMode === 'image' ? 'act' : '' }}" data-logo-mode-btn="image">Image Logo</button>
    </div>

    {{-- ─── Text mode ─── --}}
    <div data-logo-mode-panel="text" @if($footerLogoMode !== 'text') hidden @endif>
        <div class="logo-builder-colors">
            <x-tenant::color name="footer_logo_color" label="Text Color" :value="$footerLogoColor" wrapper-class="logo-builder-color-field" />
            <x-tenant::color name="footer_logo_bg_color" label="Background Color" :value="$footerLogoBgColor" allow-transparent wrapper-class="logo-builder-color-field" />
            <x-tenant::select name="footer_logo_shape" label="Shape" :value="$footerLogoShape" :options="['rectangle' => 'Rectangle', 'rounded' => 'Rounded']" wrapper-class="logo-builder-shape-field" />
        </div>

        <div class="logo-builder-grid">
            {{-- Arabic --}}
            <div class="locale-fields-group">
                <span class="locale-badge">Arabic</span>

                <x-tenant::input type="text" dir="rtl" name="footer_logo_text_ar" label="Logo Text" :value="$footerLogoTextAr" data-logo-text="ar" />

                <x-tenant::field label="Font">
                    <select class="field-control" name="footer_logo_font_ar" data-logo-font="ar">
                        @foreach($logoFonts['ar'] as $key => $font)
                            <option value="{{ $key }}" data-font-family="{{ $font['family'] }}" style="font-family:{{ $font['family'] }}" @selected($key === $footerLogoFontAr)>{{ $font['label'] }}</option>
                        @endforeach
                    </select>
                </x-tenant::field>

                <div class="logo-builder-preview">
                    <span data-logo-preview="ar" dir="rtl">{{ $footerLogoTextAr !== '' ? $footerLogoTextAr : 'اسم المتجر' }}</span>
                </div>
            </div>

            {{-- English --}}
            <div class="locale-fields-group">
                <span class="locale-badge">English</span>

                <x-tenant::input type="text" name="footer_logo_text_en" label="Logo Text" :value="$footerLogoTextEn" data-logo-text="en" />

                <x-tenant::field label="Font">
                    <select class="field-control" name="footer_logo_font_en" data-logo-font="en">
                        @foreach($logoFonts['en'] as $key => $font)
                            <option value="{{ $key }}" data-font-family="{{ $font['family'] }}" style="font-family:{{ $font['family'] }}" @selected($key === $footerLogoFontEn)>{{ $font['label'] }}</option>
                        @endforeach
                    </select>
                </x-tenant::field>

                <div class="logo-builder-preview">
                    <span data-logo-preview="en">{{ $footerLogoTextEn !== '' ? $footerLogoTextEn : 'Store Name' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Image mode ─── --}}
    <div data-logo-mode-panel="image" @if($footerLogoMode !== 'image') hidden @endif>
        <div class="logo-builder-grid">
            {{-- Arabic --}}
            <div class="locale-fields-group">
                <span class="locale-badge">Arabic</span>
                <x-tenant::image-upload name="footer_logo_upload_ar" label="Upload Arabic footer logo" :current="$footerLogoPathAr" data-logo-image-input="ar" />
                <div class="logo-builder-preview">
                    @if($footerLogoPathAr)
                        <img src="{{ $footerLogoPathAr }}" alt="Arabic footer logo" class="logo-preview-img" data-logo-image-preview="ar">
                    @else
                        <img src="" alt="Arabic footer logo" class="logo-preview-img" data-logo-image-preview="ar" hidden>
                        <span class="panel-copy t-logo-empty" data-logo-image-empty="ar">No Arabic logo uploaded.</span>
                    @endif
                </div>
            </div>

            {{-- English --}}
            <div class="locale-fields-group">
                <span class="locale-badge">English</span>
                <x-tenant::image-upload name="footer_logo_upload_en" label="Upload English footer logo" :current="$footerLogoPathEn" data-logo-image-input="en" />
                <div class="logo-builder-preview">
                    @if($footerLogoPathEn)
                        <img src="{{ $footerLogoPathEn }}" alt="English footer logo" class="logo-preview-img" data-logo-image-preview="en">
                    @else
                        <img src="" alt="English footer logo" class="logo-preview-img" data-logo-image-preview="en" hidden>
                        <span class="panel-copy t-logo-empty" data-logo-image-empty="en">No English logo uploaded.</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="form-grid form-grid-2" style="margin-top:1rem">
            <x-tenant::input
                type="number"
                name="footer_logo_width"
                label="Logo Width (px)"
                placeholder="e.g. 160"
                min="20"
                max="800"
                :value="$footerLogoWidth"
                help="Recommended display width for image logos (e.g. 160). Leave empty to use the theme default." />
        </div>
    </div>
</div>
