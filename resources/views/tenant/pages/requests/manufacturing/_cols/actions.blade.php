@php
    /** @var \App\Models\ManufacturingRequest $req */
    $dots = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5.5v.01M12 12v.01M12 18.5v.01"/></svg>';
@endphp
<x-tenant::dropdown align="end" :icon="$dots">
    <x-tenant::dropdown-item :href="route('tenant.manufacturing.show', $req->id)">View details</x-tenant::dropdown-item>
    @if($req->status === \App\Enums\ManufacturingRequestStatus::Pending)
        <x-tenant::dropdown-item danger
            data-action-url="{{ route('tenant.manufacturing.cancel', $req->id) }}"
            data-action-method="POST"
            data-confirm="Cancel this request?"
            data-success="reload-table:#manufacturing-table">Cancel request</x-tenant::dropdown-item>
    @endif
</x-tenant::dropdown>
