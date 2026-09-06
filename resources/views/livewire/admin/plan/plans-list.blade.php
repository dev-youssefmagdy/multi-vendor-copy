<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">{{ __('Packages') }}</div>
            <h1 class="D page-title">{{ __('Plans') }}</h1>
            <p class="page-copy">{{ __('Manage tenant packages, feature summaries, and package translations.') }}</p>
        </div>
        <div class="page-actions">
            @if ($canManagePackages)
                <a href="{{ route('admin.plans.create') }}" class="btn btn-primary">{{ __('Add Package') }}</a>
            @endif
        </div>
    </div>
    @if (session('status'))
        <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif
    <div class="g-stats3 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">{{ __('Packages') }}</div>
                    <div class="D stat-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="section-copy">{{ __('Subscription packages available to tenants.') }}</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">{{ __('Published') }}</div>
                    <div class="D stat-value">{{ number_format($stats['published']) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="section-copy">{{ __('Packages currently marketable in central admin.') }}</p>
        </div>
        <div class="card card-glow-violet">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">{{ __('Subscribers') }}</div>
                    <div class="D stat-value">{{ number_format($stats['tenants']) }}</div>
                </div>
                <div class="mini-stat-dot dot-violet"></div>
            </div>
            <p class="section-copy">{{ __('Tenants currently assigned to packages.') }}</p>
        </div>
    </div>
    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">{{ __('Package Filters') }}</h3>
                <p class="panel-copy">{{ __('Search by translated name, then narrow by status.') }}</p>
            </div><svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div><label class="field-label">{{ __('Search') }}</label><input type="text" class="field-control"
                    wire:model.live.debounce.300ms="search"></div>
            <div><label class="field-label">{{ __('Status') }}</label><select class="field-control"
                    wire:model.live="statusFilter">
                    <option value="">{{ __('All statuses') }}</option>@foreach ($statusOptions as $statusOption)<option
                    value="{{ $statusOption->value }}">{{ __(ucfirst($statusOption->value)) }}</option>@endforeach
                </select></div>
        </div>
        <div class="filters-actions">
            <p class="filters-note">
                {{ __('Translate package copy and maintain feature lists for public pricing pages.') }}
            </p>
            <button type="button" class="btn btn-secondary" wire:click="clearFilters">{{ __('Reset Filters') }}</button>
        </div>
    </details>
    <section class="card table-card-shell">
        @if ($packages->count())
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('Package') }}</th>
                            <th>{{ __('Term') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Features') }}</th>
                            <th>{{ __('Trial') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="ta-r">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($packages as $package)
                            @php
                                $featureLabels = array_keys((array) ($package->features ?? []));
                                $visibleFeatureLabels = array_slice($featureLabels, 0, 3);
                                $remainingFeatures = count($featureLabels) - count($visibleFeatureLabels);
                                $termIcons = [
                                    'monthly' => 'fas fa-calendar-alt',
                                    'quarterly' => 'fas fa-calendar-week',
                                    'yearly' => 'fas fa-calendar',
                                    'lifetime' => 'fas fa-infinity',
                                ];
                                $termIcon = $termIcons[$package->term->value] ?? 'fas fa-calendar-alt';
                            @endphp
                            <tr>
                                <td>
                                    <div class="entity-title" style="display:flex;align-items:center;gap:.5rem;">
                                        <i class="{{ $package->icon ?? 'fas fa-box-open' }}"
                                            style="font-size:1rem;color:var(--primary);"></i>
                                        <span>{{ $package->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-amber" style="display:inline-flex;align-items:center;gap:.3rem;">
                                        <i class="{{ $termIcon }}" style="font-size:.7rem;"></i>
                                        {{ __(ucfirst($package->term->value)) }}
                                    </span>
                                </td>
                                <td>
                                    @if((float) $package->price === 0.0)
                                        <span class="badge badge-green">{{ __('Free') }}</span>
                                    @else
                                        ${{ number_format((float) $package->price, 2) }}
                                    @endif
                                </td>
                                <td>
                                    <div class="entity-title">{{ count($featureLabels) }} {{ mb_strtolower(__('Features')) }}
                                    </div>
                                    <div class="entity-subtitle">
                                        @if (count($visibleFeatureLabels))
                                            {{ implode(', ', $visibleFeatureLabels) }}{{ $remainingFeatures > 0 ? ' +' . $remainingFeatures . ' ' . __('more') : '' }}
                                        @else
                                            {{ __('No feature list') }}
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $package->trial_days }} {{ __('days') }}</td>
                                <td><span
                                        class="badge {{ $package->status === \App\Enums\PackageStatus::Published ? 'badge-green' : ($package->status === \App\Enums\PackageStatus::Archived ? 'badge-red' : 'badge-amber') }}">{{ __(ucfirst($package->status->value)) }}</span>
                                </td>
                                <td class="ta-r">
                                    @if ($canManagePackages)
                                        <div class="table-actions-inline"><a href="{{ route('admin.plans.edit', $package) }}"
                                                class="btn btn-secondary btn-sm">{{ __('Edit') }}</a><button type="button"
                                                class="btn btn-secondary btn-sm btn-danger"
                                                wire:click="deletePackage({{ $package->id }})">{{ __('Delete') }}</button></div>
                                    @else
                                        <span class="entity-subtitle">{{ __('View only') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <h3 class="panel-title">{{ __('No packages found') }}</h3>
                <p class="panel-copy">{{ __('Create the first package to start assigning tenants to plans.') }}</p>
            </div>
        @endif
    </section>
</main>