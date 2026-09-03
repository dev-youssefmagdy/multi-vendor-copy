<?php

namespace App\Livewire\Tenant\Base;

use App\Livewire\Admin\Concerns\HasCsvExport;

abstract class ListPage extends TenantPage
{
    use HasCsvExport;

    protected bool $exportable = false;

    protected function pageView(): string
    {
        return 'livewire.tenant.pages.list-page';
    }

    protected function pageData(): array
    {
        $meta = $this->pageMeta();

        return array_merge([
            'badge' => null,
            'exportable' => $this->exportable,
            'actionLabel' => 'Add New',
            'filtersTitle' => 'Vendor Filters',
            'filtersDescription' => 'Refine the tenant records visible in this workspace.',
            'tableTitle' => 'Records Table',
            'tableDescription' => 'Tenant records currently visible in this module.',
            'headers' => ['Name', 'Status', 'Updated At', 'Actions'],
            'statistics' => [
                ['label' => 'Total Records', 'value' => '0', 'caption' => 'Tenant scoped records', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active Records', 'value' => '0', 'caption' => 'Visible and available entries', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Needs Review', 'value' => '0', 'caption' => 'Items that need attention', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
        ], $meta);
    }
}
