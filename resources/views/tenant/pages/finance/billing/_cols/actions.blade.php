<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item :href="route('tenant.finance.billing.detail', $order->id)">{{ __('Inspect') }}</x-tenant::dropdown-item>
</x-tenant::dropdown>
