{{--
Partial: one option-pair row's content, used as a "pairs" repeater's row
template (blank state — the wrapping .t-repeater-row div is added by the
repeater JS itself). $pIdx is always the __PINDEX__ placeholder token,
resolved client-side when a pair row is added; $vIdx is the *owning*
variant's index — either a real number (existing variant rows, baked in at
render time) or the __INDEX__ token (the blank variant template, patched at
runtime when a brand new variant row is created).
Variables: $vIdx (string|int), $pIdx (string), $variations (Collection)
--}}
<div class="variant-pair-row">
    <select class="field-control" name="variants[{{ $vIdx }}][pairs][{{ $pIdx }}][variation_id]" data-pair-variation>
        <option value="">Select variation group</option>
        @foreach($variations as $variation)
            <option value="{{ $variation->id }}">{{ $variation->translationValue('name') ?? $variation->slug }}</option>
        @endforeach
    </select>

    <div class="var-options">
        <select class="field-control" name="variants[{{ $vIdx }}][pairs][{{ $pIdx }}][option_id]" data-pair-option>
            <option value="">Select option value</option>
        </select>
    </div>

    <button type="button" class="btn btn-secondary btn-sm btn-danger" data-repeater-remove title="Remove this group">
        <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
    </button>
</div>
