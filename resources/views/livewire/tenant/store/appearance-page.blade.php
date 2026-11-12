<main id="mn">
<style>
    .appearance-tabs {
        display: flex;
        gap: 4px;
        padding: 4px;
        border-radius: 16px;
        background: var(--card2);
        border: 1px solid var(--border);
        width: fit-content;
    }
    .appearance-tab {
        padding: 8px 18px;
        border-radius: 12px;
        border: none;
        background: transparent;
        color: var(--t2);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.18s, color 0.18s;
    }
    .appearance-tab.act {
        background: var(--card);
        color: var(--t1);
        box-shadow: var(--shadow-sm);
    }
    .appearance-tab-panel {
        margin-top: 24px;
    }
    .appearance-table-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }
    .locale-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 10px;
        border-radius: 999px;
        background: rgba(255,255,255,0.07);
        color: var(--t2);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }
    .locale-fields-group {
        padding: 16px;
        border-radius: 14px;
        border: 1px solid var(--border2);
        background: rgba(255,255,255,0.02);
        margin-bottom: 12px;
    }
    .logo-preview-shell {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 0;
    }
    .logo-preview-img {
        width: 64px;
        height: 64px;
        object-fit: contain;
        border-radius: 10px;
        border: 1px solid var(--border2);
        background: rgba(255,255,255,0.04);
    }
    .logo-placeholder {
        width: 64px;
        height: 64px;
        border-radius: 10px;
        border: 1px dashed var(--border2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--t3);
        font-size: 11px;
    }
