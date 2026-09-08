<?php

namespace App\Livewire\Admin\Base;

abstract class ContentPage extends AdminPage
{
    protected function pageView(): string
    {
        return 'livewire.admin.pages.content-page';
    }

    protected function pageData(): array
    {
        $meta = $this->pageMeta();

        return array_merge([
            'badge' => null,
            'contentIntro' => 'This page shell is wired and ready for the deeper business implementation.',
            'bullets' => [
                'Bind Livewire form state and validations.',
                'Attach service and repository actions.',
                'Add enum-backed filters, statuses, and transitions.',
            ],
        ], $meta);
    }
}
