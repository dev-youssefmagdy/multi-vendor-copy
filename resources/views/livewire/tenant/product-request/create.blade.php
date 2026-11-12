<main id="mn">
  <div class="page-head fu d0">
    <div>
      <div class="page-title-row">
        <h1 class="D page-title">{{ __('New Product Request') }}</h1>
        <span class="page-badge">{{ __('Catalog') }}</span>
      </div>
      <p class="page-copy">{{ __('Describe the product you want the Neozena team to add to the central catalog.') }}</p>
    </div>
  </div>

  <div class="card fu d1" style="padding:28px 32px;max-width:760px;">
    <div class="stack-gap">

      <div>
        <label class="field-label" for="pr-title">{{ __('Product Title') }} <span style="color:var(--error)">*</span></label>
        <input id="pr-title" type="text" class="field-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
               wire:model.defer="title" placeholder="{{ __('e.g. Wireless Bluetooth Earbuds with Noise Cancellation') }}">
        @error('title') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="field-label" for="pr-desc">{{ __('Description') }} <span style="color:var(--error)">*</span></label>
        <p class="field-hint" style="margin-bottom:6px;">{{ __('Include specifications, target market, estimated price range, and any other relevant details.') }}</p>
        <textarea id="pr-desc" rows="8"
                  class="field-control {{ $errors->has('description') ? 'is-invalid' : '' }}"
                  wire:model.defer="description"
                  placeholder="{{ __('Describe the product in detail...') }}"></textarea>
        @error('description') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="field-label" for="pr-url">{{ __('Product URL') }} <span class="field-hint">{{ __('(optional)') }}</span></label>
        <p class="field-hint" style="margin-bottom:6px;">{{ __('Link to the product on the manufacturer, supplier, or reference site.') }}</p>
        <input id="pr-url" type="url" class="field-control {{ $errors->has('productUrl') ? 'is-invalid' : '' }}"
               wire:model.defer="productUrl" placeholder="https://example.com/product/...">
        @error('productUrl') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="field-label">{{ __('Attachments') }} <span class="field-hint">{{ __('(optional, max 5 files · 10 MB each)') }}</span></label>
        <p class="field-hint" style="margin-bottom:8px;">{{ __('Images, PDFs, spreadsheets or reference files that describe the product.') }}</p>
        <input type="file" multiple wire:model="files" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.zip"
               class="field-control">
        @error('files.*') <p class="field-error">{{ $message }}</p> @enderror
        @if (!empty($files))
          <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;">
            @foreach ($files as $f)
              <span class="badge badge-secondary">{{ $f->getClientOriginalName() }}</span>
            @endforeach
          </div>
        @endif
      </div>

      <div style="display:flex;gap:12px;padding-top:8px;">
        <button type="button" class="btn btn-primary" wire:click="submit" wire:loading.attr="disabled">
          <span wire:loading.remove wire:target="submit">{{ __('Submit Request') }}</span>
          <span wire:loading wire:target="submit">{{ __('Submitting…') }}</span>
        </button>
        <a href="{{ route('tenant.product-requests.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
      </div>

    </div>
  </div>
</main>
