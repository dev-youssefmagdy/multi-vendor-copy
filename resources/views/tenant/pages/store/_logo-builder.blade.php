{{--
    Shared logo builder partial — used by Store ▸ Appearance (General tab) and,
    eventually, Onboarding. Keep this partial's expected variables minimal and
    stable so it stays @include-able unmodified from both places.

    Expected variables:
      $logoMode      string   'text' | 'image'
      $logoTextAr    string   Arabic logo text
      $logoTextEn    string   English logo text
      $logoColor     string   '#RRGGBB'
      $logoBgColor   string   '#RRGGBB' | 'transparent'
      $logoShape     string   'rectangle' | 'rounded'
      $logoFontAr    string   key of $logoFonts['ar']
      $logoFontEn    string   key of $logoFonts['en']
      $logoPathAr    ?string  current uploaded Arabic logo URL
      $logoPathEn    ?string  current uploaded English logo URL
      $logoFonts     array    App\Repositories\Tenant\StorefrontRepository::LOGO_FONTS

    Field names are fixed (logo_mode, logo_text_ar, logo_font_en, logo_upload_ar, …)
    so the including form's FormRequest can validate them the same way regardless
    of which page renders this partial. All live-preview behaviour lives in the
    shared `resources/js/tenant/modules/logo-builder.js` module — this partial
    carries no inline <script> or <style>.
--}}
<div class="t-logo-builder span-2" data-logo-builder
    data-logo-color="{{ $logoColor }}"
    data-logo-bg-color="{{ $logoBgColor }}"
    data-logo-shape="{{ $logoShape }}"
    data-logo-font-ar="{{ $logoFontAr }}"
    data-logo-font-en="{{ $logoFontEn }}">
    <label class="field-label">Logo</label>

    <input type="hidden" name="logo_mode" value="{{ $logoMode }}" data-logo-mode-input>

    <div class="logo-builder-modes">
        <button type="button" class="logo-builder-mode {{ $logoMode === 'text' ? 'act' : '' }}" data-logo-mode-btn="text">Text Logo</button>
        <button type="button" class="logo-builder-mode {{ $logoMode === 'image' ? 'act' : '' }}" data-logo-mode-btn="image">Image Logo</button>
    </div>

    {{-- ─── Text mode ─── --}}
    <div data-logo-mode-panel="text" @if($logoMode !== 'text') hidden @endif>
        <div class="logo-builder-colors">
            <x-tenant::color name="logo_color" label="Text Color" :value="$logoColor" wrapper-class="logo-builder-color-field" />
            <x-tenant::color name="logo_bg_color" label="Background Color" :value="$logoBgColor" allow-transparent wrapper-class="logo-builder-color-field" />
            <x-tenant::select name="logo_shape" label="Shape" :value="$logoShape" :options="['rectangle' => 'Rectangle', 'rounded' => 'Rounded']" wrapper-class="logo-builder-shape-field" />
        </div>

        <div class="logo-builder-grid">
            {{-- Arabic --}}
            <div class="locale-fields-group">
                <span class="locale-badge">Arabic</span>

                <x-tenant::input type="text" dir="rtl" name="logo_text_ar" label="Logo Text" :value="$logoTextAr" data-logo-text="ar" />

                <x-tenant::field label="Font">
                    <select class="field-control" name="logo_font_ar" data-logo-font="ar">
                        @foreach($logoFonts['ar'] as $key => $font)
                            <option value="{{ $key }}" data-font-family="{{ $font['family'] }}" style="font-family:{{ $font['family'] }}" @selected($key === $logoFontAr)>{{ $font['label'] }}</option>
                        @endforeach
                    </select>
                </x-tenant::field>

                <div class="logo-builder-preview">
                    <span data-logo-preview="ar" dir="rtl">{{ $logoTextAr !== '' ? $logoTextAr : 'اسم المتجر' }}</span>
                </div>
            </div>

            {{-- English --}}
            <div class="locale-fields-group">
                <span class="locale-badge">English</span>

                <x-tenant::input type="text" name="logo_text_en" label="Logo Text" :value="$logoTextEn" data-logo-text="en" />

                <x-tenant::field label="Font">
                    <select class="field-control" name="logo_font_en" data-logo-font="en">
                        @foreach($logoFonts['en'] as $key => $font)
                            <option value="{{ $key }}" data-font-family="{{ $font['family'] }}" style="font-family:{{ $font['family'] }}" @selected($key === $logoFontEn)>{{ $font['label'] }}</option>
                        @endforeach
                    </select>
                </x-tenant::field>

                <div class="logo-builder-preview">
                    <span data-logo-preview="en">{{ $logoTextEn !== '' ? $logoTextEn : 'Store Name' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Image mode ─── --}}
    <div data-logo-mode-panel="image" @if($logoMode !== 'image') hidden @endif>
        <div class="logo-builder-grid">
            {{-- Arabic --}}
            <div class="locale-fields-group">
                <span class="locale-badge">Arabic</span>
                <x-tenant::image-upload name="logo_upload_ar" label="Upload Arabic logo" :current="$logoPathAr" data-logo-image-input="ar" />
                <div class="logo-builder-preview">
                    @if($logoPathAr)
                        <img src="{{ $logoPathAr }}" alt="Arabic logo" class="logo-preview-img" data-logo-image-preview="ar">
                    @else
                        <img src="" alt="Arabic logo" class="logo-preview-img" data-logo-image-preview="ar" hidden>
                        <span class="panel-copy t-logo-empty" data-logo-image-empty="ar">No Arabic logo uploaded.</span>
                    @endif
                </div>
            </div>

            {{-- English --}}
            <div class="locale-fields-group">
                <span class="locale-badge">English</span>
                <x-tenant::image-upload name="logo_upload_en" label="Upload English logo" :current="$logoPathEn" data-logo-image-input="en" />
                <div class="logo-builder-preview">
                    @if($logoPathEn)
                        <img src="{{ $logoPathEn }}" alt="English logo" class="logo-preview-img" data-logo-image-preview="en">
                    @else
                        <img src="" alt="English logo" class="logo-preview-img" data-logo-image-preview="en" hidden>
                        <span class="panel-copy t-logo-empty" data-logo-image-empty="en">No English logo uploaded.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
