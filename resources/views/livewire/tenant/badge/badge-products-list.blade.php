<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Tenant Catalog</div>
            <h1 class="D page-title">{{ $badgeTitle }}</h1>
            <p class="page-copy">Select which products appear under the <strong>{{ $badgeText }}</strong> badge, then
                click Save.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">
                Back to Products
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif

    <div class="g-stats4 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Selected</div>
                    <div class="D stat-value">{{ number_format($assignedCount) }}</div>
                </div>
                <div class="stat-icon">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                </div>
            </div>
            <p class="stat-sub">Products currently selected for the <strong>{{ $badgeText }}</strong> badge.</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Catalog Products</div>
                    <div class="D stat-value">{{ number_format($catalogCount) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="stat-sub">Total products available to assign to this badge.</p>
        </div>
    </div>

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
            @if ($filterCategoryId)
                <button type="button" class="btn btn-primary btn-sm" wire:click="assignAllInCategory"
                    wire:confirm="Merge ALL products in the selected category into the current selection?"
                    wire:loading.attr="disabled" wire:target="assignAllInCategory">
                    <span wire:loading.remove wire:target="assignAllInCategory">Merge all in category</span>
                    <span wire:loading wire:target="assignAllInCategory">Merging…</span>
                </button>
            @endif
        </div>

        {{-- Products multiselect --}}
        <div class="mb-5">
            <label class="field-label mb-1 block">Assigned products</label>
            <x-select multiple searchable wire:model="selectedProducts" placeholder="Search and select products…">
                @foreach ($productOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-select>
        </div>

        {{-- Save --}}
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save assignment</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </section>
</main>