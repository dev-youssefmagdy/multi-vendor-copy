<main id="mn">

    {{-- ── Page head ──────────────────────────────────────────────────────── --}}
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">{{ $badge }}</div>
            <h1 class="D page-title">{{ $title }}</h1>
            <p class="page-copy">{{ $description }}</p>
        </div>
    </div>

    {{-- ── Success notice ─────────────────────────────────────────────────── --}}
    @if ($successMessage)
        <div class="card section-gap notice-success" wire:key="sync-success">
            <div style="display:flex;align-items:center;gap:10px;">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;color:var(--green)">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                {{ $successMessage }}
            </div>
        </div>
    @endif

    {{-- ── Two-column sync builder ─────────────────────────────────────────── --}}
    <form wire:submit="sync">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;align-items:start;" class="section-gap">

            {{-- ── LEFT: Sections ─────────────────────────────────────────── --}}
            <div class="card" style="padding:1.5rem;">
                <div style="margin-bottom:1.25rem;">
                    <h3 class="panel-title">Sections to Sync</h3>
                    <p class="panel-copy">Pick one or more platform sections to push to the selected tenants.</p>
                </div>

                @php
                    $sectionMeta = [
                        'languages' => [
                            'label'    => 'Languages',
                            'desc'     => 'Available store languages and translations.',
                            'dot'      => 'dot-cyan',
                            'glow'     => 'card-glow-cyan',
                            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.5 5h.01" />',
                        ],
                        'currencies' => [
                            'label'    => 'Currencies',
                            'desc'     => 'Currency codes, symbols, and conversion rates.',
                            'dot'      => 'dot-green',
                            'glow'     => 'card-glow-green',
                            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m9-8a9 9 0 11-18 0 9 9 0 0118 0z" />',
                        ],
                        'settings' => [
                            'label'    => 'Settings',
                            'desc'     => 'General application settings.',
                            'dot'      => 'dot-amber',
                            'glow'     => 'card-glow-amber',
                            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><circle cx="12" cy="12" r="3" />',
                        ],
                        'payment-gateways' => [
                            'label'    => 'Payment Gateways',
                            'desc'     => 'Enabled payment methods and credentials.',
                            'dot'      => 'dot-violet',
                            'glow'     => 'card-glow-purple',
                            'icon'     => '<rect x="2" y="5" width="20" height="14" rx="2" /><path stroke-linecap="round" stroke-linejoin="round" d="M2 10h20" />',
                        ],
                        'themes' => [
                            'label'    => 'Themes',
                            'desc'     => 'Storefront templates and theme parts.',
                            'dot'      => 'dot-cyan',
                            'glow'     => 'card-glow-cyan',
                            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM14 13a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1v-6z" />',
                        ],
                        'email-templates' => [
                            'label'    => 'Email Templates',
                            'desc'     => 'Transactional email content and translations.',
                            'dot'      => 'dot-amber',
                            'glow'     => 'card-glow-amber',
                            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />',
                        ],
                        'pages' => [
                            'label'    => 'Pages',
                            'desc'     => 'Static content pages and translations.',
                            'dot'      => 'dot-green',
                            'glow'     => 'card-glow-green',
                            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
                        ],
                        'categories' => [
                            'label'    => 'Categories',
                            'desc'     => 'Product category tree and translations.',
                            'dot'      => 'dot-violet',
                            'glow'     => 'card-glow-purple',
                            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />',
                        ],
                        'products' => [
                            'label'    => 'Products',
                            'desc'     => 'Catalog products, variants, and pricing.',
                            'dot'      => 'dot-cyan',
                            'glow'     => 'card-glow-cyan',
                            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />',
                        ],
                        'badges.new-in' => [
                            'label'    => 'New-In Badge',
                            'desc'     => 'Products marked as new arrivals.',
                            'dot'      => 'dot-cyan',
                            'glow'     => 'card-glow-cyan',
                            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1M4.22 4.22l.707.707m12.727 12.727.707.707M3 12H4m16 0h1M4.22 19.78l.707-.707M18.95 5.05l.707-.707" /><circle cx="12" cy="12" r="4" />',
                        ],
                        'badges.best-selling' => [
                            'label'    => 'Best-Selling Badge',
                            'desc'     => 'Products flagged as top performers.',
                            'dot'      => 'dot-amber',
                            'glow'     => 'card-glow-amber',
                            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />',
                        ],
                        'flash-sales' => [
                            'label'    => 'Flash Sales',
                            'desc'     => 'Time-limited sale campaigns with discount.',
                            'dot'      => 'dot-violet',
                            'glow'     => 'card-glow-purple',
                            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />',
                        ],
                        'coupons' => [
                            'label'    => 'Coupons',
                            'desc'     => 'Platform-wide discount codes.',
                            'dot'      => 'dot-green',
                            'glow'     => 'card-glow-green',
                            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M17 17h.01M3.5 3.5l17 17M9 3H5a2 2 0 00-2 2v4m14-6h-4M3 9a9 9 0 0012.65 8.65M21 15v4a2 2 0 01-2 2h-4" />',
                        ],
                    ];
                @endphp

                <div style="display:flex;flex-direction:column;gap:0.625rem;">
                    @foreach ($availableSections as $key => $label)
                        @php $meta = $sectionMeta[$key]; @endphp
                        <label style="cursor:pointer;">
                            <input type="checkbox" wire:model.live="selectedSections" value="{{ $key }}" style="display:none;">
                            <div style="display:flex;align-items:center;gap:12px;padding:0.875rem 1rem;border-radius:8px;border:1.5px solid {{ in_array($key, $selectedSections) ? 'var(--cyan)' : 'var(--border)' }};background:{{ in_array($key, $selectedSections) ? 'rgba(0,229,255,.06)' : 'transparent' }};transition:all .15s ease;">
                                <div style="width:36px;height:36px;border-radius:8px;background:rgba(0,0,0,.18);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" style="color:var(--t2);">
                                        {!! $meta['icon'] !!}
                                    </svg>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:13.5px;font-weight:600;color:var(--t1);">{{ $meta['label'] }}</div>
                                    <div style="font-size:12px;color:var(--t3);margin-top:1px;">{{ $meta['desc'] }}</div>
                                </div>
                                <div style="width:18px;height:18px;border-radius:4px;border:1.5px solid {{ in_array($key, $selectedSections) ? 'var(--cyan)' : 'var(--border)' }};background:{{ in_array($key, $selectedSections) ? 'var(--cyan)' : 'transparent' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s ease;">
                                    @if (in_array($key, $selectedSections))
                                        <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="#000" stroke-width="3.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                @error('selectedSections')
                    <p class="field-error" style="margin-top:0.5rem;">{{ $message }}</p>
                @enderror
            </div>

            {{-- ── RIGHT: Tenants ──────────────────────────────────────────── --}}
            <div class="card" style="padding:1.5rem;">
                <div style="margin-bottom:1.25rem;">
                    <h3 class="panel-title">Target Tenants</h3>
                    <p class="panel-copy">Push to all stores at once, or pick specific tenants from the list.</p>
                </div>

                {{-- All-tenants toggle --}}
                <label style="cursor:pointer;display:block;margin-bottom:0.875rem;">
                    <input type="checkbox" wire:model.live="allTenants" style="display:none;">
                    <div style="display:flex;align-items:center;gap:12px;padding:0.875rem 1rem;border-radius:8px;border:1.5px solid {{ $allTenants ? 'var(--cyan)' : 'var(--border)' }};background:{{ $allTenants ? 'rgba(0,229,255,.06)' : 'transparent' }};transition:all .15s ease;">
                        <div style="width:36px;height:36px;border-radius:8px;background:rgba(0,0,0,.18);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" style="color:var(--t2);">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
                            </svg>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:13.5px;font-weight:600;color:var(--t1);">All Tenants</div>
                            <div style="font-size:12px;color:var(--t3);margin-top:1px;">Sync to every registered store at once.</div>
                        </div>
                        <div style="width:18px;height:18px;border-radius:4px;border:1.5px solid {{ $allTenants ? 'var(--cyan)' : 'var(--border)' }};background:{{ $allTenants ? 'var(--cyan)' : 'transparent' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s ease;">
                            @if ($allTenants)
                                <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="#000" stroke-width="3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            @endif
                        </div>
                    </div>
                </label>

                @unless ($allTenants)
                    {{-- Search --}}
                    <div style="margin-bottom:0.625rem;">
                        <input
                            type="text"
                            class="field-control"
                            wire:model.live.debounce.200ms="tenantSearch"
                            placeholder="Search tenants…"
                            autocomplete="off"
                        >
                    </div>

                    {{-- Tenant list --}}
                    <div style="border:1px solid var(--border);border-radius:8px;max-height:280px;overflow-y:auto;">
                        @php
                            $filtered = collect($tenants)->when(
                                !empty($tenantSearch),
                                fn($c) => $c->filter(fn($t) =>
                                    str_contains(strtolower($t->name ?? ''), strtolower($tenantSearch)) ||
                                    str_contains(strtolower($t->slug ?? ''), strtolower($tenantSearch))
                                )
                            );
                        @endphp

                        @forelse ($filtered as $tenant)
                            <label style="cursor:pointer;display:block;">
                                <input type="checkbox" wire:model.live="selectedTenants" value="{{ $tenant->id }}" style="display:none;">
                                <div style="display:flex;align-items:center;gap:10px;padding:0.625rem 0.875rem;border-bottom:1px solid var(--border);background:{{ in_array($tenant->id, $selectedTenants) ? 'rgba(0,229,255,.05)' : 'transparent' }};transition:background .1s ease;">
                                    <div style="width:30px;height:30px;border-radius:6px;background:var(--surface-2,rgba(255,255,255,.06));border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;font-weight:700;color:var(--t2);">
                                        {{ strtoupper(substr($tenant->name ?: $tenant->id, 0, 1)) }}
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:13px;font-weight:600;color:var(--t1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $tenant->name ?: $tenant->id }}</div>
                                        <div style="font-size:11.5px;color:var(--t3);">/{{ $tenant->slug }}</div>
                                    </div>
                                    <div style="width:16px;height:16px;border-radius:3px;border:1.5px solid {{ in_array($tenant->id, $selectedTenants) ? 'var(--cyan)' : 'var(--border)' }};background:{{ in_array($tenant->id, $selectedTenants) ? 'var(--cyan)' : 'transparent' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s ease;">
                                        @if (in_array($tenant->id, $selectedTenants))
                                            <svg width="9" height="9" fill="none" viewBox="0 0 24 24" stroke="#000" stroke-width="3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @empty
                            <div style="padding:2rem;text-align:center;font-size:12.5px;color:var(--t3);">
                                No tenants match your search.
                            </div>
                        @endforelse
                    </div>

                    @error('selectedTenants')
                        <p class="field-error" style="margin-top:0.5rem;">{{ $message }}</p>
                    @enderror
                @endunless
            </div>

        </div>

        {{-- ── Summary + Action bar ────────────────────────────────────────── --}}
        <div class="card" style="padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div style="font-size:12.5px;color:var(--t3);line-height:1.6;">
                @php
                    $sectionCount = count($selectedSections);
                    $tenantCount  = $allTenants ? $tenants->count() : count($selectedTenants);
                @endphp
                @if ($sectionCount === 0 && $tenantCount === 0)
                    <span>Select sections and tenants above to begin.</span>
                @else
                    <span>
                        Syncing
                        <strong style="color:var(--t1);">{{ $sectionCount }} {{ Str::plural('section', $sectionCount) }}</strong>
                        to
                        <strong style="color:var(--t1);">{{ $tenantCount === 0 ? '—' : $tenantCount . ' ' . Str::plural('tenant', $tenantCount) }}</strong>
                        @if ($allTenants)
                            <span style="color:var(--amber);">(all stores)</span>
                        @endif
                    </span>
                @endif
            </div>
            <button
                type="submit"
                class="btn btn-primary"
                wire:loading.attr="disabled"
                @if ($sectionCount === 0 || $tenantCount === 0) disabled @endif
            >
                <span wire:loading.remove>
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="display:inline;vertical-align:middle;margin-right:5px;margin-top:-2px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Run Sync
                </span>
                <span wire:loading>Queuing jobs…</span>
            </button>
        </div>
    </form>

</main>
