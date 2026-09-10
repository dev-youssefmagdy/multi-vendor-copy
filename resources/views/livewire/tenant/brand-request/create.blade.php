<main id="mn">
  <div class="page-head fu d0">
    <div>
      <div class="page-title-row">
        <h1 class="D page-title">New Brand Request</h1>
        <span class="page-badge">Brands</span>
      </div>
      <p class="page-copy">Tell us about the brand you'd like to request, and our team will review it.</p>
    </div>
  </div>

  <div class="card fu d1" style="padding:28px 32px;max-width:760px;">
    <div class="stack-gap">

      <div>
        <label class="field-label" for="br-title">Brand Title <span style="color:var(--error)">*</span></label>
        <input id="br-title" type="text" class="field-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
               wire:model.defer="title" placeholder="e.g. NordicWear Outdoor Apparel">
        @error('title') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="field-label" for="br-desc">Description <span style="color:var(--error)">*</span></label>
        <p class="field-hint" style="margin-bottom:6px;">Explain the brand, why you want to carry it, and any licensing or sourcing details.</p>
        <textarea id="br-desc" rows="8"
                  class="field-control {{ $errors->has('description') ? 'is-invalid' : '' }}"
                  wire:model.defer="description"
                  placeholder="Describe the brand in detail..."></textarea>
        @error('description') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="field-label">Attachments <span class="field-hint">(optional, max 5 files &middot; 10 MB each)</span></label>
        <p class="field-hint" style="margin-bottom:8px;">Logos, brand guidelines, licensing documents, or reference files.</p>
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
        <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">
          <span wire:loading.remove wire:target="save">Submit Request</span>
          <span wire:loading wire:target="save">Submitting&hellip;</span>
        </button>
        <a href="{{ route('tenant.brand-requests.index') }}" class="btn btn-secondary">Cancel</a>
      </div>

    </div>
  </div>
</main>
