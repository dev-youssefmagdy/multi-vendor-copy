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
            'actionLabel' => __('Add New'),
            'filtersTitle' => __('Vendor Filters'),
            'filtersDescription' => __('Refine the tenant records visible in this workspace.'),
            'tableTitle' => __('Records Table'),
            'tableDescription' => __('Tenant records currently visible in this module.'),
            'headers' => [__('Name'), __('Status'), __('Updated At'), __('Actions')],
            'statistics' => [
                ['label' => __('Total Records'), 'value' => '0', 'caption' => __('Tenant scoped records'), 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => __('Active Records'), 'value' => '0', 'caption' => __('Visible and available entries'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('Needs Review'), 'value' => '0', 'caption' => __('Items that need attention'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
        ], $meta);
    }
}
