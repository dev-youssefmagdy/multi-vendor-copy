@php /** @var \App\Models\ManufacturingRequest $req */ @endphp
<div class="flex gap-2 flex-wrap">
    <a href="{{ route('tenant.manufacturing.show', $req->id) }}" class="btn btn-secondary btn-sm" title="View full details">
        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right:4px;vertical-align:-1px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" /></svg>
        View
    </a>
    @if($req->status === \App\Enums\ManufacturingRequestStatus::Pending)
        <button type="button" class="btn btn-danger btn-sm"
            data-action-url="{{ route('tenant.manufacturing.cancel', $req->id) }}"
            data-action-method="POST"
            data-confirm="Cancel this request?"
            data-success="reload-table:#manufacturing-table">Cancel</button>
    @endif
</div>
