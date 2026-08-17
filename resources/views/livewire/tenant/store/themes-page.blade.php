<main id="mn">
    <style>
        .theme-library-shell {
            display: grid;
            gap: 24px;
        }

        .theme-hero {
            position: relative;
            overflow: hidden;
            padding: 26px;
            border-radius: 28px;
            border: 1px solid var(--border);
            background:
                radial-gradient(circle at top left, rgba(124, 58, 237, 0.18), transparent 34%),
                radial-gradient(circle at top right, rgba(0, 151, 196, 0.16), transparent 30%),
                linear-gradient(160deg, color-mix(in srgb, var(--card) 92%, white 8%), color-mix(in srgb, var(--card2) 94%, black 6%));
            box-shadow: var(--shadow);
        }

        .theme-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(280px, 0.75fr);
            gap: 20px;
            align-items: end;
        }

        .theme-hero-copy {
            display: grid;
            gap: 12px;
            max-width: 720px;
        }

        .theme-hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            color: var(--t1);
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .theme-hero-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(30px, 4vw, 48px);
            line-height: 0.94;
            letter-spacing: -0.04em;
        }

        .theme-hero-copy p {
            color: var(--t2);
            font-size: 15px;
            line-height: 1.7;
            max-width: 56ch;
        }

        .theme-hero-stat {
            display: grid;
            gap: 10px;
            padding: 18px 20px;
            border-radius: 24px;
            border: 1px solid var(--border2);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            align-self: stretch;
        }

        .theme-hero-stat .eyebrow {
            color: var(--t2);
            letter-spacing: 0.12em;
        }

        .theme-hero-stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 28px;
            line-height: 1;
        }

        .theme-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .theme-card {
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            border: 1px solid var(--border);
            background: linear-gradient(180deg, color-mix(in srgb, var(--card) 96%, white 4%), color-mix(in srgb, var(--card2) 94%, black 6%));
            box-shadow: var(--shadow-sm);
            transition: transform 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease;
        }

        .theme-card:hover {
            transform: translateY(-4px);
            border-color: rgba(99, 130, 179, 0.28);
            box-shadow: var(--shadow);
        }

        .theme-card.is-active {
            border-width: 2.5px;
            border-color: var(--violet);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--violet) 20%, transparent), 0 12px 40px rgba(124, 58, 237, 0.18);
        }

        .theme-card-preview {
            position: relative;
            aspect-ratio: 4 / 3.8;
            overflow: hidden;
            background:
                linear-gradient(135deg, rgba(124, 58, 237, 0.16), rgba(0, 151, 196, 0.18)),
                linear-gradient(160deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
        }

        .theme-card-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top center;
            display: block;
        }

        .theme-card-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at 25% 20%, rgba(255, 255, 255, 0.22), transparent 25%),
                radial-gradient(circle at 80% 10%, rgba(255, 255, 255, 0.14), transparent 18%),
                linear-gradient(135deg, rgba(124, 58, 237, 0.24), rgba(0, 151, 196, 0.22));
        }

        .theme-card-fallback span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 88px;
            height: 88px;
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.14);
            color: white;
            font-family: 'Syne', sans-serif;
            font-size: 34px;
            letter-spacing: -0.04em;
            text-transform: uppercase;
            backdrop-filter: blur(8px);
        }

        .theme-card-status {
            position: absolute;
            top: 16px;
            left: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(7, 13, 24, 0.62);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            backdrop-filter: blur(8px);
        }

        .theme-card-status::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--green);
            box-shadow: 0 0 0 6px rgba(5, 150, 105, 0.16);
        }

        .theme-card-body {
            display: grid;
            gap: 16px;
            padding: 18px 18px 20px;
        }

        .theme-card-copy {
            display: grid;
            gap: 6px;
            text-align: center;
        }

        .theme-card-title {
            font-family: 'Syne', sans-serif;
            font-size: 24px;
            line-height: 1;
            letter-spacing: -0.04em;
        }

        .theme-card-subtitle {
            color: var(--t2);
            font-size: 14px;
            line-height: 1.6;
        }

        .theme-card-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .theme-pill-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--border2) 75%, transparent);
            background: transparent;
            color: var(--t1);
            font-weight: 700;
            transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
        }

        .theme-pill-btn:hover {
            transform: translateY(-1px);
            border-color: color-mix(in srgb, var(--violet) 40%, var(--border2));
        }

        .theme-pill-btn.is-primary {
            border-color: transparent;
            background: linear-gradient(135deg, #6a74ff, #7687ff 44%, #8b5cf6);
            color: #fff;
            box-shadow: 0 12px 24px rgba(106, 116, 255, 0.28);
        }

        .theme-pill-btn[disabled],
        .theme-pill-btn.is-disabled {
            cursor: not-allowed;
            opacity: 0.48;
            transform: none;
            box-shadow: none;
        }

        .theme-empty-state {
            padding: 48px 24px;
            border-radius: 28px;
            border: 1px dashed var(--border2);
            background: color-mix(in srgb, var(--card) 95%, white 5%);
            text-align: center;
        }

        @media (max-width: 1100px) {
            .theme-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .theme-hero-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .theme-grid {
                grid-template-columns: 1fr;
            }

            .theme-card-title {
                font-size: 22px;
            }
        }

        .theme-country-input {
            width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;
        }
        .theme-country-list {
            max-height: 340px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 10px;
            padding: 4px; display: flex; flex-direction: column; gap: 2px; background: #000;
        }
        .theme-country-row {
            display: grid; grid-template-columns: 24px 24px 1fr auto; align-items: center; gap: 10px;
            padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 14px;
            background: #000;
        }
        .theme-country-row:hover { background: #fff;color: #000; }
        .theme-country-flag { font-size: 18px; line-height: 1; }
        .theme-country-iso { font-family: monospace; font-size: 12px; opacity: .6; }
        .theme-country-empty { text-align: center; padding: 20px; opacity: .6; margin: 0; }
        .theme-country-summary { font-size: 13px; opacity: .8; margin: 0; }
        .theme-modal-actions { display: flex; justify-content: flex-end; gap: 8px; }

        .theme-scope-badge {
            display: inline-block; padding: 2px 8px; border-radius: 999px;
            font-size: 11px; font-weight: 600; margin-right: 8px;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .theme-scope-universal { background: rgba(34, 197, 94, .15); color: #15803d; }
        .theme-scope-specific  { background: rgba(59, 130, 246, .15); color: #1d4ed8; }
        .theme-pill-btn.is-danger {
            background: rgba(239, 68, 68, .12); color: #b91c1c; border-color: rgba(239, 68, 68, .3);
        }
        .theme-pill-btn.is-danger:hover { background: rgba(239, 68, 68, .2); }
    </style>

    <div class="theme-library-shell">
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
        </div>

        <section class="theme-hero fu d1">
            <div class="theme-hero-grid">
                <div class="theme-hero-copy">
                    <span class="theme-hero-kicker">Theme Gallery</span>
                    <h2 class="theme-hero-title">Choose the storefront look that fits your catalog.</h2>
                    <p>Browse live preview cards, compare visual direction at a glance, and activate the design you want
                        customers to see right now.</p>
                </div>

                <div class="theme-hero-stat">
                    <div class="eyebrow">Current Live Theme</div>
                    <div class="theme-hero-stat-value">{{ $activeThemeName }}</div>
                    <p class="panel-copy">Use Preview to inspect the theme asset, then activate it when you are ready to
                        switch the storefront.</p>
                </div>
            </div>
        </section>

        <div class="g-stats3">
            @foreach ($statistics as $stat)
                <div class="card {{ $stat['glow'] ?? '' }} fu d{{ min($loop->iteration, 6) }}">
                    <div class="stat-head">
                        <div>
                            <div class="eyebrow">{{ $stat['label'] }}</div>
                            <div class="D stat-value">{{ $stat['value'] }}</div>
                        </div>
                        <div class="mini-stat-dot {{ $stat['dot'] ?? 'dot-cyan' }}"></div>
                    </div>
                    <p class="panel-copy">{{ $stat['caption'] }}</p>
                </div>
            @endforeach
        </div>

        @if ($variantCards)
            <section class="theme-grid">
                @foreach ($variantCards as $theme)
                    <article wire:key="variant-card-{{ $theme['theme_id'] }}-{{ $theme['variant_id'] }}"
                        class="theme-card {{ $theme['is_active'] ? 'is-active' : '' }} fu d{{ ($loop->index % 6) + 1 }}">
                        <div class="theme-card-preview">
                            @if ($theme['preview_path'])
                                <img src="{{ $theme['preview_path'] }}" alt="{{ $theme['name'] }} preview">
                            @else
                                <div class="theme-card-fallback">
                                    <span>{{ $theme['initials'] }}</span>
                                </div>
                            @endif

                            @if ($theme['is_active'])
                                <div class="theme-card-status">Live Theme</div>
                            @endif
                        </div>

                        <div class="theme-card-body">
                            <div class="theme-card-copy">
                                <h3 class="theme-card-title">{{ $theme['name'] }}</h3>
                                <div class="theme-card-subtitle">{{ $theme['theme_name'] }}</div>
                                <div class="theme-card-subtitle">
                                    <span class="theme-scope-badge theme-scope-{{ $theme['is_universal'] ? 'universal' : 'specific' }}">
                                        {{ $theme['scope_label'] }}
                                    </span>
                                    <span>🌍 {{ $theme['countries_label'] }}</span>
                                </div>
                            </div>

                            <div class="theme-card-actions">
                                @if ($theme['action_method'] === 'activateVariant')
                                    <button type="button" class="{{ $theme['action_class'] }}"
                                        wire:click="activateVariant({{ $theme['theme_id'] }}, {{ $theme['variant_id'] }})"
                                        wire:loading.attr="disabled"
                                        wire:target="activateVariant({{ $theme['theme_id'] }}, {{ $theme['variant_id'] }})">
                                        {{ $theme['action_label'] }}
                                    </button>
                                @elseif ($theme['action_method'])
                                    <button type="button" class="{{ $theme['action_class'] }}"
                                        wire:click="{{ $theme['action_method'] }}({{ $theme['theme_id'] }})"
                                        wire:loading.attr="disabled"
                                        wire:target="{{ $theme['action_method'] }}({{ $theme['theme_id'] }})">
                                        {{ $theme['action_label'] }}
                                    </button>
                                @else
                                    <button type="button" class="{{ $theme['action_class'] }}" disabled>
                                        {{ $theme['action_label'] }}
                                    </button>
                                @endif

                                @if ($theme['preview_path'])
                                    <a href="{{ $theme['preview_path'] }}" target="_blank" rel="noopener noreferrer"
                                        class="theme-pill-btn">Preview</a>
                                @else
                                    <button type="button" class="theme-pill-btn is-disabled" disabled>No Preview</button>
                                @endif

                                @if ($theme['has_countries'])
                                    <button type="button" class="theme-pill-btn"
                                        wire:click="openCountries({{ $theme['theme_id'] }})">
                                        Countries
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
        @else
            <div class="theme-empty-state">
                <div class="empty-state-title">No variants available</div>
                <p class="empty-state-copy">Variant cards will appear here after home page variants are published for your themes.</p>
            </div>
        @endif
    </div>

    <x-modal wire:model="showCountriesModal" title="Theme Countries" maxWidth="2xl" closeAction="closeCountries">
        <div class="page-stack">
            <p style="margin:0;">
                Enable or disable countries for
                <strong>{{ $countriesThemeName }}</strong>.
                You can only toggle countries allowed by the central template.
            </p>

            <div>
                <label class="field-label">Search</label>
                <input type="text" class="theme-country-input"
                    wire:model.live.debounce.250ms="countriesSearch"
                    placeholder="Search by country name or ISO code...">
            </div>

            <div class="theme-country-list">
                @forelse ($this->modalCountries as $row)
                    <label class="theme-country-row">
                        <input type="checkbox"
                            wire:click="toggleCountry({{ $row->country_id }})"
                            @checked(in_array((int) $row->country_id, $enabledCountryIds, true))>
                        <span class="theme-country-flag">{{ $row->flag_emoji ?? '🏳️' }}</span>
                        <span class="theme-country-name">{{ $row->name }}</span>
                        <span class="theme-country-iso">{{ $row->iso2 }}</span>
                    </label>
                @empty
                    <p class="theme-country-empty">No countries match your search.</p>
                @endforelse
            </div>

            <p class="theme-country-summary">
                Enabled: <strong>{{ count($enabledCountryIds) }}</strong>
            </p>

            <div class="theme-modal-actions">
                <button type="button" class="theme-pill-btn" wire:click="closeCountries">Cancel</button>
                <button type="button" class="theme-pill-btn is-primary" wire:click="saveCountries">Save</button>
            </div>
        </div>
    </x-modal>
</main>
