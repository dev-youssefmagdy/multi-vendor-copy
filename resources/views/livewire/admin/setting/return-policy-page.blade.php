<main id="mn">

    {{-- ── Page header ──────────────────────────────────────────────── --}}
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Return Policy</h1>
                <span class="page-badge">Configuration</span>
            </div>
            <p class="page-copy">
                Platform-wide return policy for products sourced from the central Neozena catalog.
                Tenants may override individual products from their own panel.
            </p>
        </div>
        <button type="button" wire:click="save" class="btn btn-primary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Save Policy</span>
            <span wire:loading wire:target="save">Saving…</span>
        </button>
    </div>

    <div class="form-two-col section-gap">

        {{-- ── Left column ──────────────────────────────────────────── --}}
        <div class="stack-gap">

            {{-- Return window & fee --}}
            <section class="card fu d1" style="padding:24px 28px;">
                <h3 class="panel-title" style="margin-bottom:4px;">Return Window &amp; Fee</h3>
                <p class="panel-copy" style="margin-bottom:20px;">Basic return parameters applied to all central-catalog products unless overridden by the tenant.</p>

                <div class="form-grid-2">
                    <div>
                        <label class="field-label" for="rp-window">Return window <span class="field-hint">(days)</span></label>
                        <input id="rp-window" type="number" min="1" max="365"
                               class="field-control {{ $errors->has('windowDays') ? 'is-invalid' : '' }}"
                               wire:model.defer="windowDays"
                               placeholder="14">
                        @error('windowDays')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                        <p class="field-hint" style="margin-top:6px;">Customers must submit a return request within this many days of delivery.</p>
                    </div>

                    <div>
                        <label class="field-label" for="rp-fee">Return fee <span class="field-hint">(platform currency)</span></label>
                        <input id="rp-fee" type="number" min="0" step="0.01"
                               class="field-control {{ $errors->has('fee') ? 'is-invalid' : '' }}"
                               wire:model.defer="fee"
                               placeholder="0.00">
                        @error('fee')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                        <p class="field-hint" style="margin-top:6px;">Set 0 for free returns. This fee is deducted from the refund amount.</p>
                    </div>
                </div>
            </section>

            {{-- Accepted conditions --}}
            <section class="card fu d2" style="padding:24px 28px;">
                <h3 class="panel-title" style="margin-bottom:4px;">Accepted Return Conditions</h3>
                <p class="panel-copy" style="margin-bottom:16px;">Describe the physical condition the product must be in to qualify for a return. Shown to customers on the return request form.</p>

                <textarea id="rp-conditions"
                          class="field-control {{ $errors->has('conditions') ? 'is-invalid' : '' }}"
                          wire:model.defer="conditions"
                          rows="5"
                          placeholder="e.g. Item must be unused and in its original packaging with all tags attached. Products showing signs of use, damage, or missing parts will not be accepted."></textarea>
                @error('conditions')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </section>

        </div>

        {{-- ── Right column ─────────────────────────────────────────── --}}
        <div class="stack-gap">

            {{-- Video evidence --}}
            <section class="card fu d3" style="padding:24px 28px;">
                <h3 class="panel-title" style="margin-bottom:4px;">Video Evidence Required</h3>
                <p class="panel-copy" style="margin-bottom:16px;">
                    Select the return reasons that must include a video as proof. Customers selecting these reasons will be prompted to upload a video before the request can be submitted.
                </p>

                <div style="display:flex;flex-direction:column;gap:10px;">
                    @foreach(\App\Enums\ReturnReason::cases() as $reason)
                        <label class="rp-check-row" style="display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:10px;border:1px solid var(--border);background:var(--surface);cursor:pointer;transition:background .14s;">
                            <input type="checkbox"
                                   class="rp-checkbox"
                                   value="{{ $reason->value }}"
                                   wire:model.live="videoRequiredReasonsArray"
                                   style="width:16px;height:16px;border-radius:4px;accent-color:var(--cyan);cursor:pointer;flex-shrink:0;">
                            <div style="min-width:0;">
                                <div style="font-size:13px;font-weight:600;color:var(--t1);line-height:1.2;">{{ $reason->label() }}</div>
                                @if($reason->requiresVideo())
                                    <div style="font-size:11px;color:var(--cyan);margin-top:2px;">Recommended — video expected for this reason</div>
                                @endif
                            </div>
                            @if(in_array($reason->value, $videoRequiredReasonsArray))
                                <span class="badge badge-cyan" style="margin-left:auto;flex-shrink:0;">Required</span>
                            @else
                                <span class="badge badge-secondary" style="margin-left:auto;flex-shrink:0;">Optional</span>
                            @endif
                        </label>
                    @endforeach
                </div>

                <p class="field-hint" style="margin-top:14px;">
                    Currently requiring video for:
                    @if(!empty($videoRequiredReasonsArray))
                        <strong>{{ implode(', ', array_map(fn($v) => \App\Enums\ReturnReason::from($v)->label(), $videoRequiredReasonsArray)) }}</strong>
                    @else
                        <em>none</em>
                    @endif
                </p>
            </section>

            {{-- Non-returnable products --}}
            <section class="card fu d4" style="padding:24px 28px;">
                <h3 class="panel-title" style="margin-bottom:4px;">Non-returnable Products</h3>
                <p class="panel-copy" style="margin-bottom:16px;">
                    Enter the central product IDs that cannot be returned under any circumstances (e.g. digital goods, perishables, custom-made items). Separate multiple IDs with commas.
                </p>

                <label class="field-label" for="rp-nr-ids">Product IDs <span class="field-hint">(comma-separated)</span></label>
                <input id="rp-nr-ids" type="text"
                       class="field-control {{ $errors->has('nonReturnableIds') ? 'is-invalid' : '' }}"
                       wire:model.defer="nonReturnableIds"
                       placeholder="e.g. 12, 45, 230">
                @error('nonReturnableIds')
                    <span class="field-error">{{ $message }}</span>
                @enderror

                @if(trim($nonReturnableIds))
                    @php
                        $ids = array_filter(array_map('trim', explode(',', $nonReturnableIds)), fn($v) => is_numeric($v));
                    @endphp
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;">
                        @foreach($ids as $pid)
                            <span class="badge badge-secondary">ID {{ $pid }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="notice-row" style="margin-top:16px;display:flex;gap:8px;align-items:flex-start;padding:10px 12px;border-radius:9px;background:rgba(0,229,255,0.06);border:1px solid rgba(0,229,255,0.18);">
                    <svg width="14" height="14" fill="none" stroke="var(--cyan)" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    <p class="panel-copy" style="margin:0;font-size:12px;">Tenants can additionally mark individual products as non-returnable from their own product settings. Both lists are checked at return-request validation time.</p>
                </div>
            </section>

        </div>
    </div>

</main>

@once
<style>
.form-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
}
.stack-gap { display: flex; flex-direction: column; gap: 20px; }
.rp-check-row:has(input:checked) {
    background: rgba(0,229,255,0.05);
    border-color: rgba(0,229,255,0.3);
}
@media (max-width: 900px) {
    .form-two-col { grid-template-columns: 1fr; }
}
</style>
@endonce
