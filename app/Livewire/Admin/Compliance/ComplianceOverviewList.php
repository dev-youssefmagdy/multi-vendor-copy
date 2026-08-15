<?php

namespace App\Livewire\Admin\Compliance;

use App\Helpers\TenantNavigation;
use App\Livewire\Admin\Base\ListPage;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Livewire\WithPagination;

class ComplianceOverviewList extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Compliance Overview',
            'badge' => 'Compliance',
            'description' => 'Every tenant\'s Compliance Center completion, with document review and verification status.',
            'tableTitle' => 'Tenants',
            'headers' => ['Tenant', 'Completion', 'Status', 'Reviewed', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $records = $this->query()->paginate(15);

        return array_merge(parent::pageData(), [
            'actionUrl' => null,
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Tenant name or email'],
                [
                    'label' => 'Status',
                    'model' => 'statusFilter',
                    'type' => 'select',
                    'options' => ['' => 'All statuses', 'pending' => 'Pending', 'verified' => 'Verified', 'needs_action' => 'Needs Action'],
                ],
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Tenants', 'value' => Tenant::query()->count(), 'caption' => 'Registered tenants', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Verified', 'value' => Tenant::query()->where('compliance_status', 'verified')->count(), 'caption' => 'Compliance approved', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Needs Action', 'value' => Tenant::query()->where('compliance_status', 'needs_action')->count(), 'caption' => 'Flagged by compliance team', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ]),
            'rows' => collect($records->items())->map(fn(Tenant $tenant) => $this->row($tenant))->all(),
            'tableDescription' => $records->total() . ' tenants matched the current filters.',
        ]);
    }

    protected function row(Tenant $tenant): array
    {
        $percent = TenantNavigation::complianceCompletionPercent($tenant);
        $status = (string) ($tenant->compliance_status ?: 'pending');

        $statusBadge = match ($status) {
            'verified' => '<span class="badge badge-green">Verified</span>',
            'needs_action' => '<span class="badge badge-red">Needs Action</span>',
            default => '<span class="badge badge-amber">Pending</span>',
        };

        return [
            '<div class="entity-title">' . e($tenant->name) . '</div><div class="entity-subtitle">' . e($tenant->email ?? $tenant->id) . '</div>',
            '<div class="entity-title">' . $percent . '%</div><div class="entity-subtitle" style="width:100px;height:6px;border-radius:999px;background:var(--elevated);overflow:hidden;"><div style="height:100%;width:' . $percent . '%;background:' . ($percent >= 100 ? 'var(--green)' : 'var(--cyan)') . ';"></div></div>',
            $statusBadge,
            $tenant->compliance_reviewed_at
                ? '<div class="entity-subtitle">' . e($tenant->compliance_reviewed_by ?? '-') . '<br>' . $tenant->compliance_reviewed_at->format('M d, Y') . '</div>'
                : '<span class="entity-subtitle">Not reviewed</span>',
            '<a href="' . route('admin.compliance.show', $tenant->id) . '" class="btn btn-secondary btn-sm">View</a>',
        ];
    }

    protected function query()
    {
        $search = trim($this->search);

        return Tenant::query()
            ->when($search !== '', fn($q) => $q->where(fn($qq) => $qq
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($this->statusFilter !== '', fn($q) => $q->where('compliance_status', $this->statusFilter))
            ->orderByDesc('created_at');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }
}
