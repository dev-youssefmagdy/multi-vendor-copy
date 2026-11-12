<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item :href="route('tenant.settings.email-templates.edit', $template)">{{ __('Edit') }}</x-tenant::dropdown-item>
</x-tenant::dropdown>
