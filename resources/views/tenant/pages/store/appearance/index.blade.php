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
        'colors' => 'Colors',
        'social_links' => 'Social Links',
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

        {{-- ─────────────────────────────── COLORS ─────────────────────────────── --}}
        <x-tenant::tab-panel key="colors" :active="$activeTab === 'colors'">
            <div class="page-stack">
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
                                <p class="panel-copy">{{ count($themeSection['variants']) }} variant(s) with customizable colors.</p>
                            </div>
                        </summary>

                        <div class="page-stack t-color-variant-list">
                            @foreach($themeSection['variants'] as $variantId => $section)
                                <x-tenant::form :id="'colors-form-'.$themeId.'-'.$variantId"
                                    :action="route('tenant.store.appearance.colors', [$themeId, $variantId])"
                                    method="PUT"
                                    :validate="route('tenant.store.appearance.colors.validate', [$themeId, $variantId])"
                                    success="none">
                                    <section class="card form-card t-color-variant-card">
                                        <div class="panel-head mb-5">
                                            <div>
                                                <h4 class="panel-title t-color-variant-title">
                                                    {{ $section['name'] }}
                                                    @if($section['is_active'])
                                                        <span class="badge badge-green t-color-theme-badge">Active Variant</span>
                                                    @endif
                                                </h4>
                                                <p class="panel-copy">Changes apply site-wide whenever this variant is active.</p>
                                            </div>
                                            <div class="flex gap-2">
                                                <button type="button" class="btn btn-secondary t-reset-colors-btn"
                                                    data-reset-colors-url="{{ route('tenant.store.appearance.colors.reset', [$themeId, $variantId]) }}"
                                                    data-reset-colors-form="colors-form-{{ $themeId }}-{{ $variantId }}">Reset to Default</button>
                                                <button type="submit" class="btn btn-primary">Save Colors</button>
                                            </div>
                                        </div>

                                        <div class="form-grid form-grid-2">
                                            @foreach($section['defaults'] as $property => $default)
                                                <x-tenant::color
                                                    :name="'values.'.$property"
                                                    :label="\Illuminate\Support\Str::headline(str_replace('--color-', '', $property))"
                                                    :value="$section['values'][$property] ?? $default"
                                                    allow-transparent
                                                />
                                            @endforeach
                                        </div>
                                    </section>
                                </x-tenant::form>
                            @endforeach
                        </div>
                    </details>
                @empty
                    <div class="card form-card">
                        <div class="empty-state">
                            <div class="empty-state-title">No customizable colors yet</div>
                            <p class="empty-state-copy">None of your storefront themes expose variants with color customization.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </x-tenant::tab-panel>

        {{-- ────────────────────────────── SOCIAL LINKS ────────────────────────────── --}}
        <x-tenant::tab-panel key="social_links" :active="$activeTab === 'social_links'">
            <div class="card table-card-shell">
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

        {{-- ────────────────────────────── PROMO BANNER ────────────────────────────── --}}
        <x-tenant::tab-panel key="promo_banner" :active="$activeTab === 'promo_banner'">
            <x-tenant::form id="appearance-promo-banner-form"
                :action="route('tenant.store.appearance.promo-banner')"
                method="PUT"
                :validate="route('tenant.store.appearance.promo-banner.validate')"
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
                        <x-tenant::input type="url" name="promo_banner_link" label="Link URL" placeholder="https://" maxlength="500" :value="$promoBanner['promo_banner_link']" />
                        <x-tenant::input type="url" name="promo_banner_image_url" label="Image URL" placeholder="https://" maxlength="1000" :value="$promoBanner['promo_banner_image_url']" />
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
                success="none">
                <section class="card form-card">
                    <div class="panel-head mb-5">
                        <div>
                            <h3 class="panel-title">Footer Settings</h3>
                            <p class="panel-copy">Manage the text and copyright notice displayed in the storefront footer. Provide a translation for each enabled language.</p>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Footer</button>
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
