<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Central Catalog</div>
            <h1 class="D page-title">Product Order</h1>
            <p class="page-copy">Drag rows to reorder how products are exposed to tenants and the central storefront.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Back to Products</a>
        </div>
    </div>

    @if (session('status'))
        <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif

    <section class="card table-card-shell section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">All Products</h3>
                <p class="panel-copy">{{ $products->count() }} products.</p>
            </div>
            <div>
                <input type="text" wire:model.live.debounce.400ms="search" class="form-input" placeholder="Search by name, slug, or SKU">
            </div>
        </div>

        @if ($products->count())
            <div class="table-scroll-wrap">
                <table class="data-table" id="products-sort-table">
                    <thead>
                        <tr>
                            <th style="width:32px"></th>
                            <th>Product</th>
                            <th>SKU</th>
                        </tr>
                    </thead>
                    <tbody id="products-sortable" wire:key="products-sortable-{{ $products->count() }}">
                        @foreach ($products as $product)
                            <tr data-id="{{ $product->id }}" class="sortable-row" style="cursor:grab">
                                <td>
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <line x1="4" y1="8" x2="20" y2="8" />
                                        <line x1="4" y1="16" x2="20" y2="16" />
                                    </svg>
                                </td>
                                <td>
                                    <div class="entity-title">{{ $product->translationValue('name') ?? $product->slug ?? 'Product #' . $product->id }}</div>
                                    <div class="entity-subtitle">/{{ $product->slug }}</div>
                                </td>
                                <td>{{ $product->sku }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <h3 class="panel-title">No products</h3>
                <p class="panel-copy">Create products before ordering them.</p>
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                const initSortable = () => {
                    const list = document.getElementById('products-sortable');
                    if (!list || typeof Sortable === 'undefined' || list.dataset.sortableInit) {
                        return;
                    }
                    list.dataset.sortableInit = '1';

                    Sortable.create(list, {
                        animation: 150,
                        handle: '.sortable-row',
                        onEnd: () => {
                            const orderedIds = Array.from(list.querySelectorAll('tr[data-id]')).map(row => parseInt(row.dataset.id, 10));
                            @this.call('updateOrder', orderedIds);
                        },
                    });
                };

                initSortable();
                document.addEventListener('livewire:morphed', initSortable);
            });
        </script>
    @endpush
</main>
