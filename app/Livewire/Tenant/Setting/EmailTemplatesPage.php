<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\ListPage;
use App\Enums\EmailTemplateAction;
use App\Models\Tenant\EmailTemplate;
use App\Repositories\Tenant\TenantPanelRepository;
use Livewire\WithPagination;

class EmailTemplatesPage extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Email Templates',
            'badge' => 'Settings',
            'description' => 'Review and adjust the vendor-facing email templates available to this tenant storefront.',
            'actionLabel' => null,
            'tableTitle' => 'Tenant Email Templates',
            'headers' => ['Template', 'Event', 'Subject', 'Body', 'Status', 'Updated At', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginateEmailTemplates([
            'search' => $this->search,
            'status' => $this->statusFilter,
        ]);
        $stats = $repository->emailTemplateStats();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Template name or subject'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All statuses', 'active' => 'Active', 'inactive' => 'Inactive']],
            ],
            'statistics' => [
                ['label' => 'Templates', 'value' => number_format($stats['total']), 'caption' => 'Templates available in this tenant database', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active', 'value' => number_format($stats['active']), 'caption' => 'Currently available for tenant mail flows', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Inactive', 'value' => number_format($stats['total'] - $stats['active']), 'caption' => 'Disabled templates retained in the tenant workspace', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn(EmailTemplate $template) => [
                '<div class="entity-title">' . e($template->name) . '</div>',
                '<span class="entity-subtitle">' . e(EmailTemplateAction::tryFrom((string) $template->action)?->label() ?? $template->action ?? '-') . '</span>',
                e($template->subject),
                '<div class="entity-subtitle">' . e(str($template->body)->stripTags()->limit(80)) . '</div>',
                '<span class="badge ' . ($template->is_active ? 'badge-green' : 'badge-amber') . '">' . e($template->is_active ? 'Active' : 'Inactive') . '</span>',
                e($template->updated_at?->format('M d, Y')),
                '<a href="' . route('tenant.settings.email-templates.edit', $template) . '" class="btn btn-secondary btn-sm">Edit</a>',
            ])->all(),
            'tableDescription' => $records->total() . ' tenant email templates matched the current filters.',
            'filtersNote' => 'These templates are seeded from central defaults and stored locally for this tenant workspace.',
        ]);
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
