<div class="form-grid form-grid-2">

    {{-- ── Async product picker ───────────────────────────────────────────── --}}
    <div class="span-2">
        <label class="field-label">Products *</label>

        {{-- Selected chips --}}
        @if (!empty($productIds))
            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                @foreach ($productIds as $selectedId)
                    @php $intId = (int) $selectedId; @endphp
                    <span
                        style="display:inline-flex;align-items:center;gap:5px;padding:3px 6px 3px 12px;border-radius:999px;background:rgba(0,229,255,.12);color:var(--cyan);font-size:11.5px;font-weight:700;">
                        {{ $selectedProductLabels[$intId] ?? 'Product #' . $intId }}
                        <button type="button" wire:click="removeProduct({{ $intId }})"
                            style="display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border-radius:50%;background:rgba(0,229,255,.18);color:var(--cyan);font-size:12px;line-height:1;border:none;cursor:pointer;flex-shrink:0;"
                            aria-label="Remove">×</button>
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Search input --}}
        <input type="text" class="field-control" wire:model.live.debounce.300ms="productSearch"
            placeholder="Search products by name or slug…" autocomplete="off">

        {{-- Results list --}}
        <div style="border:1px solid var(--border);border-radius:8px;margin-top:4px;max-height:220px;overflow-y:auto;position:relative;"
            wire:loading.class="opacity-50" wire:target="updatedProductSearch,loadMoreProducts">

            @forelse ($productSearchResults as $productId => $productName)
                @php $isSelected = in_array((string) $productId, $productIds, true); @endphp
                <button type="button" class="enhanced-select-option {{ $isSelected ? 'is-selected' : '' }}"
                    wire:click="toggleProduct({{ $productId }})" wire:key="ps-{{ $productId }}">
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
                    <button type="button" class="btn btn-secondary btn-sm" style="width:100%;" wire:click="loadMoreProducts"
                        wire:loading.attr="disabled" wire:target="loadMoreProducts">
                        <span wire:loading.remove wire:target="loadMoreProducts">Load more</span>
                        <span wire:loading wire:target="loadMoreProducts">Loading…</span>
                    </button>
                </div>
            @endif
        </div>

        @error('productIds')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    {{-- Discount --}}
    <div>
        <label class="field-label">Discount Percentage</label>
        <x-input type="number" wire:model.defer="discountPercentage" :error="$errors->has('discountPercentage')" />
        @error('discountPercentage')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    {{-- Start Date --}}
    <div>
        <label class="field-label">Start Date *</label>
        <x-input type="datetime-local" required wire:model.defer="startDate" :error="$errors->has('startDate')" />
        @error('startDate')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    {{-- End Date --}}
    <div>
        <label class="field-label">End Date *</label>
        <x-input type="datetime-local" required wire:model.defer="endDate" :error="$errors->has('endDate')" />
        @error('endDate')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    {{-- Status --}}
    <div>
        <label class="field-label">Status</label>
        <x-checkbox wire:model.defer="active" label="Flash sale is active" />
    </div>

</div>