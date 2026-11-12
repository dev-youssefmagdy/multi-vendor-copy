@php
    $dots = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5.5v.01M12 12v.01M12 18.5v.01"/></svg>';
@endphp
<x-tenant::dropdown align="end" :icon="$dots">
    <x-tenant::dropdown-item :href="route('tenant.brand-requests.show', $request->id)">{{ __('View details') }}</x-tenant::dropdown-item>
    @if($request->status === \App\Enums\BrandRequestStatus::Pending)
        <x-tenant::dropdown-item danger
            data-action-url="{{ route('tenant.brand-requests.destroy', $request->id) }}"
            data-action-method="DELETE"
            :data-confirm="__('Delete this pending brand request?')"
            data-confirm-danger
            data-success="reload-table:#brand-requests-table">{{ __('Delete') }}</x-tenant::dropdown-item>
    @endif
</x-tenant::dropdown>
