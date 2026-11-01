<main id="mn">
  <form wire:submit="save" class="page-stack">
    <div class="page-head fu d0">
      <div>
        <div class="eyebrow">Central Catalog</div>
        <h1 class="D page-title">{{ $pageTitle }}</h1>
        <p class="page-copy">A label is an icon and a short text. Every theme renders it in its own product-card label style.</p>
      </div>
      <div class="page-actions" style="flex-shrink:0">
        <a href="{{ route('admin.labels.index') }}" class="btn btn-secondary">Back to Labels</a>
        <x-btn type="submit">Save Label</x-btn>
      </div>
    </div>

    @if (session('status'))<div class="card section-gap notice-success">{{ session('status') }}</div>@endif

    <div class="grid gap-2">
      <x-card-collapse class="form-card" title="Label" subtitle="Icon and text." :start-open="true">
        <div class="form-grid">
          <div>
            <label class="field-label">Text</label>
            <input type="text" class="field-control" wire:model.defer="text" maxlength="120" placeholder="Delivered by {delivery_date}">
            <p class="field-hint">Tokens: <code>{stock}</code> (units left, capped at +99) and <code>{delivery_date}</code>.</p>
            @error('text') <p class="field-error">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="field-label">Icon</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
              <label style="display:flex;align-items:center;gap:6px;padding:6px 10px;border:1px solid var(--border);border-radius:8px;cursor:pointer;{{ $icon === '' ? 'border-color:var(--cyan)' : '' }}">
                <input type="radio" wire:model.live="icon" value="" style="display:none"><span style="font-size:12px">None</span>
              </label>
              @foreach ($icons as $key => $meta)
                <label title="{{ $meta['label'] }}" style="display:flex;align-items:center;justify-content:center;width:38px;height:38px;border:1px solid var(--border);border-radius:8px;cursor:pointer;{{ $icon === $key ? 'border-color:var(--cyan);background:var(--input)' : '' }}">
                  <input type="radio" wire:model.live="icon" value="{{ $key }}" style="display:none">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $meta['svg'] !!}</svg>
                </label>
              @endforeach
            </div>
            @error('icon') <p class="field-error">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="field-label">Sort order</label>
            <input type="number" min="0" class="field-control" wire:model.defer="sortOrder">
            @error('sortOrder') <p class="field-error">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="field-label">Status</label>
            <x-select wire:model.defer="active">
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </x-select>
          </div>
        </div>
      </x-card-collapse>

      <x-card-collapse class="form-card" title="Products" description="Products that show this label on their cards. The same assignment can be made from each product's edit page." :start-open="true">
        <x-select multiple searchable wire:model.defer="productIds" placeholder="Search and select products">
          @foreach ($products as $product)
            <option value="{{ $product->id }}">{{ $product->translationValue('name') ?? $product->slug }}</option>
          @endforeach
        </x-select>
        @error('productIds') <p class="field-error" style="margin-top:8px">{{ $message }}</p> @enderror
        @error('productIds.*') <p class="field-error" style="margin-top:8px">{{ $message }}</p> @enderror
      </x-card-collapse>
    </div>
  </form>
</main>
