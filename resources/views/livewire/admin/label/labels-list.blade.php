<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Central Catalog</div>
            <h1 class="D page-title">Product Labels</h1>
            <p class="page-copy">Icon + text labels shown on storefront product cards (e.g. "Fast delivery"). Use {stock} and {delivery_date} in the text for per-product values.</p>
        </div>
        <div class="page-actions">@if ($canManageLabels)<a href="{{ route('admin.labels.create') }}" class="btn btn-primary">Add Label</a>@endif</div>
    </div>
    @if (session('status'))<div class="card section-gap notice-success">{{ session('status') }}</div>@endif

    <div class="card filters-card section-gap">
        <div class="filters-grid">
            <div><label class="field-label">Search</label><input type="text" class="field-control" wire:model.live.debounce.300ms="search"></div>
        </div>
    </div>

    <section class="card table-card-shell">
        @if ($labels->count())
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Label</th><th>Products</th><th>Order</th><th>Status</th><th class="ta-r">Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($labels as $label)
                            <tr>
                                <td>
                                    <div class="entity-title" style="display:flex;align-items:center;gap:8px">
                                        @if ($label->icon && isset($icons[$label->icon]))
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$label->icon]['svg'] !!}</svg>
                                        @endif
                                        <span>{{ $label->text }}</span>
                                    </div>
                                </td>
                                <td>{{ number_format($label->products_count) }}</td>
                                <td>{{ $label->sort_order }}</td>
                                <td>
                                    <button type="button" wire:click="toggleActive({{ $label->id }})" @disabled(!$canManageLabels)
                                        class="badge {{ $label->active ? 'badge-green' : 'badge-amber' }}">{{ $label->active ? 'Active' : 'Inactive' }}</button>
                                </td>
                                <td class="ta-r">
                                    @if ($canManageLabels)
                                        <div class="table-actions-inline">
                                            <a href="{{ route('admin.labels.edit', $label) }}" class="btn btn-secondary btn-sm">Edit</a>
                                            <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                                wire:click="deleteLabel({{ $label->id }})"
                                                wire:confirm="Delete this label? It will be removed from all products.">Delete</button>
                                        </div>
                                    @else
                                        <span class="entity-subtitle">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:12px">{{ $labels->links() }}</div>
        @else
            <div class="empty-state">
                <h3 class="panel-title">No labels found</h3>
                <p class="panel-copy">Create a label, then assign it to products.</p>
            </div>
        @endif
    </section>
</main>
