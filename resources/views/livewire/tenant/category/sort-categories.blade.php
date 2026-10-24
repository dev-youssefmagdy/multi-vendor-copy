<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Storefront Catalog</div>
            <h1 class="D page-title">Category Order</h1>
            <p class="page-copy">Drag rows to reorder how categories appear in the storefront navigation.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('tenant.categories.index') }}" class="btn btn-secondary">Back to Categories</a>
        </div>
    </div>

    <section class="card table-card-shell section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Active Categories</h3>
                <p class="panel-copy">{{ $categories->count() }} active categories.</p>
            </div>
        </div>

        @if ($categories->count())
            <div class="table-scroll-wrap">
                <table class="data-table" id="categories-sort-table">
                    <thead>
                        <tr>
                            <th style="width:32px"></th>
                            <th>Category</th>
                            <th>Parent</th>
                        </tr>
                    </thead>
                    <tbody id="categories-sortable">
                        @foreach ($categories as $category)
                            <tr data-id="{{ $category->id }}" class="sortable-row" style="cursor:grab">
                                <td>
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <line x1="4" y1="8" x2="20" y2="8" />
                                        <line x1="4" y1="16" x2="20" y2="16" />
                                    </svg>
                                </td>
                                <td>
                                    <div class="entity-title">{{ $category->translationValue('name') ?? $category->slug ?? 'Category #' . $category->id }}</div>
                                    <div class="entity-subtitle">/{{ $category->slug }}</div>
                                </td>
                                <td>{{ $category->parent?->translationValue('name') ?? 'Root' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <h3 class="panel-title">No active categories</h3>
                <p class="panel-copy">Activate categories before ordering them.</p>
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                const list = document.getElementById('categories-sortable');
                if (!list || typeof Sortable === 'undefined') {
                    return;
                }

                Sortable.create(list, {
                    animation: 150,
                    handle: '.sortable-row',
                    onEnd: () => {
                        const orderedIds = Array.from(list.querySelectorAll('tr[data-id]')).map(row => parseInt(row.dataset.id, 10));
                        @this.call('updateOrder', orderedIds);
                    },
                });
            });
        </script>
    @endpush
</main>
