<?php

namespace App\Livewire\Tenant\Base;

abstract class ContentPage extends TenantPage
{
    protected function pageView(): string
    {
        return 'livewire.tenant.pages.content-page';
    }

    protected function pageData(): array
    {
        $meta = $this->pageMeta();

        return array_merge([
            'badge' => null,
            'contentIntro' => __('This tenant page is wired and ready for vendor-facing workflows.'),
            'bullets' => [
                __('Keep all data scoped to the current tenant database.'),
                __('Use repository-backed widgets and service-backed saves.'),
                __('Support both dark and light themes with the shared shell.'),
            ],
        ], $meta);
    }
}
