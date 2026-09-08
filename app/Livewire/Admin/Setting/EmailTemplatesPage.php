<?php

namespace App\Livewire\Admin\Setting;

use App\Enums\ActivationStatus;
use App\Enums\EmailTemplateAction;
use App\Enums\EmailTemplateType;
use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\EmailTemplate;
use App\Repositories\EmailTemplateRepository;
use App\Services\EmailTemplateService;
use Livewire\WithPagination;

class EmailTemplatesPage extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Email Templates',
            'badge' => 'Communication',
            'description' => 'Manage both central admin emails and tenant-synced vendor email templates for lifecycle, billing, shipping, and subscription events.',
            'actionLabel' => 'Add Template',
            'tableTitle' => 'Central Email Templates',
            'headers' => ['Template', 'Type', 'Event', 'Subject', 'Body', 'Status', 'Updated At', 'Actions'],
        ];
    }

    public function confirmDelete(int $templateId): void
    {
        $this->authorizePermission('settings.email-templates.manage');
        $this->confirmAction('deleteTemplate', [$templateId], [
            'title' => 'Delete email template?',
            'text' => 'This template will be removed from the central catalog.',
            'confirmButtonText' => 'Delete template',
        ]);
    }

    public function deleteTemplate(int $templateId, EmailTemplateService $service): void
    {
        $this->authorizePermission('settings.email-templates.manage');
        $service->delete(EmailTemplate::query()->findOrFail($templateId));
        $this->toast('Email template deleted successfully.');
        $this->resetPage();
    }

    protected function pageData(): array
    {
        $canManage = $this->hasPermission('settings.email-templates.manage');
        $repository = app(EmailTemplateRepository::class);
        $records = $repository->paginate([
            'search' => $this->search,
            'type' => $this->typeFilter,
            'status' => $this->statusFilter,
        ]);
        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'actionUrl' => $canManage ? route('admin.settings.email-templates.create') : null,
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Template name or subject'],
                ['label' => 'Type', 'model' => 'typeFilter', 'type' => 'select', 'options' => ['' => 'All types', 'admin' => 'Admin', 'tenant' => 'Tenant']],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All statuses', 'active' => 'Active', 'inactive' => 'Inactive']],
            ],
            'statistics' => [
                ['label' => 'Templates', 'value' => number_format($stats['total']), 'caption' => 'Central reusable messages', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Admin Templates', 'value' => number_format($stats['admin']), 'caption' => 'Used for marketplace and central operations', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Tenant Templates', 'value' => number_format($stats['tenant']), 'caption' => 'Synced into tenant databases for vendor mail flows', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Active', 'value' => number_format($stats['active']), 'caption' => 'Available for mail workflows', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
            ],
            'rows' => collect($records->items())->map(fn($template) => [
                '<div class="entity-title">' . e($template->name) . '</div>',
                '<span class="badge ' . ($template->type === EmailTemplateType::Tenant ? 'badge-cyan' : 'badge-green') . '">' . e($template->type->label()) . '</span>',
                '<span class="entity-subtitle">' . e(EmailTemplateAction::tryFrom((string) $template->action)?->label() ?? $template->action ?? '-') . '</span>',
                e($template->subject),
                '<div class="entity-subtitle">' . e(str($template->body)->stripTags()->limit(80)) . '</div>',
                '<span class="badge ' . ($template->status === ActivationStatus::Active ? 'badge-green' : 'badge-amber') . '">' . e($template->status->label()) . '</span>',
                e($template->updated_at?->format('M d, Y')),
                $canManage
                ? '<div class="flex gap-2"><a href="' . route('admin.settings.email-templates.edit', $template) . '" class="btn btn-secondary btn-sm">Edit</a><button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $template->id . ')">Delete</button></div>'
                : '<span class="entity-subtitle">View only</span>',
            ])->all(),
            'tableDescription' => $records->total() . ' email templates matched the current filters.',
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

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter']);
        $this->resetPage();
    }
}
