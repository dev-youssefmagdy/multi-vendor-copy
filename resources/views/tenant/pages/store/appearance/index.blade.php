@extends('tenant.layouts.app')

@section('title', 'Appearance')

@section('content')
    <x-tenant::page-header title="Appearance" badge="Storefront" description="Manage your storefront logo, banners, social links, and footer.">
        <x-slot:actions>
            @if($previewUrl)
                <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="btn btn-secondary">Preview</a>
            @endif
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::tabs mode="hash" :active="$activeTab" :tabs="[
        'general' => 'General',
        'colors' => 'Colors & Fonts',
        'social_links' => 'Social Links',
        'top_header_links' => 'Top Header Links',
        'promo_banner' => 'Promo Banner',
        'footer' => 'Footer',
    ]">
        {{-- ─────────────────────────────── GENERAL ─────────────────────────────── --}}
        <x-tenant::tab-panel key="general" :active="$activeTab === 'general'">
            <x-tenant::form id="appearance-general-form"
                :action="route('tenant.store.appearance.general')"
                method="PUT"
                :validate="route('tenant.store.appearance.general.validate')"
                :files="true"
                success="none">
                <section class="card form-card">
                    <div class="panel-head mb-5">
                        <div>
                            <h3 class="panel-title">Store Identity</h3>
                            <p class="panel-copy">Set the display name and logo for the storefront.</p>
                        </div>
                        <button type="submit" class="btn btn-primary">Save General</button>
                    </div>

                    <div class="form-grid form-grid-2">
                        @include('tenant.pages.store._logo-builder', [
                            'logoMode' => $general['logo_mode'],
                            'logoTextAr' => $general['logo_text_ar'],
                            'logoTextEn' => $general['logo_text_en'],
                            'logoColor' => $general['logo_color'],
                            'logoBgColor' => $general['logo_bg_color'],
                            'logoShape' => $general['logo_shape'],
                            'logoFontAr' => $general['logo_font_ar'],
                            'logoFontEn' => $general['logo_font_en'],
                            'logoPathAr' => $general['logo_path_ar'],
                            'logoPathEn' => $general['logo_path_en'],
                            'logoFonts' => $logoFonts,
                        ])
                    </div>
                </section>
            </x-tenant::form>
        </x-tenant::tab-panel>

        {{-- ─────────────────────────────── COLORS & FONTS ─────────────────────────────── --}}
        <x-tenant::tab-panel key="colors" :active="$activeTab === 'colors'">
            @php
                $ltrFonts = \App\Support\ThemeDesign\ThemeFonts::LTR;
                $rtlFonts = \App\Support\ThemeDesign\ThemeFonts::RTL;
                $previewFonts = collect([
                    \App\Support\ThemeDesign\ThemeFonts::googleStylesheetUrl('ltr', array_keys($ltrFonts)),
                    \App\Support\ThemeDesign\ThemeFonts::googleStylesheetUrl('rtl', array_keys($rtlFonts)),
                ])->filter();
            @endphp
            @foreach($previewFonts as $previewFontUrl)
                <link rel="stylesheet" href="{{ $previewFontUrl }}">
            @endforeach

            <div class="page-stack">
                <div class="card form-card t-design-intro">
                    <h3 class="panel-title">Colors &amp; fonts per theme edition</h3>
                    <p class="panel-copy">
                        Each storefront theme comes in several editions. Every edition has its own set of colors and its own fonts,
                        so a change only affects the edition you edit. Under each color you can read what it changes in the store.
                        The active edition shows your changes right after you save; other editions keep their settings for when you switch.
                        Use <strong>Reset to Default</strong> to return an edition to the design it shipped with.
                    </p>
                    <div class="t-design-legend">
                        <span><strong>Fonts</strong> — one for left-to-right languages (English&hellip;) and one for right-to-left languages (Arabic&hellip;); only the font of the language being viewed is loaded.</span>
                        <span><strong>Colors</strong> — grouped by where they appear (brand, header, text, backgrounds, status, flash sale, footer&hellip;).</span>
                    </div>
                </div>

                @forelse($colorThemes as $themeId => $themeSection)
                    <details class="card form-card theme-color-group" @if($themeSection['is_active']) open @endif>
                        <summary class="panel-head mb-0 t-color-theme-summary">
                            <div>
                                <h3 class="panel-title">
                                    {{ $themeSection['name'] }}
                                    @if($themeSection['is_active'])
                                        <span class="badge badge-cyan t-color-theme-badge">Active Theme</span>
                                    @endif
                                </h3>
                                <p class="panel-copy">{{ count($themeSection['variants']) }} edition(s) &middot; each with its own colors and fonts.</p>
                            </div>
                        </summary>

                        <div class="page-stack t-color-variant-list">
                            @foreach($themeSection['variants'] as $variantId => $section)
                                @php $formId = 'colors-form-'.$themeId.'-'.$variantId; @endphp
                                <x-tenant::form :id="$formId"
                                    :action="route('tenant.store.appearance.colors', [$themeId, $variantId])"
                                    method="PUT"
                                    :validate="route('tenant.store.appearance.colors.validate', [$themeId, $variantId])"
                                    data-design-form :data-roles="json_encode($section['roles'])">
                                    <section class="card form-card t-color-variant-card">
                                        <div class="panel-head">
                                            <div>
                                                <h4 class="panel-title t-color-variant-title">
                                                    {{ $section['name'] }}
                                                    @if($section['is_active'])
                                                        <span class="badge badge-green t-color-theme-badge">Active Edition</span>
                                                    @endif
                                                </h4>
                                                <p class="panel-copy">{{ $section['description'] }}</p>
                                            </div>
                                            <div class="panel-actions">
                                                <button type="button" class="btn btn-secondary t-reset-colors-btn"
                                                    data-reset-colors-url="{{ route('tenant.store.appearance.colors.reset', [$themeId, $variantId]) }}"
                                                    data-reset-colors-form="{{ $formId }}">Reset to Default</button>
                                                <button type="submit" class="btn btn-primary">Save Colors &amp; Fonts</button>
                                            </div>
                                        </div>

                                        <div class="t-design-layout">
                                        <div class="t-design-main">
                                        {{-- Fonts --}}
                                        <div class="t-design-block">
                                            <h5 class="t-design-block-title">Fonts</h5>
                                            <p class="t-design-block-copy">Applied to every text of the storefront for this edition (menus, product cards, buttons, headings). The store-name text logo keeps the font chosen under General.</p>
                                            <div class="form-grid form-grid-2">
                                                @foreach(['ltr' => ['Left-to-right font', 'English, French and other Latin languages', $ltrFonts, 'The quick brown fox jumps over the lazy dog 0123456789'],
                                                          'rtl' => ['Right-to-left font', 'Arabic and other right-to-left languages', $rtlFonts, 'نص تجريبي لمعاينة الخط المختار في المتجر ٠١٢٣٤٥٦٧٨٩']] as $dir => [$fontLabel, $fontHelp, $fontOptions, $sample])
                                                    @php
                                                        $current = $section['fonts']['values'][$dir];
                                                        $default = $section['fonts']['defaults'][$dir];
                                                    @endphp
                                                    <div class="t-field" data-field="fonts.{{ $dir }}">
                                                        <label class="field-label" for="{{ $formId }}-font-{{ $dir }}">{{ $fontLabel }}</label>
                                                        <select class="field-control" id="{{ $formId }}-font-{{ $dir }}" name="fonts[{{ $dir }}]"
                                                            data-font-select data-default="{{ $default }}">
                                                            @foreach($fontOptions as $fontKey => $font)
                                                                <option value="{{ $fontKey }}" data-stack="{{ $font[1] }}" @selected($current === $fontKey)>
                                                                    {{ $font[0] }} — {{ $font[3] }}{{ $fontKey === $default ? ' (default)' : '' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <p class="t-help">{{ $fontHelp }}. Default: {{ $fontOptions[$default][0] ?? $default }}.</p>
                                                        <div class="t-font-preview" data-font-preview dir="{{ $dir }}" style="font-family: {{ $fontOptions[$current][1] ?? 'inherit' }}">{{ $sample }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Colors --}}
                                        <div class="t-design-block">
                                            <div class="t-design-block-head">
                                                <div>
                                                    <h5 class="t-design-block-title">Colors <span class="t-design-count">{{ count($section['defaults']) }}</span></h5>
                                                    <p class="t-design-block-copy">Every color this edition uses. Pick a color or type a hex code; use the small reset link to restore a single color.</p>
                                                </div>
                                                <input type="search" class="field-control t-design-search" placeholder="Search colors (e.g. header, footer, price)…" data-design-search autocomplete="off">
                                            </div>

                                            @foreach($section['groups'] as $groupName => $tokens)
                                                <details class="t-design-group" open data-design-group>
                                                    <summary><span>{{ $groupName }}</span> <span class="t-design-count">{{ count($tokens) }}</span></summary>
                                                    @include('tenant.pages.store.appearance._design-group-preview', ['groupName' => $groupName, 'tokens' => $tokens, 'section' => $section])
                                                    <div class="form-grid form-grid-2">
                                                        @foreach($tokens as $property => $token)
                                                            @php
                                                                $help = $token['description'] . ($token['areas'] ? ' Affects: ' . implode(', ', $token['areas']) . '.' : '');
                                                            @endphp
                                                            <div class="t-design-token" data-design-token data-token="{{ $property }}" data-search="{{ strtolower($token['label'].' '.$token['description'].' '.implode(' ', $token['areas']).' '.$property.' '.$groupName) }}">
                                                                <x-tenant::color
                                                                    :name="'values.'.$property"
                                                                    :label="$token['label']"
                                                                    :value="$section['values'][$property] ?? $token['default']"
                                                                    :help="$help"
                                                                />
                                                                <div class="t-design-token-meta">
                                                                    <code>{{ $property }}</code>
                                                                    <button type="button" class="t-link-btn" data-default-color="{{ $token['default'] }}">Default {{ $token['default'] }}</button>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endforeach
                                            <p class="t-design-empty" data-design-empty hidden>No colors match your search.</p>
                                        </div>
                                        </div>
                                        @include('tenant.pages.store.appearance._design-scene')
                                        </div>
                                    </section>
                                </x-tenant::form>
                            @endforeach
                        </div>
                    </details>
                @empty
                    <div class="card form-card">
                        <div class="empty-state">
                            <div class="empty-state-title">No customizable design yet</div>
                            <p class="empty-state-copy">None of your storefront themes expose editions with color and font customization.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </x-tenant::tab-panel>

        {{-- ────────────────────────────── SOCIAL LINKS ────────────────────────────── --}}
        <x-tenant::tab-panel key="social_links" :active="$activeTab === 'social_links'">
            <div class="card table-card-shell ds-table">
                <div class="table-header-shell">
                    <div>
                        <h3 class="panel-title">Social Links</h3>
                        <p class="panel-copy">Social media profile links displayed in the storefront.</p>
                    </div>
                    <div class="table-header-actions">
                        <button type="button" class="btn btn-primary" data-modal-open="social-link-modal">Add Social Link</button>
                    </div>
                </div>

                <x-tenant::datatable id="social-links-table"
                    mode="client"
                    :rows="$socialLinks->map(fn ($link) => [
                        view('tenant.pages.store.appearance._cols.social-icon', ['link' => $link])->render(),
                        view('tenant.pages.store.appearance._cols.social-url', ['link' => $link])->render(),
                        e($link->serial_number),
                        view('tenant.pages.store.appearance._cols.social-actions', ['link' => $link])->render(),
                    ])->values()->all()"
                    :columns="[
                        ['title' => 'Icon'],
                        ['title' => 'URL'],
                        ['title' => 'Order'],
                        ['title' => 'Actions'],
                    ]"
                    empty-title="No social links yet"
                    empty-copy="Add social media links to display in your storefront." />
            </div>

            <x-tenant::modal id="social-link-modal" title="Add / Edit Social Link">
                <x-tenant::form id="social-link-form"
                    :action="route('tenant.store.appearance.social.store')"
                    method="POST"
                    :validate="route('tenant.store.appearance.social.validate')"
                    success="close-modal reload-page">
                    <div class="form-grid form-grid-2">
                        <x-tenant::select name="icon" label="Icon / Platform" required :options="[
                            'facebook' => 'Facebook',
                            'twitter' => 'Twitter / X',
                            'instagram' => 'Instagram',
                            'linkedin' => 'LinkedIn',
                            'youtube' => 'YouTube',
                            'whatsapp' => 'WhatsApp',
                        ]" />
                        <x-tenant::input type="number" min="0" name="serial_number" label="Order" required value="0" />
                        <div class="span-2">
                            <x-tenant::input type="url" name="url" label="Profile URL" placeholder="https://..." required maxlength="500" />
                        </div>
                    </div>

                    <div class="page-actions compact-actions justify-end t-modal-actions">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Link</button>
                    </div>
                </x-tenant::form>
            </x-tenant::modal>
        </x-tenant::tab-panel>

        {{-- ─────────────────────────── TOP HEADER LINKS ─────────────────────────── --}}
        <x-tenant::tab-panel key="top_header_links" :active="$activeTab === 'top_header_links'">
            <x-tenant::form id="appearance-top-header-links-form"
                :action="route('tenant.store.appearance.top-header-links')"
                method="PUT"
                :validate="route('tenant.store.appearance.top-header-links.validate')"
                success="none">
                <section class="card form-card">
                    <div class="panel-head mb-5">
                        <div>
                            <h3 class="panel-title">Top Header Links</h3>
                            <p class="panel-copy">Customize the two links shown in the top utility bar of the storefront. Leave a URL blank to use the default.</p>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Links</button>
                    </div>

                    <div class="form-grid form-grid-2">
                        <x-tenant::input name="top_header_link_1_text" label="Link 1 – Text" placeholder="Find a Store" maxlength="80" :value="$topHeaderLinks['top_header_link_1_text']" />
                        <x-tenant::input type="url" name="top_header_link_1_url" label="Link 1 – URL" placeholder="https://" maxlength="500" :value="$topHeaderLinks['top_header_link_1_url']" />
                        <x-tenant::input name="top_header_link_2_text" label="Link 2 – Text" placeholder="Shop" maxlength="80" :value="$topHeaderLinks['top_header_link_2_text']" />
                        <x-tenant::input type="url" name="top_header_link_2_url" label="Link 2 – URL" placeholder="https://" maxlength="500" :value="$topHeaderLinks['top_header_link_2_url']" />
                    </div>
                </section>
            </x-tenant::form>
        </x-tenant::tab-panel>

        {{-- ────────────────────────────── PROMO BANNER ────────────────────────────── --}}
        <x-tenant::tab-panel key="promo_banner" :active="$activeTab === 'promo_banner'">
            <x-tenant::form id="appearance-promo-banner-form"
                :action="route('tenant.store.appearance.promo-banner')"
                method="PUT"
                :validate="route('tenant.store.appearance.promo-banner.validate')"
                :files="true"
                success="none">
                <section class="card form-card">
                    <div class="panel-head mb-5">
                        <div>
                            <h3 class="panel-title">Promotional Banner</h3>
                            <p class="panel-copy">Configure the promotional banner shown on your homepage. Leave the image and title empty to hide it.</p>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Promo Banner</button>
                    </div>

                    <div class="form-grid form-grid-2">
                        <x-tenant::input name="promo_banner_title" label="Title" placeholder="Summer Sale" maxlength="120" :value="$promoBanner['promo_banner_title']" />
                        <x-tenant::input name="promo_banner_cta_text" label="Call-to-Action Text" placeholder="Shop Now" maxlength="40" :value="$promoBanner['promo_banner_cta_text']" />
                        <div class="span-2">
                            <x-tenant::input name="promo_banner_subtitle" label="Subtitle" placeholder="Up to 50% off selected items" maxlength="255" :value="$promoBanner['promo_banner_subtitle']" />
                        </div>
                        <div class="span-2">
                            <x-tenant::input type="url" name="promo_banner_link" label="Link URL" placeholder="https://" maxlength="500" :value="$promoBanner['promo_banner_link']" />
                        </div>
                        <x-tenant::image-upload
                            name="promo_banner_image"
                            label="Banner Image (Desktop)"
                            :current="$promoBanner['promo_banner_image_url'] ?: null"
                            :removable="true"
                            aspect="16/5"
                            help="Recommended: 1280 × 400 px. Max 4 MB." />
                        <x-tenant::image-upload
                            name="promo_banner_mobile_image"
                            label="Banner Image (Mobile)"
                            :current="$promoBanner['promo_banner_mobile_image_url'] ?: null"
                            :removable="true"
                            aspect="4/3"
                            help="Recommended: 800 × 600 px. Max 4 MB." />
                    </div>
                </section>
            </x-tenant::form>
        </x-tenant::tab-panel>

        {{-- ───────────────────────────────── FOOTER ───────────────────────────────── --}}
        <x-tenant::tab-panel key="footer" :active="$activeTab === 'footer'">
            <x-tenant::form id="appearance-footer-form"
                :action="route('tenant.store.appearance.footer')"
                method="PUT"
                :validate="route('tenant.store.appearance.footer.validate')"
                :files="true"
                success="none">
                <section class="card form-card">
                    <div class="panel-head mb-5">
                        <div>
                            <h3 class="panel-title">Store Identity – Footer</h3>
                            <p class="panel-copy">Set the logo displayed in the storefront footer. Supports text and image modes, just like the header logo.</p>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Footer</button>
                    </div>

                    <div class="form-grid form-grid-2">
                        @include('tenant.pages.store._footer-logo-builder', [
                            'footerLogoMode'    => $footerLogo['footer_logo_mode'],
                            'footerLogoTextAr'  => $footerLogo['footer_logo_text_ar'],
                            'footerLogoTextEn'  => $footerLogo['footer_logo_text_en'],
                            'footerLogoColor'   => $footerLogo['footer_logo_color'],
                            'footerLogoBgColor' => $footerLogo['footer_logo_bg_color'],
                            'footerLogoShape'   => $footerLogo['footer_logo_shape'],
                            'footerLogoFontAr'  => $footerLogo['footer_logo_font_ar'],
                            'footerLogoFontEn'  => $footerLogo['footer_logo_font_en'],
                            'footerLogoPathAr'  => $footerLogo['footer_logo_path_ar'],
                            'footerLogoPathEn'  => $footerLogo['footer_logo_path_en'],
                            'footerLogoWidth'   => $footerLogo['footer_logo_width'],
                            'logoFonts'         => $logoFonts,
                        ])
                    </div>
                </section>

                <section class="card form-card">
                    <div class="panel-head mb-5">
                        <div>
                            <h3 class="panel-title">Footer Settings</h3>
                            <p class="panel-copy">Manage the text and copyright notice displayed in the storefront footer. Provide a translation for each enabled language.</p>
                        </div>
                    </div>

                    <x-tenant::locale-tabs :languages="$languages->map(fn ($l) => ['code' => $l->code, 'name' => $l->native_name ?? $l->name, 'is_default' => $l->is_default])" :active="$activeLocale">
                        @foreach($languages as $language)
                            <x-tenant::locale-pane :code="$language->code" :active="$language->code === $activeLocale">
                                <div class="form-grid">
                                    <x-tenant::textarea :name="'translations.'.$language->code.'.footer_text'" label="Footer Text" rows="4" :value="$footerTranslations[$language->code]['footer_text'] ?? ''" maxlength="1000" />
                                    <x-tenant::input :name="'translations.'.$language->code.'.footer_copyright'" label="Copyright" placeholder="© {{ date('Y') }} Your Store. All rights reserved." :value="$footerTranslations[$language->code]['footer_copyright'] ?? ''" maxlength="255" />
                                </div>
                            </x-tenant::locale-pane>
                        @endforeach
                    </x-tenant::locale-tabs>
                </section>
            </x-tenant::form>
        </x-tenant::tab-panel>
    </x-tenant::tabs>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/appearance.js')
@endpush