</style>

    {{-- Page header --}}
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                @if ($badge)
                    <span class="page-badge">{{ $badge }}</span>
                @endif
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        @if ($previewUrl)
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="btn btn-secondary">
                {{ __('Preview') }}
            </a>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="appearance-tabs fu d1 section-gap">
        @foreach ([
            'general'      => __('General'),
            'colors'       => __('Colors'),
            'social_links' => __('Social Links'),
            'promo_banner' => __('Promo Banner'),
            'footer'       => __('Footer'),
        ] as $key => $label)
            <button type="button"
                class="appearance-tab {{ $activeTab === $key ? 'act' : '' }}"
                wire:click="setTab('{{ $key }}')">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ─────────────────────────────────── TAB: GENERAL ─────────────────────────────────── --}}
    @if ($activeTab === 'general')
        <div class="appearance-tab-panel fu d2">
            <form wire:submit="saveGeneral" class="page-stack">
                <section class="card form-card">
                    <div class="panel-head mb-5">
                        <div>
                            <h3 class="panel-title">{{ __('Store Identity') }}</h3>
                            <p class="panel-copy">{{ __('Set the display name and logo for the storefront.') }}</p>
                        </div>
                        <x-btn type="submit">{{ __('Save General') }}</x-btn>
                    </div>

                    <div class="form-grid form-grid-2">
                        {{-- Logo --}}
                        @include('livewire.tenant.store.partials.logo-builder')
                    </div>
                </section>
            </form>
        </div>
    @endif

    {{-- ─────────────────────────────────── TAB: COLORS ───────────────────────────────────── --}}
    @if ($activeTab === 'colors')
        <div class="appearance-tab-panel fu d2 page-stack">
            @forelse ($colorThemes as $themeId => $themeSection)
                <details class="card form-card theme-color-group" @if ($themeSection['is_active']) open @endif>
                    <summary class="panel-head mb-0" style="cursor:pointer;list-style:none;">
                        <div>
                            <h3 class="panel-title">
                                {{ $themeSection['name'] }}
                                @if ($themeSection['is_active'])
                                    <span class="badge badge-cyan" style="margin-inline-start:8px;">{{ __('Active Theme') }}</span>
                                @endif
                            </h3>
                            <p class="panel-copy">{{ __(':variantsCount variant(s) with customizable colors.', ['variantsCount' => count($themeSection['variants'])]) }}</p>
                        </div>
                    </summary>

                    <div class="page-stack" style="margin-top:20px;">
                        @foreach ($themeSection['variants'] as $variantId => $section)
                            <section class="card form-card" style="background:transparent;">
                                <div class="panel-head mb-5">
                                    <div>
                                        <h4 class="panel-title" style="font-size:14px;">
                                            {{ $section['name'] }}
                                            @if ($section['is_active'])
                                                <span class="badge badge-green" style="margin-inline-start:8px;">{{ __('Active Variant') }}</span>
                                            @endif
                                        </h4>
                                        <p class="panel-copy">{{ __('Changes apply site-wide whenever this variant is active.') }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <x-btn type="button" variant="secondary" wire:click="resetColors({{ $themeId }}, {{ $variantId }})">{{ __('Reset to Default') }}</x-btn>
                                        <x-btn type="button" wire:click="saveColors({{ $themeId }}, {{ $variantId }})">{{ __('Save Colors') }}</x-btn>
                                    </div>
                                </div>

                                <div class="form-grid form-grid-2">
                                    @foreach ($section['defaults'] as $property => $default)
                                        <div>
                                            <label class="field-label">{{ __((string) \Illuminate\Support\Str::headline(str_replace('--color-', '', $property))) }}</label>
                                            <div class="flex items-center gap-3">
                                                <input type="color"
                                                    wire:model.live="colorThemes.{{ $themeId }}.variants.{{ $variantId }}.values.{{ $property }}"
                                                    style="width:44px;height:38px;padding:2px;border-radius:8px;" />
                                                <x-input type="text" wire:model.live="colorThemes.{{ $themeId }}.variants.{{ $variantId }}.values.{{ $property }}"
                                                    :error="$errors->has('colorThemes.' . $themeId . '.variants.' . $variantId . '.values.' . $property)" />
                                            </div>
                                            @error('colorThemes.' . $themeId . '.variants.' . $variantId . '.values.' . $property)<div class="field-error">{{ $message }}</div>@enderror
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                </details>
            @empty
                <div class="card form-card">
                    <div class="empty-state">
                        <div class="empty-state-title">{{ __('No customizable colors yet') }}</div>
                        <p class="empty-state-copy">{{ __('None of your storefront themes expose variants with color customization.') }}</p>
                    </div>
                </div>
            @endforelse
        </div>
    @endif

    {{-- ──────────────────────────────── TAB: SOCIAL LINKS ──────────────────────────────── --}}
    @if ($activeTab === 'social_links')
        <div class="appearance-tab-panel fu d2">
            <div class="card table-card-shell">
                <div class="table-header-shell">
                    <div>
                        <h3 class="panel-title">{{ __('Social Links') }}</h3>
                        <p class="panel-copy">{{ __('Social media profile links displayed in the storefront.') }}</p>
                    </div>
                    <div class="table-header-actions">
                        <x-btn type="button" wire:click="openSocialModal()">{{ __('Add Social Link') }}</x-btn>
                    </div>
                </div>

                <x-table :headers="[__('Icon'), 'URL', __('Order'), __('Actions')]">
                    @forelse ($socialLinks as $link)
                        <tr>
                            <td>
                                <span class="badge badge-cyan" style="text-transform:capitalize;">
                                    {{ e($link->icon->name) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ e($link->url) }}" target="_blank" rel="noopener"
                                    class="panel-copy" style="text-decoration:underline;word-break:break-all;">
                                    {{ e(\Illuminate\Support\Str::limit($link->url, 50)) }}
                                </a>
                            </td>
                            <td>{{ e($link->serial_number) }}</td>
                            <td>
                                <div class="flex gap-2">
                                    <x-btn type="button" variant="secondary" class="btn-sm"
                                        wire:click="openSocialModal({{ $link->id }})">{{ __('Edit') }}</x-btn>
                                    <x-btn type="button" variant="secondary" class="btn-sm"
                                        wire:click="confirmDeleteSocial({{ $link->id }})">{{ __('Delete') }}</x-btn>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <div class="empty-state-title">{{ __('No social links yet') }}</div>
                                    <p class="empty-state-copy">{{ __('Add social media links to display in your storefront.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </div>
        </div>

        {{-- Social link modal --}}
        <x-modal wire:model="socialModalOpen"
            title="{{ $socialId ? __('Edit Social Link') : __('Add Social Link') }}"
            maxWidth="2xl"
            closeAction="closeSocialModal">
            <form wire:submit="saveSocialLink" class="page-stack">
                <div class="form-grid form-grid-2">
                    <div>
                        <label class="field-label">{{ __('Icon / Platform') }}</label>
                        <x-select wire:model.defer="socialIcon" class="{{ $errors->has('socialIcon') ? 'is-invalid' : '' }}">
                            @foreach ([
                                'facebook'  => __('Facebook'),
                                'instagram' => __('Instagram'),
                                'twitter'   => __('Twitter / X'),
                                'youtube'   => 'YouTube',
                                'tiktok'    => 'TikTok',
                                'linkedin'  => 'LinkedIn',
                                'pinterest' => __('Pinterest'),
                                'snapchat'  => __('Snapchat'),
                                'whatsapp'  => 'WhatsApp',
                                'telegram'  => __('Telegram'),
                            ] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        @error('socialIcon')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="span-2">
                        <label class="field-label">{{ __('Profile URL') }}</label>
                        <x-input type="url" wire:model.defer="socialUrl"
                            placeholder="https://..."
                            :error="$errors->has('socialUrl')" />
                        @error('socialUrl')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="page-actions compact-actions justify-end">
                    <x-btn type="button" variant="secondary" wire:click="closeSocialModal">{{ __('Cancel') }}</x-btn>
                    <x-btn type="submit">
                        {{ $socialId ? __('Update Link') : __('Add Link') }}
                    </x-btn>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- ─────────────────────────────────── TAB: PROMO BANNER ─────────────────────────────────── --}}
    @if ($activeTab === 'promo_banner')
        <div class="appearance-tab-panel fu d2">
            <form wire:submit="savePromoBanner" class="page-stack">
                <section class="card form-card">
                    <div class="panel-head mb-5">
                        <div>
                            <h3 class="panel-title">{{ __('Promotional Banner') }}</h3>
                            <p class="panel-copy">{{ __('Configure the promotional banner shown on your homepage. Leave the image and title empty to hide it.') }}</p>
                        </div>
                        <x-btn type="submit">{{ __('Save Promo Banner') }}</x-btn>
                    </div>

                    <div class="form-grid form-grid-2">
                        <div>
                            <label class="field-label">{{ __('Title') }}</label>
                            <x-input type="text" wire:model.defer="promoBannerTitle"
                                :placeholder="__('Summer Sale')" :error="$errors->has('promoBannerTitle')" />
                            @error('promoBannerTitle')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="field-label">{{ __('Call-to-Action Text') }}</label>
                            <x-input type="text" wire:model.defer="promoBannerCtaText"
                                :placeholder="__('Shop Now')" :error="$errors->has('promoBannerCtaText')" />
                            @error('promoBannerCtaText')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="span-2">
                            <label class="field-label">{{ __('Subtitle') }}</label>
                            <x-input type="text" wire:model.defer="promoBannerSubtitle"
                                :placeholder="__('Up to 50% off selected items')" :error="$errors->has('promoBannerSubtitle')" />
                            @error('promoBannerSubtitle')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="field-label">{{ __('Link URL') }}</label>
                            <x-input type="url" wire:model.defer="promoBannerLink"
                                placeholder="https://" :error="$errors->has('promoBannerLink')" />
                            @error('promoBannerLink')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="field-label">{{ __('Image URL') }}</label>
                            <x-input type="url" wire:model.defer="promoBannerImageUrl"
                                placeholder="https://" :error="$errors->has('promoBannerImageUrl')" />
                            @error('promoBannerImageUrl')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="span-2">
                            <label class="field-label">{{ __('Featured Products Card Image (Souqify)') }}</label>
                            <x-input type="url" wire:model.defer="souqifyFeaturedPromoImageUrl"
                                placeholder="https://" :error="$errors->has('souqifyFeaturedPromoImageUrl')" />
                            <p class="panel-copy">{{ __('Background of the discount card beside Featured Products on the Souqify home page. Leave empty to use the default.') }}</p>
                            @error('souqifyFeaturedPromoImageUrl')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>
            </form>
        </div>
    @endif

    {{-- ──────────────────────────────────── TAB: FOOTER ────────────────────────────────── --}}
    @if ($activeTab === 'footer')
        <div class="appearance-tab-panel fu d2">
            <form wire:submit="saveFooter" class="page-stack">
                <section class="card form-card">
                    <div class="panel-head mb-5">
                        <div>
                            <h3 class="panel-title">{{ __('Footer Settings') }}</h3>
                            <p class="panel-copy">{{ __('Manage the text and copyright notice displayed in the storefront footer. Provide a translation for each enabled language.') }}</p>
                        </div>
                        <x-btn type="submit">{{ __('Save Footer') }}</x-btn>
                    </div>

                    @foreach ($languages as $language)
                        <div class="locale-fields-group">
                            <div class="locale-badge">{{ $language->native_name ?? $language->name }} ({{ $language->code }})</div>
                            <div class="form-grid">
                                <div>
                                    <label class="field-label">{{ __('Footer Text') }}</label>
                                    <x-textarea rows="4"
                                        wire:model.defer="footerTranslations.{{ $language->code }}.footer_text"
                                        :error="$errors->has('footerTranslations.' . $language->code . '.footer_text')" />
                                    @error('footerTranslations.' . $language->code . '.footer_text')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="field-label">{{ __('Copyright') }}</label>
                                    <x-input type="text"
                                        wire:model.defer="footerTranslations.{{ $language->code }}.footer_copyright"
                                        :placeholder="__('© :date Your Store. All rights reserved.', ['date' => date('Y')])"
                                        :error="$errors->has('footerTranslations.' . $language->code . '.footer_copyright')" />
                                    @error('footerTranslations.' . $language->code . '.footer_copyright')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </section>
            </form>
        </div>
    @endif

</main>
