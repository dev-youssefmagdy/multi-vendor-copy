<?php

namespace App\Livewire\Admin\Base;

use App\Livewire\Admin\Concerns\HasCsvExport;

abstract class ListPage extends AdminPage
{
    use HasCsvExport;

    protected bool $exportable = false;

    protected function pageView(): string
    {
        return 'livewire.admin.pages.list-page';
    }

    protected function pageData(): array
    {
        $meta = $this->pageMeta();

        return array_merge([
            'badge' => null,
            'exportable' => $this->exportable,
            'actionLabel' => 'Add New',
            'filtersTitle' => 'Main Filters',
            'filtersDescription' => 'Refine the central records displayed on this page.',
            'tableTitle' => 'Records Table',
            'tableDescription' => 'This section will host the final Livewire table implementation.',
            'headers' => ['Name', 'Status', 'Updated At', 'Actions'],
            'statistics' => [
                ['label' => 'Total Records', 'value' => '0', 'caption' => 'Ready for central metrics', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active Records', 'value' => '0', 'caption' => 'Backed by enum-driven statuses', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Needs Review', 'value' => '0', 'caption' => 'Attach approval and moderation flows', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
        ], $meta);
    }
}
