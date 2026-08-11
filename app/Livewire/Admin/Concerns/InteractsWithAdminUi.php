<?php

namespace App\Livewire\Admin\Concerns;

trait InteractsWithAdminUi
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
            title: $options['title'] ?? 'Are you sure?',
            text: $options['text'] ?? 'This action cannot be undone.',
            confirmButtonText: $options['confirmButtonText'] ?? 'Confirm',
            cancelButtonText: $options['cancelButtonText'] ?? 'Cancel',
            icon: $options['icon'] ?? 'warning'
        );
    }
}
