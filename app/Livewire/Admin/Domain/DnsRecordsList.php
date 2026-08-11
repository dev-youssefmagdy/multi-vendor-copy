<?php

namespace App\Livewire\Admin\Domain;

use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\DnsRecord;
use App\Repositories\DnsRecordRepository;
use App\Services\DnsRecordService;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

class DnsRecordsList extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    public string $search = '';
    public string $typeFilter = '';
    public bool $showFormModal = false;
    public ?int $dnsRecordId = null;

    // Form fields
    public string $type = 'A';
    public string $name = '@';
    public string $value = '';
    public string $ttl = '3600';
    public string $priority = '';
    public string $description = '';
    public bool $isRequired = true;

    protected function pageMeta(): array
    {
        return [
            'title'       => 'DNS Records',
            'badge'       => 'A · AAAA · CNAME · MX · TXT · NS · SRV',
            'description' => 'Manage the DNS records vendors must configure when connecting a custom domain to your platform.',
            'actionLabel' => 'Add Record',
            'tableTitle'  => 'DNS Records Library',
            'headers'     => ['Type', 'Name', 'Value', 'TTL', 'Priority', 'Required', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $canManage  = $this->hasPermission('domains.dns.manage');
        $repository = app(DnsRecordRepository::class);
        $records    = $repository->paginate(['search' => $this->search, 'type' => $this->typeFilter]);
        $stats      = $repository->stats();

        $typeOptions = array_merge(['' => 'All types'], array_combine(DnsRecord::types(), DnsRecord::types()));

        return array_merge(parent::pageData(), [
            'actionMethod' => $canManage ? 'openCreateModal' : null,
            'records'      => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Name or value'],
                ['label' => 'Type', 'model' => 'typeFilter', 'type' => 'select', 'options' => $typeOptions],
            ],
            'statistics' => [
                ['label' => 'Total Records', 'value' => number_format($stats['total']), 'caption' => 'All DNS record entries', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Required', 'value' => number_format($stats['required']), 'caption' => 'Must be set by vendor', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn($r) => [
                '<span class="badge badge-cyan font-mono text-xs">' . e($r->type) . '</span>',
                '<span class="font-mono text-sm">' . e($r->name) . '</span>',
                '<span class="font-mono text-xs break-all">' . e($r->value) . '</span>',
                e($r->ttl),
                $r->priority !== null ? e($r->priority) : '—',
                $r->is_required
                    ? '<span class="badge badge-green">Required</span>'
                    : '<span class="badge badge-gray">Optional</span>',
                $canManage
                    ? '<div class="flex gap-2"><button type="button" class="btn btn-secondary btn-sm" wire:click="editRecord(' . $r->id . ')">Edit</button><button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $r->id . ')">Delete</button></div>'
                    : '<span class="entity-subtitle">View only</span>',
            ])->all(),
            'tableDescription' => $records->total() . ' DNS records matched the current filters.',
            'modalModel'        => 'showFormModal',
            'modalTitle'        => $this->dnsRecordId ? 'Edit DNS Record' : 'Add DNS Record',
            'modalCloseAction'  => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel'  => $this->dnsRecordId ? 'Update Record' : 'Create Record',
            'modalFieldGroups'  => $canManage ? [
                [
                    'gridClass' => 'form-grid-2',
                    'fields'    => [
                        ['label' => 'Type', 'model' => 'type', 'type' => 'select', 'options' => array_combine(DnsRecord::types(), DnsRecord::types())],
                        ['label' => 'TTL (seconds)', 'model' => 'ttl', 'type' => 'number', 'placeholder' => '3600'],
                        ['label' => 'Name / Host', 'model' => 'name', 'placeholder' => '@ or www'],
                        ['label' => 'Value / Target', 'model' => 'value', 'placeholder' => '1.2.3.4 or hostname'],
                        ['label' => 'Priority (MX/SRV)', 'model' => 'priority', 'type' => 'number', 'placeholder' => '10'],
                        ['label' => 'Required for connection', 'model' => 'isRequired', 'type' => 'toggle'],
                        ['label' => 'Description / Instructions', 'model' => 'description', 'type' => 'textarea', 'wrapperClass' => 'span-2'],
                    ],
                ],
            ] : [],
        ]);
    }

    public function openCreateModal(): void
    {
        $this->authorizePermission('domains.dns.manage');
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function editRecord(int $id): void
    {
        $this->authorizePermission('domains.dns.manage');
        $record = DnsRecord::query()->findOrFail($id);

        $this->dnsRecordId  = $record->id;
        $this->type         = $record->type;
        $this->name         = $record->name;
        $this->value        = $record->value;
        $this->ttl          = (string) $record->ttl;
        $this->priority     = $record->priority !== null ? (string) $record->priority : '';
        $this->description  = (string) ($record->description ?? '');
        $this->isRequired   = $record->is_required;
        $this->showFormModal = true;
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetErrorBag();
    }

    public function save(DnsRecordService $service): void
    {
        $this->authorizePermission('domains.dns.manage');

        $this->validate([
            'type'        => ['required', Rule::in(DnsRecord::types())],
            'name'        => ['required', 'string', 'max:255'],
            'value'       => ['required', 'string', 'max:500'],
            'ttl'         => ['required', 'integer', 'min:60', 'max:86400'],
            'priority'    => ['nullable', 'integer', 'min:0', 'max:65535'],
            'description' => ['nullable', 'string', 'max:1000'],
            'isRequired'  => ['boolean'],
        ]);

        $service->save([
            'type'        => $this->type,
            'name'        => $this->name,
            'value'       => $this->value,
            'ttl'         => $this->ttl,
            'priority'    => $this->priority !== '' ? $this->priority : null,
            'description' => $this->description,
            'is_required' => $this->isRequired,
        ], $this->dnsRecordId ? DnsRecord::query()->findOrFail($this->dnsRecordId) : null);

        $this->closeModal();
        $this->resetForm();
        $this->toast($this->dnsRecordId ? 'DNS record updated.' : 'DNS record created.');
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorizePermission('domains.dns.manage');
        $this->confirmAction('deleteRecord', [$id], [
            'title'             => 'Delete DNS record?',
            'text'              => 'This will remove it from the DNS instructions shown to vendors.',
            'confirmButtonText' => 'Delete record',
        ]);
    }

    public function deleteRecord(int $id, DnsRecordService $service): void
    {
        $this->authorizePermission('domains.dns.manage');
        $service->delete(DnsRecord::query()->findOrFail($id));
        $this->toast('DNS record deleted.');
        $this->resetPage();
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function updatedTypeFilter(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search', 'typeFilter']);
        $this->resetPage();
    }

    protected function resetForm(): void
    {
        $this->reset(['dnsRecordId', 'name', 'value', 'priority', 'description']);
        $this->type       = 'A';
        $this->ttl        = '3600';
        $this->isRequired = true;
        $this->resetErrorBag();
    }
}
