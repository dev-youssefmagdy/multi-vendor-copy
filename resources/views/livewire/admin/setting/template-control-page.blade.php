<main id="mn">

    {{-- Page Header --}}
    <div class="page-head fu d0">
        <div style="display:flex;align-items:center;gap:16px;">
            <div
                style="width:52px;height:52px;border-radius:12px;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"
                    style="color:var(--t2)">
                    <rect x="3" y="3" width="7" height="7" rx="1" />
                    <rect x="14" y="3" width="7" height="7" rx="1" />
                    <rect x="3" y="14" width="7" height="7" rx="1" />
                    <rect x="14" y="14" width="7" height="7" rx="1" />
                </svg>
            </div>
            <div>
                <h1 class="D page-title">Store Templates</h1>
                <p class="page-copy">This screen matches the user theme browser, but adds admin controls for
                    availability and theme details.</p>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="g-stats3 section-gap">
        <div class="card card-glow-cyan fu d1">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Templates</div>
                    <div class="D stat-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="panel-copy">Installed storefront templates</p>
        </div>
        <div class="card card-glow-green fu d2">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Active</div>
                    <div class="D stat-value">{{ number_format($stats['active']) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="panel-copy">Currently enabled templates</p>
        </div>
        <div class="card card-glow-violet fu d3">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Default</div>
                    <div class="D stat-value">{{ number_format($stats['default']) }}</div>
                </div>
                <div class="mini-stat-dot dot-violet"></div>
            </div>
            <p class="panel-copy">Default storefront template</p>
        </div>
    </div>

    {{-- Template Cards --}}
    @if ($templates->isEmpty())
        <div class="card section-gap">
            <div class="empty-state">
                <div class="empty-state-title">No templates installed</div>
                <p class="empty-state-copy">Install a storefront template to begin managing availability and defaults.</p>
            </div>
        </div>
    @else
        @php
            $scheme = parse_url(config('app.url', 'http://localhost'), PHP_URL_SCHEME) ?: 'http';
            $centralDomain = config('tenancy.central_domains.0')
                ?: (parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');
            $previewBase = $scheme . '://' . $centralDomain . '/preview';
        @endphp
        <div class="tpl-grid section-gap">
            @foreach ($templates as $template)
                <div class="tpl-card fu d{{ min($loop->index + 1, 6) }}">

                    {{-- Preview --}}
                    <div class="tpl-preview">
                        @if ($template->previewFile?->path)
                            <img src="{{ asset($template->previewFile->full_path) }}" alt="{{ $template->name }}"
                                class="tpl-preview-img">
                        @else
                            <div class="tpl-preview-placeholder">
                                <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"
                                    style="color:var(--t3)">
                                    <rect x="3" y="3" width="18" height="18" rx="2" />
                                    <path d="M3 9h18M9 21V9" />
                                </svg>
                            </div>
                        @endif

                        {{-- Badges --}}
                        <div class="tpl-badge-row">
                            @if ($template->is_active)
                                <span class="tpl-badge tpl-badge-active">Active</span>
                            @else
                                <span class="tpl-badge tpl-badge-inactive">Inactive</span>
                            @endif
                            @if ($template->is_default)
                                <span class="tpl-badge tpl-badge-default">Default</span>
                            @endif
                        </div>

                        {{-- Edit button --}}
                        <button type="button" class="tpl-edit-btn" wire:click="openEdit({{ $template->id }})"
                            title="Edit template details">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                            </svg>
                        </button>

                        {{-- Preview overlay --}}
                        <div class="tpl-preview-overlay">
                            <a href="{{ $previewBase }}?theme={{ $template->slug }}" target="_blank" rel="noopener"
                                class="tpl-preview-open-btn">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                Preview
                            </a>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="tpl-body">
                        <div class="tpl-name-row">
                            <h3 class="tpl-name">{{ $template->name }}</h3>
                            <span class="tpl-palette-icon">
                                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.6">
                                    <path
                                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.97-4.03-9-9-9z" />
                                    <circle cx="6.5" cy="11.5" r="1.5" fill="currentColor" stroke="none" />
                                    <circle cx="9.5" cy="7.5" r="1.5" fill="currentColor" stroke="none" />
                                    <circle cx="14.5" cy="7.5" r="1.5" fill="currentColor" stroke="none" />
                                    <circle cx="17.5" cy="11.5" r="1.5" fill="currentColor" stroke="none" />
                                </svg>
                            </span>
                        </div>
                        <p class="tpl-slug">Slug: {{ $template->slug }}</p>
                        <p class="tpl-description">No description added for this language yet.</p>

                        <div class="tpl-meta-grid">
                            <div class="tpl-meta-box">
                                <span class="tpl-meta-label">Version</span>
                                <span class="tpl-meta-value">{{ $template->version ?? 'n/a' }}</span>
                            </div>
                            <div class="tpl-meta-box">
                                <span class="tpl-meta-label">Author</span>
                                <span class="tpl-meta-value">{{ $template->author ?? 'Unknown' }}</span>
                            </div>
                        </div>

                        <div class="tpl-meta-box" style="margin-top:8px;">
                            <span class="tpl-meta-label">Preview URL</span>
                            <span
                                class="tpl-meta-value">{{ $template->previewFile?->path ? asset($template->previewFile->path) : 'Not set' }}</span>
                        </div>

                        {{-- Actions --}}
                        <div class="tpl-actions">
                            @if ($template->is_active)
                                <button type="button" class="btn btn-secondary btn-sm"
                                    wire:click="requestDeactivate({{ $template->id }})">
                                    Deactivate
                                </button>
                            @else
                                <button type="button" class="btn btn-secondary btn-sm tpl-btn-activate"
                                    wire:click="requestActivate({{ $template->id }})">
                                    Activate
                                </button>
                            @endif

                            @if (!$template->is_default)
                                @if ($template->countries_count > 0)
                                    {{-- Template has country restrictions, so it can't be the global default.
                                    Show a disabled button with an explanatory tooltip. --}}
                                    <button type="button" class="btn btn-secondary btn-sm tpl-btn-default is-locked" disabled
                                        title="Only templates assigned to all countries can be set as default.">
                                        Set Default
                                    </button>
                                @else
                                    <button type="button" class="btn btn-secondary btn-sm tpl-btn-default"
                                        wire:click="requestSetDefault({{ $template->id }})">
                                        Set Default
                                    </button>
                                @endif
                            @else
                                <button type="button" class="btn btn-secondary btn-sm tpl-btn-is-default" disabled>
                                    ✓ Default
                                </button>
                            @endif

                            <button type="button" class="btn btn-secondary btn-sm"
                                wire:click="openCountries({{ $template->id }})" title="Assign countries to this template">
                                🌍 Countries
                                <span class="tpl-country-count">
                                    ({{ $template->countries_count > 0 ? $template->countries_count : 'All' }})
                                </span>
                            </button>

                            <a href="{{ route('admin.settings.template-parts.show', ['templateId' => $template->id]) }}"
                                class="btn btn-secondary btn-sm" title="Edit header &amp; footer parts">
                                🧩 Parts
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Edit Modal --}}
    <x-modal wire:model="showEditModal" title="Edit Template Details" maxWidth="2xl" closeAction="closeEdit">
        <form wire:submit="saveEdit" class="page-stack">

            {{-- Preview image --}}
            <div>
                <label class="field-label">Preview Image</label>

                @if ($previewImage)
                    {{-- New upload preview --}}
                    <div class="tpl-img-preview-wrap">
                        <img src="{{ $previewImage->temporaryUrl() }}" alt="New preview" class="tpl-img-preview">
                        <button type="button" class="tpl-img-remove-btn" wire:click="$set('previewImage', null)"
                            title="Remove">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>
                @elseif ($existingPreviewUrl && !$removePreview)
                    {{-- Existing stored image --}}
                    <div class="tpl-img-preview-wrap">
                        <img src="{{ $existingPreviewUrl }}" alt="Current preview" class="tpl-img-preview">
                        <button type="button" class="tpl-img-remove-btn" wire:click="removePreviewImage"
                            title="Remove current image">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>
                @endif

                @if (!$previewImage && (!$existingPreviewUrl || $removePreview))
                    <x-dropzone model="previewImage" :multiple="false" label="Upload preview image"
                        sublabel="PNG, JPG, WEBP up to 4MB — scaled to 1200×800" accept="image/*" />
                @endif

                @error('previewImage')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
            </div>

            {{-- Text fields --}}
            <div class="form-grid form-grid-2">
                <div class="span-2">
                    <label class="field-label">Name</label>
                    <x-input type="text" wire:model.defer="editName" />
                    @error('editName')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="span-2">
                    <label class="field-label">Slug</label>
                    <p class="field-control bg-gray-50 text-gray-500 font-mono text-sm select-all cursor-default">
                        {{ $editSlug }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Slug is set when the template is created and cannot be
                        changed.</p>
                </div>
                <div>
                    <label class="field-label">Version</label>
                    <x-input type="text" wire:model.defer="editVersion" placeholder="e.g. 1.0.0" />
                    @error('editVersion')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Author</label>
                    <x-input type="text" wire:model.defer="editAuthor" placeholder="e.g. dokanplus.com" />
                    @error('editAuthor')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="page-actions compact-actions justify-end">
                <button type="button" class="btn btn-secondary" wire:click="closeEdit">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </x-modal>

    {{-- Countries Modal --}}
    <x-modal wire:model="showCountriesModal" title="Template Countries" maxWidth="2xl" closeAction="closeCountries">
        <div class="page-stack">
            <p class="tpl-slug" style="margin:0;">
                Select which countries the template
                <strong>{{ $countriesTemplateName }}</strong>
                is available in. Tenants can only enable countries from this list.
            </p>

            <label class="tpl-country-all">
                <input type="checkbox" wire:click="toggleAllCountries" @checked($countriesAll)>
                <span>All countries (default — every country is allowed)</span>
            </label>

            @if (!$countriesAll)
                <div>
                    <label class="field-label">Search</label>
                    <input type="text" class="tpl-input" wire:model.live.debounce.250ms="countriesSearch"
                        placeholder="Search by country name or ISO code...">
                </div>

                <div class="tpl-country-list">
                    @forelse ($countries as $country)
                        <label class="tpl-country-row">
                            <input type="checkbox" wire:click="toggleCountry({{ $country->id }})"
                                @checked(in_array($country->id, $selectedCountryIds))>
                            <span class="tpl-country-flag">{{ $country->flag_emoji ?? '🏳️' }}</span>
                            <span class="tpl-country-name">{{ $country->name }}</span>
                            <span class="tpl-country-iso">{{ $country->iso2 }}</span>
                        </label>
                    @empty
                        <p class="tpl-country-empty">No countries match your search.</p>
                    @endforelse
                </div>

                <p class="tpl-country-summary">
                    Selected: <strong>{{ count($selectedCountryIds) }}</strong>
                </p>
            @endif

            <div class="tpl-modal-actions">
                <button type="button" class="btn btn-secondary btn-sm" wire:click="closeCountries">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" wire:click="saveCountries">Save</button>
            </div>
        </div>
    </x-modal>

</main>

@push('styles')
    <style>
        .tpl-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        @media (max-width: 1024px) {
            .tpl-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .tpl-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: box-shadow .2s, border-color .2s;
        }

        .tpl-card:hover {
            box-shadow: 0 4px 24px rgba(0, 0, 0, .10);
            border-color: var(--cyan);
        }

        /* Preview area */
        .tpl-preview {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            background: var(--surface);
            overflow: hidden;
        }

        .tpl-preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .tpl-preview-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Badges */
        .tpl-badge-row {
            position: absolute;
            top: 10px;
            left: 10px;
            display: flex;
            gap: 6px;
        }

        .tpl-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: .3px;
        }

        .tpl-badge-active {
            background: #22c55e;
            color: #fff;
        }

        .tpl-badge-inactive {
            background: #94a3b8;
            color: #fff;
        }

        .tpl-badge-default {
            background: #8b5cf6;
            color: #fff;
        }

        /* Preview overlay */
        .tpl-preview-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, .35);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            opacity: 0;
            transition: opacity .2s;
            pointer-events: none;
        }

        .tpl-card:hover .tpl-preview-overlay {
            opacity: 1;
            pointer-events: auto;
        }

        .tpl-preview-open-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            background: rgba(255, 255, 255, .92);
            color: #111;
            font-size: 13px;
            font-weight: 600;
            border-radius: 30px;
            text-decoration: none;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .18);
            transition: background .15s, transform .15s;
        }

        .tpl-preview-open-btn:hover {
            background: #fff;
            transform: scale(1.04);
        }

        /* Edit button */
        .tpl-edit-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(0, 0, 0, .45);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s;
            z-index: 2;
        }

        .tpl-edit-btn:hover {
            background: rgba(0, 0, 0, .7);
        }

        /* Body */
        .tpl-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
        }

        .tpl-name-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .tpl-name {
            font-size: 17px;
            font-weight: 700;
            color: var(--t1);
            margin: 0;
            text-transform: capitalize;
        }

        .tpl-palette-icon {
            color: var(--t3);
            flex-shrink: 0;
        }

        .tpl-slug {
            font-size: 12px;
            color: var(--t3);
            margin: 0;
        }

        .tpl-description {
            font-size: 13px;
            color: var(--t2);
            margin: 4px 0 8px;
            line-height: 1.5;
        }

        /* Meta boxes */
        .tpl-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .tpl-meta-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .tpl-meta-label {
            font-size: 11px;
            color: var(--t3);
            font-weight: 500;
        }

        .tpl-meta-value {
            font-size: 13px;
            font-weight: 600;
            color: var(--t1);
            word-break: break-all;
        }

        /* Action buttons */
        .tpl-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            padding-top: 12px;
        }

        .tpl-actions .btn {
            flex: 1;
            justify-content: center;
        }

        .tpl-btn-activate {
            color: var(--green, #22c55e) !important;
            border-color: var(--green, #22c55e) !important;
        }

        .tpl-btn-default {
            color: var(--cyan) !important;
            border-color: var(--cyan) !important;
        }

        .tpl-btn-is-default {
            color: var(--violet, #8b5cf6) !important;
            border-color: var(--violet, #8b5cf6) !important;
            opacity: .7;
            cursor: default;
        }

        .tpl-btn-default.is-locked {
            opacity: .5;
            cursor: not-allowed;
        }

        /* Modal preview image */
        .tpl-img-preview-wrap {
            position: relative;
            display: inline-block;
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--border);
            margin-bottom: 4px;
        }

        .tpl-img-preview {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            display: block;
        }

        .tpl-img-remove-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(0, 0, 0, .55);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s;
        }

        .tpl-img-remove-btn:hover {
            background: rgba(220, 38, 38, .85);
        }

        .tpl-country-count {
            opacity: .75;
            margin-left: 4px;
            font-size: 12px;
        }

        .tpl-country-all {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            cursor: pointer;
        }

        .tpl-country-list {
            max-height: 320px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 4px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            background: #fafafa;
            color: inherit;
        }

        .tpl-country-row {
            display: grid;
            grid-template-columns: 24px 24px 1fr auto;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .tpl-country-row:hover {
            background: #fff;
        }

        /* ── Dark mode ─────────────────────────────────────────────── */
        [data-theme="dark"] .tpl-country-list {
            background: #1e2433;
            border-color: #2e3650;
            color: #e2e8f0;
        }

        [data-theme="dark"] .tpl-country-row {
            color: #e2e8f0;
        }

        [data-theme="dark"] .tpl-country-row:hover {
            background: #29304a;
        }

        [data-theme="dark"] .tpl-country-all {
            color: #e2e8f0;
        }

        [data-theme="dark"] .tpl-input {
            background: #1e2433;
            border-color: #2e3650;
            color: #e2e8f0;
        }

        [data-theme="dark"] .tpl-input::placeholder {
            color: #64748b;
        }

        .tpl-country-flag {
            font-size: 18px;
            line-height: 1;
        }

        .tpl-country-iso {
            font-family: monospace;
            font-size: 12px;
            opacity: .6;
        }

        .tpl-country-empty {
            text-align: center;
            padding: 20px;
            opacity: .6;
            margin: 0;
        }

        .tpl-country-summary {
            font-size: 13px;
            opacity: .8;
            margin: 0;
        }

        .tpl-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .tpl-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
    </style>
@endpush