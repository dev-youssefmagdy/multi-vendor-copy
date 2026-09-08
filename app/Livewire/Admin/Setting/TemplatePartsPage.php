<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Template;
use App\Models\TemplatePart;
use App\Services\TemplatePartService;
use Livewire\Attributes\Url;
use Livewire\Component;

class TemplatePartsPage extends Component
{
    use AuthorizesAdminPermissions;
    use InteractsWithAdminUi;

    #[Url(as: 'template', except: null)]
    public ?int $templateId = null;

    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    // ── Pending delete ────────────────────────────────────────────────────────
    public ?int $pendingDeleteId = null;

    public function mount(?int $templateId = null): void
    {
        $this->authorizePermission('settings.templates.manage');

        // Route param takes precedence over URL query param.
        if ($templateId !== null) {
            $this->templateId = $templateId;
        }
    }

    public function selectTemplate(?int $id): void
    {
        $this->templateId = $id;
        $this->typeFilter = '';
    }

    public function setTypeFilter(string $type): void
    {
        $this->typeFilter = $type;
    }

    public function setDefault(int $partId, TemplatePartService $service): void
    {
        $this->authorizePermission('settings.templates.manage');
        $part = TemplatePart::findOrFail($partId);
        $service->setDefault($part);
        $this->toast('Set as default successfully.');
    }

    public function toggleActive(int $partId, TemplatePartService $service): void
    {
        $this->authorizePermission('settings.templates.manage');
        $part = TemplatePart::findOrFail($partId);
        $service->setActive($part, !$part->is_active);
        $this->toast($part->is_active ? 'Part deactivated.' : 'Part activated.');
    }

    public function requestDelete(int $partId): void
    {
        $this->pendingDeleteId = $partId;
        $this->confirmAction('deletePart', [$partId], [
            'title' => 'Delete Template Part?',
            'text' => 'This will remove the part and cascade to all synced tenant theme parts.',
            'confirmButtonText' => 'Delete',
            'icon' => 'warning',
        ]);
    }

    public function deletePart(int $partId, TemplatePartService $service): void
    {
        $this->authorizePermission('settings.templates.manage');
        $part = TemplatePart::findOrFail($partId);
        $service->delete($part);
        $this->pendingDeleteId = null;
        $this->toast('Template part deleted.');
    }

    public function render()
    {
        $this->authorizePermission('settings.templates.manage');

        $templates = Template::orderBy('name')->get();
        $template = $this->templateId ? $templates->firstWhere('id', $this->templateId) : null;

        $query = TemplatePart::with('template')->orderBy('type')->orderBy('name');

        if ($this->templateId) {
            $query->where('template_id', $this->templateId);
        }

        if ($this->typeFilter !== '') {
            $query->where('type', $this->typeFilter);
        }

        $parts = $query->get();

        $stats = [
            'total' => $parts->count(),
            'headers' => $parts->where('type', 'header')->count(),
            'footers' => $parts->where('type', 'footer')->count(),
            'active' => $parts->where('is_active', true)->count(),
        ];

        return view('livewire.admin.setting.template-parts-page', compact(
            'templates',
            'template',
            'parts',
            'stats'
        ));
    }
}
