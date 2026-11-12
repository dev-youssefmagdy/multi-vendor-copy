<main id="mn">
    <form wire:submit="save" class="page-stack section-gap">
        <div class="page-head fu d0">
            <div>
                <div class="page-title-row">
                    <h1 class="D page-title">{{ $pageTitle }}</h1>
                    <span class="page-badge">{{ $badge ?? __('Manufacturing') }}</span>
                </div>
                <p class="page-copy">{{ $pageDescription }}</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('tenant.manufacturing.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
                <x-btn type="submit" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ __('Submit Request') }}</span>
                    <span wire:loading wire:target="save">{{ __('Submitting...') }}</span>
                </x-btn>
            </div>
        </div>

        @if (session('status'))
            <div class="card section-gap notice-success">{{ session('status') }}</div>
        @endif

        <x-card-collapse :title="__('Request Details')" :start-open="true">
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">{{ __('Product Name') }} <span class="field-required">*</span></label>
                    <x-input wire:model.defer="productName" :placeholder="__('What product do you need manufactured?')" />
                    @error('productName') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">{{ __('Quantity') }} <span class="field-required">*</span></label>
                    <x-input type="number" wire:model.defer="quantity" min="1" />
                    @error('quantity') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-col-full">
                    <label class="field-label">{{ __('Description') }} <span class="field-optional">{{ __('(optional)') }}</span></label>
                    <x-textarea wire:model.defer="description" rows="4"
                        :placeholder="__('Add any specifications, dimensions, materials, or special requirements...')" />
                    @error('description') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">{{ __('Link to Existing Product') }} <span
                            class="field-optional">{{ __('(optional)') }}</span></label>

                    @if ($linkedProductId)
                        <div
                            style="display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:rgba(0,229,255,.06);">
                            <span style="flex:1;font-size:13px;color:var(--t1);">{{ $linkedProductName }}</span>
                            <button type="button" wire:click="clearLinkedProduct"
                                style="background:none;border:none;cursor:pointer;color:var(--t3);font-size:16px;line-height:1;padding:0 2px;"
                                title="{{ __('Remove') }}">{{ __('×') }}</button>
                        </div>
                    @else
                        <input type="text" class="field-control" wire:model.live.debounce.300ms="productSearch"
                            placeholder="{{ __('Search by name or slug…') }}" autocomplete="off">

                        <div style="border:1px solid var(--border);border-radius:8px;margin-top:4px;max-height:220px;overflow-y:auto;"
                            wire:loading.class="opacity-50" wire:target="updatedProductSearch,loadMoreProducts">

                            @forelse ($productSearchResults as $productId => $productLabel)
                                <button type="button" class="enhanced-select-option"
                                    wire:click="selectProduct({{ $productId }})" wire:key="ps-{{ $productId }}">
                                    <span>{{ $productLabel }}</span>
                                </button>
                            @empty
                                <div style="padding:14px;text-align:center;font-size:12.5px;color:var(--t3);">
                                    {{ __('No products found') }}
                                </div>
                            @endforelse

                            @if ($hasMoreProducts)
                                <div style="padding:6px;">
                                    <button type="button" class="btn btn-secondary btn-sm" style="width:100%;"
                                        wire:click="loadMoreProducts" wire:loading.attr="disabled"
                                        wire:target="loadMoreProducts">
                                        <span wire:loading.remove wire:target="loadMoreProducts">{{ __('Load more') }}</span>
                                        <span wire:loading wire:target="loadMoreProducts">{{ __('Loading…') }}</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif

                    @error('linkedProductId') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-card-collapse>
    </form>
</main>