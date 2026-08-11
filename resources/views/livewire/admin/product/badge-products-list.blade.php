<main id="mn">

    {{-- ── Page head ─────────────────────────────────────────────────────── --}}
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Product Badges</div>
            <h1 class="D page-title">{{ $badgeTitle }}</h1>
            <p class="page-copy">Select which central products appear under the
                <strong>{{ $badgeText }}</strong> badge. Saving syncs changes to all tenants automatically.
            </p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                Back to Products
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif

    {{-- ── Stats ─────────────────────────────────────────────────────────── --}}
    <div class="g-stats4 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Selected</div>
                    <div class="D stat-value">{{ number_format($assignedCount) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="stat-sub">Products currently selected for the <em>{{ $badgeText }}</em> badge.</p>
        </div>
        <div class="card card-glow-purple">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Total Products</div>
                    <div class="D stat-value">{{ number_format($catalogCount) }}</div>
                </div>
                <div class="mini-stat-dot dot-purple"></div>
            </div>
            <p class="stat-sub">All central catalog products available to assign to this badge.</p>
        </div>
    </div>

    {{-- ── Assignment card ──────────────────────────────────────────────── --}}
    <section class="card section-gap">
        <div class="table-header-shell" style="margin-bottom:1.25rem">
            <div>
                <h3 class="panel-title">{{ $badgeTitle }}</h3>
                <p class="panel-copy">Use the category filter to bulk-select products, then fine-tune in the multiselect
                    below.</p>
            </div>
        </div>

        {{-- Assign by category --}}
        <div class="flex items-end gap-3 flex-wrap mb-5">
            <div style="min-width:260px;flex:1">
                <label class="field-label mb-1 block">Bulk-assign by category</label>
                <x-select wire:model.live="filterCategoryId" searchable tree placeholder="Filter by category">
                    <option value="">— choose a category —</option>
                    @foreach ($categoryTree as $row)
                        <option value="{{ $row['id'] }}" data-depth="{{ $row['depth'] }}">
                            {{ str_repeat('— ', $row['depth']) }}{{ $row['name'] }}
                        </option>
                    @endforeach
                </x-select>
            </div>
            @if ($canManage && $filterCategoryId)
                <button type="button" class="btn btn-primary btn-sm" wire:click="assignAllInCategory"
                    wire:confirm="Merge ALL products in the selected category into the current selection?"
                    wire:loading.attr="disabled" wire:target="assignAllInCategory">
                    <span wire:loading.remove wire:target="assignAllInCategory">Merge all in category</span>
                    <span wire:loading wire:target="assignAllInCategory">Merging…</span>
                </button>
            @endif
        </div>

        {{-- Products async picker --}}
        <div class="mb-5">
            <label class="field-label mb-1 block">Assigned products</label>

            {{-- Selected chips --}}
            @if (!empty($selectedProductLabels))
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                    @foreach ($selectedProductLabels as $chipId => $chipName)
                        <span
                            style="display:inline-flex;align-items:center;gap:5px;padding:3px 6px 3px 12px;border-radius:999px;background:rgba(0,229,255,.12);color:var(--cyan);font-size:11.5px;font-weight:700;">
                            {{ $chipName }}
                            @if ($canManage)
                                <button type="button" wire:click="removeProduct({{ $chipId }})"
                                    style="display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border-radius:50%;background:rgba(0,229,255,.18);color:var(--cyan);font-size:12px;line-height:1;border:none;cursor:pointer;flex-shrink:0;"
                                    aria-label="Remove">×</button>
                            @endif
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Search input --}}
            <input type="text" class="field-control" wire:model.live.debounce.300ms="productSearch"
                placeholder="Search products by name or SKU…" autocomplete="off" @disabled(!$canManage)>

            {{-- Results list --}}
            <div style="border:1px solid var(--border);border-radius:8px;margin-top:4px;max-height:240px;overflow-y:auto;"
                wire:loading.class="opacity-50" wire:target="updatedProductSearch,loadMoreProducts">

                @forelse ($productSearchResults as $productId => $productName)
                    @php $isSelected = in_array((string) $productId, $selectedProducts, true); @endphp
                    <button type="button" class="enhanced-select-option {{ $isSelected ? 'is-selected' : '' }}"
                        wire:click="{{ $canManage ? 'toggleProduct(' . $productId . ')' : '' }}"
                        wire:key="bp-{{ $productId }}" @disabled(!$canManage)>
                        <span class="enhanced-select-check {{ $isSelected ? 'is-selected' : '' }}"></span>
                        <span>{{ $productName }}</span>
                    </button>
                @empty
                    <div style="padding:14px;text-align:center;font-size:12.5px;color:var(--t3);">
                        No products found
                    </div>
                @endforelse

                @if ($hasMoreProducts)
                    <div style="padding:6px;">
                        <button type="button" class="btn btn-secondary btn-sm" style="width:100%;"
                            wire:click="loadMoreProducts" wire:loading.attr="disabled" wire:target="loadMoreProducts">
                            <span wire:loading.remove wire:target="loadMoreProducts">Load more</span>
                            <span wire:loading wire:target="loadMoreProducts">Loading…</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Save --}}
        @if ($canManage)
            <div class="flex justify-end gap-3">
                <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save assignment</span>
                    <span wire:loading wire:target="save">Saving &amp; syncing…</span>
                </button>
            </div>
        @endif
    </section>
</main>