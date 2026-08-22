<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Central Catalog</div>
            <h1 class="D page-title">Products List</h1>
            <p class="page-copy">Manage central products, translated merchandising copy, media assets, and delivery
                coverage.</p>
        </div>
        <div class="page-actions" style="flex-shrink:0; display:flex; gap:8px;">
            @if ($canManageProducts)
                <a href="{{ route('admin.products.sort') }}" class="btn btn-secondary" style="width:100%;justify-content:center">Sort Products</a>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary"
                    style="width:100%;justify-content:center">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    <span>Add Product</span>
                </a>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif

    {{-- ── Stats ─────────────────────────────────────────────────────────── --}}
    <div class="g-stats4 section-gap">

        {{-- Products In-store --}}
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Products In-store</div>
                    <div class="D stat-value">{{ number_format($stats['products_total']) }}</div>
                </div>
                <div class="stat-icon">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                </div>
            </div>
            <p class="stat-sub">Central catalog items available for tenant consumption.</p>
        </div>

        {{-- Categories --}}
        <div class="card card-glow-violet">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Categories</div>
                    <div class="D stat-value">
                        {{ number_format($stats['categories_with_items']) }}/{{ number_format($stats['categories_total']) }}
                    </div>
                </div>
                <div class="stat-icon">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8">
                        <rect x="2" y="3" width="7" height="7" />
                        <rect x="15" y="3" width="7" height="7" />
                        <rect x="2" y="14" width="7" height="7" />
                        <rect x="15" y="14" width="7" height="7" />
                    </svg>
                </div>
            </div>
            <p class="stat-sub">has items</p>
        </div>

        {{-- Sales --}}
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Sales</div>
                    <div class="D stat-value">${{ number_format($stats['sales_amount'], 2) }}</div>
                </div>
                <div class="stat-icon">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8">
                        <polyline points="20 12 20 22 4 22 4 12" />
                        <rect x="2" y="7" width="20" height="5" />
                        <path d="M12 22V7" />
                        <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z" />
                        <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z" />
                    </svg>
                </div>
            </div>
            <p class="stat-sub">{{ number_format($stats['sales_orders']) }} orders <span
                    class="stat-pill-pos">+0.00%</span></p>
        </div>

        {{-- Affiliate --}}
        <div class="card card-glow-amber">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Affiliate</div>
                    <div class="D stat-value">${{ number_format($stats['affiliate_amount'], 2) }}</div>
                </div>
                <div class="stat-icon">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                        <path d="M12 6v2m0 8v2M8 12H6m12 0h-2" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </div>
            </div>
            <p class="stat-sub">{{ number_format($stats['affiliate_orders']) }} orders <span
                    class="stat-pill-neg">-0.00%</span></p>
        </div>

    </div>

    {{-- ── Filters ───────────────────────────────────────────────────────── --}}
    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Catalog Filters</h3>
                <p class="panel-copy">Search by SKU or translated name, then narrow by category, status, or delivery
                    scope.</p>
            </div>
            <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>

        <div class="filters-grid">
            <div>
                <label class="field-label" for="product-search">Search</label>
                <input id="product-search" type="text" class="field-control" wire:model.live.debounce.300ms="search"
                    placeholder="SKU or product name">
            </div>

            <div>
                <label class="field-label" for="status-filter">Status</label>
                <select id="status-filter" class="field-control" wire:model.live="statusFilter">
                    <option value="">All statuses</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label" for="category-filter">Category</label>
                <select id="category-filter" class="field-control" wire:model.live="categoryFilter">
                    <option value="">All categories</option>
                    @foreach ($categories as $cat)
                        @php
                            $prefix = str_repeat('—', $cat['depth']) . ($cat['depth'] > 0 ? ' ' : '');
                        @endphp
                        <option value="{{ $cat['id'] }}" @if(!$cat['is_leaf']) disabled @endif>
                            {{ $prefix }}{{ $cat['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label" for="scope-filter">Delivery</label>
                <select id="scope-filter" class="field-control" wire:model.live="deliveryScopeFilter">
                    <option value="">All delivery scopes</option>
                    @foreach ($deliveryScopes as $scope)
                        <option value="{{ $scope->value }}">{{ $scope->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label" for="stock-filter">Stock</label>
                <select id="stock-filter" class="field-control" wire:model.live="stockFilter">
                    <option value="">All</option>
                    <option value="in">In Stock</option>
                    <option value="partial">Partially Out of Stock</option>
                    <option value="out">Out of Stock</option>
                </select>
            </div>

            <div>
                <label class="field-label" for="show-deleted-filter">Deleted products</label>
                <label class="field-control" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input id="show-deleted-filter" type="checkbox" wire:model.live="showDeleted">
                    <span>Show deleted products</span>
                </label>
            </div>
        </div>

        <div class="filters-actions">
            <p class="filters-note">{{ number_format($stats['categories_total']) }} categories available for assignment.
            </p>
            <button type="button" class="btn btn-secondary" wire:click="clearFilters">Reset Filters</button>
        </div>
    </details>

    {{-- ── Table ─────────────────────────────────────────────────────────── --}}
    <section class="card table-card-shell">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Central Products</h3>
                <p class="panel-copy">{{ $products->total() }} records matched the current filters.</p>
            </div>
            <div class="table-header-actions">
                <div class="table-search section-copy">Showing page {{ $products->currentPage() }} of
                    {{ $products->lastPage() }}
                </div>
            </div>
        </div>

        @if ($products->count())
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>SKU</th>
                            <th>Factory</th>
                            <th>Date Added</th>
                            <th>Price</th>
                            <th>Weight (g)</th>
                            <th>Categories</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th class="ta-r">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            @php
                                $statusTone = match ($product->status) {
                                    \App\Enums\ProductStatus::Published => 'badge badge-green',
                                    \App\Enums\ProductStatus::Archived => 'badge badge-red',
                                    default => 'badge badge-amber',
                                };

                                // Build a merged tree from all assigned categories + their ancestors,
                                // so shared parents appear only once.
                                $catMap = [];
                                foreach ($product->categories as $cat) {
                                    $pathNodes = [];
                                    $node = $cat;
                                    while ($node) {
                                        array_unshift($pathNodes, $node);
                                        $node = $node->parent ?? null;
                                    }
                                    $parentId = null;
                                    foreach ($pathNodes as $pNode) {
                                        $nid = $pNode->id;
                                        if (!array_key_exists($nid, $catMap)) {
                                            $catMap[$nid] = ['model' => $pNode, 'parent_id' => $parentId, 'children' => []];
                                            if ($parentId !== null && isset($catMap[$parentId])) {
                                                $catMap[$parentId]['children'][] = $nid;
                                            }
                                        }
                                        $parentId = $nid;
                                    }
                                }
                                $catRoots = array_keys(array_filter($catMap, fn($n) => $n['parent_id'] === null));

                                // Recursive HTML renderer — user values escaped with e()
                                $folderSvg = '<svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="ct-icon"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>';
                                $renderCatTree = function (array $ids) use (&$renderCatTree, &$catMap, $folderSvg): string {
                                    $html = '';
                                    foreach ($ids as $id) {
                                        $n = $catMap[$id];
                                        $name = e($n['model']->translationValue('name') ?? $n['model']->slug);
                                        if (!empty($n['children'])) {
                                            $html .= '<details open class="ct-details">'
                                                . '<summary>'
                                                . '<span class="ct-toggle"></span>'
                                                . $folderSvg
                                                . '<span class="ct-name">' . $name . '</span>'
                                                . '</summary>'
                                                . '<div class="ct-children">'
                                                . $renderCatTree($n['children'])
                                                . '</div></details>';
                                        } else {
                                            $html .= '<div class="ct-leaf">'
                                                . $folderSvg
                                                . '<span class="ct-name">' . $name . '</span>'
                                                . '</div>';
                                        }
                                    }
                                    return $html;
                                };
                                $catHtml = $renderCatTree($catRoots);

                                $stockStatus = $product->stockStatus();
                                $rowStockClass = match ($stockStatus) {
                                    'out_of_stock' => 'row-out-of-stock',
                                    'partial' => 'row-partial-stock',
                                    default => '',
                                };
                              @endphp
                            <tr wire:key="product-row-{{ $product->id }}" class="{{ $rowStockClass }}">

                                {{-- Image --}}
                                <td>
                                    <div class="entity-media">
                                        @if ($product->primary_image_url)
                                            <img src="{{ $product->primary_image_url }}"
                                                alt="{{ $product->translationValue('name') ?? $product->sku }}"
                                                class="entity-thumb">
                                        @else
                                            <div class="entity-placeholder">
                                                {{ strtoupper(substr($product->translationValue('name') ?? $product->sku, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Title --}}
                                <td>
                                    <div class="entity-title">{{ $product->translationValue('name') ?? 'Untitled product' }}
                                    </div>
                                    <div class="entity-subtitle">/{{ $product->slug }}</div>
                                    @if ($stockStatus === 'out_of_stock')
                                        <span class="badge badge-red" style="font-size:10px;margin-top:4px;display:inline-block;">Out of Stock</span>
                                    @elseif ($stockStatus === 'partial')
                                        <span class="badge badge-amber" style="font-size:10px;margin-top:4px;display:inline-block;">Partial</span>
                                    @endif
                                    @if ($product->badges->isNotEmpty())
                                        <div class="entity-badges" style="margin-top:4px;display:flex;flex-wrap:wrap;gap:4px;">
                                            @foreach ($product->badges as $badge)
                                                @php
                                                    $badgeTone = match ($badge->text) {
                                                        'featured' => 'badge badge-violet',
                                                        'recommended' => 'badge badge-green',
                                                        'best-selling' => 'badge badge-amber',
                                                        'new-in' => 'badge badge-cyan',
                                                        default => 'badge badge-amber',
                                                    };
                                                @endphp
                                                <span class="{{ $badgeTone }}" style="font-size:10px;">{{ ucfirst(str_replace('-', ' ', $badge->text)) }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>

                                {{-- SKU --}}
                                <td>
                                    <div class="table-stat">{{ $product->sku }}</div>
                                </td>

                                {{-- Factory --}}
                                <td>
                                    <div class="table-stat">{{ $product->factory ?: '—' }}</div>
                                </td>

                                {{-- Date Added --}}
                                <td>
                                    <div class="table-stat">{{ $product->created_at->format('d M Y') }}</div>
                                    <div class="table-meta">{{ $product->created_at->format('H:i') }}</div>
                                </td>

                                {{-- Price --}}
                                <td>
                                    @if ($product->sale_price)
                                        <div class="table-stat">${{ number_format((float) $product->sale_price, 2) }}</div>
                                        <div class="table-meta">Base ${{ number_format((float) $product->base_price, 2) }}</div>
                                    @else
                                        <div class="table-stat">${{ number_format((float) $product->base_price, 2) }}</div>
                                        <div class="table-meta">No sale price</div>
                                    @endif
                                </td>

                                {{-- Weight --}}
                                <td>
                                    <div class="table-stat">
                                        {{ $product->weight_grams !== null ? number_format($product->weight_grams) . ' g' : '—' }}
                                    </div>
                                </td>

                                {{-- Categories collapse/expand tree --}}
                                <td>
                                    @if (empty($catHtml))
                                        <span class="table-meta">—</span>
                                    @else
                                        <div class="cat-tree">{!! $catHtml !!}</div>
                                    @endif
                                </td>

                                {{-- Stock --}}
                                <td>
                                    @php
                                        $stockQty = $product->variants->isNotEmpty()
                                            ? $product->variants->sum('stock')
                                            : ($product->stock ?? 0);
                                    @endphp
                                    <div class="table-stat">{{ number_format($stockQty) }}</div>
                                    @if ($stockStatus === 'out_of_stock')
                                        <div class="table-meta" style="color:var(--red);">Out of stock</div>
                                    @elseif ($stockStatus === 'partial')
                                        <div class="table-meta" style="color:var(--amber);">Partial stock</div>
                                    @endif
                                    <div class="table-meta">Min {{ number_format($product->min_stock) }}</div>
                                </td>

                                {{-- Status --}}
                                <td>
                                    @if ($product->trashed())
                                        <span class="badge badge-red">Deleted</span>
                                    @else
                                        <span class="{{ $statusTone }}">{{ $product->status->label() }}</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="ta-r">
                                    @if ($canManageProducts)
                                        <div class="table-actions-inline">
                                            @if ($product->trashed())
                                                <button type="button" class="btn btn-secondary btn-sm"
                                                    wire:click="restoreProduct({{ $product->id }})"
                                                    wire:confirm="Restore this product? It will reappear in tenant panels and storefronts.">Restore</button>
                                            @else
                                                <a href="{{ route('admin.products.edit', $product) }}"
                                                    class="btn btn-secondary btn-sm">Edit</a>
                                                <button type="button" class="btn btn-secondary btn-sm"
                                                    wire:click="openPriceListModal({{ $product->id }})"
                                                    title="View per-country price breakdown">Prices</button>
                                                <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                                    wire:click="deleteProduct({{ $product->id }})"
                                                    wire:confirm="Delete this product? It will disappear from tenant panels and storefronts until restored.">Delete</button>
                                            @endif
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
        @else
            <div class="empty-state">
                <div class="empty-icon">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M20 7L12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <h3 class="panel-title">No products found</h3>
                <p class="panel-copy">Try clearing the active filters or create the first product in the central catalog.
                </p>
            </div>
        @endif

        <x-pagination :paginator="$products" />
    </section>

    {{-- ── Price list modal ────────────────────────────────────────────────── --}}
    @if ($priceListOpen)
        <div style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);"
            wire:click.self="closePriceListModal">
            <div
                style="background:var(--surface-2,#1e293b);border:1px solid rgba(255,255,255,.1);border-radius:16px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;padding:24px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <h3 style="font-size:16px;font-weight:600;color:var(--text-primary,#f1f5f9);margin:0;">
                        Price Breakdown — {{ $priceListProductName }}
                    </h3>
                    <button type="button" wire:click="closePriceListModal"
                        style="background:none;border:none;cursor:pointer;color:var(--text-muted,#94a3b8);font-size:18px;line-height:1;padding:4px;">&times;</button>
                </div>
                <p class="entity-subtitle" style="margin-bottom:16px;">
                    Central sale price + configured shipping per country = tenant cost before vendor profit.
                    Tenants then add their profit percentage on top.
                </p>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr style="border-bottom:1px solid rgba(255,255,255,.08);">
                                <th
                                    style="text-align:left;padding:8px 12px;color:var(--text-muted,#94a3b8);font-weight:500;">
                                    Country</th>
                                <th
                                    style="text-align:right;padding:8px 12px;color:var(--text-muted,#94a3b8);font-weight:500;">
                                    Shipping</th>
                                <th
                                    style="text-align:right;padding:8px 12px;color:var(--text-muted,#94a3b8);font-weight:500;">
                                    Total Cost (pre-profit)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($priceListRows as $row)
                                <tr style="border-bottom:1px solid rgba(255,255,255,.04);">
                                    <td style="padding:8px 12px;color:var(--text-primary,#f1f5f9);">{{ $row['country'] }}</td>
                                    <td style="padding:8px 12px;text-align:right;color:var(--text-secondary,#cbd5e1);">
                                        ${{ number_format($row['shipping'], 2) }}
                                    </td>
                                    <td
                                        style="padding:8px 12px;text-align:right;color:var(--text-primary,#f1f5f9);font-weight:600;">
                                        ${{ number_format($row['total_cost'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:16px;text-align:right;">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="closePriceListModal">Close</button>
                </div>
            </div>
        </div>
    @endif

</main>