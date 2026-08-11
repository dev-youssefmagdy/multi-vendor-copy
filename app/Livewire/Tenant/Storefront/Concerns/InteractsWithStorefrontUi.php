<?php

namespace App\Livewire\Tenant\Storefront\Concerns;

trait InteractsWithStorefrontUi
{
    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('storefront-toast', message: $message, type: $type);
    }
}
