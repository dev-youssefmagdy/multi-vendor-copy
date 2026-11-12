@props(['subscriber'])

<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item danger
        data-action-url="{{ route('tenant.settings.subscribers.destroy', $subscriber) }}"
        data-action-method="DELETE"
        :data-confirm="__('Delete subscriber?')"
        data-success="reload-table:#subscribers-table">{{ __('Delete') }}</x-tenant::dropdown-item>
</x-tenant::dropdown>
