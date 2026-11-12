<?php

namespace App\Livewire\Tenant\Concerns;

trait InteractsWithTenantUi
{
    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('admin-toast', message: $message, type: $type);
    }

    protected function confirmAction(string $method, array $parameters = [], array $options = []): void
    {
        $this->dispatch(
            'admin-confirm',
            componentId: $this->getId(),
            actionMethod: $method,
            actionParams: array_values($parameters),
            title: $options['title'] ?? __('Are you sure?'),
            text: $options['text'] ?? __('This action cannot be undone.'),
            confirmButtonText: $options['confirmButtonText'] ?? __('Confirm'),
            cancelButtonText: $options['cancelButtonText'] ?? __('Cancel'),
            icon: $options['icon'] ?? 'warning'
        );
    }
}
