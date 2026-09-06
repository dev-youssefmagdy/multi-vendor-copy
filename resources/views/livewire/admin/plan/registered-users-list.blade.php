<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Tenants</div>
            <h1 class="D page-title">Registered Users</h1>
            <p class="page-copy">Manage central tenant accounts, package assignments, and onboarding status.</p>
        </div>
        <div class="page-actions">
            <x-btn variant="secondary" class="xs-hide" type="button" wire:click="export">Export</x-btn>
            @if ($canManageTenants)<a href="{{ route('admin.plans.users.create') }}" class="btn btn-primary">Add
            Tenant</a>@endif
        </div>
    </div>
    @if (session('status'))
    <div class="card section-gap notice-success">{{ session('status') }}</div>@endif
    <div class="g-stats3 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Tenants</div>
                    <div class="D stat-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="section-copy">Tenant accounts tracked in the central database.</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Active</div>
                    <div class="D stat-value">{{ number_format($stats['active']) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="section-copy">Stores ready for production traffic.</p>
        </div>
        <div class="card card-glow-amber">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Onboarding</div>
                    <div class="D stat-value">{{ number_format($stats['onboarding']) }}</div>
                </div>
                <div class="mini-stat-dot dot-amber"></div>
            </div>
            <p class="section-copy">Accounts still being configured.</p>
        </div>
    </div>
    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Tenant Filters</h3>
                <p class="panel-copy">Search by name, email, slug, or id and filter by status, catalog, and package.</p>
            </div><svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div><label class="field-label">Search</label><input type="text" class="field-control"
                    wire:model.live.debounce.300ms="search"></div>
            <div><label class="field-label">Status</label><select class="field-control" wire:model.live="statusFilter">
                    <option value="">All statuses</option>@foreach ($statusOptions as $statusOption)<option
                    value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</option>@endforeach
                </select></div>
            <div><label class="field-label">Package</label><select class="field-control"
                    wire:model.live="packageFilter">
                    <option value="">All packages</option>@foreach ($packages as $package)<option
                        value="{{ $package->id }}">
                        {{ $package->name }}
                    </option>@endforeach
                </select></div>
            <div><label class="field-label">Logo</label><select class="field-control"
                    wire:model.live="imageFilter">
                    <option value="">All tenants</option>
                    <option value="yes">Has image</option>
                    <option value="no">No image</option>
                </select></div>
        </div>
        <div class="filters-actions">
            <p class="filters-note">Tenant domains and catalog assignment are editable from the tenant form.</p><button
                type="button" class="btn btn-secondary" wire:click="clearFilters">Reset Filters</button>
        </div>
    </details>
    <section class="card table-card-shell">@if ($tenants->count())
        <div class="table-scroll-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <!-- <th>Catalog</th> -->
                        <th>Package</th>
                        <th>Language</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th class="ta-r">Actions</th>
                    </tr>
                </thead>
                <tbody>@foreach ($tenants as $tenant)<tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                @php($logoUrl = $tenant->logo)
                                                        @if($logoUrl)
                                                            <img src="{{ $logoUrl }}" alt="{{ $tenant->name }}"
                                                                style="width:36px;height:36px;object-fit:contain;border-radius:6px;border:1px solid var(--border-color,#e5e7eb);background:#fff;flex-shrink:0;">
                                                        @else
                                                            <div
                                                                style="width:36px;height:36px;border-radius:6px;border:1px solid var(--border-color,#e5e7eb);background:var(--surface-2,#f3f4f6);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px;font-weight:700;color:var(--text-muted,#9ca3af);">
                                                                {{ strtoupper(substr($tenant->name ?: $tenant->id, 0, 1)) }}
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <div class="entity-title">{{ $tenant->name ?: $tenant->id }}</div>
                                                            <div class="entity-subtitle">{{ $tenant->email }} · /{{ $tenant->slug }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <!-- <td>{{ $tenant->catalog?->name ?? 'No catalog' }}</td> -->
                                                <td>{{ $tenant->package?->name ?? 'No package' }}</td>
                                                <td>{{ strtoupper($tenant->primaryLanguage?->code ?? 'n/a') }}</td>
                                                <td>{{ $tenant->domains->first()?->domain ?? 'No domain' }}</td>
                                                <td><span
                                                        class="badge {{ $tenant->status === \App\Enums\TenantStatus::Active ? 'badge-green' : ($tenant->status === \App\Enums\TenantStatus::Archived ? 'badge-red' : 'badge-amber') }}">{{ ucfirst($tenant->status->value) }}</span>
                                                </td>
                                                <td class="ta-r">
                                                    <div class="table-actions-inline">
                                                        @if ($logoUrl)
                                                        <button type="button" class="btn btn-secondary btn-sm"
                                                            data-print-logo data-logo-url="{{ $logoUrl }}">Print
                                                            Logo</button>
                                                        <button type="button" class="btn btn-secondary btn-sm"
                                                            wire:click="downloadLogo('{{ $tenant->id }}')">Download
                                                            Logo</button>
                                                        @endif
                                                        @if ($canManageTenants)
                                                        <a href="{{ route('admin.plans.users.edit', $tenant) }}"
                                                            class="btn btn-secondary btn-sm">Edit</a>
                                                        <button type="button"
                                                            class="btn btn-secondary btn-sm btn-danger"
                                                            wire:click="confirmDeleteTenant('{{ $tenant->id }}')">Delete</button>
                                                        @elseif (!$logoUrl)
                                                        <span class="entity-subtitle">View only</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>@endforeach</tbody>
                                    </table>
                                </div>
        <div class="table-pagination">
            <x-pagination :paginator="$tenants" />
        </div>@else<div class="empty-state">
            <h3 class="panel-title">No tenants found</h3>
            <p class="panel-copy">Create the first tenant account and assign its catalog, package, and language.</p>
        </div>@endif
    </section>
</main>

@script
<script>
    $wire.on('csv-download', ({ content, filename }) => {
        const bytes = Uint8Array.from(atob(content), c => c.charCodeAt(0));
        const blob = new Blob([bytes], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('[data-print-logo]');
        if (!trigger) return;
        const url = trigger.getAttribute('data-logo-url');
        if (!url) return;
        const win = window.open('', '_blank');
        if (!win) return;
        const img = win.document.createElement('img');
        img.src = url;
        img.style.maxWidth = '100%';
        img.style.maxHeight = '100vh';
        img.onload = () => win.print();
        win.document.title = 'Print Logo';
        win.document.body.style.margin = '0';
        win.document.body.style.display = 'flex';
        win.document.body.style.alignItems = 'center';
        win.document.body.style.justifyContent = 'center';
        win.document.body.style.height = '100vh';
        win.document.body.appendChild(img);
    });
</script>
@endscript
