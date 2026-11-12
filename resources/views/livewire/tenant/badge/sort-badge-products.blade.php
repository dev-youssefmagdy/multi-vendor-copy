<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">{{ __('Tenant Catalog') }}</div>
            <h1 class="D page-title">{{ __('Sort — :text', ['text' => __(ucwords(str_replace('-', ' ', $badge->text)))]) }}</h1>
            <p class="page-copy">{{ __('Drag rows to reorder how these products appear for this badge on the storefront.') }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('tenant.badges.show', $badge) }}" class="btn btn-secondary">{{ __('Back to Assignment') }}</a>
        </div>
    </div>

    <section class="card table-card-shell section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">{{ __('Assigned Products') }}</h3>
                <p class="panel-copy">{{ __(':products products assigned to this badge.', ['products' => $products->count()]) }}</p>
            </div>
        </div>

        @if ($products->count())
            <div class="table-scroll-wrap">
                <table class="data-table" id="badge-products-sort-table">
                    <thead>
                        <tr>
                            <th style="width:32px"></th>
                            <th>{{ __('Product') }}</th>
                        </tr>
                    </thead>
                    <tbody id="badge-products-sortable">
                        @foreach ($products as $product)
                            <tr data-id="{{ $product->id }}" class="sortable-row" style="cursor:grab">
                                <td>
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <line x1="4" y1="8" x2="20" y2="8" />
                                        <line x1="4" y1="16" x2="20" y2="16" />
                                    </svg>
                                </td>
                                <td>
                                    <div class="entity-title">{{ $product->translationValue('name') ?? $product->slug ?? __('Product #:id', ['id' => $product->id]) }}</div>
                                    <div class="entity-subtitle">/{{ $product->slug }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <h3 class="panel-title">{{ __('No products assigned') }}</h3>
                <p class="panel-copy">{{ __('Assign products to this badge before ordering them.') }}</p>
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                const list = document.getElementById('badge-products-sortable');
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
