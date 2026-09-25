@php
    /** @var \App\Models\ProductRequest $request */
    $dots = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5.5v.01M12 12v.01M12 18.5v.01"/></svg>';
@endphp
<x-tenant::dropdown align="end" :icon="$dots">
    <x-tenant::dropdown-item :href="route('tenant.product-requests.show', $request->id)">View details</x-tenant::dropdown-item>
</x-tenant::dropdown>
