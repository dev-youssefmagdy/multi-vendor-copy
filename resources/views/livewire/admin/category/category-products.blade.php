<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Central Catalog</div>
            <h1 class="D page-title">Product Order — {{ $category->translationValue('name') ?? $category->slug }}</h1>
            <p class="page-copy">Drag rows to reorder how products appear inside this category.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Back to Categories</a>
        </div>
    </div>

    <section class="card table-card-shell section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Category Products</h3>
                <p class="panel-copy">{{ $products->count() }} products assigned to this category.</p>
            </div>
        </div>

        @if ($products->count())
            <div class="table-scroll-wrap">
                <table class="data-table" id="category-products-table" wire:ignore.self>
                    <thead>
                        <tr>
                            <th style="width:32px"></th>
                            <th>Thumbnail</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="category-products-sortable">
                        @foreach ($products as $product)
                            <tr data-id="{{ $product->id }}" class="sortable-row" style="cursor:grab">
                                <td>
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <line x1="4" y1="8" x2="20" y2="8" />
                                        <line x1="4" y1="16" x2="20" y2="16" />
                                    </svg>
                                </td>
                                <td>
                                    <div class="entity-media">
                                        @if ($product->primary_image_url)
                                            <img src="{{ $product->primary_image_url }}" alt="{{ $product->translationValue('name') ?? $product->slug }}" class="entity-thumb">
                                        @else
                                            <div class="entity-placeholder">{{ strtoupper(substr($product->translationValue('name') ?? $product->slug, 0, 1)) }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="entity-title">{{ $product->translationValue('name') ?? $product->slug }}</div>
                                    <div class="entity-subtitle">/{{ $product->slug }}</div>
                                </td>
                                <td>{{ $product->sku }}</td>
                                <td><span class="badge badge-green">{{ $product->status->label() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <h3 class="panel-title">No products assigned</h3>
                <p class="panel-copy">Assign products to this category before ordering them.</p>
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                const list = document.getElementById('category-products-sortable');
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
