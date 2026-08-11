<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Central Catalog</div>
            <h1 class="D page-title">Categories List</h1>
            <p class="page-copy">Manage translated product categories, hierarchy, and storefront visibility.</p>
        </div>
        <div class="page-actions">
            @if ($canManageCategories)
                <a href="{{ route('admin.categories.create') }}"  class="btn btn-primary">Add Category</a>
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
                    <div class="eyebrow">Categories</div>
                    <div class="D stat-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="section-copy">Central catalog categories across all locales.</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Published</div>
                    <div class="D stat-value">{{ number_format($stats['published']) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="section-copy">Visible categories ready for product assignment.</p>
        </div>
        <div class="card card-glow-violet">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Featured</div>
                    <div class="D stat-value">{{ number_format($stats['featured']) }}</div>
                </div>
                <div class="mini-stat-dot dot-violet"></div>
            </div>
            <p class="section-copy">Highlighted categories for hero, nav, or promo blocks.</p>
        </div>
    </div>

    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Category Filters</h3>
                <p class="panel-copy">Search by slug or translated label and filter by publish state.</p>
            </div>
            <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div><label class="field-label" for="category-search">Search</label><input id="category-search" type="text"
                    class="field-control" wire:model.live.debounce.300ms="search" placeholder="Slug or translated name">
            </div>
            <div><label class="field-label" for="category-status">Status</label><select id="category-status"
                    class="field-control" wire:model.live="statusFilter">
                    <option value="">All statuses</option>@foreach ($statusOptions as $statusOption)<option
                    value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>@endforeach
                </select></div>
            <div><label class="field-label" for="category-catalog">Catalog</label><select id="category-catalog"
                    class="field-control" wire:model.live="catalogFilter">
                    <option value="">All catalogs</option>@foreach ($catalogs as $catalog)<option
                    value="{{ $catalog->id }}">{{ $catalog->name ?? $catalog->slug }}</option>@endforeach
                </select></div>
        </div>
        <div class="filters-actions">
            <p class="filters-note">Use translated names for storefront display and keep slugs stable for URLs.</p>
            <button type="button" class="btn btn-secondary" wire:click="clearFilters">Reset Filters</button>
        </div>
    </details>

    <section class="card table-card-shell">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Catalog Categories</h3>
                <p class="panel-copy">{{ $categories->total() }} records matched the current filters.</p>
            </div>
        </div>
        @if ($categories->count())
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Thumbnail</th>
                            <th>Category</th>
                            <th>Catalog</th>
                            <th>Parent</th>
                            <th>Featured</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="ta-r">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td>
                                    <div class="entity-media">
                                        @if ($category->thumb_url)
                                            <img src="{{ $category->thumb_url }}" alt="{{ $category->name ?? $category->slug }}"
                                                class="entity-thumb">
                                        @else
                                            <div class="entity-placeholder">
                                                {{ strtoupper(substr($category->translationValue('name') ?? $category->slug, 0, 1)) }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="entity-title">{{ $category->translationValue('name') ?? $category->slug }}</div>
                                    <div class="entity-subtitle">/{{ $category->slug }}</div>
                                </td>
                                <td>
                                    @forelse ($category->catalogs as $cat)
                                        <span class="badge badge-cyan">{{ $cat->translationValue('name') ?? $cat->slug }}</span>
                                    @empty
                                        <span class="entity-subtitle">No catalog</span>
                                    @endforelse
                                </td>
                                <td>{{ $category->parent?->translationValue('name') ?? 'Root' }}</td>
                                <td>{{ $category->is_featured ? 'Featured' : 'Standard' }}</td>
                                <td><span
                                        class="badge {{ $category->status === \App\Enums\CategoryStatus::Published ? 'badge-green' : ($category->status === \App\Enums\CategoryStatus::Archived ? 'badge-red' : 'badge-amber') }}">{{ $category->status->label() }}</span>
                                </td>
                                <td>{{ $category->updated_at?->format('M d, Y') }}</td>
                                <td class="ta-r">
                                    @if ($canManageCategories)
                                        <div class="table-actions-inline"><a href="{{ route('admin.categories.edit', $category) }}"
                                                 class="btn btn-secondary btn-sm">Edit</a><button type="button"
                                                class="btn btn-secondary btn-sm btn-danger"
                                                wire:click="deleteCategory({{ $category->id }})">Delete</button></div>
                                    @else
                                        <span class="entity-subtitle">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <h3 class="panel-title">No categories found</h3>
                <p class="panel-copy">Create the first translated category to start structuring the product catalog.</p>
            </div>
        @endif
        <x-pagination :paginator="$categories" />
    </section>
</main>
