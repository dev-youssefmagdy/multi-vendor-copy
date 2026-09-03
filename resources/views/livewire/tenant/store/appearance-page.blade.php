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
                Preview
            </a>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="appearance-tabs fu d1 section-gap">
        @foreach ([
            'general'      => 'General',
            'colors'       => 'Colors',
            'social_links' => 'Social Links',
            'promo_banner' => 'Promo Banner',
            'footer'       => 'Footer',
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
                            <h3 class="panel-title">Store Identity</h3>
                            <p class="panel-copy">Set the display name and logo for the storefront.</p>
                        </div>
                        <x-btn type="submit">Save General</x-btn>
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
        <div class="appearance-tab-panel fu d2">
            <form wire:submit="saveColors" class="page-stack">
                <section class="card form-card">
                    <div class="panel-head mb-5">
                        <div>
                            <h3 class="panel-title">Storefront Colors</h3>
                            <p class="panel-copy">Customize the colors of your active theme's variant. Changes apply site-wide.</p>
                        </div>
                        <div class="flex gap-2">
                            <x-btn type="button" variant="secondary" wire:click="resetColors">Reset to Default</x-btn>
                            <x-btn type="submit">Save Colors</x-btn>
                        </div>
                    </div>

                    @if (empty($colorDefaults))
                        <div class="empty-state">
                            <div class="empty-state-title">No customizable colors for this variant</div>
                            <p class="empty-state-copy">The active theme variant doesn't expose color customization yet.</p>
                        </div>
                    @else
                        <div class="form-grid form-grid-2">
                            @foreach ($colorDefaults as $property => $default)
                                <div>
                                    <label class="field-label">{{ \Illuminate\Support\Str::headline(str_replace('--color-', '', $property)) }}</label>
                                    <div class="flex items-center gap-3">
                                        <input type="color"
                                            wire:model.live="colorValues.{{ $property }}"
                                            style="width:44px;height:38px;padding:2px;border-radius:8px;" />
                                        <x-input type="text" wire:model.live="colorValues.{{ $property }}"
                                            :error="$errors->has('colorValues.' . $property)" />
                                    </div>
                                    @error('colorValues.' . $property)<div class="field-error">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </form>
        </div>
    @endif

    {{-- ──────────────────────────────── TAB: SOCIAL LINKS ──────────────────────────────── --}}
    @if ($activeTab === 'social_links')
        <div class="appearance-tab-panel fu d2">
            <div class="card table-card-shell">
                <div class="table-header-shell">
                    <div>
                        <h3 class="panel-title">Social Links</h3>
                        <p class="panel-copy">Social media profile links displayed in the storefront.</p>
                    </div>
                    <div class="table-header-actions">
                        <x-btn type="button" wire:click="openSocialModal()">Add Social Link</x-btn>
                    </div>
                </div>

                <x-table :headers="['Icon', 'URL', 'Order', 'Actions']">
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
                                        wire:click="openSocialModal({{ $link->id }})">Edit</x-btn>
                                    <x-btn type="button" variant="secondary" class="btn-sm"
                                        wire:click="confirmDeleteSocial({{ $link->id }})">Delete</x-btn>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <div class="empty-state-title">No social links yet</div>
                                    <p class="empty-state-copy">Add social media links to display in your storefront.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </div>
        </div>

        {{-- Social link modal --}}
        <x-modal wire:model="socialModalOpen"
            title="{{ $socialId ? 'Edit Social Link' : 'Add Social Link' }}"
            maxWidth="2xl"
            closeAction="closeSocialModal">
            <form wire:submit="saveSocialLink" class="page-stack">
                <div class="form-grid form-grid-2">
                    <div>
                        <label class="field-label">Icon / Platform</label>
                        <x-select wire:model.defer="socialIcon" class="{{ $errors->has('socialIcon') ? 'is-invalid' : '' }}">
                            @foreach ([
                                'facebook'  => 'Facebook',
                                'instagram' => 'Instagram',
                                'twitter'   => 'Twitter / X',
                                'youtube'   => 'YouTube',
                                'tiktok'    => 'TikTok',
                                'linkedin'  => 'LinkedIn',
                                'pinterest' => 'Pinterest',
                                'snapchat'  => 'Snapchat',
                                'whatsapp'  => 'WhatsApp',
                                'telegram'  => 'Telegram',
                            ] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        @error('socialIcon')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="span-2">
                        <label class="field-label">Profile URL</label>
                        <x-input type="url" wire:model.defer="socialUrl"
                            placeholder="https://..."
                            :error="$errors->has('socialUrl')" />
                        @error('socialUrl')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="page-actions compact-actions justify-end">
                    <x-btn type="button" variant="secondary" wire:click="closeSocialModal">Cancel</x-btn>
                    <x-btn type="submit">
                        {{ $socialId ? 'Update Link' : 'Add Link' }}
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
                            <h3 class="panel-title">Promotional Banner</h3>
                            <p class="panel-copy">Configure the promotional banner shown on your homepage. Leave the image and title empty to hide it.</p>
                        </div>
                        <x-btn type="submit">Save Promo Banner</x-btn>
                    </div>

                    <div class="form-grid form-grid-2">
                        <div>
                            <label class="field-label">Title</label>
                            <x-input type="text" wire:model.defer="promoBannerTitle"
                                placeholder="Summer Sale" :error="$errors->has('promoBannerTitle')" />
                            @error('promoBannerTitle')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="field-label">Call-to-Action Text</label>
                            <x-input type="text" wire:model.defer="promoBannerCtaText"
                                placeholder="Shop Now" :error="$errors->has('promoBannerCtaText')" />
                            @error('promoBannerCtaText')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="span-2">
                            <label class="field-label">Subtitle</label>
                            <x-input type="text" wire:model.defer="promoBannerSubtitle"
                                placeholder="Up to 50% off selected items" :error="$errors->has('promoBannerSubtitle')" />
                            @error('promoBannerSubtitle')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="field-label">Link URL</label>
                            <x-input type="url" wire:model.defer="promoBannerLink"
                                placeholder="https://" :error="$errors->has('promoBannerLink')" />
                            @error('promoBannerLink')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="field-label">Image URL</label>
                            <x-input type="url" wire:model.defer="promoBannerImageUrl"
                                placeholder="https://" :error="$errors->has('promoBannerImageUrl')" />
                            @error('promoBannerImageUrl')<div class="field-error">{{ $message }}</div>@enderror
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
                            <h3 class="panel-title">Footer Settings</h3>
                            <p class="panel-copy">Manage the text and copyright notice displayed in the storefront footer. Provide a translation for each enabled language.</p>
                        </div>
                        <x-btn type="submit">Save Footer</x-btn>
                    </div>

                    @foreach ($languages as $language)
                        <div class="locale-fields-group">
                            <div class="locale-badge">{{ $language->native_name ?? $language->name }} ({{ $language->code }})</div>
                            <div class="form-grid">
                                <div>
                                    <label class="field-label">Footer Text</label>
                                    <x-textarea rows="4"
                                        wire:model.defer="footerTranslations.{{ $language->code }}.footer_text"
                                        :error="$errors->has('footerTranslations.' . $language->code . '.footer_text')" />
                                    @error('footerTranslations.' . $language->code . '.footer_text')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="field-label">Copyright</label>
                                    <x-input type="text"
                                        wire:model.defer="footerTranslations.{{ $language->code }}.footer_copyright"
                                        placeholder="© {{ date('Y') }} Your Store. All rights reserved."
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
